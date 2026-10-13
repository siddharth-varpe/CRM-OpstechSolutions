<?php

namespace App\Http\Controllers;

use App\Models\ServiceSchedule;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\CustomerAsset;
use Illuminate\Http\Request;

class DuesController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'services'); // services, payments, calendar

        // 1. Service Dues (Upcoming & Overdue periodic maintenance schedules)
        $serviceDuesQuery = ServiceSchedule::with(['customer', 'asset.product', 'warranty'])
            ->whereIn('status', ['PENDING', 'OVERDUE'])
            ->orderBy('planned_date', 'asc');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $serviceDuesQuery->whereHas('customer', function ($cq) use ($search) {
                $cq->where('full_name', 'like', "%{$search}%")
                   ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $serviceDues = $serviceDuesQuery->paginate(15, ['*'], 'services_page')->withQueryString();

        // 2. Payment Dues (Outstanding invoices with due dates)
        $paymentDuesQuery = Invoice::with('customer')
            ->where('balance_amount', '>', 0)
            ->where('status', '!=', 'CANCELLED')
            ->orderBy('due_date', 'asc');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $paymentDuesQuery->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('full_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $paymentDues = $paymentDuesQuery->paginate(15, ['*'], 'payments_page')->withQueryString();

        // Summaries
        $totalServiceDuesCount = ServiceSchedule::whereIn('status', ['PENDING', 'OVERDUE'])->count();
        $totalPaymentDuesSum = Invoice::where('balance_amount', '>', 0)->where('status', '!=', 'CANCELLED')->sum('balance_amount');
        $overdueInvoicesCount = Invoice::where('balance_amount', '>', 0)->where('due_date', '<', now())->where('status', '!=', 'CANCELLED')->count();

        return view('dues.index', compact(
            'serviceDues',
            'paymentDues',
            'tab',
            'totalServiceDuesCount',
            'totalPaymentDuesSum',
            'overdueInvoicesCount'
        ));
    }
}
