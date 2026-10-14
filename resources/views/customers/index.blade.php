@extends('layouts.app')

@section('title', 'Customers Directory')

@section('content')
<div class="space-y-5">

    <!-- Top Header Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-display font-extrabold text-slate-900 tracking-tight">Customer Directory</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Commercial facilities, water treatment plants, and domestic customer profiles.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('customers.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold rounded-lg shadow-2xs transition-colors">
                <i data-lucide="download" class="w-4 h-4 text-slate-500"></i>
                <span>Export CSV</span>
            </a>
            <button type="button" onclick="openCustomerImportModal()" class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold rounded-lg shadow-2xs transition-colors cursor-pointer">
                <i data-lucide="upload" class="w-4 h-4 text-slate-500"></i>
                <span>Import Customers</span>
            </button>
            <button type="button" onclick="openAddCustomerModal()" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold rounded-lg shadow-md transition-colors cursor-pointer">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>+ Add Customer</span>
            </button>
        </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
        <div class="bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Directory</span>
            <div class="text-xl font-extrabold text-slate-900 mt-1">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Active Accounts</span>
            <div class="text-xl font-extrabold text-slate-900 mt-1">{{ $stats['active'] }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-sky-600">Commercial Clients</span>
            <div class="text-xl font-extrabold text-slate-900 mt-1">{{ $stats['commercial'] }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Residential Accounts</span>
            <div class="text-xl font-extrabold text-slate-900 mt-1">{{ $stats['individual'] }}</div>
        </div>
    </div>

    <!-- Filter and Search Toolbar -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-2xs flex flex-col md:flex-row gap-3 items-center justify-between">
        <form action="{{ route('customers.index') }}" method="GET" class="w-full flex flex-col sm:flex-row gap-2.5 items-center">
            <!-- Search Input -->
            <div class="relative w-full sm:w-72">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, phone, GST, ID..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            <!-- Customer Type Filter -->
            <select name="type" onchange="this.form.submit()" class="w-full sm:w-auto px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="ALL" {{ $type === 'ALL' ? 'selected' : '' }}>All Client Types</option>
                <option value="COMMERCIAL" {{ $type === 'COMMERCIAL' ? 'selected' : '' }}>Commercial Clients</option>
                <option value="INDIVIDUAL" {{ $type === 'INDIVIDUAL' ? 'selected' : '' }}>Individual Customers</option>
            </select>

            <!-- Status Filter -->
            <select name="status" onchange="this.form.submit()" class="w-full sm:w-auto px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="ALL" {{ $status === 'ALL' ? 'selected' : '' }}>All Statuses</option>
                <option value="ACTIVE" {{ $status === 'ACTIVE' ? 'selected' : '' }}>Active Only</option>
                <option value="INACTIVE" {{ $status === 'INACTIVE' ? 'selected' : '' }}>Inactive Accounts</option>
            </select>

            <!-- Label Filter -->
            <select name="label" onchange="this.form.submit()" class="w-full sm:w-auto px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="ALL" {{ $label === 'ALL' ? 'selected' : '' }}>All Labels</option>
                <option value="GOOD" {{ $label === 'GOOD' ? 'selected' : '' }}>Good Label</option>
                <option value="BAD" {{ $label === 'BAD' ? 'selected' : '' }}>Risk / Bad Label</option>
            </select>

            @if($search || $type !== 'ALL' || $status !== 'ALL' || $label !== 'ALL')
                <a href="{{ route('customers.index') }}" class="text-xs text-rose-600 hover:underline px-2 py-1">Reset</a>
            @endif
        </form>
    </div>

    <!-- Customers Table -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">
                        <th class="p-3.5">Customer ID</th>
                        <th class="p-3.5">Customer &amp; Company</th>
                        <th class="p-3.5">Phone &amp; Email</th>
                        <th class="p-3.5">Type</th>
                        <th class="p-3.5">City &amp; Address</th>
                        <th class="p-3.5">Assets</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($customers as $c)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="p-3.5 font-bold font-mono text-primary-600">
                            <a href="{{ route('customers.show', $c->id) }}" class="hover:underline">{{ $c->customer_number }}</a>
                        </td>
                        <td class="p-3.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-700 font-extrabold flex items-center justify-center shrink-0 text-xs">
                                    {{ strtoupper(substr($c->full_name, 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('customers.show', $c->id) }}" class="font-bold text-slate-900 hover:text-primary-600 block truncate">
                                        {{ $c->full_name }}
                                    </a>
                                    @if($c->company_name)
                                        <span class="text-[11px] text-slate-500 block truncate">{{ $c->company_name }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="p-3.5">
                            <span class="text-slate-900 block font-semibold">{{ $c->phone }}</span>
                            <span class="text-[11px] text-slate-500 block truncate">{{ $c->email ?? '—' }}</span>
                        </td>
                        <td class="p-3.5">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $c->customer_type === 'COMMERCIAL' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-700' }}">
                                {{ $c->customer_type }}
                            </span>
                        </td>
                        <td class="p-3.5">
                            @php $addr = $c->primaryAddress; @endphp
                            <span class="text-slate-800 block">{{ $addr ? $addr->city : '—' }}</span>
                            <span class="text-[11px] text-slate-400 block truncate max-w-[150px]">{{ $addr ? $addr->address_line1 : '' }}</span>
                        </td>
                        <td class="p-3.5">
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold text-[10px]">
                                {{ $c->assets->count() }} unit(s)
                            </span>
                        </td>
                        <td class="p-3.5">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $c->status === 'ACTIVE' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                {{ $c->status }}
                            </span>
                        </td>
                        <td class="p-3.5 text-right">
                            <a href="{{ route('customers.show', $c->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 rounded-lg text-xs font-semibold shadow-2xs transition-colors">
                                <span>Profile</span>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-10 text-center text-slate-400">
                            <i data-lucide="users" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                            <span class="block font-semibold">No customer records matching criteria.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($customers->hasPages())
        <div class="p-3.5 border-t border-slate-200 bg-slate-50">
            {{ $customers->links() }}
        </div>
        @endif
    </div>

</div>

<!-- Add Customer Modal Dialog -->
<div id="add-customer-modal" class="hidden fixed inset-0 z-50 modal-backdrop items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-modal border border-slate-200 max-w-xl w-full max-h-[90vh] overflow-y-auto fade-in">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
            <div class="flex items-center gap-2">
                <i data-lucide="user-plus" class="w-5 h-5 text-primary-600"></i>
                <h3 class="font-display font-extrabold text-sm text-slate-900 uppercase tracking-wider">New Customer Registration</h3>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-600" onclick="closeModal('add-customer-modal')">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form action="{{ route('customers.store') }}" method="POST" data-ajax="true" class="p-5 space-y-4">
            @csrf

            <!-- Client Type -->
            <div class="grid grid-cols-2 gap-3">
                <label class="flex items-center gap-2 p-3 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                    <input type="radio" name="customer_type" value="INDIVIDUAL" checked onchange="$('#commercial-fields').addClass('hidden')">
                    <span class="text-xs font-bold text-slate-800">Domestic / Individual</span>
                </label>
                <label class="flex items-center gap-2 p-3 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                    <input type="radio" name="customer_type" value="COMMERCIAL" onchange="$('#commercial-fields').removeClass('hidden')">
                    <span class="text-xs font-bold text-slate-800">Commercial / Corporate</span>
                </label>
            </div>

            <!-- Commercial Fields -->
            <div id="commercial-fields" class="hidden p-3.5 bg-slate-50 rounded-xl space-y-3 border border-slate-200">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Company / Facility Name</label>
                    <input type="text" name="company_name" placeholder="e.g. Sahyadri Speciality Hospital" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-primary-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">GSTIN Number (Optional)</label>
                    <input type="text" name="gst_number" placeholder="27AAACS1234F1Z8" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs uppercase font-mono focus:ring-2 focus:ring-primary-500 focus:outline-none">
                </div>
            </div>

            <!-- Full Name & Phone -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Contact Person / Full Name *</label>
                    <input type="text" name="full_name" required placeholder="Full Name" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:ring-2 focus:ring-primary-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Mobile Phone Number *</label>
                    <input type="tel" name="phone" required placeholder="10-digit mobile number" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:ring-2 focus:ring-primary-500 focus:outline-none">
                </div>
            </div>

            <!-- Email & Label -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email Address (Optional)</label>
                    <input type="email" name="email" placeholder="customer@example.com" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:ring-2 focus:ring-primary-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Customer Label</label>
                    <select name="customer_label" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:ring-2 focus:ring-primary-500 focus:outline-none">
                        <option value="GOOD">Good / VIP</option>
                        <option value="BAD">High Risk / Followup</option>
                    </select>
                </div>
            </div>

            <!-- Status Hidden Input -->
            <input type="hidden" name="status" value="ACTIVE">

            <!-- Primary Address Header -->
            <div class="pt-2 border-t border-slate-100">
                <span class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">Installation &amp; Service Address</span>
                <div class="space-y-2.5">
                    <div>
                        <input type="text" name="address_line1" required placeholder="Flat / Shop No., Building Name, Street Address *" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-2.5">
                        <input type="text" name="address_line2" placeholder="Area / Colony" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:ring-2 focus:ring-primary-500 focus:outline-none">
                        <input type="text" name="landmark" placeholder="Nearest Landmark" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    </div>
                    <div class="grid grid-cols-3 gap-2.5">
                        <input type="text" name="city" required value="Pune" placeholder="City *" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:ring-2 focus:ring-primary-500 focus:outline-none">
                        <input type="text" name="state" required value="Maharashtra" placeholder="State *" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:ring-2 focus:ring-primary-500 focus:outline-none">
                        <input type="text" name="postal_code" required placeholder="Pincode *" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Precision Destination Coordinates Map Picker -->
            @include('customers.partials.location_picker', ['prefix' => 'customer_create'])

            <!-- Notes -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Operational Notes (Optional)</label>
                <textarea name="notes" rows="2" placeholder="Key water TDS details, inlet source, key contact timings..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:ring-2 focus:ring-primary-500 focus:outline-none"></textarea>
            </div>

            <!-- Submit Button -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" class="px-4 py-2 border border-slate-200 text-slate-700 rounded-lg text-xs font-bold hover:bg-slate-50" onclick="closeModal('add-customer-modal')">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-bold shadow-md transition-colors">
                    Save Customer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Import Customers Modal Dialog -->
<div id="import-customer-modal" class="hidden fixed inset-0 z-50 modal-backdrop items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-modal border border-slate-200 max-w-2xl w-full max-h-[92vh] overflow-y-auto fade-in flex flex-col">
        <!-- Modal Header -->
        <div class="p-4 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-sky-50 border border-sky-200 text-sky-700 flex items-center justify-center">
                    <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="font-display font-extrabold text-sm text-slate-900 uppercase tracking-wider">Import Customers</h3>
                    <p class="text-[11px] text-slate-500">Upload Excel or CSV file with Name, Number, and Address columns</p>
                </div>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-600 transition p-1 rounded-lg hover:bg-slate-100" onclick="closeModal('import-customer-modal')">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="p-5 flex-1 space-y-4">
            <!-- Step 1: Upload & File Selection Area -->
            <div id="importStepUpload" class="space-y-4">
                <!-- Dropzone Area -->
                <div id="importDropZone" class="relative overflow-hidden border-2 border-dashed border-slate-300 hover:border-sky-500 rounded-2xl p-6 text-center bg-slate-50/70 hover:bg-sky-50/40 transition-all cursor-pointer group">
                    <!-- Native transparent file input covering the entire dropzone -->
                    <input type="file" 
                           id="inputImportFile" 
                           name="file" 
                           accept=".csv,.xlsx,.xls,.txt" 
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
                           title="Click to browse file or drag & drop here">
                    
                    <!-- Decorative inner content with pointer-events-none so it never intercepts clicks or drag & drop -->
                    <div class="pointer-events-none select-none">
                        <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 shadow-2xs flex items-center justify-center mx-auto mb-3 text-sky-600 group-hover:scale-105 group-hover:border-sky-200 transition-all">
                            <i data-lucide="file-spreadsheet" class="w-6 h-6"></i>
                        </div>
                        <div class="text-xs font-bold text-slate-800">
                            <span class="text-sky-600 underline decoration-sky-400 font-semibold">Click to browse file</span> or drag & drop here
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Accepts Microsoft Excel (.xlsx, .xls) and Comma-Separated Values (.csv)</p>
                    </div>
                    
                    <!-- Selected File Pill (z-20 so it sits above the file input and buttons work) -->
                    <div id="selectedFileInfo" class="hidden relative z-20 mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-sky-100 text-sky-800 text-xs font-semibold shadow-xs">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5 text-sky-600"></i>
                        <span id="selectedFileName">file.csv</span>
                        <span id="selectedFileSize" class="text-[10px] text-sky-600 font-mono">(0 KB)</span>
                        <button type="button" id="btnRemoveSelectedFile" class="ml-1 text-sky-700 hover:text-rose-600 p-0.5 rounded-full hover:bg-sky-200 transition" title="Remove file">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>

                <!-- Guidance & Column Spec Info Box -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/90 text-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-800 flex items-center gap-1.5">
                            <i data-lucide="info" class="w-4 h-4 text-sky-600"></i>
                            Column Format Rules
                        </span>
                        <a href="{{ route('customers.import.sample') }}" class="text-[11px] font-bold text-sky-600 hover:text-sky-700 hover:underline inline-flex items-center gap-1">
                            <i data-lucide="download" class="w-3 h-3"></i>
                            <span>Download Sample CSV</span>
                        </a>
                    </div>
                    <ul class="text-[11px] text-slate-600 space-y-1 list-disc list-inside">
                        <li>File must contain columns for <strong>name</strong>, <strong>number</strong>, and <strong>address</strong> (or match these header names).</li>
                        <li><strong>Missing fields:</strong> If any row is missing a name, number, or address, it will be safely kept empty while importing.</li>
                        <li>An analysis report will be shown for your review before final database insertion.</li>
                    </ul>
                </div>

                <!-- Error Container -->
                <div id="importUploadError" class="hidden p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 font-medium flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                    <span id="importUploadErrorText">Error message</span>
                </div>

                <!-- Action Button -->
                <div class="pt-2 flex items-center justify-end gap-2.5">
                    <button type="button" class="px-4 py-2 border border-slate-200 text-slate-700 rounded-lg text-xs font-bold hover:bg-slate-50" onclick="closeModal('import-customer-modal')">Cancel</button>
                    <button type="button" id="btnAnalyzeFile" disabled class="px-5 py-2 bg-slate-300 text-white rounded-lg text-xs font-bold shadow-sm transition-all flex items-center gap-1.5 cursor-not-allowed">
                        <i data-lucide="activity" class="w-4 h-4"></i>
                        <span id="btnAnalyzeFileText">Analyze File</span>
                    </button>
                </div>
            </div>

            <!-- Step 2: Pre-Import Analysis Report -->
            <div id="importStepReport" class="hidden space-y-4">
                <!-- Summary Metrics Grid -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-center">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Rows</span>
                        <div id="repTotalRows" class="text-lg font-extrabold text-slate-900 mt-0.5">0</div>
                    </div>
                    <div class="p-3 bg-emerald-50/70 border border-emerald-200 rounded-xl text-center">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-emerald-700">Complete Records</span>
                        <div id="repCompleteRows" class="text-lg font-extrabold text-emerald-800 mt-0.5">0</div>
                    </div>
                    <div class="p-3 bg-amber-50/70 border border-amber-200 rounded-xl text-center">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-amber-700">Missing Fields</span>
                        <div id="repPartialRows" class="text-lg font-extrabold text-amber-800 mt-0.5">0</div>
                    </div>
                </div>

                <!-- Column Mapping Selector -->
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                            <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5 text-sky-600"></i>
                            Column Mapping
                        </span>
                        <span class="text-[10px] text-slate-500">Auto-detected. Adjust if needed:</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Customer Name *</label>
                            <select id="mapColName" class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 font-semibold focus:outline-none focus:ring-1 focus:ring-sky-500">
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Phone / Mobile *</label>
                            <select id="mapColNumber" class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 font-semibold focus:outline-none focus:ring-1 focus:ring-sky-500">
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Address (Optional)</label>
                            <select id="mapColAddress" class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 font-semibold focus:outline-none focus:ring-1 focus:ring-sky-500">
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Mapping Status Banner -->
                <div class="p-3 rounded-xl bg-sky-50 border border-sky-200 text-xs text-sky-800 flex items-start gap-2">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-sky-600 shrink-0 mt-0.5"></i>
                    <div class="flex-1">
                        <span class="font-bold">Columns verified:</span> 
                        <span id="repColNames">Name, Number, Address</span>.
                        <div class="text-[11px] text-sky-700 mt-0.5">
                            Any rows with missing name, phone, or address will be preserved safely as empty records during import.
                        </div>
                    </div>
                </div>

                <!-- Preview Table -->
                <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                    <div class="px-3.5 py-2 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                        <span class="text-xs font-extrabold text-slate-700 uppercase tracking-wider">Pre-Import Data Preview</span>
                        <span id="repPreviewCount" class="text-[11px] text-slate-500 font-mono">Showing first records</span>
                    </div>
                    <div class="max-h-60 overflow-y-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead class="bg-slate-100/70 sticky top-0 z-5 text-[10px] font-bold uppercase text-slate-500">
                                <tr>
                                    <th class="py-2 px-3 w-12 text-center">Row</th>
                                    <th class="py-2 px-3">Name</th>
                                    <th class="py-2 px-3">Number</th>
                                    <th class="py-2 px-3">Address</th>
                                    <th class="py-2 px-3 text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody id="repPreviewTableBody" class="divide-y divide-slate-100 font-medium">
                                <!-- Populated dynamically by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Progress Bar Container -->
                <div id="importProgressBarContainer" class="hidden space-y-2 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <div class="flex items-center justify-between text-xs text-slate-700 font-bold">
                        <span id="importProgressText" class="flex items-center gap-1.5">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin text-sky-600"></i>
                            Importing customers...
                        </span>
                        <span id="importProgressPercent" class="font-mono text-sky-700 font-extrabold">0%</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                        <div id="importProgressBar" class="bg-primary-600 h-2.5 rounded-full transition-all duration-200" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Actions: Choose different file or Final Import -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2.5">
                    <button type="button" onclick="resetCustomerImportModal()" class="px-3.5 py-2 border border-slate-200 text-slate-700 rounded-lg text-xs font-bold hover:bg-slate-50 flex items-center gap-1.5">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                        <span>Upload Different File</span>
                    </button>
                    <button type="button" id="btnExecuteFinalImport" onclick="executeFinalImport()" class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white rounded-lg text-xs font-bold shadow-md transition-colors flex items-center gap-2 cursor-pointer">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        <span id="btnFinalImportText">Final Import</span>
                    </button>
                </div>
            </div>

            <!-- Step 3: Success Completed State -->
            <div id="importStepSuccess" class="hidden py-6 text-center space-y-3">
                <div class="w-14 h-14 rounded-full bg-emerald-100 border border-emerald-300 text-emerald-600 flex items-center justify-center mx-auto shadow-sm">
                    <i data-lucide="check" class="w-7 h-7 stroke-[3]"></i>
                </div>
                <h4 class="text-base font-extrabold text-slate-900 font-display">Import Completed Successfully</h4>
                <p id="successImportMessage" class="text-xs text-slate-600 max-w-sm mx-auto">
                    All customers have been imported into the directory.
                </p>
                <div class="pt-3">
                    <button type="button" onclick="window.location.reload()" class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-bold shadow-md transition-colors inline-flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i>
                        <span>Done & Refresh Directory</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentImportFile = null;
let analyzedImportRows = [];
let currentReportData = null;

function openCustomerImportModal() {
    resetCustomerImportModal();
    openModal('import-customer-modal');
}

function resetCustomerImportModal() {
    currentImportFile = null;
    analyzedImportRows = [];
    currentReportData = null;
    $('#inputImportFile').val('');
    $('#selectedFileInfo').addClass('hidden');
    $('#selectedFileName').text('');
    $('#selectedFileSize').text('');
    $('#importUploadError').addClass('hidden');
    $('#btnAnalyzeFile').prop('disabled', true)
        .removeClass('bg-primary-600 hover:bg-primary-700 cursor-pointer')
        .addClass('bg-slate-300 cursor-not-allowed');
    $('#btnAnalyzeFileText').text('Analyze File');
    $('#btnExecuteFinalImport').prop('disabled', false);

    $('#importProgressBarContainer').addClass('hidden');
    $('#importProgressBar').css('width', '0%');
    $('#importProgressPercent').text('0%');

    $('#importStepUpload').removeClass('hidden');
    $('#importStepReport').addClass('hidden');
    $('#importStepSuccess').addClass('hidden');
    
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function openAddCustomerModal() {
    openModal('add-customer-modal');
    setTimeout(function() {
        if (!window.locationPicker_customer_create) {
            window.initLocationPicker({
                prefix: 'customer_create',
                getAddressString: function() {
                    const form = document.querySelector('#add-customer-modal form');
                    if (!form) return '';
                    const parts = [
                        form.querySelector('[name="address_line1"]')?.value,
                        form.querySelector('[name="address_line2"]')?.value,
                        form.querySelector('[name="landmark"]')?.value,
                        form.querySelector('[name="city"]')?.value,
                        form.querySelector('[name="postal_code"]')?.value
                    ].filter(Boolean);
                    return parts.join(', ');
                }
            });
        } else {
            window.locationPicker_customer_create.invalidate();
        }
    }, 200);
}

$(document).ready(function() {
    // If query string ?action=create, automatically open the modal
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'create') {
        openAddCustomerModal();
    }

    const $drop = $('#importDropZone');
    const $fileInput = $('#inputImportFile');

    // Prevent default window navigation if a file is dropped in modal
    $('#import-customer-modal').on('dragover drop', function(e) {
        e.preventDefault();
    });

    // Fallback click on dropzone container in case user clicks outside the input bounds
    $drop.on('click', function(e) {
        if ($(e.target).closest('#btnRemoveSelectedFile').length) return;
        if (e.target !== $fileInput[0]) {
            $fileInput[0].click();
        }
    });

    // Remove file
    $('#btnRemoveSelectedFile').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        resetCustomerImportModal();
    });

    // File Input change (triggered natively on file select or drop)
    $fileInput.on('change', function(e) {
        const file = this.files && this.files.length ? this.files[0] : (e.target.files ? e.target.files[0] : null);
        if (file) {
            handleFileSelection(file);
        }
    });

    // Drag & Drop events on the input/dropzone
    $fileInput.on('dragenter dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $drop.addClass('border-sky-500 bg-sky-50/80 ring-2 ring-sky-300/60');
    });

    $fileInput.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $drop.removeClass('border-sky-500 bg-sky-50/80 ring-2 ring-sky-300/60');
    });

    $fileInput.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $drop.removeClass('border-sky-500 bg-sky-50/80 ring-2 ring-sky-300/60');
        const dt = e.originalEvent && e.originalEvent.dataTransfer ? e.originalEvent.dataTransfer : null;
        if (dt && dt.files && dt.files.length) {
            try {
                $fileInput[0].files = dt.files;
            } catch (err) {
                // Some browsers prevent programmatic assignment to HTMLInputElement.files
            }
            handleFileSelection(dt.files[0]);
        }
    });

    function handleFileSelection(file) {
        if (!file) return;
        const validExtensions = ['csv', 'xlsx', 'xls', 'txt'];
        const ext = file.name.split('.').pop().toLowerCase();
        if (!validExtensions.includes(ext)) {
            showImportError('Please choose a valid Excel (.xlsx, .xls) or CSV (.csv) file.');
            return;
        }

        currentImportFile = file;
        $('#importUploadError').addClass('hidden');
        $('#selectedFileName').text(file.name);
        const sizeKb = (file.size / 1024).toFixed(1);
        $('#selectedFileSize').text(`(${sizeKb} KB)`);
        $('#selectedFileInfo').removeClass('hidden');

        $('#btnAnalyzeFile').prop('disabled', false)
            .removeClass('bg-slate-300 cursor-not-allowed')
            .addClass('bg-primary-600 hover:bg-primary-700 cursor-pointer');

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    function showImportError(msg) {
        $('#importUploadErrorText').text(msg);
        $('#importUploadError').removeClass('hidden');
    }

    // Step 1: Analyze File Button Click
    $('#btnAnalyzeFile').on('click', function() {
        if (!currentImportFile) return;

        const formData = new FormData();
        formData.append('file', currentImportFile);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

        $('#btnAnalyzeFile').prop('disabled', true);
        $('#btnAnalyzeFileText').html('<i data-lucide="loader-2" class="w-4 h-4 animate-spin inline mr-1"></i> Analyzing...');
        if (typeof lucide !== 'undefined') lucide.createIcons();

        $.ajax({
            url: "{{ route('customers.import.analyze') }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success && res.report) {
                    renderAnalysisReport(res.report);
                } else {
                    showImportError(res.message || 'Unable to analyze file.');
                    $('#btnAnalyzeFile').prop('disabled', false);
                    $('#btnAnalyzeFileText').text('Analyze File');
                }
            },
            error: function(xhr) {
                const err = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error analyzing file. Please check file format.';
                showImportError(err);
                $('#btnAnalyzeFile').prop('disabled', false);
                $('#btnAnalyzeFileText').text('Analyze File');
            }
        });
    });

    function renderAnalysisReport(report) {
        currentReportData = report;
        analyzedImportRows = report.rows || [];
        
        $('#repTotalRows').text(report.total_rows);
        $('#repCompleteRows').text(report.complete_count);
        $('#repPartialRows').text(report.partial_count);

        if (report.columns) {
            $('#repColNames').text(`${report.columns.name_header}, ${report.columns.number_header}, ${report.columns.address_header}`);
        }

        // Populate Column Mapping Dropdowns
        const headers = report.headers || [];
        const detected = report.detected_cols || { name: 0, number: 1, address: -1 };

        let nameOptions = '';
        let numOptions = '';
        let addrOptions = '<option value="-1">None (Leave Address Empty)</option>';

        headers.forEach(function(h) {
            const isNameSel = h.index === detected.name ? 'selected' : '';
            const isNumSel = h.index === detected.number ? 'selected' : '';
            const isAddrSel = h.index === detected.address ? 'selected' : '';

            nameOptions += `<option value="${h.index}" ${isNameSel}>Col ${h.index + 1}: ${h.name}</option>`;
            numOptions += `<option value="${h.index}" ${isNumSel}>Col ${h.index + 1}: ${h.name}</option>`;
            addrOptions += `<option value="${h.index}" ${isAddrSel}>Col ${h.index + 1}: ${h.name}</option>`;
        });

        $('#mapColName').html(nameOptions);
        $('#mapColNumber').html(numOptions);
        $('#mapColAddress').html(addrOptions);

        renderPreviewTable(report.preview || [], report.total_rows);

        $('#btnFinalImportText').text(`Final Import (${report.total_rows} Customers)`);

        $('#importStepUpload').addClass('hidden');
        $('#importStepReport').removeClass('hidden');

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    function renderPreviewTable(previewList, totalCount) {
        let rowsHtml = '';
        previewList.forEach(function(row) {
            const nameHtml = row.name ? `<span class="font-bold text-slate-900">${escapeHtml(row.name)}</span>` : `<span class="text-slate-400 italic">Empty</span>`;
            const phoneHtml = row.number ? `<span class="font-mono text-slate-700">${escapeHtml(row.number)}</span>` : `<span class="text-slate-400 italic">Empty</span>`;
            const addressHtml = row.address ? `<span class="text-slate-700 line-clamp-1">${escapeHtml(row.address)}</span>` : `<span class="text-slate-400 italic">Empty</span>`;
            
            let badgeHtml = '';
            if (row.status === 'COMPLETE') {
                badgeHtml = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Complete</span>';
            } else {
                const missingText = (row.missing && row.missing.length) ? 'Missing ' + row.missing.join(', ') : 'Partial';
                badgeHtml = `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">${missingText}</span>`;
            }

            rowsHtml += `
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-2 px-3 text-center text-slate-400 font-mono text-[11px]">${row.row_num}</td>
                    <td class="py-2 px-3">${nameHtml}</td>
                    <td class="py-2 px-3">${phoneHtml}</td>
                    <td class="py-2 px-3">${addressHtml}</td>
                    <td class="py-2 px-3 text-right">${badgeHtml}</td>
                </tr>
            `;
        });

        $('#repPreviewTableBody').html(rowsHtml);
        $('#repPreviewCount').text(`Showing ${previewList.length} of ${totalCount} records`);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Trigger re-analysis when user modifies Column Mapping dropdowns
    $('#mapColName, #mapColNumber, #mapColAddress').on('change', function() {
        if (!currentImportFile) return;

        const formData = new FormData();
        formData.append('file', currentImportFile);
        formData.append('name_col', $('#mapColName').val());
        formData.append('number_col', $('#mapColNumber').val());
        formData.append('address_col', $('#mapColAddress').val());
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

        $('#repPreviewTableBody').html('<tr><td colspan="5" class="py-4 text-center text-slate-400"><i data-lucide="loader-2" class="w-4 h-4 animate-spin inline mr-1"></i> Updating preview...</td></tr>');
        if (typeof lucide !== 'undefined') lucide.createIcons();

        $.ajax({
            url: "{{ route('customers.import.analyze') }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success && res.report) {
                    analyzedImportRows = res.report.rows || [];
                    $('#repTotalRows').text(res.report.total_rows);
                    $('#repCompleteRows').text(res.report.complete_count);
                    $('#repPartialRows').text(res.report.partial_count);
                    if (res.report.columns) {
                        $('#repColNames').text(`${res.report.columns.name_header}, ${res.report.columns.number_header}, ${res.report.columns.address_header}`);
                    }
                    renderPreviewTable(res.report.preview || [], res.report.total_rows);
                    $('#btnFinalImportText').text(`Final Import (${res.report.total_rows} Customers)`);
                }
            }
        });
    });
});

