@extends('layouts.app')

@section('title', 'Dues & Receivables Ledger - Enterprises CRM')

@section('content')
<div class="space-y-6 max-w-[1600px] mx-auto pb-12 select-none">
    <!-- 1. Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center shadow-xs shrink-0">
                <i data-lucide="clock" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dues &amp; Receivables Ledger</h1>
                <p class="text-xs sm:text-sm text-slate-500 font-normal">
                    Track upcoming periodic maintenance visits &amp; overdue receivables with one-click follow-up
                </p>
            </div>
        </div>
    </div>

    <!-- 2. KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Service Visits Due</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i data-lucide="wrench" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-indigo-600 font-mono">
                {{ $totalServiceDuesCount }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Periodic service schedules due</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Payment Dues</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-amber-600 font-mono">
                ₹{{ number_format($totalPaymentDuesSum, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Unsettled invoice receivables</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Overdue Invoices</span>
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-rose-600 font-mono">
                {{ $overdueInvoicesCount }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Passed payment due date</div>
        </div>
    </div>

    <!-- 3. Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
        <a href="{{ route('dues.index', ['tab' => 'services']) }}"
           class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition {{ $tab === 'services' ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
            <i data-lucide="wrench" class="w-4 h-4"></i>
            <span>Service Maintenance Dues</span>
        </a>
        <a href="{{ route('dues.index', ['tab' => 'payments']) }}"
           class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition {{ $tab === 'payments' ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
            <i data-lucide="credit-card" class="w-4 h-4"></i>
            <span>Payment Receivables Dues</span>
        </a>
    </div>

    <!-- 4. Tab Content -->
    @if($tab === 'services')
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Customer</th>
                            <th class="py-3.5 px-4">Asset / Machine</th>
                            <th class="py-3.5 px-4">Planned Date</th>
                            <th class="py-3.5 px-4 text-center">Service #</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($serviceDues as $sd)
                            @php
                                $planned = \Carbon\Carbon::parse($sd->planned_date);
                                $isOverdue = $planned->isPast();
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3.5 px-4">
                                    <div>
                                        <a href="{{ $sd->customer_id ? route('customers.show', $sd->customer_id) : '#' }}" class="font-semibold text-slate-900 hover:text-sky-600 flex items-center gap-1">
                                            <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                            {{ $sd->customer->name ?? 'Walk-in Customer' }}
                                        </a>
                                        <div class="text-xs text-slate-500 mt-0.5 font-mono">
                                            {{ $sd->customer->phone ?? 'N/A' }}
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-slate-800 text-xs">
                                        {{ $sd->asset->custom_name ?? ($sd->asset->product->name ?? 'Equipment / Unit') }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5 font-mono">
                                        S/N: {{ $sd->asset->serial_number ?? 'N/A' }}
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 font-mono text-xs">
                                    <div class="font-semibold {{ $isOverdue ? 'text-rose-600' : 'text-slate-800' }}">
                                        {{ $planned->format('d M Y') }}
                                    </div>
                                    @if($isOverdue)
                                        <span class="text-[10px] text-rose-600 font-bold uppercase tracking-wider font-sans">Overdue</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-center font-mono text-xs">
                                    Schedule {{ $sd->schedule_index }} of {{ $sd->total_schedules }}
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $isOverdue ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200' }}">
                                        {{ $isOverdue ? 'OVERDUE' : $sd->status }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($sd->customer && $sd->customer->phone)
                                            <button onclick="sendServiceDueWhatsApp('{{ $sd->customer->name }}', '{{ $sd->customer->phone }}', '{{ addslashes($sd->asset->custom_name ?? ($sd->asset->product->name ?? 'Equipment')) }}', '{{ $planned->format('d M Y') }}')"
                                                    class="btn-outline py-1 px-2.5 text-xs inline-flex items-center gap-1 text-emerald-700 border-emerald-300 hover:bg-emerald-50">
                                                <i data-lucide="message-circle" class="w-3.5 h-3.5 text-emerald-600"></i>
                                                <span>WhatsApp</span>
                                            </button>
                                        @endif
                                        <a href="{{ route('services.index') }}" class="btn-primary py-1 px-2.5 text-xs inline-flex items-center gap-1">
                                            <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                            <span>Schedule</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i data-lucide="check-circle" class="w-10 h-10 text-emerald-400 mb-2"></i>
                                        <p class="font-medium">All service visits are up to date</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($serviceDues->hasPages())
                <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                    {{ $serviceDues->links() }}
                </div>
            @endif
        </div>
    @elseif($tab === 'payments')
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Invoice #</th>
                            <th class="py-3.5 px-4">Customer</th>
                            <th class="py-3.5 px-4">Due Date</th>
                            <th class="py-3.5 px-4 text-right">Total Invoiced</th>
                            <th class="py-3.5 px-4 text-right">Outstanding (₹)</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($paymentDues as $pd)
                            @php
                                $due = \Carbon\Carbon::parse($pd->due_date);
                                $isOverdue = $due->isPast();
                                $daysOverdue = $isOverdue ? ceil(now()->diffInDays($due)) : 0;
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3.5 px-4">
                                    <a href="{{ route('invoices.show', $pd->id) }}" class="font-bold text-slate-900 hover:text-sky-600 font-mono flex items-center gap-1">
                                        {{ $pd->invoice_number }}
                                        <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 text-slate-400"></i>
                                    </a>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div>
                                        <a href="{{ $pd->customer_id ? route('customers.show', $pd->customer_id) : '#' }}" class="font-semibold text-slate-900 hover:text-sky-600 flex items-center gap-1">
                                            <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                            {{ $pd->customer->name ?? 'Direct Customer' }}
                                        </a>
                                        <div class="text-xs text-slate-500 mt-0.5 font-mono">
                                            {{ $pd->customer->phone ?? 'N/A' }}
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 font-mono text-xs">
                                    <div class="font-semibold {{ $isOverdue ? 'text-rose-600' : 'text-slate-800' }}">
                                        {{ $due->format('d M Y') }}
                                    </div>
                                    @if($isOverdue)
                                        <span class="text-[10px] text-rose-600 font-bold uppercase tracking-wider font-sans">
                                            {{ $daysOverdue }} days overdue
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-right font-mono text-xs">
                                    ₹{{ number_format($pd->total_amount, 2) }}
                                </td>

                                <td class="py-3.5 px-4 text-right font-mono text-xs font-bold text-amber-700">
                                    ₹{{ number_format($pd->balance_amount, 2) }}
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $isOverdue ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200' }}">
                                        {{ $isOverdue ? 'OVERDUE' : $pd->status }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($pd->customer && $pd->customer->phone)
                                            <button onclick="sendPaymentDueWhatsApp('{{ $pd->customer->name }}', '{{ $pd->customer->phone }}', '{{ $pd->invoice_number }}', '{{ number_format($pd->balance_amount, 2) }}', '{{ $due->format('d M Y') }}')"
                                                    class="btn-outline py-1 px-2.5 text-xs inline-flex items-center gap-1 text-emerald-700 border-emerald-300 hover:bg-emerald-50">
                                                <i data-lucide="message-circle" class="w-3.5 h-3.5 text-emerald-600"></i>
                                                <span>WhatsApp</span>
                                            </button>
                                        @endif
                                        <a href="{{ route('invoices.show', $pd->id) }}" class="btn-outline py-1 px-2.5 text-xs inline-flex items-center gap-1">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            <span>View</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i data-lucide="check-circle" class="w-10 h-10 text-emerald-400 mb-2"></i>
                                        <p class="font-medium">No outstanding payment dues</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($paymentDues->hasPages())
                <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                    {{ $paymentDues->links() }}
                </div>
            @endif
        </div>
    @endif
</div>

<script>
function sendServiceDueWhatsApp(name, phone, assetName, dateStr) {
    const cleanPhone = phone.replace(/[^0-9]/g, '');
    const fullPhone = cleanPhone.length === 10 ? '91' + cleanPhone : cleanPhone;
    const msg = encodeURIComponent(
        `Dear ${name},\n\n` +
        `This is a friendly reminder from *{{ \App\Models\SystemSetting::get('company_name', 'Enterprises CRM') }}* that your *${assetName}* is due for its periodic maintenance service on *${dateStr}*.\n\n` +
        `Please let us know a suitable time so we can dispatch our certified technician.\n\n` +
        `Helpline: +91 98765 43210`
    );
    window.open(`https://wa.me/${fullPhone}?text=${msg}`, '_blank');
}

function sendPaymentDueWhatsApp(name, phone, invNumber, balanceAmount, dueDateStr) {
    const cleanPhone = phone.replace(/[^0-9]/g, '');
    const fullPhone = cleanPhone.length === 10 ? '91' + cleanPhone : cleanPhone;
    const msg = encodeURIComponent(
        `Dear ${name},\n\n` +
        `Greetings from *{{ \App\Models\SystemSetting::get('company_name', 'Enterprises CRM') }}*.\n\n` +
        `This is a payment reminder for Invoice *${invNumber}* with an outstanding balance of *₹${balanceAmount}* due on *${dueDateStr}*.\n\n` +
        `UPI ID: enterprises.crm@upi\n\n` +
        `Thank you for your prompt payment!`
    );
    window.open(`https://wa.me/${fullPhone}?text=${msg}`, '_blank');
}
</script>
@endsection
