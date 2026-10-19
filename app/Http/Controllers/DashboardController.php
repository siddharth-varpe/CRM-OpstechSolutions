<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Inquiry;
use App\Models\Warranty;
use App\Models\Invoice;
use App\Models\Technician;
use App\Models\Customer;
use App\Models\Notification;

class DashboardController extends Controller
{
    /**
     * Compute real-time dashboard data metrics
     */
    public static function getMetrics(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $in30Days = now()->addDays(30);

        // 1. Services: Scheduled for today or pending overdue
        $servicesDueToday = Service::whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->whereDate('scheduled_date', '<=', now()->toDateString())
            ->count();

        $servicesUrgent = Service::whereIn('priority', ['HIGH', 'URGENT', 'EMERGENCY'])
            ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->count();

        $servicesOverdue = Service::whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->whereDate('scheduled_date', '<', now()->toDateString())
            ->count();

        // 2. Inquiries
        $newInquiries = Inquiry::where('status', 'NEW')->count();
        $inquiriesUnread = Inquiry::whereIn('status', ['NEW', 'CONTACTED'])->count();

        // 3. Warranties Expiring (Active within 30 days)
        $warrantiesExpiring = Warranty::whereIn('status', ['ACTIVE', 'EXPIRING_SOON'])
            ->where('end_date', '<=', $in30Days)
            ->where('end_date', '>=', $todayStart)
            ->count();

        // 4. Invoices & Payments Due: authoritatively from unpaid invoices
        $unpaidInvoices = Invoice::whereNotIn('status', ['PAID', 'CANCELLED'])
            ->where('balance_amount', '>', 0)
            ->get();

        $paymentsDue = $unpaidInvoices->count();
        $paymentsOverdue = $unpaidInvoices->filter(fn($inv) => $inv->due_date && $inv->due_date < now())->count();
        $totalOverdueAmount = (float) $unpaidInvoices->filter(fn($inv) => $inv->due_date && $inv->due_date < now())->sum('balance_amount');

        // 5. Technicians
        $techniciansOnDuty = Technician::where('status', 'ACTIVE')->count();
        $techniciansAvailable = Technician::where('status', 'ACTIVE')
            ->whereDoesntHave('services', function ($q) {
                $q->where('status', 'IN_PROGRESS');
            })->count();

        // 6. Today's & Upcoming Schedule
        // First check today's services (active or overdue pending)
        $scheduleServices = Service::with(['customer', 'asset.product', 'technician'])
            ->whereNotIn('status', ['CANCELLED'])
            ->where(function ($q) {
                $q->whereDate('scheduled_date', now()->toDateString())
                  ->orWhere(function ($activeQ) {
                      $activeQ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
                              ->where('scheduled_date', '<=', now()->endOfDay());
                  });
            })
            ->orderByRaw("CASE WHEN status = 'IN_PROGRESS' THEN 1 WHEN status IN ('SCHEDULED', 'ASSIGNED') THEN 2 ELSE 3 END")
            ->orderByRaw("CASE WHEN priority IN ('URGENT', 'EMERGENCY') THEN 1 WHEN priority = 'HIGH' THEN 2 ELSE 3 END")
            ->orderBy('scheduled_date', 'asc')
            ->take(8)
            ->get();

        // If no services today or overdue, display upcoming active services
        if ($scheduleServices->isEmpty()) {
            $scheduleServices = Service::with(['customer', 'asset.product', 'technician'])
                ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
                ->orderBy('scheduled_date', 'asc')
                ->take(8)
                ->get();
        }

        // 7. Payment Reminders / Overdue Invoices
        $paymentReminders = Invoice::with('customer')
            ->whereNotIn('status', ['PAID', 'CANCELLED'])
            ->where('balance_amount', '>', 0)
            ->orderByRaw("CASE WHEN due_date < '" . now()->toDateTimeString() . "' THEN 0 ELSE 1 END")
            ->orderBy('due_date', 'asc')
            ->take(6)
            ->get();

        return [
            'cards' => [
                'servicesDueToday' => $servicesDueToday,
                'servicesUrgent' => $servicesUrgent,
                'servicesOverdue' => $servicesOverdue,
                'newInquiries' => $newInquiries,
                'inquiriesUnread' => $inquiriesUnread,
                'warrantiesExpiring' => $warrantiesExpiring,
                'paymentsDue' => $paymentsDue,
                'paymentsOverdue' => $paymentsOverdue,
                'totalOverdueAmount' => $totalOverdueAmount,
                'techniciansOnDuty' => $techniciansOnDuty,
                'techniciansAvailable' => $techniciansAvailable,
            ],
            'schedule' => $scheduleServices,
            'paymentReminders' => $paymentReminders,
            'counts' => [
                'totalCustomers' => Customer::count(),
                'activeServices' => Service::whereNotIn('status', ['COMPLETED', 'CANCELLED'])->count(),
                'totalInvoices' => Invoice::where('status', '!=', 'CANCELLED')->count(),
                'activeWarranties' => Warranty::where('status', 'ACTIVE')->count(),
            ]
        ];
    }

    /* 1. View / List Details */
    public function index()
    {
        $data = self::getMetrics();
        return view('dashboard.index', $data);
    }

    /* 1. AJAX Live Telemetry Overview */
    public function overview()
    {
        $metrics = self::getMetrics();

        // Formatted payload for live AJAX DOM updates
        $formattedSchedule = $metrics['schedule']->map(function ($srv) {
            return [
                'id' => $srv->id,
                'service_number' => $srv->service_number,
                'service_classification' => $srv->service_classification ?? 'GENERAL',
                'status' => $srv->status,
                'customer_name' => $srv->customer->full_name ?? 'Customer',
                'product_name' => $srv->asset->custom_name ?? ($srv->asset->product->name ?? 'Equipment / Unit'),
                'scheduled_date_formatted' => $srv->scheduled_date ? $srv->scheduled_date->format('d M, Y') : 'Date Unset',
                'scheduled_time_slot' => $srv->scheduled_time_slot ?? 'Flexible Slot',
                'technician_name' => $srv->technician->full_name ?? null,
                'url' => route('services.show', $srv->id),
            ];
        });

        $formattedReminders = $metrics['paymentReminders']->map(function ($inv) {
            return [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'customer_id' => $inv->customer_id,
                'customer_name' => $inv->customer->full_name ?? 'Customer',
                'customer_url' => $inv->customer_id ? route('customers.show', $inv->customer_id) : '#',
                'due_date_formatted' => $inv->due_date ? $inv->due_date->format('d M, Y') : 'Immediate',
                'total_amount_formatted' => '₹' . number_format($inv->total_amount, 2),
                'balance_amount_formatted' => '₹' . number_format($inv->balance_amount, 2),
                'is_overdue' => $inv->isOverdue(),
                'status' => $inv->isOverdue() ? 'OVERDUE' : $inv->status,
                'invoice_url' => route('invoices.show', $inv->id),
                'pay_url' => route('payments.index') . '?invoice_id=' . $inv->id . '&action=record',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'cards' => $metrics['cards'],
                'counts' => $metrics['counts'],
                'schedule' => $formattedSchedule,
                'paymentReminders' => $formattedReminders,
                'totalOverdueAmountFormatted' => '₹' . number_format($metrics['cards']['totalOverdueAmount'], 2),
            ],
        ]);
    }
}