// Step 2: Final Import Commit Click with chunking & live progress bar
async function executeFinalImport() {
    if (!analyzedImportRows || analyzedImportRows.length === 0) {
        showToast('No customer records to import.', 'danger');
        return;
    }

    const $btn = $('#btnExecuteFinalImport');
    const $btnText = $('#btnFinalImportText');
    const $progressContainer = $('#importProgressBarContainer');
    const $progressBar = $('#importProgressBar');
    const $progressText = $('#importProgressText');
    const $progressPercent = $('#importProgressPercent');

    $btn.prop('disabled', true);
    $progressContainer.removeClass('hidden');

    const total = analyzedImportRows.length;
    const chunkSize = 1000;
    let importedTotal = 0;

    for (let i = 0; i < total; i += chunkSize) {
        const chunk = analyzedImportRows.slice(i, i + chunkSize);
        const currentBatch = Math.min(i + chunkSize, total);
        const percent = Math.round((currentBatch / total) * 100);

        $btnText.html(`<i data-lucide="loader-2" class="w-4 h-4 animate-spin inline mr-1"></i> Importing (${currentBatch}/${total})...`);
        $progressText.html(`<i data-lucide="loader-2" class="w-4 h-4 animate-spin inline text-sky-600 mr-1.5"></i> Importing ${currentBatch} of ${total} customers...`);
        $progressBar.css('width', `${percent}%`);
        $progressPercent.text(`${percent}%`);
        if (typeof lucide !== 'undefined') lucide.createIcons();

        try {
            const res = await $.ajax({
                url: "{{ route('customers.import.commit') }}",
                type: 'POST',
                data: JSON.stringify({
                    rows: chunk,
                    _token: $('meta[name="csrf-token"]').attr('content')
                }),
                contentType: 'application/json',
                dataType: 'json'
            });

            if (!res.success) {
                showToast(res.message || 'Error executing import.', 'danger');
                $btn.prop('disabled', false);
                $btnText.text(`Final Import (${total} Customers)`);
                return;
            }
            importedTotal += (res.imported_count || chunk.length);
        } catch (xhr) {
            const err = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error executing final import.';
            showToast(err, 'danger');
            $btn.prop('disabled', false);
            $btnText.text(`Final Import (${total} Customers)`);
            return;
        }
    }

    // Finished all batches successfully
    $progressBar.css('width', '100%');
    $progressPercent.text('100%');
    $progressText.html('<i data-lucide="check" class="w-4 h-4 text-emerald-600 inline mr-1.5"></i> Done!');

    $('#importStepReport').addClass('hidden');
    $('#successImportMessage').text(`Successfully imported all ${importedTotal} customers into the directory.`);
    $('#importStepSuccess').removeClass('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
</script>
@endsection

