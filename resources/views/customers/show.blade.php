@extends('layouts.app')

@section('title', $customer->full_name . ' — Profile')

@section('content')
<div class="space-y-6">

    <!-- Top Profile Banner -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-card flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-start gap-4">
            <div class="w-14 h-14 rounded-2xl bg-[#00152B] text-white flex items-center justify-center font-extrabold text-xl shadow-md shrink-0">
                {{ strtoupper(substr($customer->full_name, 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-xl sm:text-2xl font-display font-extrabold text-slate-900 tracking-tight">{{ $customer->full_name }}</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-primary-50 text-primary-700 border border-primary-200">
                        {{ $customer->customer_number }}
                    </span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $customer->customer_type === 'COMMERCIAL' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-700' }}">
                        {{ $customer->customer_type }}
                    </span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $customer->status === 'ACTIVE' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                        {{ $customer->status }}
                    </span>
                    @if($customer->customer_label === 'GOOD')
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-200">VIP Client</span>
                    @endif
                </div>
                <div class="flex items-center gap-4 text-xs text-slate-500 mt-2 flex-wrap">
                    <span class="flex items-center gap-1 font-semibold text-slate-700">
                        <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400"></i>
                        {{ $customer->phone }}
                    </span>
                    @if($customer->email)
                    <span class="flex items-center gap-1">
                        <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400"></i>
                        {{ $customer->email }}
                    </span>
                    @endif
                    @if($customer->company_name)
                    <span class="flex items-center gap-1">
                        <i data-lucide="building" class="w-3.5 h-3.5 text-slate-400"></i>
                        {{ $customer->company_name }}
                    </span>
                    @endif
                    @if($customer->gst_number)
                    <span class="flex items-center gap-1 font-mono">
                        <i data-lucide="file-check" class="w-3.5 h-3.5 text-slate-400"></i>
                        GST: {{ $customer->gst_number }}
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Communication Actions -->
        <div class="flex items-center gap-2 self-start md:self-auto shrink-0">
            <a href="https://wa.me/91{{ preg_replace('/[^0-9]/', '', $customer->phone) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-sm transition-colors">
                <i data-lucide="message-circle" class="w-4 h-4"></i>
                <span>WhatsApp</span>
            </a>
            <a href="tel:{{ $customer->phone }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition-colors">
                <i data-lucide="phone-call" class="w-4 h-4 text-slate-500"></i>
                <span>Call</span>
            </a>
            <button type="button" onclick="openModal('edit-customer-modal')" class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition-colors">
                <i data-lucide="edit-3" class="w-4 h-4"></i>
                <span>Edit</span>
            </button>
        </div>
    </div>

    <!-- Tabbed Navigation -->
    <div class="border-b border-slate-200 flex items-center gap-4 text-xs font-bold overflow-x-auto pb-px">
        <button type="button" class="tab-btn px-4 py-2.5 border-b-2 border-primary-600 text-primary-600 transition-colors" data-target="tab-assets">
            Installed Assets ({{ $customer->assets->count() }})
        </button>
        <button type="button" class="tab-btn px-4 py-2.5 border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition-colors" data-target="tab-invoices">
            Invoices &amp; Billing ({{ $customer->invoices->count() }})
        </button>
        <button type="button" class="tab-btn px-4 py-2.5 border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition-colors" data-target="tab-services">
            Service History ({{ $customer->services->count() }})
        </button>
        <button type="button" class="tab-btn px-4 py-2.5 border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition-colors" data-target="tab-addresses">
            Addresses ({{ $customer->addresses->count() }})
        </button>
    </div>

    <!-- Tab 1: Installed Assets & Warranties -->
    <div id="tab-assets" class="tab-pane space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Customer Equipment &amp; Assets</h2>
            <button type="button" onclick="openModal('add-asset-modal')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold rounded-lg shadow-sm transition-colors">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Register Machine Asset</span>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($customer->assets as $asset)
                <div class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-card flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-bold text-primary-600">{{ $asset->asset_number }}</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $asset->status === 'ACTIVE' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                {{ $asset->status }}
                            </span>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 mt-1.5">{{ $asset->custom_name ?? ($asset->product->name ?? 'Equipment / Asset') }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5 font-mono">Serial #: {{ $asset->serial_number ?? 'Not Serialized' }}</p>

                        <!-- Warranty Status -->
                        @php $war = $asset->activeWarranty; @endphp
                        <div class="mt-3 p-2.5 rounded-lg {{ $war ? 'bg-emerald-50/70 border border-emerald-200/60 text-emerald-800' : 'bg-slate-50 text-slate-600' }} text-xs">
                            <div class="flex items-center justify-between font-semibold">
                                <span>Warranty Status:</span>
                                <span>{{ $war ? 'ACTIVE (' . $war->warranty_number . ')' : 'NO ACTIVE WARRANTY' }}</span>
                            </div>
                            @if($war)
                                <span class="text-[10px] text-emerald-700 block mt-0.5">Valid until {{ $war->end_date->format('d M, Y') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-400">Purchased: {{ $asset->purchase_date->format('d M, Y') }}</span>
                        <a href="{{ route('services.index') }}?asset_id={{ $asset->id }}&action=schedule" class="text-primary-600 hover:underline font-bold">+ Schedule Service</a>
                    </div>
                </div>
            @empty
                <div class="col-span-2 p-10 bg-white rounded-xl border border-slate-200 text-center text-slate-400">
                    <i data-lucide="package" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                    <span>No equipment registered for this customer yet. Click "Register Machine Asset" above.</span>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Tab 2: Invoices & Billing History -->
    <div id="tab-invoices" class="tab-pane hidden space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Invoices &amp; Tax Billing Records</h2>
            <a href="{{ route('sales.index') }}?customer_id={{ $customer->id }}&action=create" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold rounded-lg shadow-sm transition-colors">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Create New Sale / Invoice</span>
            </a>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-card overflow-hidden">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="p-3.5">Invoice #</th>
                        <th class="p-3.5">Date</th>
                        <th class="p-3.5">Due Date</th>
                        <th class="p-3.5">Total Amount</th>
                        <th class="p-3.5">Paid</th>
                        <th class="p-3.5">Balance</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5 text-right">View</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($customer->invoices as $inv)
                        <tr class="hover:bg-slate-50">
                            <td class="p-3.5 font-bold font-mono text-primary-600">{{ $inv->invoice_number }}</td>
                            <td class="p-3.5 text-slate-600">{{ $inv->invoice_date->format('d M, Y') }}</td>
                            <td class="p-3.5 text-slate-600">{{ $inv->due_date->format('d M, Y') }}</td>
                            <td class="p-3.5 font-bold text-slate-900">₹{{ number_format($inv->total_amount, 2) }}</td>
                            <td class="p-3.5 text-emerald-600">₹{{ number_format($inv->paid_amount, 2) }}</td>
                            <td class="p-3.5 font-bold text-rose-600">₹{{ number_format($inv->balance_amount, 2) }}</td>
                            <td class="p-3.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $inv->status === 'PAID' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $inv->status }}
                                </span>
                            </td>
                            <td class="p-3.5 text-right">
                                <a href="{{ route('invoices.show', $inv->id) }}" class="text-primary-600 hover:underline font-bold">Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">No invoices generated for this customer.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab 3: Service History -->
    <div id="tab-services" class="tab-pane hidden space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Service &amp; Maintenance Logs</h2>
            <a href="{{ route('services.index') }}?customer_id={{ $customer->id }}&action=create" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold rounded-lg shadow-sm transition-colors">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Schedule Service</span>
            </a>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-card overflow-hidden">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="p-3.5">Service #</th>
                        <th class="p-3.5">Date</th>
                        <th class="p-3.5">Type</th>
                        <th class="p-3.5">Classification</th>
                        <th class="p-3.5">Technician</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5 text-right">Job Card</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($customer->services as $srv)
                        <tr class="hover:bg-slate-50">
                            <td class="p-3.5 font-bold font-mono text-primary-600">{{ $srv->service_number }}</td>
                            <td class="p-3.5 text-slate-700">{{ $srv->scheduled_date->format('d M, Y') }}</td>
                            <td class="p-3.5 text-slate-900">{{ str_replace('_', ' ', $srv->service_type) }}</td>
                            <td class="p-3.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $srv->service_classification === 'WARRANTY' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $srv->service_classification }}
                                </span>
                            </td>
                            <td class="p-3.5 text-teal-700 font-semibold">{{ $srv->technician->full_name ?? 'Unassigned' }}</td>
                            <td class="p-3.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $srv->status === 'COMPLETED' ? 'bg-emerald-100 text-emerald-800' : 'bg-sky-100 text-sky-800' }}">
                                    {{ $srv->status }}
                                </span>
                            </td>
                            <td class="p-3.5 text-right">
                                <a href="{{ route('services.show', $srv->id) }}" class="text-primary-600 hover:underline font-bold">View Card</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">No service requests recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab 4: Addresses -->
    <div id="tab-addresses" class="tab-pane hidden space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Registered Delivery &amp; Service Locations</h2>
            <button type="button" onclick="openModal('add-address-modal')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold rounded-lg shadow-sm transition-colors">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Add Additional Address</span>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($customer->addresses as $addr)
                <div class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-card">
                    <div class="flex items-center justify-between">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                            {{ $addr->address_type }} ADDRESS
                        </span>
                        @if($addr->is_default)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-primary-50 text-primary-700 border border-primary-200">
                                PRIMARY
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-800 font-semibold mt-2 leading-relaxed">
                        {{ $addr->address_line1 }}<br>
                        @if($addr->address_line2) {{ $addr->address_line2 }}<br> @endif
                        @if($addr->landmark) Landmark: {{ $addr->landmark }}<br> @endif
                        {{ $addr->city }}, {{ $addr->state }} - {{ $addr->postal_code }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>

</div>

<!-- Modal: Add Asset / Machine -->
<div id="add-asset-modal" class="hidden fixed inset-0 z-50 modal-backdrop items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-modal border border-slate-200 max-w-lg w-full p-5 fade-in">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <h3 class="font-extrabold text-sm text-slate-900 uppercase tracking-wider">Register Equipment Asset</h3>
            <button type="button" onclick="closeModal('add-asset-modal')" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form action="{{ route('assets.store') }}" method="POST" data-ajax="true" class="space-y-3.5">
            @csrf
            <input type="hidden" name="customer_id" value="{{ $customer->id }}">
            
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Catalog Product / Model *</label>
                <select name="product_id" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs">
                    @foreach(\App\Models\Product::where('is_active', true)->get() as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Machine Custom Name / Label</label>
                <input type="text" name="custom_name" placeholder="e.g. Cafeteria 50 LPH Plant" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Serial Number (Chassis / Unit)</label>
                <input type="text" name="serial_number" placeholder="e.g. SN-2026-9921" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Installation Date *</label>
                    <input type="date" name="purchase_date" required value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Warranty Period (Months)</label>
                    <input type="number" name="initial_warranty_months" value="12" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeModal('add-asset-modal')" class="px-4 py-2 border border-slate-200 rounded-lg text-xs font-bold">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-bold">Save Asset</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Tab Switching
    $('.tab-btn').on('click', function() {
        const target = $(this).data('target');
        $('.tab-btn').removeClass('border-primary-600 text-primary-600').addClass('border-transparent text-slate-500');
        $(this).addClass('border-primary-600 text-primary-600').removeClass('border-transparent text-slate-500');
        $('.tab-pane').addClass('hidden');
        $('#' + target).removeClass('hidden');
    });
});
</script>
@endsection
