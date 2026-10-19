@extends('layouts.app')

@section('title', 'Operations Dashboard')

@section('content')
<div class="space-y-6">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-display font-extrabold text-slate-900 tracking-tight">Operations Command Center</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Commercial &amp; Domestic Reverse Osmosis operational status, dues, and dispatch.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('customers.index') }}?action=create" class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold rounded-lg shadow-2xs transition-colors">
                <i data-lucide="user-plus" class="w-4 h-4 text-primary-600"></i>
                <span>+ Customer</span>
            </a>
            <a href="{{ route('sales.index') }}?action=create" class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold rounded-lg shadow-2xs transition-colors">
                <i data-lucide="shopping-cart" class="w-4 h-4 text-emerald-600"></i>
                <span>+ Sale Order</span>
            </a>
            <a href="{{ route('services.index') }}?action=create" class="inline-flex items-center gap-1.5 px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold rounded-lg shadow-md transition-colors">
                <i data-lucide="wrench" class="w-4 h-4"></i>
                <span>+ Schedule Service</span>
            </a>
        </div>
    </div>

    <!-- 1. Operational KPI Cards Row (4 Cards) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
        <!-- Card 1: Services Due Today -->
        <a href="{{ route('services.index') }}" class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-card hover:shadow-elevated transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Services Due</span>
                <div class="w-8 h-8 rounded-lg bg-red-600 text-white flex items-center justify-center shadow-sm">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <span id="kpi-services-due-count" class="text-2xl font-display font-extrabold text-slate-900">{{ $cards['servicesDueToday'] }}</span>
                <div class="flex items-center gap-1.5 mt-1">
                    <span id="kpi-services-urgent-label" class="text-[11px] font-bold text-red-600">{{ $cards['servicesUrgent'] }} urgent</span>
                </div>
            </div>
        </a>

        <!-- Card 2: Warranty Expiries -->
        <a href="{{ route('warranties.index') }}" class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-card hover:shadow-elevated transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Warranty Expiries</span>
                <div class="w-8 h-8 rounded-lg bg-amber-600 text-white flex items-center justify-center shadow-sm">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <span id="kpi-warranties-expiring-count" class="text-2xl font-display font-extrabold text-slate-900">{{ $cards['warrantiesExpiring'] }}</span>
                <div class="flex items-center gap-1.5 mt-1">
                    <span class="text-[11px] font-medium text-slate-500">Within 30 days</span>
                </div>
            </div>
        </a>

        <!-- Card 3: Payments Due -->
        <a href="{{ route('payments.index') }}" class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-card hover:shadow-elevated transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Payments Due</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shadow-sm">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <span id="kpi-payments-due-count" class="text-2xl font-display font-extrabold text-slate-900">{{ $cards['paymentsDue'] }}</span>
                <div class="flex items-center gap-1.5 mt-1">
                    <span id="kpi-payments-overdue-label" class="text-[11px] font-bold text-red-600">{{ $cards['paymentsOverdue'] }} overdue</span>
                </div>
            </div>
        </a>

        <!-- Card 4: Technicians On Duty -->
        <a href="{{ route('technicians.index') }}" class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-card hover:shadow-elevated transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Field Technicians</span>
                <div class="w-8 h-8 rounded-lg bg-teal-600 text-white flex items-center justify-center shadow-sm">
                    <i data-lucide="user-cog" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <span id="kpi-technicians-duty-count" class="text-2xl font-display font-extrabold text-slate-900">{{ $cards['techniciansOnDuty'] }}</span>
                <div class="flex items-center gap-1.5 mt-1">
                    <span id="kpi-technicians-available-label" class="text-[11px] font-bold text-emerald-600">Available: {{ $cards['techniciansAvailable'] }}</span>
                </div>
            </div>
        </a>
    </div>

    <!-- 2. Middle Row: Today's Schedule & Operational Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left: Today's Schedule (2 Columns) -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200/90 shadow-card overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <i data-lucide="calendar-clock" class="w-5 h-5 text-primary-600 shrink-0"></i>
                    <h2 class="text-xs sm:text-sm font-extrabold text-slate-900 uppercase tracking-wider truncate">Scheduled Services &amp; Dispatches</h2>
                </div>
                <a href="{{ route('services.index') }}" class="text-xs font-bold text-primary-600 hover:text-primary-800 shrink-0">View All &rarr;</a>
            </div>
            <div id="dashboard-schedule-list" class="divide-y divide-slate-100 max-h-[380px] overflow-y-auto">
                @forelse($schedule as $srv)
                    <a href="{{ route('services.show', $srv->id) }}" class="p-4 hover:bg-slate-50 transition-colors flex items-start justify-between gap-3 block group">
                        <div class="flex items-start gap-3 min-w-0 flex-1">
                            <div class="p-2 rounded-lg bg-sky-50 text-sky-700 shrink-0 mt-0.5 group-hover:bg-primary-50 group-hover:text-primary-700 transition-colors">
                                <i data-lucide="wrench" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="font-bold text-xs text-slate-900 group-hover:text-primary-600 transition-colors">{{ $srv->service_number }}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $srv->service_classification === 'WARRANTY' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $srv->service_classification ?? 'GENERAL' }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $srv->status === 'COMPLETED' ? 'bg-emerald-100 text-emerald-800' : ($srv->status === 'IN_PROGRESS' ? 'bg-indigo-100 text-indigo-800' : 'bg-sky-100 text-sky-800') }}">
                                        {{ $srv->status }}
                                    </span>
                                </div>
                                <h3 class="text-xs font-semibold text-slate-800 mt-1 truncate">{{ $srv->customer->full_name ?? 'Customer' }}</h3>
                                <p class="text-[11px] text-slate-500 truncate">{{ $srv->asset->custom_name ?? ($srv->asset->product->name ?? 'Equipment / Unit') }}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-bold text-slate-800 block">{{ $srv->scheduled_date ? $srv->scheduled_date->format('d M, Y') : 'Date Unset' }}</span>
                            <span class="text-[11px] text-slate-500 block">{{ $srv->scheduled_time_slot ?? 'Flexible Slot' }}</span>
                            @if($srv->technician)
                                <span class="text-[10px] font-semibold text-teal-700 mt-1 block">Tech: {{ $srv->technician->full_name }}</span>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center text-xs text-slate-400">
                        <i data-lucide="calendar" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                        <span>No upcoming services scheduled for today.</span>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right: Operational Master Overview (1 Column) -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-card p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-100">
                    <i data-lucide="bar-chart-3" class="w-5 h-5 text-teal-600"></i>
                    <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">Enterprise Summary</h2>
                </div>
                <div class="space-y-3.5">
                    <a href="{{ route('customers.index') }}" class="flex items-center justify-between p-3 rounded-lg bg-slate-50 hover:bg-slate-100/80 transition-colors group">
                        <span class="text-xs font-semibold text-slate-600 group-hover:text-slate-900">Total Registered Customers</span>
                        <span id="summary-total-customers" class="text-sm font-extrabold text-slate-900">{{ $counts['totalCustomers'] }}</span>
                    </a>
                    <a href="{{ route('warranties.index') }}" class="flex items-center justify-between p-3 rounded-lg bg-slate-50 hover:bg-slate-100/80 transition-colors group">
                        <span class="text-xs font-semibold text-slate-600 group-hover:text-slate-900">Active Warranties</span>
                        <span id="summary-active-warranties" class="text-sm font-extrabold text-slate-900">{{ $counts['activeWarranties'] }}</span>
                    </a>
                    <a href="{{ route('services.index') }}" class="flex items-center justify-between p-3 rounded-lg bg-slate-50 hover:bg-slate-100/80 transition-colors group">
                        <span class="text-xs font-semibold text-slate-600 group-hover:text-slate-900">Active Service Tasks</span>
                        <span id="summary-active-services" class="text-sm font-extrabold text-slate-900">{{ $counts['activeServices'] }}</span>
                    </a>
                    <a href="{{ route('invoices.index') }}" class="flex items-center justify-between p-3 rounded-lg bg-slate-50 hover:bg-slate-100/80 transition-colors group">
                        <span class="text-xs font-semibold text-slate-600 group-hover:text-slate-900">Total Invoices Minted</span>
                        <span id="summary-total-invoices" class="text-sm font-extrabold text-slate-900">{{ $counts['totalInvoices'] }}</span>
                    </a>
                </div>
            </div>

            <!-- Overdue Warning Badge -->
            <div id="overdue-warning-container" class="{{ $cards['totalOverdueAmount'] > 0 ? '' : 'hidden' }}">
                <a href="{{ route('dues.index') }}" class="block mt-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 hover:bg-rose-100/70 transition-colors">
                    <div class="flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600"></i>
                        <span class="text-xs font-bold">Unpaid Aging Dues</span>
                    </div>
                    <div id="summary-overdue-amount" class="text-lg font-extrabold mt-1">₹{{ number_format($cards['totalOverdueAmount'], 2) }}</div>
                    <p id="summary-overdue-desc" class="text-[10px] text-rose-600 mt-0.5">{{ $cards['paymentsOverdue'] }} invoice(s) are past their due date.</p>
                </a>
            </div>
        </div>
    </div>

    <!-- 3. Bottom Row: Pending Payment Dues & Collection Ledger -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-card overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="wallet-cards" class="w-5 h-5 text-emerald-600"></i>
                <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">Payment Reminders &amp; Outstanding Receivables</h2>
            </div>
            <a href="{{ route('payments.index') }}" class="text-xs font-bold text-primary-600 hover:text-primary-800">Open Dues Ledger &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="p-3.5">Invoice #</th>
                        <th class="p-3.5">Customer Name</th>
                        <th class="p-3.5">Due Date</th>
                        <th class="p-3.5">Total Amount</th>
                        <th class="p-3.5">Balance Due</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5 text-right">Quick Action</th>
                    </tr>
                </thead>
                <tbody id="payment-reminders-tbody" class="divide-y divide-slate-100 font-medium">
                    @forelse($paymentReminders as $inv)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-3.5 font-bold text-slate-900">
                                <a href="{{ route('invoices.show', $inv->id) }}" class="hover:text-primary-600 font-bold">
                                    {{ $inv->invoice_number }}
                                </a>
                            </td>
                            <td class="p-3.5 text-slate-800">
                                <a href="{{ route('customers.show', $inv->customer_id) }}" class="hover:text-primary-600 font-semibold">
                                    {{ $inv->customer->full_name ?? 'Customer' }}
                                </a>
                            </td>
                            <td class="p-3.5 text-slate-600">{{ $inv->due_date ? $inv->due_date->format('d M, Y') : 'Immediate' }}</td>
                            <td class="p-3.5 text-slate-900">₹{{ number_format($inv->total_amount, 2) }}</td>
                            <td class="p-3.5 font-bold text-rose-600">₹{{ number_format($inv->balance_amount, 2) }}</td>
                            <td class="p-3.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $inv->isOverdue() ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $inv->isOverdue() ? 'OVERDUE' : $inv->status }}
                                </span>
                            </td>
                            <td class="p-3.5 text-right">
                                <a href="{{ route('payments.index') }}?invoice_id={{ $inv->id }}&action=record" class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold rounded shadow-2xs transition-colors">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    <span>Record Pay</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">
                                <i data-lucide="check-circle" class="w-6 h-6 mx-auto mb-1 text-slate-300"></i>
                                <span>No outstanding payment dues recorded. Great job!</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Client-side real-time responsiveness script -->
