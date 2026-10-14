@extends('layouts.app')

@section('title', 'Service ' . $service->service_number . ' - Enterprises CRM')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto pb-16">
    <!-- Action Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
        <div class="flex items-center gap-3">
            <a href="{{ route('services.index') }}" class="btn-outline flex items-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back</span>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="font-bold text-slate-900 text-lg font-mono">{{ $service->service_number }}</h2>
                    @php
                        $statusColors = [
                            'COMPLETED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'IN_PROGRESS' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'ON_HOLD' => 'bg-amber-100 text-amber-800 border-amber-300',
                            'ASSIGNED' => 'bg-sky-50 text-sky-700 border-sky-200',
                            'SCHEDULED' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                            'CANCELLED' => 'bg-slate-100 text-slate-600 border-slate-200',
                        ];
                        $st = $statusColors[$service->status] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                    @endphp
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $st }}">
                        @if($service->status === 'ON_HOLD')
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        @endif
                        {{ $service->status === 'ON_HOLD' ? 'ON HOLD' : $service->status }}
                    </span>
                </div>
                <div class="text-xs text-slate-500 mt-0.5">
                    Customer: <span class="font-semibold text-slate-700">{{ $service->customer->name ?? 'Walk-in' }}</span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($service->jobCard)
                <a href="{{ route('job_cards.show', $service->jobCard->id) }}" class="btn-outline flex items-center gap-2 text-indigo-700 border-indigo-300 hover:bg-indigo-50">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    <span>Print Job Card</span>
                </a>
            @endif

            <button onclick="CRM.openModal('modalAssignTech')" class="btn-outline flex items-center gap-2">
                <i data-lucide="user-plus" class="w-4 h-4 text-slate-500"></i>
                <span>{{ $service->technician ? 'Reassign Tech' : 'Assign Tech' }}</span>
            </button>

            @if($service->status === 'ON_HOLD')
                <button onclick="updateAdminStatus('IN_PROGRESS', 'Resumed by admin')" class="btn-primary flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white shadow-2xs">
                    <i data-lucide="play-circle" class="w-4 h-4"></i>
                    <span>Resume Service</span>
                </button>
            @elseif($service->status !== 'COMPLETED' && $service->status !== 'CANCELLED')
                <button onclick="CRM.openModal('modalHoldServiceAdmin')" class="btn-outline flex items-center gap-2 text-amber-700 border-amber-300 hover:bg-amber-50">
                    <i data-lucide="pause-circle" class="w-4 h-4 text-amber-600"></i>
                    <span>Hold Service</span>
                </button>
            @endif

            @if($service->status !== 'COMPLETED' && $service->status !== 'CANCELLED')
                <button onclick="CRM.openModal('modalCompleteService')" class="btn-primary flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    <span>Complete Service</span>
                </button>
            @endif
        </div>
    </div>

    @if($service->status === 'ON_HOLD')
        <div class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-4 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center shrink-0">
                    <i data-lucide="pause-circle" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-800 bg-amber-200/70 px-2 py-0.5 rounded font-mono">Service On Hold</span>
                        <span class="text-xs text-slate-500">Technician field work currently paused</span>
                    </div>
                    @if($service->internal_notes)
                        <p class="text-xs text-amber-900 mt-1 font-mono whitespace-pre-line">{{ $service->internal_notes }}</p>
                    @endif
                </div>
            </div>
            <button onclick="updateAdminStatus('IN_PROGRESS', 'Resumed by admin')" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold flex items-center gap-1.5 transition shrink-0 cursor-pointer shadow-2xs">
                <i data-lucide="play-circle" class="w-4 h-4"></i>
                <span>Resume (In Progress)</span>
            </button>
        </div>
    @endif

    <!-- Service Details & Status Banner -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Customer & Location Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                <i data-lucide="user" class="w-4 h-4 text-sky-600"></i>
                <span>Customer Details</span>
            </h3>
            <div>
                <a href="{{ $service->customer_id ? route('customers.show', $service->customer_id) : '#' }}" class="text-base font-bold text-slate-900 hover:text-sky-600">
                    {{ $service->customer->name ?? 'Direct Customer' }}
                </a>
                <div class="text-xs text-slate-600 mt-1 space-y-1">
                    <div class="font-mono"><strong>Phone:</strong> {{ $service->customer->phone ?? 'N/A' }}</div>
                    @if($service->customer && $service->customer->addresses->isNotEmpty())
                        @php $addr = $service->customer->addresses->first(); @endphp
                        <div>
                            <strong>Address:</strong><br>
                            {{ $addr->address_line1 }}, {{ $addr->city }} - {{ $addr->pincode }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Asset / Machine Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                <i data-lucide="cpu" class="w-4 h-4 text-teal-600"></i>
                <span>Machine / Asset</span>
            </h3>
            <div>
                <div class="text-base font-bold text-slate-900">
                    {{ $service->asset->custom_name ?? $service->asset->product->name ?? 'Machine / Asset' }}
                </div>
                <div class="text-xs text-slate-600 mt-1 space-y-1 font-mono">
                    <div><strong>Serial Number:</strong> {{ $service->asset->serial_number ?? 'N/A' }}</div>
                    @if($service->asset && $service->asset->product)
                        <div><strong>SKU:</strong> {{ $service->asset->product->sku }}</div>
                    @endif
                    @if($service->warranty)
                        <div class="text-emerald-700 font-semibold font-sans">
                            Warranty Active (Until {{ \Carbon\Carbon::parse($service->warranty->end_date)->format('M Y') }})
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Assigned Technician Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                <i data-lucide="wrench" class="w-4 h-4 text-indigo-600"></i>
                <span>Assigned Technician</span>
            </h3>
            <div>
                @if($service->technician)
                    <div class="text-base font-bold text-slate-900">
                        {{ $service->technician->full_name }}
                    </div>
                    <div class="text-xs text-slate-600 mt-1 font-mono space-y-1">
                        <div><strong>Phone:</strong> {{ $service->technician->phone }}</div>
                        <div class="font-sans text-[11px] text-slate-500">Status: {{ $service->technician->status }}</div>
                    </div>
                @else
                    <div class="text-sm text-amber-600 italic">No technician assigned yet.</div>
                    <button onclick="CRM.openModal('modalAssignTech')" class="btn-outline py-1 px-2.5 text-xs mt-2">
                        Assign Now
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Appointment Schedule Info -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-6">
        <h3 class="font-bold text-slate-900 text-base mb-4 flex items-center gap-2">
            <i data-lucide="calendar" class="w-5 h-5 text-indigo-600"></i>
            <span>Schedule &amp; Problem Details</span>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-xs text-slate-700 pb-4 border-b border-slate-200">
            <div>
                <span class="text-slate-400 uppercase font-semibold">Service Type:</span>
                <div class="font-bold text-slate-900 text-sm mt-0.5">{{ str_replace('_', ' ', $service->service_type) }}</div>
            </div>
            <div>
                <span class="text-slate-400 uppercase font-semibold">Scheduled Date &amp; Slot:</span>
                <div class="font-bold text-slate-900 text-sm mt-0.5 font-mono">
                    {{ \Carbon\Carbon::parse($service->scheduled_date)->format('d F Y') }} ({{ $service->scheduled_time_slot ?? 'Flexible' }})
                </div>
            </div>
            <div>
                <span class="text-slate-400 uppercase font-semibold">Priority:</span>
                <div class="font-bold text-slate-900 text-sm mt-0.5">{{ $service->priority }}</div>
            </div>
        </div>

        <div class="mt-4 text-xs">
            <span class="text-slate-400 uppercase font-semibold block mb-1">Customer Reported Problem / Notes:</span>
            <p class="text-slate-800 bg-slate-50 p-3 rounded-xl border border-slate-200/80">
                {{ $service->customer_notes ?? 'No specific problem description provided.' }}
            </p>
        </div>
    </div>

    <!-- Job Card & Work Execution Details -->
    @if($service->jobCard)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                    <i data-lucide="file-check-2" class="w-5 h-5 text-emerald-600"></i>
                    <span>Job Card Execution: <span class="font-mono text-indigo-600">{{ $service->jobCard->job_card_number }}</span></span>
                </h3>
                <a href="{{ route('job_cards.show', $service->jobCard->id) }}" class="btn-outline py-1 px-3 text-xs flex items-center gap-1.5">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    <span>View / Print Job Card</span>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                <div>
                    <span class="text-slate-400 uppercase font-semibold block mb-1">Diagnosis &amp; Inspection:</span>
                    <p class="text-slate-800 bg-slate-50 p-3 rounded-xl border border-slate-200/80">
                        {{ $service->jobCard->diagnosis ?? 'Pending technician on-site diagnosis.' }}
                    </p>
                </div>
                <div>
                    <span class="text-slate-400 uppercase font-semibold block mb-1">Work Performed:</span>
                    <p class="text-slate-800 bg-slate-50 p-3 rounded-xl border border-slate-200/80">
                        {{ $service->jobCard->work_performed ?? 'Service visit in progress or scheduled.' }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4 pt-4 border-t border-slate-200 text-xs">
                <div class="bg-slate-50 p-3 rounded-xl">
                    <span class="text-slate-500">Labor Charges:</span>
                    <div class="font-mono font-bold text-slate-900 text-sm">₹{{ number_format($service->jobCard->labor_charges, 2) }}</div>
                </div>
                <div class="bg-slate-50 p-3 rounded-xl">
                    <span class="text-slate-500">Parts Charges:</span>
                    <div class="font-mono font-bold text-slate-900 text-sm">₹{{ number_format($service->jobCard->parts_charges, 2) }}</div>
                </div>
                <div class="bg-emerald-50 p-3 rounded-xl border border-emerald-200">
                    <span class="text-emerald-800 font-semibold">Total Job Charges:</span>
                    <div class="font-mono font-black text-emerald-700 text-sm">₹{{ number_format($service->jobCard->total_charges, 2) }}</div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Modal: Assign Technician -->
<div id="modalAssignTech" class="modal-backdrop hidden">
    <div class="modal-box max-w-md">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 text-lg flex items-center gap-2">
                <i data-lucide="user-check" class="w-5 h-5 text-indigo-600"></i>
                <span>Assign Technician</span>
            </h3>
            <button onclick="CRM.closeModal('modalAssignTech')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="formAssignTech" action="{{ route('services.assign', $service->id) }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Select Field Technician <span class="text-rose-500">*</span></label>
                <select name="technician_id" required class="form-input text-sm">
                    <option value="">Select Technician</option>
                    @foreach($technicians as $t)
                        <option value="{{ $t->id }}" {{ $service->technician_id == $t->id ? 'selected' : '' }}>
                            {{ $t->full_name }} ({{ $t->phone }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="CRM.closeModal('modalAssignTech')" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary">Assign Technician</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Complete Service -->
<div id="modalCompleteService" class="modal-backdrop hidden">
    <div class="modal-box max-w-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 text-lg flex items-center gap-2">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                <span>Complete Service &amp; Close Job Card</span>
            </h3>
            <button onclick="CRM.closeModal('modalCompleteService')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="formCompleteService" action="{{ route('services.complete', $service->id) }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Diagnosis &amp; Root Cause <span class="text-rose-500">*</span></label>
                <textarea name="diagnosis" rows="2" required placeholder="e.g. Sediment filter blocked due to high TDS..." class="form-input text-xs"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Work Performed <span class="text-rose-500">*</span></label>
                <textarea name="work_performed" rows="2" required placeholder="e.g. Replaced sediment filter, cleaned membrane housing, TDS calibrated to 85..." class="form-input text-xs"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Labor / Service Charges (₹)</label>
                    <input type="number" name="labor_charges" value="350" min="0" step="0.01" class="form-input text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Parts Charges (₹)</label>
                    <input type="number" name="parts_charges" value="0" min="0" step="0.01" class="form-input text-sm font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Parts Replaced (Optional)</label>
                <input type="text" name="parts_replaced" placeholder="e.g. 1x Sediment Filter, 1x Inline Carbon" class="form-input text-xs">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Customer Feedback / Remarks</label>
                <input type="text" name="customer_remarks" placeholder="Customer satisfied with water flow and taste" class="form-input text-xs">
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="CRM.closeModal('modalCompleteService')" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary bg-emerald-600 hover:bg-emerald-700 text-white">
                    Submit &amp; Mark Completed
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    CRM.handleAjaxForm('#formAssignTech', function(res) {
        CRM.toast(res.message || 'Technician assigned successfully!');
        setTimeout(() => location.reload(), 600);
    });

    CRM.handleAjaxForm('#formCompleteService', function(res) {
        CRM.toast(res.message || 'Service marked completed!');
        setTimeout(() => location.reload(), 600);
    });
});

function submitHoldServiceAdmin(e) {
    e.preventDefault();
    const reason = $('#adminHoldReason').val() || 'Service put on hold by admin';
    updateAdminStatus('ON_HOLD', reason);
    CRM.closeModal('modalHoldServiceAdmin');
}

function updateAdminStatus(status, reason) {
    $.ajax({
        url: "{{ route('services.status', $service->id) }}",
        type: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            status: status,
            reason: reason || ''
        },
        success: function(res) {
            CRM.toast(res.message || 'Status updated.', 'success');
            setTimeout(() => location.reload(), 500);
        },
        error: function(xhr) {
            const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to update status.';
            CRM.toast(msg, 'error');
        }
    });
}
</script>

<!-- Modal: Hold Service (Admin) -->
<div id="modalHoldServiceAdmin" class="modal-backdrop hidden">
    <div class="modal-box max-w-md">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 text-base flex items-center gap-2 text-amber-600">
                <i data-lucide="pause-circle" class="w-5 h-5"></i>
                <span>Put Service on Hold</span>
            </h3>
            <button onclick="CRM.closeModal('modalHoldServiceAdmin')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form onsubmit="submitHoldServiceAdmin(event)" class="mt-4 space-y-4">
            <p class="text-xs text-slate-600">
                Mark service <span class="font-mono font-bold text-slate-900">{{ $service->service_number }}</span> as ON HOLD. The technician portal and technician panel will update immediately.
            </p>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Reason for Hold <span class="text-rose-500">*</span></label>
                <textarea id="adminHoldReason" rows="3" required class="form-input text-xs" placeholder="e.g. Waiting for spare part delivery, customer rescheduling..."></textarea>
            </div>

            <div class="flex flex-wrap gap-1.5">
                <button type="button" onclick="$('#adminHoldReason').val('Awaiting spare parts from vendor')" class="text-[10px] px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700">Spare Parts Awaited</button>
                <button type="button" onclick="$('#adminHoldReason').val('Customer requested reschedule to a later date')" class="text-[10px] px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700">Customer Reschedule</button>
                <button type="button" onclick="$('#adminHoldReason').val('Pending site approval / Customer premise locked')" class="text-[10px] px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700">Premise Locked</button>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="CRM.closeModal('modalHoldServiceAdmin')" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary bg-amber-600 hover:bg-amber-700 text-white">
                    Confirm Hold
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
