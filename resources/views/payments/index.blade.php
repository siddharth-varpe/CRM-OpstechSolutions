@extends('layouts.app')

@section('title', 'Payments & Collections - Enterprises CRM')

@section('content')
<div class="space-y-6 max-w-[1600px] mx-auto pb-12 select-none">
    <!-- 1. Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shadow-xs shrink-0">
                <i data-lucide="credit-card" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Payments &amp; Collections</h1>
                <p class="text-xs sm:text-sm text-slate-500 font-normal">
                    Track customer receipts, settlement modes and reconcile cashflows
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <button onclick="CRM.openModal('modalRecordPayment')" class="btn-primary flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Record Payment</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Collected</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-slate-900 font-mono">
                ₹{{ number_format($totalCollected, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Cumulative collections</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">This Month</span>
                <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-sky-600 font-mono">
                ₹{{ number_format($thisMonthCollected, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Receipts for {{ date('F Y') }}</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Today</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i data-lucide="arrow-down-left" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-indigo-600 font-mono">
                ₹{{ number_format($todayCollected, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Settled today</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending Invoices</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-amber-600 font-mono">
                {{ $pendingInvoicesCount }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Invoices awaiting full settlement</div>
        </div>
    </div>

    <!-- 3. Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs">
        <form method="GET" action="{{ route('payments.index') }}" class="flex flex-col md:flex-row gap-3 items-center justify-between">
            <div class="relative w-full md:w-96">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search receipt #, invoice #, customer, txn ref..."
                       class="form-input pl-10">
            </div>

            <div class="flex items-center gap-2.5 w-full md:w-auto">
                <select name="method" onchange="this.form.submit()" class="form-input text-xs w-full md:w-44">
                    <option value="ALL">All Payment Modes</option>
                    <option value="UPI" {{ request('method') === 'UPI' ? 'selected' : '' }}>UPI</option>
                    <option value="CASH" {{ request('method') === 'CASH' ? 'selected' : '' }}>Cash</option>
                    <option value="BANK_TRANSFER" {{ request('method') === 'BANK_TRANSFER' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="CHEQUE" {{ request('method') === 'CHEQUE' ? 'selected' : '' }}>Cheque</option>
                    <option value="CARD" {{ request('method') === 'CARD' ? 'selected' : '' }}>Card</option>
                </select>

                @if(request()->hasAny(['search', 'method']))
                    <a href="{{ route('payments.index') }}" class="btn-outline text-xs whitespace-nowrap">
                        Reset Filters
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- 4. Payments Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Receipt #</th>
                        <th class="py-3.5 px-4">Customer</th>
                        <th class="py-3.5 px-4">Invoice Ref</th>
                        <th class="py-3.5 px-4">Mode</th>
                        <th class="py-3.5 px-4">Reference / Txn ID</th>
                        <th class="py-3.5 px-4 text-right">Amount (₹)</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($payments as $p)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3.5 px-4">
                                <div class="flex items-start gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/80 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                                        <i data-lucide="receipt" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 font-mono">{{ $p->payment_number }}</div>
                                        <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-1 font-mono">
                                            <i data-lucide="calendar" class="w-3 h-3 text-slate-400"></i>
                                            {{ \Carbon\Carbon::parse($p->payment_date)->format('d M Y') }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div>
                                    <a href="{{ $p->customer_id ? route('customers.show', $p->customer_id) : '#' }}" class="font-semibold text-slate-900 hover:text-sky-600 flex items-center gap-1">
                                        <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                        {{ $p->customer->name ?? 'Walk-in Customer' }}
                                    </a>
                                    <div class="text-xs text-slate-500 mt-0.5 font-mono">
                                        {{ $p->customer->phone ?? 'N/A' }}
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                @if($p->invoice)
                                    <a href="{{ route('invoices.show', $p->invoice_id) }}" class="font-mono text-xs font-semibold text-sky-600 hover:underline flex items-center gap-1">
                                        {{ $p->invoice->invoice_number }}
                                        <i data-lucide="external-link" class="w-3 h-3"></i>
                                    </a>
                                @else
                                    <span class="text-slate-400 text-xs">—</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ $p->payment_method }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 font-mono text-xs text-slate-600">
                                {{ $p->reference_number ?? '—' }}
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono text-xs font-bold text-emerald-700">
                                ₹{{ number_format($p->amount, 2) }}
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    {{ $p->status }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <button onclick="viewPaymentReceipt('{{ $p->id }}')" class="btn-outline py-1 px-3 text-xs inline-flex items-center gap-1.5">
                                    <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-500"></i>
                                    <span>Receipt</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i data-lucide="credit-card" class="w-10 h-10 text-slate-300 mb-2"></i>
                                    <p class="font-medium">No payment records found</p>
                                    <p class="text-xs text-slate-400 mt-1">Record payments against customer invoices to manage financial settlements.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payments->hasPages())
            <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: Record Payment -->
<div id="modalRecordPayment" class="modal-backdrop hidden">
    <div class="modal-box max-w-lg">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="credit-card" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-lg">Record Customer Payment</h3>
                    <p class="text-xs text-slate-500">Apply payment to outstanding invoice with auto receipt #</p>
                </div>
            </div>
            <button onclick="CRM.closeModal('modalRecordPayment')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="formRecordPayment" action="{{ route('payments.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Select Invoice to Settle <span class="text-rose-500">*</span></label>
                <select name="invoice_id" id="paymentInvoiceSelect" required class="form-input text-sm">
                    <option value="">Select Invoice</option>
                    @foreach($pendingInvoices as $pi)
                        <option value="{{ $pi->id }}" data-balance="{{ $pi->balance_amount }}" data-customer="{{ $pi->customer->name ?? '' }}">
                            {{ $pi->invoice_number }} - {{ $pi->customer->name ?? 'Walk-in' }} (Due: ₹{{ number_format($pi->balance_amount, 2) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Amount (₹) <span class="text-rose-500">*</span></label>
                    <input type="number" name="amount" id="paymentAmountInput" min="1" step="0.01" required class="form-input text-sm font-mono font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required class="form-input text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Mode <span class="text-rose-500">*</span></label>
                    <select name="payment_mode" required class="form-input text-xs">
                        <option value="UPI">UPI (Google Pay / PhonePe / Paytm)</option>
                        <option value="CASH">Cash</option>
                        <option value="BANK_TRANSFER">Bank Transfer (NEFT/IMPS)</option>
                        <option value="CHEQUE">Cheque</option>
                        <option value="CARD">Debit / Credit Card</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reference / UTR #</label>
                    <input type="text" name="transaction_reference" placeholder="e.g. UPI Ref / Cheque No." class="form-input text-xs">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" placeholder="Payment remarks..." class="form-input text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="CRM.closeModal('modalRecordPayment')" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Record &amp; Issue Receipt</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Printable Payment Receipt -->
<div id="modalPaymentReceipt" class="modal-backdrop hidden">
    <div class="modal-box max-w-lg p-6 font-sans">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Official Payment Receipt</span>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="btn-outline py-1 px-2.5 text-xs flex items-center gap-1.5">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    <span>Print</span>
                </button>
                <button onclick="CRM.closeModal('modalPaymentReceipt')" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>

        <div id="receiptContent" class="mt-4 space-y-4 text-xs text-slate-700">
            <div class="text-center pb-3 border-b border-dashed border-slate-200">
                <img src="/assets/crm-logo.png" alt="Logo" class="h-10 mx-auto mb-1">
                <div class="font-bold text-sm text-slate-900 uppercase">{{ \App\Models\SystemSetting::get('company_name', 'ENTERPRISES CRM') }}</div>
                <div class="text-[11px] text-slate-500">{{ \App\Models\SystemSetting::get('company_address', 'Pune, Maharashtra') }} • GSTIN: {{ \App\Models\SystemSetting::get('company_gstin', \App\Models\SystemSetting::get('gst_number', '27AABCS1429B1Z')) }}</div>
            </div>

            <div class="flex justify-between items-center py-2 bg-slate-50 rounded-lg px-3">
                <div>
                    <span class="text-[10px] text-slate-400 uppercase font-semibold">Receipt Number</span>
                    <div class="font-mono font-bold text-slate-900" id="rcptNum">—</div>
                </div>
                <div class="text-right">
                    <span class="text-[10px] text-slate-400 uppercase font-semibold">Date</span>
                    <div class="font-mono text-slate-900" id="rcptDate">—</div>
                </div>
            </div>

            <div class="space-y-1.5 py-2 border-b border-slate-200">
                <div class="flex justify-between">
                    <span class="text-slate-500">Received From:</span>
                    <span class="font-bold text-slate-900" id="rcptCustomer">—</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Invoice Number:</span>
                    <span class="font-mono font-semibold text-slate-900" id="rcptInvoice">—</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Payment Mode:</span>
                    <span class="font-semibold text-slate-900" id="rcptMode">—</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Transaction Ref:</span>
                    <span class="font-mono text-slate-700" id="rcptRef">—</span>
                </div>
            </div>

            <div class="py-3 bg-emerald-50/70 border border-emerald-200/60 rounded-xl px-4 flex justify-between items-center">
                <span class="font-bold text-emerald-900 uppercase text-xs">Amount Received</span>
                <span class="font-mono font-black text-emerald-700 text-lg" id="rcptAmount">₹0.00</span>
            </div>

            <div class="pt-2 text-center text-[10px] text-slate-400">
                This is a computer-generated receipt and requires no physical signature.<br>
                Thank you for your payment!
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#paymentInvoiceSelect').on('change', function() {
        const balance = $(this).find(':selected').data('balance') || '';
        $('#paymentAmountInput').val(balance).attr('max', balance);
    });

    CRM.handleAjaxForm('#formRecordPayment', function(res) {
        CRM.toast(res.message || 'Payment recorded successfully!');
        setTimeout(() => location.reload(), 600);
    });
});

function viewPaymentReceipt(paymentId) {
    $.get('/payments/' + paymentId, function(res) {
        if (res.success && res.data) {
            const p = res.data;
            $('#rcptNum').text(p.payment_number);
            $('#rcptDate').text(new Date(p.payment_date).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }));
            $('#rcptCustomer').text(p.customer?.full_name || 'Walk-in Customer');
            $('#rcptInvoice').text(p.invoice?.invoice_number || 'N/A');
            $('#rcptMode').text(p.payment_method);
            $('#rcptRef').text(p.reference_number || 'N/A');
            $('#rcptAmount').text('₹' + parseFloat(p.amount).toLocaleString('en-IN', { minimumFractionDigits: 2 }));

            CRM.openModal('modalPaymentReceipt');
        }
    });
}
</script>
@endsection
