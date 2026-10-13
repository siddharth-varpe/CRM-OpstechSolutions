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
                        <input type="text" name="company_name" value="{{ $settings['company_name'] ?? 'Enterprises CRM' }}" class="form-input text-sm font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">GSTIN (GST Identification Number)</label>
                        <input type="text" name="company_gstin" value="{{ $settings['company_gstin'] ?? ($settings['gst_number'] ?? '27AABCS1429B1Z') }}" class="form-input text-sm font-mono uppercase">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Official Phone / Helpline</label>
                        <input type="text" name="company_phone" value="{{ $settings['company_phone'] ?? ($settings['support_phone'] ?? '+91 98765 43210') }}" class="form-input text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Official Email Address</label>
                        <input type="email" name="company_email" value="{{ $settings['company_email'] ?? ($settings['support_email'] ?? 'billing@enterprisescrm.com') }}" class="form-input text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Registered Workshop / Office Address</label>
                    <textarea name="company_address" rows="2" class="form-input text-xs">{{ $settings['company_address'] ?? 'Plot No. 42, Industrial Area, Phase II, Pune - 411019, MH, India' }}</textarea>
                </div>

                <div class="pt-4 border-t border-slate-200">
                    <h4 class="font-bold text-slate-800 text-xs uppercase tracking-wider mb-4">Bank Settlement &amp; UPI Account Details</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Bank Name</label>
                            <input type="text" name="bank_name" value="{{ $settings['bank_name'] ?? 'State Bank of India' }}" class="form-input text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Account Number</label>
                            <input type="text" name="bank_account_number" value="{{ $settings['bank_account_number'] ?? '30998877665' }}" class="form-input text-xs font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">IFSC Code</label>
                            <input type="text" name="bank_ifsc" value="{{ $settings['bank_ifsc'] ?? 'SBIN0001234' }}" class="form-input text-xs font-mono uppercase">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Branch Name</label>
                            <input type="text" name="bank_branch" value="{{ $settings['bank_branch'] ?? 'Main Branch, Pune' }}" class="form-input text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">UPI VPA Handle</label>
                            <input type="text" name="upi_id" value="{{ $settings['upi_id'] ?? 'enterprises.crm@upi' }}" class="form-input text-xs font-mono">
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
                        <input type="number" name="default_gst_rate" value="{{ $settings['default_gst_rate'] ?? 18 }}" min="0" max="100" step="0.5" class="form-input text-sm font-mono">
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
</div>

<script>
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
});
</script>
@endsection
