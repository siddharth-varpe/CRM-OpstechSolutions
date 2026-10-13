@extends('layouts.app')

@section('title', 'Sales Orders - Enterprises CRM')

@section('content')
<div class="space-y-6 max-w-[1600px] mx-auto pb-12 select-none">
    <!-- 1. Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center shadow-xs shrink-0">
                <i data-lucide="shopping-bag" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Sales &amp; Orders</h1>
                <p class="text-xs sm:text-sm text-slate-500 font-normal">
                    Manage orders, track revenue, auto-generate warranties &amp; tax invoices
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <button onclick="CRM.openModal('modalCreateSale')" class="btn-primary flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>New Sale Order</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Cards -->
    @php
        $totalOrdersCount = \App\Models\Sale::count();
        $totalRevenueSum = \App\Models\Sale::sum('total_amount');
        $avgTicket = $totalOrdersCount > 0 ? $totalRevenueSum / $totalOrdersCount : 0;
        $totalItemsSold = \App\Models\SaleItem::sum('quantity');
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Orders</span>
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-slate-900 font-mono">
                {{ $totalOrdersCount }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Orders placed across all channels</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Gross Sales</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="trending-up" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-emerald-600 font-mono">
                ₹{{ number_format($totalRevenueSum, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Total billings generated</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Average Ticket</span>
                <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                    <i data-lucide="tag" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-sky-600 font-mono">
                ₹{{ number_format($avgTicket, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Per order sales value</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Units Delivered</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                    <i data-lucide="package-check" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-purple-600 font-mono">
                {{ $totalItemsSold }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Products &amp; spares dispatched</div>
        </div>
    </div>

    <!-- 3. Filters Toolbar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs">
        <form method="GET" action="{{ route('sales.index') }}" class="flex flex-col md:flex-row gap-3 items-center justify-between">
            <div class="relative w-full md:w-96">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search sale order #, customer name, phone..."
                       class="form-input pl-10">
            </div>

            <div class="flex items-center gap-2.5 w-full md:w-auto">
                <select name="status" onchange="this.form.submit()" class="form-input text-xs w-full md:w-44">
                    <option value="ALL">All Statuses</option>
                    <option value="COMPLETED" {{ request('status') === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                    <option value="DRAFT" {{ request('status') === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                    <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                </select>

                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('sales.index') }}" class="btn-outline text-xs whitespace-nowrap">
                        Reset Filters
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- 4. Sales Orders Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Order #</th>
                        <th class="py-3.5 px-4">Customer</th>
                        <th class="py-3.5 px-4">Items / Product</th>
                        <th class="py-3.5 px-4 text-right">Subtotal</th>
                        <th class="py-3.5 px-4 text-right">GST (18%)</th>
                        <th class="py-3.5 px-4 text-right">Total Amount</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($sales as $sale)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3.5 px-4">
                                <div class="flex items-start gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 border border-rose-200/80 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                                        <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 font-mono">{{ $sale->sale_number }}</div>
                                        <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-1 font-mono">
                                            <i data-lucide="calendar" class="w-3 h-3 text-slate-400"></i>
                                            {{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div>
                                    <a href="{{ $sale->customer_id ? route('customers.show', $sale->customer_id) : '#' }}" class="font-semibold text-slate-900 hover:text-sky-600 flex items-center gap-1">
                                        <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                        {{ $sale->customer->name ?? 'Walk-in Customer' }}
                                    </a>
                                    <div class="text-xs text-slate-500 mt-0.5 font-mono">
                                        {{ $sale->customer->phone ?? 'N/A' }}
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                @if($sale->items->isNotEmpty())
                                    <div class="font-medium text-slate-800 text-xs">
                                        {{ $sale->items->first()->product_name_snapshot }}
                                        @if($sale->items->count() > 1)
                                            <span class="text-slate-500 font-normal">(+{{ $sale->items->count() - 1 }} more)</span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5 font-mono">
                                        Qty: {{ $sale->items->sum('quantity') }}
                                        @if($sale->items->first()->serial_number)
                                            • S/N: {{ $sale->items->first()->serial_number }}
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-400 text-xs">—</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono text-xs">
                                ₹{{ number_format($sale->subtotal, 2) }}
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono text-xs text-slate-600">
                                @if((float)$sale->tax_amount > 0)
                                    ₹{{ number_format($sale->tax_amount, 2) }}
                                @else
                                    <span class="text-slate-400 font-sans text-[11px] bg-slate-100 px-1.5 py-0.5 rounded">0% (None)</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono text-xs font-bold text-slate-900">
                                ₹{{ number_format($sale->total_amount, 2) }}
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                @php
                                    $badgeColors = [
                                        'COMPLETED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'DRAFT' => 'bg-slate-50 text-slate-700 border-slate-200',
                                        'CANCELLED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    ];
                                    $badge = $badgeColors[$sale->status] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $badge }}">
                                    {{ $sale->status }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                @php
                                    $invoice = $sale->invoices ? $sale->invoices->first() : null;
                                @endphp
                                @if($invoice)
                                    <a href="{{ route('invoices.show', $invoice->id) }}" class="btn-outline py-1 px-3 text-xs inline-flex items-center gap-1.5 text-sky-700 border-sky-300 hover:bg-sky-50">
                                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                        <span>Invoice</span>
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i data-lucide="shopping-bag" class="w-10 h-10 text-slate-300 mb-2"></i>
                                    <p class="font-medium">No sales orders found</p>
                                    <p class="text-xs text-slate-400 mt-1">Create a sales order to bill customers and manage warranty contracts.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
            <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                {{ $sales->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: Create New Sale Order -->
<div id="modalCreateSale" class="modal-backdrop hidden">
    <div class="modal-box max-w-xl">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-lg">New Sales Order</h3>
                    <p class="text-xs text-slate-500">Auto-mints invoice &amp; warranty registration</p>
                </div>
            </div>
            <button onclick="CRM.closeModal('modalCreateSale')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="formCreateSale" action="{{ route('sales.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Customer <span class="text-rose-500">*</span></label>
                <select name="customer_id" required class="form-input text-sm">
                    <option value="">Select Customer</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700">
                        Product / Machine Name <span class="text-rose-500">*</span>
                    </label>
                    <span class="text-[11px] text-slate-400 font-normal">Free text &bull; No options limit</span>
                </div>
                <div class="relative">
                    <input type="text" 
                           name="product_name" 
                           id="saleProductName" 
                           list="saleProductList" 
                           placeholder="Type product name (e.g. Kent Grand Plus RO, Industrial 100 LPH, Water Purifier...)" 
                           required 
                           autocomplete="off" 
                           class="form-input text-sm font-semibold pr-9 text-slate-900">
                    <input type="hidden" name="product_id" id="saleProductId" value="">
                    
                    <datalist id="saleProductList">
                        @foreach($products as $p)
                            <option value="{{ $p->name }}" data-price="{{ $p->unit_price }}" data-sku="{{ $p->sku }}" data-id="{{ $p->id }}">
                                ₹{{ number_format($p->unit_price, 2) }} (SKU: {{ $p->sku }})
                            </option>
                        @endforeach
                    </datalist>

                    <div class="absolute right-3 top-2.5 text-slate-400 pointer-events-none">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                    </div>
                </div>
                <p class="text-[11px] text-slate-500 mt-1">
                    Enter any free-text product name. The sale order will proceed directly with this product name.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Quantity <span class="text-rose-500">*</span></label>
                    <input type="number" name="quantity" id="saleQty" value="1" min="1" step="1" required class="form-input text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Unit Price (₹) <span class="text-rose-500">*</span></label>
                    <input type="number" name="unit_price" id="saleUnitPrice" value="0" min="0" step="0.01" required class="form-input text-sm font-mono font-bold">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Discount Amount (₹)</label>
                    <input type="number" name="discount_amount" id="saleDiscount" value="0" min="0" step="0.01" class="form-input text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Machine Serial Number</label>
                    <input type="text" name="serial_number" placeholder="e.g. SN-KENT-2026-889" class="form-input text-sm">
                </div>
            </div>

            <!-- Optional GST / Tax Options -->
            <div class="p-3 bg-slate-50/90 rounded-xl border border-slate-200/90 space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="hidden" name="apply_gst" value="0">
                        <input type="checkbox" 
                               name="apply_gst" 
                               id="saleApplyGst" 
                               value="1" 
                               class="w-4 h-4 rounded text-rose-600 focus:ring-rose-500 border-slate-300 cursor-pointer">
                        <span class="text-xs font-semibold text-slate-800">Apply GST (Tax)</span>
                    </label>

                    <div id="saleGstRateGroup" class="flex items-center gap-1.5 text-xs transition-opacity duration-150 opacity-40 pointer-events-none">
                        <label for="saleGstRate" class="text-slate-500 font-medium whitespace-nowrap">GST Rate:</label>
                        <select name="tax_rate" id="saleGstRate" class="form-input text-xs py-1 px-2 rounded-lg border-slate-300 font-semibold text-slate-700 bg-white">
                            <option value="18" selected>18% (Standard)</option>
                            <option value="12">12%</option>
                            <option value="5">5%</option>
                            <option value="28">28%</option>
                            <option value="custom">Custom %</option>
                        </select>
                        <input type="number" 
                               name="custom_tax_rate" 
                               id="saleCustomTaxRate" 
                               min="0" 
                               max="100" 
                               step="0.01" 
                               placeholder="%" 
                               class="hidden form-input text-xs py-1 px-2 w-16 font-mono text-slate-700 bg-white">
                    </div>
                </div>
                <div class="text-[11px] text-slate-500 flex items-center justify-between" id="saleGstHint">
                    <span>GST is 0% by default. Check "Apply GST" to add tax if required.</span>
                </div>
            </div>

            <!-- Auto Calculation preview -->
            <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200/80 space-y-1.5 text-xs">
                <div class="flex justify-between text-slate-600">
                    <span>Taxable Base:</span>
                    <span class="font-mono font-semibold" id="saleSubtotalDisp">₹0.00</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span id="saleTaxRateLabel">GST (0% - Not Applied):</span>
                    <span class="font-mono font-semibold text-slate-400" id="saleTaxDisp">₹0.00</span>
                </div>
                <div class="flex justify-between text-base font-bold text-slate-900 border-t border-slate-200 pt-1.5">
                    <span id="saleTotalLabel">Total Amount:</span>
                    <span class="font-mono text-rose-600" id="saleTotalDisp">₹0.00</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Notes / Terms</label>
                <textarea name="notes" rows="2" placeholder="Sales order remarks or special instructions..." class="form-input text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="CRM.closeModal('modalCreateSale')" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary bg-rose-600 hover:bg-rose-700 text-white flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Generate Order &amp; Invoice</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    function recalcSale() {
        const qty = parseFloat($('#saleQty').val()) || 0;
        const price = parseFloat($('#saleUnitPrice').val()) || 0;
        const disc = parseFloat($('#saleDiscount').val()) || 0;
        const applyGst = $('#saleApplyGst').is(':checked');

        let rate = 0;
        if (applyGst) {
            const selectedRate = $('#saleGstRate').val();
            if (selectedRate === 'custom') {
                rate = parseFloat($('#saleCustomTaxRate').val()) || 0;
            } else {
                rate = parseFloat(selectedRate) || 0;
            }
        }

        const subtotal = Math.max(0, (qty * price) - disc);
        const tax = (applyGst && rate > 0) ? (subtotal * rate) / 100 : 0;
        const total = subtotal + tax;

        $('#saleSubtotalDisp').text('₹' + subtotal.toFixed(2));

        if (applyGst && rate > 0) {
            $('#saleTaxRateLabel').text('GST (' + rate + '%):');
            $('#saleTaxDisp').text('₹' + tax.toFixed(2)).removeClass('text-slate-400').addClass('text-slate-600');
            $('#saleTotalLabel').text('Total Amount (with GST):');
        } else {
            $('#saleTaxRateLabel').text('GST (0% - Not Applied):');
            $('#saleTaxDisp').text('₹0.00').addClass('text-slate-400').removeClass('text-slate-600');
            $('#saleTotalLabel').text('Total Amount:');
        }

        $('#saleTotalDisp').text('₹' + total.toFixed(2));
    }

    const catalogProducts = @json($catalogProducts ?? []);

    $('#saleProductName').on('input change', function() {
        const val = ($(this).val() || '').trim().toLowerCase();
        const matched = catalogProducts.find(p => p.name.toLowerCase() === val);
        if (matched) {
            $('#saleProductId').val(matched.id);
            if (!$('#saleUnitPrice').data('user-touched') || parseFloat($('#saleUnitPrice').val()) === 0) {
                $('#saleUnitPrice').val(matched.price);
            }
        } else {
            $('#saleProductId').val('');
        }
        recalcSale();
    });

    $('#saleUnitPrice').on('input', function() {
        $(this).data('user-touched', true);
    });

    $('#saleApplyGst').on('change', function() {
        const checked = $(this).is(':checked');
        if (checked) {
            $('#saleGstRateGroup').removeClass('opacity-40 pointer-events-none').addClass('opacity-100');
            $('#saleGstHint').html('<span>GST enabled. Standard or custom tax will be added to the invoice.</span>');
        } else {
            $('#saleGstRateGroup').addClass('opacity-40 pointer-events-none').removeClass('opacity-100');
            $('#saleGstHint').html('<span class="text-amber-600 font-medium">GST disabled. Sale order &amp; invoice will be created with ₹0.00 tax.</span>');
        }
        recalcSale();
    });

    $('#saleGstRate').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#saleCustomTaxRate').removeClass('hidden').focus();
        } else {
            $('#saleCustomTaxRate').addClass('hidden');
        }
        recalcSale();
    });

    $('#saleCustomTaxRate').on('input change', function() {
        recalcSale();
    });

    $('#saleQty, #saleUnitPrice, #saleDiscount').on('input', function() {
        recalcSale();
    });

    CRM.handleAjaxForm('#formCreateSale', function(res) {
        CRM.toast(res.message || 'Sale created successfully!');
        if (res.redirect) {
            setTimeout(() => window.location.href = res.redirect, 600);
        } else {
            setTimeout(() => location.reload(), 600);
        }
    });
});
</script>
@endsection