<script>
$(document).ready(function() {
    function refreshDashboardMetrics() {
        $.ajax({
            url: "{{ route('dashboard.overview') }}",
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (!res || !res.success || !res.data) return;
                const d = res.data;
                const cards = d.cards || {};
                const counts = d.counts || {};

                // 1. Update KPI Card 1 (Services Due)
                if ($('#kpi-services-due-count').length) {
                    $('#kpi-services-due-count').text(cards.servicesDueToday ?? 0);
                    $('#kpi-services-urgent-label').text((cards.servicesUrgent ?? 0) + ' urgent');
                }

                // 2. Update KPI Card 2 (Warranties)
                if ($('#kpi-warranties-expiring-count').length) {
                    $('#kpi-warranties-expiring-count').text(cards.warrantiesExpiring ?? 0);
                }

                // 3. Update KPI Card 3 (Payments Due)
                if ($('#kpi-payments-due-count').length) {
                    $('#kpi-payments-due-count').text(cards.paymentsDue ?? 0);
                    $('#kpi-payments-overdue-label').text((cards.paymentsOverdue ?? 0) + ' overdue');
                }

                // 4. Update KPI Card 4 (Technicians)
                if ($('#kpi-technicians-duty-count').length) {
                    $('#kpi-technicians-duty-count').text(cards.techniciansOnDuty ?? 0);
                    $('#kpi-technicians-available-label').text('Available: ' + (cards.techniciansAvailable ?? 0));
                }

                // 6. Update Enterprise Summary Counts
                if ($('#summary-total-customers').length) {
                    $('#summary-total-customers').text(counts.totalCustomers ?? 0);
                    $('#summary-active-warranties').text(counts.activeWarranties ?? 0);
                    $('#summary-active-services').text(counts.activeServices ?? 0);
                    $('#summary-total-invoices').text(counts.totalInvoices ?? 0);
                }

                // 7. Update Overdue Warning Box
                if ((cards.totalOverdueAmount ?? 0) > 0) {
                    $('#overdue-warning-container').removeClass('hidden');
                    $('#summary-overdue-amount').text(d.totalOverdueAmountFormatted || ('₹' + Number(cards.totalOverdueAmount).toFixed(2)));
                    $('#summary-overdue-desc').text((cards.paymentsOverdue ?? 0) + ' invoice(s) are past their due date.');
                } else {
                    $('#overdue-warning-container').addClass('hidden');
                }

                // 8. Update Schedule List
                if (d.schedule && $('#dashboard-schedule-list').length) {
                    if (d.schedule.length === 0) {
                        $('#dashboard-schedule-list').html(`
                            <div class="p-8 text-center text-xs text-slate-400">
                                <i data-lucide="calendar" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                                <span>No upcoming services scheduled for today.</span>
                            </div>
                        `);
                    } else {
                        let html = '';
                        d.schedule.forEach(function(srv) {
                            const classBadge = srv.service_classification === 'WARRANTY'
                                ? 'bg-amber-100 text-amber-800'
                                : 'bg-slate-100 text-slate-700';
                            const statusBadge = srv.status === 'COMPLETED'
                                ? 'bg-emerald-100 text-emerald-800'
                                : (srv.status === 'IN_PROGRESS' ? 'bg-indigo-100 text-indigo-800' : 'bg-sky-100 text-sky-800');
                            const techHtml = srv.technician_name
                                ? `<span class="text-[10px] font-semibold text-teal-700 mt-1 block">Tech: ${srv.technician_name}</span>`
                                : '';

                            html += `
                                <a href="${srv.url}" class="p-4 hover:bg-slate-50 transition-colors flex items-start justify-between gap-3 block group">
                                    <div class="flex items-start gap-3">
                                        <div class="p-2 rounded-lg bg-sky-50 text-sky-700 shrink-0 mt-0.5 group-hover:bg-primary-50 group-hover:text-primary-700 transition-colors">
                                            <i data-lucide="wrench" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-xs text-slate-900 group-hover:text-primary-600 transition-colors">${srv.service_number}</span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase ${classBadge}">
                                                    ${srv.service_classification}
                                                </span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase ${statusBadge}">
                                                    ${srv.status}
                                                </span>
                                            </div>
                                            <h3 class="text-xs font-semibold text-slate-800 mt-1">${srv.customer_name}</h3>
                                            <p class="text-[11px] text-slate-500">${srv.product_name}</p>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-xs font-bold text-slate-800 block">${srv.scheduled_date_formatted}</span>
                                        <span class="text-[11px] text-slate-500 block">${srv.scheduled_time_slot}</span>
                                        ${techHtml}
                                    </div>
                                </a>
                            `;
                        });
                        $('#dashboard-schedule-list').html(html);
                    }
                }

                // 9. Update Payment Reminders Table
                if (d.paymentReminders && $('#payment-reminders-tbody').length) {
                    if (d.paymentReminders.length === 0) {
                        $('#payment-reminders-tbody').html(`
                            <tr>
                                <td colspan="7" class="p-8 text-center text-slate-400">
                                    <i data-lucide="check-circle" class="w-6 h-6 mx-auto mb-1 text-slate-300"></i>
                                    <span>No outstanding payment dues recorded. Great job!</span>
                                </td>
                            </tr>
                        `);
                    } else {
                        let html = '';
                        d.paymentReminders.forEach(function(inv) {
                            const statusBadge = inv.is_overdue
                                ? 'bg-red-100 text-red-800'
                                : 'bg-amber-100 text-amber-800';

                            html += `
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="p-3.5 font-bold text-slate-900">
                                        <a href="${inv.invoice_url}" class="hover:text-primary-600 font-bold">
                                            ${inv.invoice_number}
                                        </a>
                                    </td>
                                    <td class="p-3.5 text-slate-800">
                                        <a href="${inv.customer_url}" class="hover:text-primary-600 font-semibold">
                                            ${inv.customer_name}
                                        </a>
                                    </td>
                                    <td class="p-3.5 text-slate-600">${inv.due_date_formatted}</td>
                                    <td class="p-3.5 text-slate-900">${inv.total_amount_formatted}</td>
                                    <td class="p-3.5 font-bold text-rose-600">${inv.balance_amount_formatted}</td>
                                    <td class="p-3.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase ${statusBadge}">
                                            ${inv.status}
                                        </span>
                                    </td>
                                    <td class="p-3.5 text-right">
                                        <a href="${inv.pay_url}" class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold rounded shadow-2xs transition-colors">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                            <span>Record Pay</span>
                                        </a>
                                    </td>
                                </tr>
                            `;
                        });
                        $('#payment-reminders-tbody').html(html);
                    }
                }

                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        });
    }

    // Live polling every 12 seconds
    setInterval(refreshDashboardMetrics, 12000);

    // Instant update on tab focus or visibility change
    $(window).on('focus', refreshDashboardMetrics);
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            refreshDashboardMetrics();
        }
    });

    // Inter-tab sync via storage events & custom dispatch
    window.addEventListener('storage', function(e) {
        if (e.key === 'crm_dashboard_refresh_tick') {
            refreshDashboardMetrics();
        }
    });
    window.addEventListener('crm_dashboard_refresh', refreshDashboardMetrics);
});
</script>
@endsection
