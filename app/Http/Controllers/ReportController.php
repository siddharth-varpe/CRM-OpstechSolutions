<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Service;
use App\Models\Customer;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        // 1. Revenue & Billing
        $totalInvoiced = Invoice::where('status', '!=', 'CANCELLED')->sum('total_amount');
        $totalCollected = Payment::where('status', 'COMPLETED')->sum('amount');
        $totalOutstanding = Invoice::where('status', '!=', 'CANCELLED')->sum('balance_amount');

        // 2. Sales Orders
        $totalSalesCount = Sale::where('status', 'COMPLETED')->count();
        $totalSalesRevenue = Sale::where('status', 'COMPLETED')->sum('total_amount');

        // 3. Service Operations
        $totalServices = Service::count();
        $completedServices = Service::where('status', 'COMPLETED')->count();
        $scheduledServices = Service::whereIn('status', ['SCHEDULED', 'ASSIGNED', 'IN_PROGRESS'])->count();

        // 4. Inventory Valuation
        $allItems = InventoryItem::all();
        $totalInventoryValue = $allItems->sum(function ($it) {
            return $it->current_stock * $it->purchase_price;
        });

        // 5. Customer Metrics
        $totalCustomers = Customer::where('status', 'ACTIVE')->count();
        $individualCount = Customer::where('status', 'ACTIVE')->where('customer_type', 'INDIVIDUAL')->count();
        $commercialCount = Customer::where('status', 'ACTIVE')->where('customer_type', 'COMMERCIAL')->count();

        // 6. Monthly trend data (Last 6 Months)
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $monthKey = $monthDate->format('M Y');
            $invoiced = Invoice::where('status', '!=', 'CANCELLED')
                ->whereYear('invoice_date', $monthDate->year)
                ->whereMonth('invoice_date', $monthDate->month)
                ->sum('total_amount');
            $collected = Payment::where('status', 'COMPLETED')
                ->whereYear('payment_date', $monthDate->year)
                ->whereMonth('payment_date', $monthDate->month)
                ->sum('amount');

            $monthlyRevenue[] = [
                'month' => $monthKey,
                'invoiced' => $invoiced,
                'collected' => $collected,
            ];
        }

        return view('reports.index', compact(
            'totalInvoiced',
            'totalCollected',
            'totalOutstanding',
            'totalSalesCount',
            'totalSalesRevenue',
            'totalServices',
            'completedServices',
            'scheduledServices',
            'totalInventoryValue',
            'totalCustomers',
            'individualCount',
            'commercialCount',
            'monthlyRevenue'
        ));
    }
}
