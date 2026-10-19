<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Service;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Rental;
use App\Models\RentalPayment;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /* 1. View / List Details */
    public function index(Request $request)
    {
        // 1. Revenue & Billing (Invoices & General Collections)
        $totalInvoiced = (float) Invoice::where('status', '!=', 'CANCELLED')->sum('total_amount');
        $invoicePaid = (float) Invoice::where('status', '!=', 'CANCELLED')->sum('paid_amount');
        $paymentsSum = (float) Payment::where('status', 'COMPLETED')->sum('amount');

        // Rental payments collections
        $totalRentalCollected = (float) RentalPayment::where('status', 'PAID')->sum('amount');

        // Total realized collections across invoices and equipment rentals
        $totalCollected = max($invoicePaid, $paymentsSum) + $totalRentalCollected;
        $totalOutstanding = (float) Invoice::where('status', '!=', 'CANCELLED')->sum('balance_amount');

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

        // 6. Equipment Rentals & Subscriptions
        $totalRentals = Rental::count();
        $activeRentalsCount = Rental::where('status', 'ACTIVE')->count();
        $monthlyRentalRevenue = (float) Rental::where('status', 'ACTIVE')->sum('monthly_fee');
        $totalRentalDeposits = (float) Rental::where('status', 'ACTIVE')->sum('deposit_amount');
        $totalRentalPaymentsCount = RentalPayment::where('status', 'PAID')->count();

        // 7. Monthly trend data (Last 6 Months)
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $monthKey = $monthDate->format('M Y');
            $invoiced = (float) Invoice::where('status', '!=', 'CANCELLED')
                ->whereYear('invoice_date', $monthDate->year)
                ->whereMonth('invoice_date', $monthDate->month)
                ->sum('total_amount');

            $collectedPayments = (float) Payment::where('status', 'COMPLETED')
                ->whereYear('payment_date', $monthDate->year)
                ->whereMonth('payment_date', $monthDate->month)
                ->sum('amount');

            $collectedInvoices = (float) Invoice::where('status', '!=', 'CANCELLED')
                ->whereYear('invoice_date', $monthDate->year)
                ->whereMonth('invoice_date', $monthDate->month)
                ->sum('paid_amount');

            $rentalCollected = (float) RentalPayment::where('status', 'PAID')
                ->whereYear('paid_date', $monthDate->year)
                ->whereMonth('paid_date', $monthDate->month)
                ->sum('amount');

            $collected = max($collectedPayments, $collectedInvoices) + $rentalCollected;

            $monthlyRevenue[] = [
                'month' => $monthKey,
                'invoiced' => $invoiced,
                'collected' => $collected,
                'rental_collected' => $rentalCollected,
            ];
        }

        return view('reports.index', compact(
            'totalInvoiced',
            'totalCollected',
            'totalRentalCollected',
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
            'totalRentals',
            'activeRentalsCount',
            'monthlyRentalRevenue',
            'totalRentalDeposits',
            'totalRentalPaymentsCount',
            'monthlyRevenue'
        ));
    }
}
