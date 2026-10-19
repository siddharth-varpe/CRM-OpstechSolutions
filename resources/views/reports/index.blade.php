@extends('layouts.app')

@section('title', 'Executive Reports & Analytics - Enterprises CRM')

@section('content')
<div class="space-y-6 max-w-[1600px] mx-auto pb-12 select-none">
    <!-- 1. Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center shadow-xs shrink-0">
                <i data-lucide="bar-chart-3" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Executive Reports &amp; Analytics</h1>
                <p class="text-xs sm:text-sm text-slate-500 font-normal">
                    Business performance, revenue trends, service SLA execution &amp; inventory valuation
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="btn-outline flex items-center gap-2">
                <i data-lucide="printer" class="w-4 h-4 text-slate-500"></i>
                <span>Print Executive Summary</span>
            </button>
        </div>
    </div>

    <!-- 2. Macro KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Gross Invoiced</span>
                <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-slate-900 font-mono">
                ₹{{ number_format($totalInvoiced, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">All tax invoices billed</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Realized Collections</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-emerald-600 font-mono">
                ₹{{ number_format($totalCollected, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                @php
                    $collectionRate = $totalInvoiced > 0 ? ($totalCollected / $totalInvoiced) * 100 : 0;
                @endphp
                {{ number_format($collectionRate, 1) }}% total recovery rate
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rental Collections</span>
                <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center">
                    <i data-lucide="repeat" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-cyan-600 font-mono">
                ₹{{ number_format($totalRentalCollected, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">{{ $totalRentalPaymentsCount }} rental receipts recorded</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Receivables Due</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-amber-600 font-mono">
                ₹{{ number_format($totalOutstanding, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Pending customer balance</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs sm:col-span-2 lg:col-span-1">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Warehouse Assets</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                    <i data-lucide="boxes" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-purple-600 font-mono">
                ₹{{ number_format($totalInventoryValue, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Spare parts &amp; inventory valuation</div>
        </div>
    </div>

    <!-- 3. Monthly Financial Trajectory Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                <i data-lucide="trending-up" class="w-5 h-5 text-indigo-600"></i>
                <span>6-Month Financial Billing &amp; Collections Ledger</span>
            </h3>
            <span class="text-xs text-slate-400">Monthly reconciliation</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                        <th class="p-3">Month</th>
                        <th class="p-3 text-right">Invoiced (₹)</th>
                        <th class="p-3 text-right">Rental Collected (₹)</th>
                        <th class="p-3 text-right">Collections Realized (₹)</th>
                        <th class="p-3 text-right">Variance / Pending</th>
                        <th class="p-3 text-right">Recovery Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($monthlyRevenue as $mr)
                        @php
                            $var = max(0, $mr['invoiced'] - $mr['collected']);
                            $rate = $mr['invoiced'] > 0 ? ($mr['collected'] / $mr['invoiced']) * 100 : 100;
                        @endphp
                        <tr class="hover:bg-slate-50/50">
                            <td class="p-3 font-bold text-slate-900">{{ $mr['month'] }}</td>
                            <td class="p-3 text-right font-mono text-slate-800 font-semibold">₹{{ number_format($mr['invoiced'], 2) }}</td>
                            <td class="p-3 text-right font-mono text-cyan-700 font-semibold">₹{{ number_format($mr['rental_collected'] ?? 0, 2) }}</td>
                            <td class="p-3 text-right font-mono text-emerald-700 font-bold">₹{{ number_format($mr['collected'], 2) }}</td>
                            <td class="p-3 text-right font-mono text-amber-700">₹{{ number_format($var, 2) }}</td>
                            <td class="p-3 text-right font-mono font-bold">
                                <span class="{{ $rate >= 80 ? 'text-emerald-600' : 'text-amber-600' }}">
                                    {{ number_format($rate, 1) }}%
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- 4. Operational Breakdown Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <!-- Field Service Operations -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-6 space-y-4">
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <i data-lucide="wrench" class="w-4 h-4 text-teal-600"></i>
                <span>Field Service Execution</span>
            </h3>
            <div class="space-y-3 text-xs">
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-500">Total Visits Booked:</span>
                    <span class="font-mono font-bold text-slate-900">{{ $totalServices }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-500">Completed On-Site:</span>
                    <span class="font-mono font-bold text-emerald-600">{{ $completedServices }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-500">Scheduled / Active:</span>
                    <span class="font-mono font-bold text-sky-600">{{ $scheduledServices }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-slate-500">Completion Ratio:</span>
                    <span class="font-mono font-bold text-teal-600">
                        {{ $totalServices > 0 ? number_format(($completedServices / $totalServices) * 100, 1) : 0 }}%
                    </span>
                </div>
            </div>
        </div>

        <!-- Equipment Rentals & Subscriptions -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-6 space-y-4">
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <i data-lucide="repeat" class="w-4 h-4 text-cyan-600"></i>
                <span>Equipment Rentals &amp; Subscriptions</span>
            </h3>
            <div class="space-y-3 text-xs">
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-500">Active Machine Contracts:</span>
                    <span class="font-mono font-bold text-slate-900">{{ $activeRentalsCount }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-500">Monthly Run-Rate (MRR):</span>
                    <span class="font-mono font-bold text-emerald-600">₹{{ number_format($monthlyRentalRevenue, 2) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-500">Rental Collections Recorded:</span>
                    <span class="font-mono font-bold text-cyan-600">₹{{ number_format($totalRentalCollected, 2) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-500">Payments Recorded:</span>
                    <span class="font-mono font-bold text-slate-800">{{ $totalRentalPaymentsCount }} receipts</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-slate-500">Security Deposits Held:</span>
                    <span class="font-mono font-bold text-purple-600">₹{{ number_format($totalRentalDeposits, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Customer Base Breakdown -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-6 space-y-4">
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <i data-lucide="users" class="w-4 h-4 text-sky-600"></i>
                <span>Customer Demographics</span>
            </h3>
            <div class="space-y-3 text-xs">
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-500">Active Customer Accounts:</span>
                    <span class="font-mono font-bold text-slate-900">{{ $totalCustomers }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-500">Individual Households:</span>
                    <span class="font-mono font-bold text-sky-600">{{ $individualCount }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-500">Commercial / Offices:</span>
                    <span class="font-mono font-bold text-purple-600">{{ $commercialCount }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-slate-500">Commercial Share:</span>
                    <span class="font-mono font-bold text-indigo-600">
                        {{ $totalCustomers > 0 ? number_format(($commercialCount / $totalCustomers) * 100, 1) : 0 }}%
                    </span>
                </div>
            </div>
        </div>

        <!-- Sales & Merchandising -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-6 space-y-4">
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <i data-lucide="shopping-bag" class="w-4 h-4 text-rose-600"></i>
                <span>Sales Orders Volume</span>
            </h3>
            <div class="space-y-3 text-xs">
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-500">Completed Orders:</span>
                    <span class="font-mono font-bold text-slate-900">{{ $totalSalesCount }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-500">Gross Sales Value:</span>
                    <span class="font-mono font-bold text-emerald-600">₹{{ number_format($totalSalesRevenue, 2) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-500">Average Ticket Size:</span>
                    <span class="font-mono font-bold text-slate-800">
                        ₹{{ $totalSalesCount > 0 ? number_format($totalSalesRevenue / $totalSalesCount, 2) : 0 }}
                    </span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-slate-500">System Uptime:</span>
                    <span class="font-mono font-bold text-emerald-600">100% Operational</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
