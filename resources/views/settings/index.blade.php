@extends('layouts.app')

@section('title', 'System Settings & Audit - Enterprises CRM')

@section('content')
<div class="space-y-6 max-w-[1600px] mx-auto pb-12 select-none">
    <!-- 1. Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center shadow-xs shrink-0">
                <i data-lucide="settings" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">System Settings &amp; Configuration</h1>
                <p class="text-xs sm:text-sm text-slate-500 font-normal">
                    Manage business metadata, GST defaults, document sequences and audit trail
                </p>
            </div>
        </div>
    </div>

    <!-- 2. Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
        <a href="{{ route('settings.index', ['tab' => 'business']) }}"
           class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition {{ $tab === 'business' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
            <i data-lucide="building" class="w-4 h-4"></i>
            <span>Company &amp; Bank Profile</span>
        </a>
        <a href="{{ route('settings.index', ['tab' => 'numbering']) }}"
           class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition {{ $tab === 'numbering' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
            <i data-lucide="hash" class="w-4 h-4"></i>
            <span>Document Numbering</span>
        </a>
        <a href="{{ route('settings.index', ['tab' => 'tax']) }}"
           class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition {{ $tab === 'tax' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
            <i data-lucide="receipt" class="w-4 h-4"></i>
            <span>GST &amp; Invoice Terms</span>
        </a>
        <a href="{{ route('settings.index', ['tab' => 'audit']) }}"
           class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition {{ $tab === 'audit' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
            <i data-lucide="shield-check" class="w-4 h-4"></i>
            <span>Audit Trail Log</span>
        </a>
        @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('settings.index', ['tab' => 'data']) }}"
           class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition {{ $tab === 'data' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-rose-600' }}">
            <i data-lucide="database" class="w-4 h-4"></i>
            <span>Data Management</span>
        </a>
        @endif
    </div>

    <!-- 3. Tab Contents -->
    @if($tab === 'business')
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-6 max-w-4xl">
            <h3 class="font-bold text-slate-900 text-base mb-1">Company Profile &amp; Banking Details</h3>
            <p class="text-xs text-slate-500 mb-6">These details appear automatically on all official GST tax invoices, job cards and receipts.</p>

            <form id="formSaveSettings" action="{{ route('settings.update') }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="tab" value="business">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Company Trade Name</label>
                        <input type="text" name="company_name" value="{{ $settings['company_name'] ?? '' }}" placeholder="Enterprises CRM" class="form-input text-sm font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">GSTIN (GST Identification Number)</label>
                        <input type="text" name="company_gstin" value="{{ $settings['company_gstin'] ?? ($settings['gst_number'] ?? '') }}" placeholder="27AABCS1429B1Z" class="form-input text-sm font-mono uppercase">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Official Phone / Helpline</label>
                        <input type="text" name="company_phone" value="{{ $settings['company_phone'] ?? ($settings['support_phone'] ?? '') }}" placeholder="+91 98765 43210" class="form-input text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Official Email Address</label>
                        <input type="email" name="company_email" value="{{ $settings['company_email'] ?? ($settings['support_email'] ?? '') }}" placeholder="billing@enterprisescrm.com" class="form-input text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Registered Workshop / Office Address</label>
                    <textarea name="company_address" rows="2" placeholder="Plot No. 42, Industrial Area, Phase II, Pune - 411019, MH, India" class="form-input text-xs">{{ $settings['company_address'] ?? '' }}</textarea>
                </div>

                <div class="pt-4 border-t border-slate-200">
                    <h4 class="font-bold text-slate-800 text-xs uppercase tracking-wider mb-4">Bank Settlement &amp; UPI Account Details</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Bank Name</label>
                            <input type="text" name="bank_name" value="{{ $settings['bank_name'] ?? '' }}" placeholder="State Bank of India" class="form-input text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Account Number</label>
                            <input type="text" name="bank_account_number" value="{{ $settings['bank_account_number'] ?? '' }}" placeholder="30998877665" class="form-input text-xs font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">IFSC Code</label>
                            <input type="text" name="bank_ifsc" value="{{ $settings['bank_ifsc'] ?? '' }}" placeholder="SBIN0001234" class="form-input text-xs font-mono uppercase">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Branch Name</label>
                            <input type="text" name="bank_branch" value="{{ $settings['bank_branch'] ?? '' }}" placeholder="Main Branch, Pune" class="form-input text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">UPI VPA Handle</label>
                            <input type="text" name="upi_id" value="{{ $settings['upi_id'] ?? '' }}" placeholder="enterprises.crm@upi" class="form-input text-xs font-mono">
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200 flex justify-end">
                    <button type="submit" class="btn-primary flex items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Save Business Profile</span>
                    </button>
                </div>
            </form>
        </div>
    @elseif($tab === 'numbering')
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-6 max-w-4xl space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h3 class="font-bold text-slate-900 text-base mb-1">Document Sequences &amp; Numbering Rules</h3>
                    <p class="text-xs text-slate-500">Atomic, collision-free sequential generation with calendar year resets.</p>
                </div>
            </div>

            <form id="formSaveSequences" action="{{ route('settings.sequences.update') }}" method="POST" class="space-y-4">
                @csrf
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-semibold uppercase">
                            <tr>
                                <th class="p-3">Sequence Name</th>
                                <th class="p-3">Prefix Format</th>
                                <th class="p-3 text-center">Padding</th>
                                <th class="p-3 text-center">Reset Year</th>
                                <th class="p-3 text-right">Current Counter</th>
                                <th class="p-3">Live Sample Preview</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($sequences as $seq)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-3 font-bold text-slate-900 font-mono">{{ $seq->name }}</td>
                                    <td class="p-3">
                                        <input type="text"
                                               name="sequences[{{ $seq->name }}][prefix]"
                                               value="{{ $seq->prefix }}"
                                               data-seq="{{ $seq->name }}"
                                               data-year="{{ $seq->current_year }}"
                                               data-counter="{{ $seq->current_val > 0 ? $seq->current_val : 1 }}"
                                               class="form-input text-xs font-mono uppercase w-28 seq-prefix-input">
                                    </td>
                                    <td class="p-3 text-center">
                                        <input type="number"
                                               name="sequences[{{ $seq->name }}][padding]"
                                               value="{{ $seq->padding ?? 4 }}"
                                               min="2"
                                               max="10"
                                               data-seq="{{ $seq->name }}"
                                               class="form-input text-xs font-mono w-20 text-center mx-auto seq-pad-input">
                                    </td>
                                    <td class="p-3 text-center font-mono font-bold text-slate-600">{{ $seq->current_year }}</td>
                                    <td class="p-3 text-right font-mono font-bold text-slate-800">{{ $seq->current_val }}</td>
                                    <td class="p-3 font-mono font-bold text-indigo-700">
                                        <span id="preview-{{ $seq->name }}">
                                            {{ $seq->prefix }}-{{ $seq->current_year }}-{{ str_pad((string)($seq->current_val > 0 ? $seq->current_val : 1), $seq->padding ?? 4, '0', STR_PAD_LEFT) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pt-4 border-t border-slate-200 flex justify-end">
                    <button type="submit" class="btn-primary flex items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Save Numbering Rules</span>
                    </button>
                </div>
            </form>
        </div>
    @elseif($tab === 'tax')
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-6 max-w-4xl space-y-6">
            <div>
                <h3 class="font-bold text-slate-900 text-base mb-1">GST, Invoicing &amp; Operational Policies</h3>
                <p class="text-xs text-slate-500">Default taxation rates, billing terms and service operation parameters.</p>
            </div>

            <form id="formSaveTaxSettings" action="{{ route('settings.update') }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="tab" value="tax">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Default GST Rate (%)</label>
                        <input type="number" name="default_gst_rate" value="{{ $settings['default_gst_rate'] ?? 0 }}" min="0" max="100" step="0.5" class="form-input text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Standard HSN / SAC Code</label>
                        <input type="text" name="default_hsn" value="{{ $settings['default_hsn'] ?? '842121' }}" class="form-input text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Invoice Payment Due Period (Days)</label>
                        <input type="number" name="payment_terms_days" value="{{ $settings['payment_terms_days'] ?? 30 }}" min="0" max="365" class="form-input text-sm font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Standard Terms &amp; Conditions (Printed on Tax Invoices)</label>
                    <textarea name="invoice_terms" rows="4" class="form-input text-xs">{{ $settings['invoice_terms'] ?? "1) Goods once sold will not be taken back or exchanged.\n2) Warranty as per manufacturer terms and conditions.\n3) Service charges applicable for out-of-warranty services.\n4) Subject to local jurisdiction.\n5) Payment due immediately or as per agreed terms." }}</textarea>
                </div>

                <div class="pt-4 border-t border-slate-200">
                    <h4 class="font-bold text-slate-800 text-xs uppercase tracking-wider mb-4">Service &amp; Inventory Operational Defaults</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Default Machine Warranty (Months)</label>
                            <input type="number" name="default_warranty_months" value="{{ $settings['default_warranty_months'] ?? 12 }}" min="0" max="120" class="form-input text-xs font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Preventive Maintenance Interval (Months)</label>
                            <input type="number" name="default_service_interval_months" value="{{ $settings['default_service_interval_months'] ?? 6 }}" min="1" max="36" class="form-input text-xs font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Field Service SLA Commitment (Hours)</label>
                            <input type="number" name="sla_hours" value="{{ $settings['sla_hours'] ?? 24 }}" min="1" max="720" class="form-input text-xs font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Low Stock Inventory Alert Threshold (Units)</label>
                            <input type="number" name="low_stock_threshold" value="{{ $settings['low_stock_threshold'] ?? 5 }}" min="0" max="1000" class="form-input text-xs font-mono">
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200 flex justify-end">
                    <button type="submit" class="btn-primary flex items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Save Tax &amp; Operational Policies</span>
                    </button>
                </div>
            </form>
        </div>
    @elseif($tab === 'audit')
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-900 text-sm">System Audit Activity Trail</h3>
                    <p class="text-xs text-slate-500">Immutable ledger of administrative actions and document mutations</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                        <tr>
                            <th class="p-3">Timestamp</th>
                            <th class="p-3">Action</th>
                            <th class="p-3">Entity</th>
                            <th class="p-3">User</th>
                            <th class="p-3">Mutations / Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($auditLogs as $log)
                            <tr class="hover:bg-slate-50/50">
                                <td class="p-3 font-mono text-slate-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i:s') }}</td>
                                <td class="p-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold {{ $log->action === 'UPDATE' ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="p-3 font-mono text-slate-700 font-semibold">{{ $log->entity_type }} ({{ $log->entity_id ?? 'global' }})</td>
                                <td class="p-3 font-semibold text-slate-900">{{ $log->user?->display_name ?? ($log->user?->name ?? 'System Administrator') }}</td>
                                <td class="p-3 max-w-sm">
                                    @if(is_array($log->new_values) && count($log->new_values) > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($log->new_values as $k => $v)
                                                <span class="inline-block bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded text-[10px] font-mono border border-slate-200/60">
                                                    <strong>{{ $k }}:</strong> {{ is_array($v) ? json_encode($v) : \Illuminate\Support\Str::limit((string)$v, 20) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-slate-400 font-mono text-[11px]">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-slate-400">No audit activity logged yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($auditLogs->hasPages())
                <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                    {{ $auditLogs->links() }}
                </div>
            @endif
        </div>
    @endif

    @if($tab === 'data')
        <div class="space-y-6 max-w-4xl">
            <!-- Data Management Section Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center shrink-0">
                        <i data-lucide="database-zap" class="w-6 h-6"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-slate-900 text-base mb-1">Data Management</h3>
                        <p class="text-xs text-slate-500 mb-6">Manage system records lifecycle, database maintenance, and operational resets.</p>
                        
                        <div class="p-5 rounded-2xl border border-rose-200/80 bg-rose-50/40 space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                        <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600"></i>
                                        Clean CRM Data
                                    </h4>
                                    <p class="text-xs text-slate-600 mt-1 max-w-2xl leading-relaxed">
                                        Permanently remove selected business, transaction, inventory, service, and demonstration records to prepare the CRM for a fresh operational start. Your application, database structure, essential configuration, and protected administrator access will remain intact.
                                    </p>
                                </div>
                                <div class="shrink-0">
                                    <button type="button" 
                                            id="btnOpenCleanDataModal" 
                                            class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        <span>Clean CRM Data</span>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2 text-[11px] text-rose-700 font-medium pt-2 border-t border-rose-200/60">
                                <i data-lucide="info" class="w-3.5 h-3.5 shrink-0"></i>
                                <span>This operation is irreversible without a backup. Protected administrator access, roles, permissions, settings, and database structure will be preserved.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Clean CRM Data Modal (Two-Stage Confirmation) -->
        <div id="cleanDataModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-2xl w-full overflow-hidden transition-all my-8">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center">
                            <i data-lucide="alert-octagon" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Clean CRM Business Data</h3>
                            <p class="text-xs text-slate-500">Destructive operational database reset</p>
                        </div>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition" onclick="closeCleanDataModal()">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                    <!-- Loading State -->
                    <div id="cleanDataLoading" class="py-12 text-center space-y-3">
                        <i data-lucide="loader-2" class="w-8 h-8 text-rose-600 animate-spin mx-auto"></i>
                        <p class="text-xs font-semibold text-slate-600">Calculating real-time database impact &amp; record counts...</p>
                    </div>

                    <!-- Content Container (Shown once preview loaded) -->
                    <div id="cleanDataContent" class="hidden space-y-5">
                        <!-- Explicit Warning Box -->
                        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs space-y-1.5">
                            <div class="font-bold flex items-center gap-2 text-amber-800">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                                Permanent Deletion Notice
                            </div>
                            <p class="text-[11px] text-amber-800/90 leading-relaxed">
                                This operation permanently deletes the records listed below. It cannot be undone through the CRM interface. Create and verify a backup before proceeding.
                            </p>
                        </div>

                        <!-- Stage 1: Deletion Breakdown -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Scheduled for Deletion</h4>
                                <span id="previewTotalCount" class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 font-mono">0 records</span>
                            </div>
                            <div id="previewCategoriesList" class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                <!-- Populated dynamically by JS -->
                            </div>
                        </div>

                        <!-- Protected Entities -->
                        <div class="p-3.5 rounded-2xl bg-emerald-50/60 border border-emerald-100 text-xs space-y-2">
                            <h4 class="font-bold text-emerald-900 flex items-center gap-1.5 text-xs">
                                <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
                                Protected &amp; Intact Resources
                            </h4>
                            <ul class="text-[11px] text-emerald-800 space-y-1 list-disc list-inside">
                                <li><strong>Super Admin Access:</strong> Your login credentials and settings privileges remain untouched.</li>
                                <li><strong>Catalogue &amp; Masters:</strong> Product definitions and spare parts catalog remain intact (stock reset to 0).</li>
                                <li><strong>Structure &amp; Audit:</strong> Database tables, schema migrations, and audit trails are preserved.</li>
                            </ul>
                        </div>

                        <!-- Stage 2: Explicit Confirmation Form -->
                        <form id="formExecuteCleanData" class="space-y-4 pt-3 border-t border-slate-200">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1">
                                    Type <span class="font-mono text-rose-600 select-all bg-rose-50 px-1 py-0.5 rounded border border-rose-200">DELETE CRM DATA</span> to confirm:
                                </label>
                                <input type="text" 
                                       id="inputConfirmPhrase" 
                                       name="confirmation_phrase" 
                                       autocomplete="off" 
                                       placeholder="Type DELETE CRM DATA" 
                                       class="form-input text-xs font-mono font-bold tracking-wider">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1">
                                    Super Administrator Password (Re-authentication):
                                </label>
                                <input type="password" 
                                       id="inputAdminPassword" 
                                       name="password" 
                                       autocomplete="current-password" 
                                       placeholder="Enter your current password" 
                                       class="form-input text-xs font-semibold">
                            </div>

                            <div class="flex items-start gap-2.5 pt-1">
                                <input type="checkbox" 
                                       id="chkAcknowledge" 
                                       name="acknowledge_risks" 
                                       value="1" 
                                       class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 mt-0.5 cursor-pointer">
                                <label for="chkAcknowledge" class="text-xs text-slate-700 font-medium leading-snug cursor-pointer">
                                    I understand that this action will permanently wipe all operational customer, invoice, and transactional records and cannot be undone.
                                </label>
                            </div>

                            <!-- Error Alert -->
                            <div id="cleanDataError" class="hidden p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                                <span id="cleanDataErrorText"></span>
                            </div>

                            <!-- Modal Actions -->
                            <div class="flex items-center justify-end gap-3 pt-3">
                                <button type="button" 
                                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer" 
                                        onclick="closeCleanDataModal()">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        id="btnConfirmDelete" 
                                        disabled 
                                        class="px-5 py-2.5 rounded-xl bg-slate-300 text-slate-500 font-bold text-xs transition flex items-center gap-2 cursor-not-allowed">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    <span id="btnConfirmDeleteText">Permanently Delete CRM Data</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
function closeCleanDataModal() {
    $('#cleanDataModal').addClass('hidden');
    $('#formExecuteCleanData')[0]?.reset();
    $('#cleanDataError').addClass('hidden');
    checkConfirmButtonState();
}

function checkConfirmButtonState() {
    const phrase = ($('#inputConfirmPhrase').val() || '').trim();
    const ack = $('#chkAcknowledge').is(':checked');
    const pwd = ($('#inputAdminPassword').val() || '').trim();

    const isValid = (phrase === 'DELETE CRM DATA' && ack && pwd.length > 0);

    const $btn = $('#btnConfirmDelete');
    if (isValid) {
        $btn.prop('disabled', false)
            .removeClass('bg-slate-300 text-slate-500 cursor-not-allowed')
            .addClass('bg-rose-600 hover:bg-rose-700 text-white cursor-pointer shadow-xs');
    } else {
        $btn.prop('disabled', true)
            .removeClass('bg-rose-600 hover:bg-rose-700 text-white cursor-pointer shadow-xs')
            .addClass('bg-slate-300 text-slate-500 cursor-not-allowed');
    }
}

$(document).ready(function() {
    CRM.handleAjaxForm('#formSaveSettings', function(res) {
        CRM.toast(res.message || 'Business profile settings saved successfully!');
    });

    CRM.handleAjaxForm('#formSaveSequences', function(res) {
        CRM.toast(res.message || 'Document numbering rules saved successfully!');
    });

    CRM.handleAjaxForm('#formSaveTaxSettings', function(res) {
        CRM.toast(res.message || 'Tax & operational policies saved successfully!');
    });

    // Dynamic Live Preview in Sequences Table
    function updateSamplePreview(row) {
        const $row = $(row);
        const $prefixInput = $row.find('.seq-prefix-input');
        const $padInput = $row.find('.seq-pad-input');
        const seq = $prefixInput.data('seq');
        const year = $prefixInput.data('year');
        const counter = $prefixInput.data('counter') || 1;

        const prefix = ($prefixInput.val() || seq).toUpperCase().trim();
        const pad = Math.max(2, Math.min(10, parseInt($padInput.val()) || 4));
        const paddedCounter = String(counter).padStart(pad, '0');

        $(`#preview-${seq}`).text(`${prefix}-${year}-${paddedCounter}`);
    }

    $(document).on('input', '.seq-prefix-input, .seq-pad-input', function() {
        updateSamplePreview($(this).closest('tr'));
    });

    // Clean CRM Data Modal Trigger
    $('#btnOpenCleanDataModal').on('click', function() {
        $('#cleanDataModal').removeClass('hidden');
        $('#cleanDataLoading').removeClass('hidden');
        $('#cleanDataContent').addClass('hidden');
        $('#cleanDataError').addClass('hidden');
        $('#formExecuteCleanData')[0]?.reset();
        checkConfirmButtonState();

        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Fetch real-time server calculated impact preview
        $.ajax({
            url: "{{ route('settings.clean_data.preview') }}",
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                $('#cleanDataLoading').addClass('hidden');
                $('#cleanDataContent').removeClass('hidden');

                if (res.success && res.preview) {
                    const p = res.preview;
                    $('#previewTotalCount').text(`${p.total_records.toLocaleString()} records`);

                    let html = '';
                    for (const [key, cat] of Object.entries(p.categories)) {
                        html += `
                            <div class="p-2.5 rounded-xl border border-slate-200/90 bg-slate-50/60 flex items-center justify-between">
                                <div class="pr-2">
                                    <div class="font-semibold text-slate-800 text-[11px]">${cat.label}</div>
                                    <div class="text-[10px] text-slate-400 line-clamp-1">${cat.description}</div>
                                </div>
                                <span class="font-mono font-bold text-xs px-2 py-0.5 rounded ${cat.count > 0 ? 'bg-rose-100 text-rose-800' : 'bg-slate-200/70 text-slate-600'}">
                                    ${cat.count.toLocaleString()}
                                </span>
                            </div>
                        `;
                    }
                    $('#previewCategoriesList').html(html);
                }
                if (typeof lucide !== 'undefined') lucide.createIcons();
            },
            error: function(xhr) {
                $('#cleanDataLoading').addClass('hidden');
                $('#cleanDataContent').removeClass('hidden');
                const err = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Unable to calculate preview.';
                $('#cleanDataErrorText').text(err);
                $('#cleanDataError').removeClass('hidden');
            }
        });
    });

    // Real-time Confirmation Validation
    $('#inputConfirmPhrase, #inputAdminPassword').on('input', checkConfirmButtonState);
    $('#chkAcknowledge').on('change', checkConfirmButtonState);

    // Clean CRM Data Form Submission
    $('#formExecuteCleanData').on('submit', function(e) {
        e.preventDefault();
        $('#cleanDataError').addClass('hidden');

        const $btn = $('#btnConfirmDelete');
        const $btnText = $('#btnConfirmDeleteText');
        $btn.prop('disabled', true);
        $btnText.text('Executing Cleanup...');

        const formData = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            confirmation_phrase: $('#inputConfirmPhrase').val(),
            acknowledge_risks: $('#chkAcknowledge').is(':checked') ? 1 : 0,
            password: $('#inputAdminPassword').val()
        };

        $.ajax({
            url: "{{ route('settings.clean_data.execute') }}",
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    closeCleanDataModal();
                    CRM.toast(res.message || 'CRM business data cleaned successfully.');
                    setTimeout(function() {
                        window.location.href = "{{ route('settings.index', ['tab' => 'data']) }}";
                    }, 1200);
                } else {
                    $('#cleanDataErrorText').text(res.message || 'Operation failed.');
                    $('#cleanDataError').removeClass('hidden');
                    $btn.prop('disabled', false);
                    $btnText.text('Permanently Delete CRM Data');
                }
            },
            error: function(xhr) {
                let err = 'An error occurred during database cleanup.';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.errors && xhr.responseJSON.errors.password) {
                        err = xhr.responseJSON.errors.password[0];
                    } else if (xhr.responseJSON.message) {
                        err = xhr.responseJSON.message;
                    }
                }
                $('#cleanDataErrorText').text(err);
                $('#cleanDataError').removeClass('hidden');
                $btn.prop('disabled', false);
                $btnText.text('Permanently Delete CRM Data');
            }
        });
    });
});
</script>
@endsection
