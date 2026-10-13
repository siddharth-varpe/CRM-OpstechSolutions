@extends('layouts.app')

@section('title', 'Inventory & Spares - Enterprises CRM')

@section('content')
<div class="space-y-6 max-w-[1600px] mx-auto pb-12 select-none">
    <!-- 1. Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center shadow-xs shrink-0">
                <i data-lucide="boxes" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Inventory &amp; Spares</h1>
                <p class="text-xs sm:text-sm text-slate-500 font-normal">
                    Manage stock balances, vendor procurements, spare parts and reorder points
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button onclick="CRM.openModal('modalRecordPurchase')" class="btn-outline flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-4 h-4 text-slate-500"></i>
                <span>Purchase Stock</span>
            </button>
            <button onclick="CRM.openModal('modalRecordSale')" class="btn-outline flex items-center gap-2">
                <i data-lucide="arrow-up-right" class="w-4 h-4 text-slate-500"></i>
                <span>Dispatch / Sell</span>
            </button>
            <button onclick="CRM.openModal('modalCreateItem')" class="btn-primary flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Add Item</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total SKUs</span>
                <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center">
                    <i data-lucide="package" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-slate-900 font-mono">
                {{ $totalItemsCount }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Catalogued items &amp; components</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Stock Valuation</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="badge-dollar-sign" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-emerald-600 font-mono">
                ₹{{ number_format($totalStockValue, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Current cost valuation in warehouse</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Low Stock Alerts</span>
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-rose-600 font-mono">
                {{ $lowStockCount }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Items below minimum reorder point</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Purchases Inflow</span>
                <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                    <i data-lucide="truck" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-sky-600 font-mono">
                ₹{{ number_format($totalPurchasesValue, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Total vendor purchase bookings</div>
        </div>
    </div>

    <!-- 3. Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
        <a href="{{ route('inventory.index', ['tab' => 'items']) }}"
           class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition {{ $tab === 'items' ? 'bg-teal-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
            <i data-lucide="boxes" class="w-4 h-4"></i>
            <span>Stock Items</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'purchases']) }}"
           class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition {{ $tab === 'purchases' ? 'bg-teal-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
            <span>Purchases Ledger</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'sales']) }}"
           class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition {{ $tab === 'sales' ? 'bg-teal-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
            <i data-lucide="truck" class="w-4 h-4"></i>
            <span>Dispatches &amp; Sales</span>
        </a>
    </div>

    <!-- 4. Tab Contents -->
    @if($tab === 'items')
        <!-- Items Filter Bar -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs">
            <form method="GET" action="{{ route('inventory.index') }}" class="flex flex-col md:flex-row gap-3 items-center justify-between">
                <input type="hidden" name="tab" value="items">
                <div class="relative w-full md:w-96">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search item name, brand, part #, category..."
                           class="form-input pl-10">
                </div>

                <div class="flex items-center gap-2.5 w-full md:w-auto">
                    <select name="category" onchange="this.form.submit()" class="form-input text-xs w-full md:w-44">
                        <option value="ALL">All Categories</option>
                        <option value="Filter" {{ request('category') === 'Filter' ? 'selected' : '' }}>Filter</option>
                        <option value="Membrane" {{ request('category') === 'Membrane' ? 'selected' : '' }}>Membrane</option>
                        <option value="Pump" {{ request('category') === 'Pump' ? 'selected' : '' }}>Pump</option>
                        <option value="SMPS" {{ request('category') === 'SMPS' ? 'selected' : '' }}>SMPS</option>
                        <option value="Fitting" {{ request('category') === 'Fitting' ? 'selected' : '' }}>Fitting</option>
                        <option value="Tubing" {{ request('category') === 'Tubing' ? 'selected' : '' }}>Tubing</option>
                        <option value="Accessories" {{ request('category') === 'Accessories' ? 'selected' : '' }}>Accessories</option>
                        <option value="Other" {{ request('category') === 'Other' ? 'selected' : '' }}>Other</option>
                    </select>

                    @if(request()->hasAny(['search', 'category']))
                        <a href="{{ route('inventory.index', ['tab' => 'items']) }}" class="btn-outline text-xs whitespace-nowrap">
                            Reset Filters
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Stock Items Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Item Name</th>
                            <th class="py-3.5 px-4">Category</th>
                            <th class="py-3.5 px-4">Part # / Brand</th>
                            <th class="py-3.5 px-4 text-center">Current Stock</th>
                            <th class="py-3.5 px-4 text-right">Cost Price</th>
                            <th class="py-3.5 px-4 text-right">Selling Price</th>
                            <th class="py-3.5 px-4 text-right">Stock Value</th>
                            <th class="py-3.5 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($items as $it)
                            @php $isLow = $it->isLowStock(); @endphp
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900">{{ $it->name }}</div>
                                    @if($it->description)
                                        <div class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ $it->description }}</div>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ $it->category }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-xs font-mono text-slate-600">
                                    {{ $it->part_number ?? '—' }}
                                    @if($it->brand) • <span class="font-sans font-semibold text-slate-700">{{ $it->brand }}</span>@endif
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <div class="inline-flex items-center gap-1.5 font-mono font-bold text-sm {{ $isLow ? 'text-rose-600' : 'text-slate-800' }}">
                                        {{ $it->current_stock }}
                                        @if($isLow)
                                            <span class="text-[10px] bg-rose-50 border border-rose-200 text-rose-700 px-1.5 py-0.2 rounded font-sans font-bold uppercase">
                                                Low
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-mono">Min: {{ $it->min_stock_level }}</div>
                                </td>

                                <td class="py-3.5 px-4 text-right font-mono text-xs">
                                    ₹{{ number_format($it->purchase_price, 2) }}
                                </td>

                                <td class="py-3.5 px-4 text-right font-mono text-xs font-semibold text-slate-900">
                                    ₹{{ number_format($it->selling_price, 2) }}
                                </td>

                                <td class="py-3.5 px-4 text-right font-mono text-xs font-bold text-emerald-700">
                                    ₹{{ number_format($it->current_stock * $it->purchase_price, 2) }}
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <button onclick="quickDispatchItem('{{ $it->id }}', '{{ addslashes($it->name) }}', '{{ $it->selling_price }}', '{{ $it->current_stock }}')"
                                            class="btn-outline py-1 px-2.5 text-xs inline-flex items-center gap-1 text-teal-700 border-teal-300 hover:bg-teal-50">
                                        <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                                        <span>Dispatch</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i data-lucide="boxes" class="w-10 h-10 text-slate-300 mb-2"></i>
                                        <p class="font-medium">No inventory items found</p>
                                        <p class="text-xs text-slate-400 mt-1">Add items to maintain catalogued spare parts and stock balances.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($items->hasPages())
                <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    @elseif($tab === 'purchases')
        <!-- Purchases Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Purchase #</th>
                            <th class="py-3.5 px-4">Item Name</th>
                            <th class="py-3.5 px-4">Supplier / Vendor</th>
                            <th class="py-3.5 px-4 text-center">Quantity</th>
                            <th class="py-3.5 px-4 text-right">Unit Cost</th>
                            <th class="py-3.5 px-4 text-right">Total Amount (₹)</th>
                            <th class="py-3.5 px-4">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($purchases as $p)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ $p->purchase_number }}</td>
                                <td class="py-3.5 px-4 font-semibold text-slate-800">{{ $p->item->name ?? '—' }}</td>
                                <td class="py-3.5 px-4 text-slate-600">{{ $p->supplier_name }}</td>
                                <td class="py-3.5 px-4 text-center font-mono font-bold">{{ $p->quantity }}</td>
                                <td class="py-3.5 px-4 text-right font-mono text-xs">₹{{ number_format($p->purchase_price_per_unit, 2) }}</td>
                                <td class="py-3.5 px-4 text-right font-mono text-xs font-bold text-slate-900">₹{{ number_format($p->total_amount, 2) }}</td>
                                <td class="py-3.5 px-4 text-xs font-mono text-slate-500">{{ \Carbon\Carbon::parse($p->purchase_date)->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">No purchase records registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($purchases->hasPages())
                <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                    {{ $purchases->links() }}
                </div>
            @endif
        </div>
    @elseif($tab === 'sales')
        <!-- Sales Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Dispatch #</th>
                            <th class="py-3.5 px-4">Item Name</th>
                            <th class="py-3.5 px-4">Customer</th>
                            <th class="py-3.5 px-4 text-center">Qty</th>
                            <th class="py-3.5 px-4 text-right">Selling Price</th>
                            <th class="py-3.5 px-4 text-right">Total Revenue</th>
                            <th class="py-3.5 px-4 text-right">Margin / Profit</th>
                            <th class="py-3.5 px-4">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($sales as $s)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ $s->sale_number }}</td>
                                <td class="py-3.5 px-4 font-semibold text-slate-800">{{ $s->item->name ?? '—' }}</td>
                                <td class="py-3.5 px-4 text-slate-600">{{ $s->customer_name }}</td>
                                <td class="py-3.5 px-4 text-center font-mono font-bold">{{ $s->quantity }}</td>
                                <td class="py-3.5 px-4 text-right font-mono text-xs">₹{{ number_format($s->selling_price_per_unit, 2) }}</td>
                                <td class="py-3.5 px-4 text-right font-mono text-xs font-bold text-slate-900">₹{{ number_format($s->total_sale_amount, 2) }}</td>
                                <td class="py-3.5 px-4 text-right font-mono text-xs font-bold text-emerald-700">₹{{ number_format($s->profit, 2) }}</td>
                                <td class="py-3.5 px-4 text-xs font-mono text-slate-500">{{ \Carbon\Carbon::parse($s->sale_date)->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">No dispatches recorded yet.</td>
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
    @endif
</div>

<!-- Modal: Add New Inventory Item -->
<div id="modalCreateItem" class="modal-backdrop hidden">
    <div class="modal-box max-w-lg">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 text-lg flex items-center gap-2">
                <i data-lucide="boxes" class="w-5 h-5 text-teal-600"></i>
                <span>Add Inventory SKU</span>
            </h3>
            <button onclick="CRM.closeModal('modalCreateItem')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="formCreateItem" action="{{ route('inventory.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Item Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required placeholder="e.g. Industrial Valve / Spare Part 5 Micron" class="form-input text-sm">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Category <span class="text-rose-500">*</span></label>
                    <select name="category" required class="form-input text-xs">
                        <option value="Filter">Filter</option>
                        <option value="Membrane">Membrane</option>
                        <option value="Pump">Pump</option>
                        <option value="SMPS">SMPS</option>
                        <option value="Fitting">Fitting</option>
                        <option value="Tubing">Tubing</option>
                        <option value="Accessories">Accessories</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Brand</label>
                    <input type="text" name="brand" placeholder="e.g. Kent, Aquaguard" class="form-input text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Part / Model Number</label>
                    <input type="text" name="part_number" placeholder="e.g. PN-SED-05" class="form-input text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Initial Stock <span class="text-rose-500">*</span></label>
                    <input type="number" name="current_stock" value="0" min="0" required class="form-input text-xs font-mono">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Purchase Cost (₹) <span class="text-rose-500">*</span></label>
                    <input type="number" name="purchase_price" value="0" min="0" step="0.01" required class="form-input text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Selling Price (₹) <span class="text-rose-500">*</span></label>
                    <input type="number" name="selling_price" value="0" min="0" step="0.01" required class="form-input text-xs font-mono font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Min Alert Level <span class="text-rose-500">*</span></label>
                    <input type="number" name="min_stock_level" value="5" min="0" required class="form-input text-xs font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="2" placeholder="Specifications, warranty or replacement guidelines..." class="form-input text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="CRM.closeModal('modalCreateItem')" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Save Item</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Record Purchase -->
<div id="modalRecordPurchase" class="modal-backdrop hidden">
    <div class="modal-box max-w-lg">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 text-lg flex items-center gap-2">
                <i data-lucide="truck" class="w-5 h-5 text-sky-600"></i>
                <span>Record Stock Purchase</span>
            </h3>
            <button onclick="CRM.closeModal('modalRecordPurchase')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="formRecordPurchase" action="{{ route('inventory.purchases') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Inventory Item <span class="text-rose-500">*</span></label>
                <select name="item_id" required class="form-input text-sm">
                    <option value="">Select Item</option>
                    @foreach($items as $it)
                        <option value="{{ $it->id }}">{{ $it->name }} (Current: {{ $it->current_stock }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="supplier_name" required placeholder="e.g. Apex Water Technologies" class="form-input text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Purchase Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="purchase_date" value="{{ date('Y-m-d') }}" required class="form-input text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Quantity Received <span class="text-rose-500">*</span></label>
                    <input type="number" name="quantity" min="1" value="1" required class="form-input text-sm font-mono font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Unit Cost (₹) <span class="text-rose-500">*</span></label>
                    <input type="number" name="purchase_price_per_unit" min="0" step="0.01" required class="form-input text-sm font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Notes / Bill Reference</label>
                <textarea name="notes" rows="2" placeholder="Vendor invoice number or delivery note ref..." class="form-input text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="CRM.closeModal('modalRecordPurchase')" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Add to Stock</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Record Dispatch / Sale -->
<div id="modalRecordSale" class="modal-backdrop hidden">
    <div class="modal-box max-w-lg">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 text-lg flex items-center gap-2">
                <i data-lucide="arrow-up-right" class="w-5 h-5 text-teal-600"></i>
                <span>Dispatch / Sell Stock</span>
            </h3>
            <button onclick="CRM.closeModal('modalRecordSale')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="formRecordSale" action="{{ route('inventory.sales') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Select Item <span class="text-rose-500">*</span></label>
                <select name="item_id" id="dispatchItemSelect" required class="form-input text-sm">
                    <option value="">Select Item</option>
                    @foreach($items as $it)
                        <option value="{{ $it->id }}" data-price="{{ $it->selling_price }}" data-stock="{{ $it->current_stock }}">
                            {{ $it->name }} (Available: {{ $it->current_stock }}) - ₹{{ number_format($it->selling_price, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Customer (Optional)</label>
                    <select name="customer_id" class="form-input text-xs">
                        <option value="">Walk-in Customer</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Dispatch Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="sale_date" value="{{ date('Y-m-d') }}" required class="form-input text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Quantity Dispatched <span class="text-rose-500">*</span></label>
                    <input type="number" name="quantity" id="dispatchQty" min="1" value="1" required class="form-input text-sm font-mono font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Selling Rate (₹) <span class="text-rose-500">*</span></label>
                    <input type="number" name="selling_price_per_unit" id="dispatchPrice" min="0" step="0.01" required class="form-input text-sm font-mono font-bold">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Remarks</label>
                <textarea name="notes" rows="2" placeholder="Job card ref, technician who picked the spare, etc..." class="form-input text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="CRM.closeModal('modalRecordSale')" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Confirm Dispatch</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function quickDispatchItem(itemId, itemName, sellPrice, stock) {
    $('#dispatchItemSelect').val(itemId);
    $('#dispatchPrice').val(sellPrice);
    $('#dispatchQty').attr('max', stock).val(1);
    CRM.openModal('modalRecordSale');
}

$(document).ready(function() {
    $('#dispatchItemSelect').on('change', function() {
        const price = $(this).find(':selected').data('price') || 0;
        const stock = $(this).find(':selected').data('stock') || 1;
        $('#dispatchPrice').val(price);
        $('#dispatchQty').attr('max', stock);
    });

    CRM.handleAjaxForm('#formCreateItem', function(res) {
        CRM.toast(res.message || 'Item created successfully!');
        setTimeout(() => location.reload(), 600);
    });

    CRM.handleAjaxForm('#formRecordPurchase', function(res) {
        CRM.toast(res.message || 'Purchase recorded successfully!');
        setTimeout(() => location.reload(), 600);
    });

    CRM.handleAjaxForm('#formRecordSale', function(res) {
        CRM.toast(res.message || 'Stock dispatched successfully!');
        setTimeout(() => location.reload(), 600);
    });
});
</script>
@endsection
