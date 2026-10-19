@extends('layouts.app')

@section('title', 'Invoices - Enterprises CRM')

@section('content')
<div class="space-y-6 max-w-[1600px] mx-auto pb-12 select-none">
    <!-- 1. Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center shadow-xs shrink-0">
                <i data-lucide="file-text" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Invoices</h1>
                <p class="text-xs sm:text-sm text-slate-500 font-normal">
                    Browse, track and manage customer tax invoices and payments
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('invoices.export') }}" class="btn-outline flex items-center gap-2">
                <i data-lucide="download" class="w-4 h-4 text-slate-500"></i>
                <span>Export CSV</span>
            </a>
            <button onclick="CRM.openModal('modalCreateInvoice')" class="btn-primary flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Create Invoice</span>
            </button>
        </div>
    </div>

    <!-- 2. Live KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Invoiced</span>
                <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                    <i data-lucide="receipt" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-slate-900 font-mono">
                ₹{{ number_format($totalInvoiced, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Gross billing across all records</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Collected</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-emerald-600 font-mono">
                ₹{{ number_format($totalPaid, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Realized collections</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Outstanding</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-amber-600 font-mono">
                ₹{{ number_format($totalOutstanding, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Pending receivables</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Overdue Invoices</span>
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-rose-600 font-mono">
                {{ $overdueCount }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Require immediate follow-up</div>
        </div>
    </div>

    <!-- 3. Filter Toolbar & Search -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs">
        <form method="GET" action="{{ route('invoices.index') }}" class="flex flex-col md:flex-row gap-3 items-center justify-between">
            <div class="relative w-full md:w-96">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search invoice #, customer name, phone..."
                       class="form-input pl-10">
            </div>

            <div class="flex items-center gap-2.5 w-full md:w-auto">
                <select name="status" onchange="this.form.submit()" class="form-input text-xs w-full md:w-44">
                    <option value="ALL">All Statuses</option>
                    <option value="ISSUED" {{ request('status') === 'ISSUED' ? 'selected' : '' }}>Issued</option>
                    <option value="PAID" {{ request('status') === 'PAID' ? 'selected' : '' }}>Paid</option>
                    <option value="PARTIAL" {{ request('status') === 'PARTIAL' ? 'selected' : '' }}>Partial</option>
                    <option value="OVERDUE" {{ request('status') === 'OVERDUE' ? 'selected' : '' }}>Overdue</option>
                    <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                </select>

                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('invoices.index') }}" class="btn-outline text-xs whitespace-nowrap">
                        Reset Filters
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- 4. Invoices Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Invoice #</th>
                        <th class="py-3.5 px-4">Customer</th>
                        <th class="py-3.5 px-4">Due Date</th>
                        <th class="py-3.5 px-4 text-right">Total Billed</th>
                        <th class="py-3.5 px-4 text-right">Outstanding</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($invoices as $inv)
                        @php
                            $isOverdue = $inv->due_date && \Carbon\Carbon::parse($inv->due_date)->isPast() && $inv->outstanding_amount > 0 && $inv->status !== 'CANCELLED';
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3.5 px-4">
                                <div class="flex items-start gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-700 border border-sky-200/80 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <a href="{{ route('invoices.show', $inv->id) }}" class="font-bold text-slate-900 hover:text-sky-600 font-mono flex items-center gap-1">
                                            {{ $inv->invoice_number }}
                                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 text-slate-400"></i>
                                        </a>
                                        <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-1 font-mono">
                                            <i data-lucide="calendar" class="w-3 h-3 text-slate-400"></i>
                                            {{ \Carbon\Carbon::parse($inv->invoice_date)->format('d M Y') }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div>
                                    <a href="{{ $inv->customer_id ? route('customers.show', $inv->customer_id) : '#' }}" class="font-semibold text-slate-900 hover:text-sky-600 flex items-center gap-1">
                                        <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                        {{ $inv->customer_name }}
                                    </a>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        <span class="font-mono">{{ $inv->customer_phone ?? 'N/A' }}</span>
                                        @if($inv->customer && $inv->customer->customer_number)
                                            • <span class="font-mono text-[11px] font-semibold">{{ $inv->customer->customer_number }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                @if($inv->status === 'PAID' || $inv->outstanding_amount <= 0)
                                    <span class="text-xs font-mono text-slate-400">—</span>
                                @else
                                    <div class="text-xs font-mono">
                                        <div class="{{ $isOverdue ? 'text-rose-600 font-bold' : 'text-slate-700 font-semibold' }}">
                                            {{ \Carbon\Carbon::parse($inv->due_date)->format('d M Y') }}
                                        </div>
                                        @if($isOverdue)
                                            <span class="text-[10px] text-rose-600 font-bold uppercase tracking-wider">Overdue</span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <div class="font-bold text-slate-900 font-mono text-xs">
                                    ₹{{ number_format($inv->total_amount, 2) }}
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <div class="font-mono text-xs font-bold {{ $inv->outstanding_amount > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                                    @if($inv->outstanding_amount > 0)
                                        ₹{{ number_format($inv->outstanding_amount, 2) }}
                                    @else
                                        Paid in Full
                                    @endif
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                @php
                                    $badgeColors = [
                                        'PAID' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'ISSUED' => 'bg-sky-50 text-sky-700 border-sky-200',
                                        'PARTIAL' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'OVERDUE' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        'CANCELLED' => 'bg-slate-100 text-slate-600 border-slate-200',
                                    ];
                                    $badge = $badgeColors[$inv->status] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $badge }}">
                                    {{ $inv->status }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('invoices.show', $inv->id) }}" class="btn-outline py-1 px-3 text-xs inline-flex items-center gap-1.5">
                                    <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-500"></i>
                                    <span>View</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i data-lucide="file-question" class="w-10 h-10 text-slate-300 mb-2"></i>
                                    <p class="font-medium">No invoices found matching criteria</p>
                                    <p class="text-xs text-slate-400 mt-1">Try adjusting your filters or generate a new invoice.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: Create New Invoice -->
<div id="modalCreateInvoice" class="modal-backdrop hidden">
    <div class="modal-box max-w-4xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                    <i data-lucide="file-plus" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-lg">Create New Invoice</h3>
                    <p class="text-xs text-slate-500">Generate a GST tax invoice with auto document numbering</p>
                </div>
            </div>
            <button onclick="CRM.closeModal('modalCreateInvoice')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="formCreateInvoice" action="{{ route('invoices.store') }}" method="POST" class="mt-5 space-y-5">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Customer <span class="text-rose-500">*</span></label>
                    <select name="customer_id" required class="form-input text-sm">
                        <option value="">Select Customer</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->full_name ?? $c->name }} ({{ $c->phone }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Invoice Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="invoice_date" value="{{ date('Y-m-d') }}" required class="form-input text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Due Date</label>
                    <input type="date" name="due_date" value="{{ date('Y-m-d', strtotime('+15 days')) }}" class="form-input text-sm">
                </div>
            </div>

            <!-- Invoice Line Items Table -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Line Items</label>
                    <button type="button" id="btnAddItemRow" class="text-xs text-sky-600 hover:text-sky-700 font-semibold flex items-center gap-1">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i> Add Item
                    </button>
                </div>

                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-xs" id="invoiceItemsTable">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                            <tr>
                                <th class="p-3 w-5/12">Item Description</th>
                                <th class="p-3 w-2/12">Qty</th>
                                <th class="p-3 w-2/12">Unit Price (₹)</th>
                                <th class="p-3 w-1/12">GST %</th>
                                <th class="p-3 w-2/12 text-right">Total (₹)</th>
                                <th class="p-3 w-12 text-center"></th>
                            </tr>
                        </thead>
                        <tbody id="invoiceItemsBody" class="divide-y divide-slate-100">
                            <!-- Default item row -->
                            <tr class="item-row">
                                <td class="p-2">
                                    <input type="text" name="items[0][item_description]" required placeholder="Product or service description" class="form-input text-xs">
                                </td>
                                <td class="p-2">
                                    <input type="number" name="items[0][quantity]" value="1" min="1" step="1" required class="form-input text-xs qty-input">
                                </td>
                                <td class="p-2">
                                    <input type="number" name="items[0][unit_price]" value="0" min="0" step="0.01" required class="form-input text-xs price-input">
                                </td>
                                <td class="p-2">
                                    <input type="number" name="items[0][tax_rate]" value="{{ \App\Models\SystemSetting::get('default_gst_rate', 0) }}" min="0" step="1" class="form-input text-xs tax-input">
                                </td>
                                <td class="p-2 text-right font-mono font-bold text-slate-800 row-total">
                                    0.00
                                </td>
                                <td class="p-2 text-center">
                                    <button type="button" class="text-slate-400 hover:text-rose-500 btn-remove-row">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Totals & Notes -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Notes / Terms</label>
                    <textarea name="notes" rows="3" placeholder="Invoice notes, warranty details, payment conditions..." class="form-input text-xs"></textarea>
                </div>

                <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200/80 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Taxable Subtotal:</span>
                        <span class="font-mono font-semibold" id="calcSubtotal">₹0.00</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>CGST + SGST (Tax):</span>
                        <span class="font-mono font-semibold" id="calcTax">₹0.00</span>
                    </div>
                    <div class="flex justify-between text-base font-bold text-slate-900 border-t border-slate-200 pt-2">
                        <span>Grand Total:</span>
                        <span class="font-mono text-sky-600" id="calcTotal">₹0.00</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="button" onclick="CRM.closeModal('modalCreateInvoice')" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Generate Invoice</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    let rowIndex = 1;

    function recalculateInvoice() {
        let subtotal = 0;
        let totalTax = 0;

        $('#invoiceItemsBody tr').each(function() {
            const qty = parseFloat($(this).find('.qty-input').val()) || 0;
            const price = parseFloat($(this).find('.price-input').val()) || 0;
            const taxRate = parseFloat($(this).find('.tax-input').val()) || 0;

            const lineBase = qty * price;
            const lineTax = (lineBase * taxRate) / 100;
            const lineTotal = lineBase + lineTax;

            $(this).find('.row-total').text(lineTotal.toFixed(2));
            subtotal += lineBase;
            totalTax += lineTax;
        });

        const grandTotal = subtotal + totalTax;
        $('#calcSubtotal').text('₹' + subtotal.toFixed(2));
        $('#calcTax').text('₹' + totalTax.toFixed(2));
        $('#calcTotal').text('₹' + grandTotal.toFixed(2));
    }

    $(document).on('input', '.qty-input, .price-input, .tax-input', function() {
        recalculateInvoice();
    });

    $('#btnAddItemRow').on('click', function() {
        const rowHtml = `
            <tr class="item-row">
                <td class="p-2">
                    <input type="text" name="items[${rowIndex}][item_description]" required placeholder="Product or service description" class="form-input text-xs">
                </td>
                <td class="p-2">
                    <input type="number" name="items[${rowIndex}][quantity]" value="1" min="1" step="1" required class="form-input text-xs qty-input">
                </td>
                <td class="p-2">
                    <input type="number" name="items[${rowIndex}][unit_price]" value="0" min="0" step="0.01" required class="form-input text-xs price-input">
                </td>
                <td class="p-2">
                    <input type="number" name="items[${rowIndex}][tax_rate]" value="{{ \App\Models\SystemSetting::get('default_gst_rate', 0) }}" min="0" step="1" class="form-input text-xs tax-input">
                </td>
                <td class="p-2 text-right font-mono font-bold text-slate-800 row-total">
                    0.00
                </td>
                <td class="p-2 text-center">
                    <button type="button" class="text-slate-400 hover:text-rose-500 btn-remove-row">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#invoiceItemsBody').append(rowHtml);
        rowIndex++;
        lucide.createIcons();
    });

    $(document).on('click', '.btn-remove-row', function() {
        if ($('#invoiceItemsBody tr').length > 1) {
            $(this).closest('tr').remove();
            recalculateInvoice();
        } else {
            CRM.toast('At least one item is required in the invoice.', 'warning');
        }
    });

    CRM.handleAjaxForm('#formCreateInvoice', function(res) {
        CRM.toast(res.message || 'Invoice generated successfully!');
        setTimeout(() => window.location.href = '/invoices/' + res.data.id, 600);
    });
});
</script>
@endsection
