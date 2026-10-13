@extends('layouts.app')

@section('title', 'Warranty ' . $warranty->warranty_number . ' - Enterprises CRM')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto pb-16">
    <!-- Action Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
        <div class="flex items-center gap-3">
            <a href="{{ route('warranties.index') }}" class="btn-outline flex items-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back</span>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="font-bold text-slate-900 text-lg font-mono">{{ $warranty->warranty_number }}</h2>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ $warranty->status }}
                    </span>
                </div>
                <div class="text-xs text-slate-500 mt-0.5">
                    Customer: <span class="font-semibold text-slate-700">{{ $warranty->customer->name ?? 'Walk-in Customer' }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="CRM.openModal('modalLogWarrantyEvent')" class="btn-primary flex items-center gap-2">
                <i data-lucide="shield-alert" class="w-4 h-4"></i>
                <span>Log Claim / Event</span>
            </button>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                <i data-lucide="user" class="w-4 h-4 text-sky-600"></i>
                <span>Customer</span>
            </h3>
            <div>
                <div class="text-base font-bold text-slate-900">{{ $warranty->customer->name ?? 'Direct Customer' }}</div>
                <div class="text-xs text-slate-600 mt-1 space-y-1 font-mono">
                    <div>Phone: {{ $warranty->customer->phone ?? 'N/A' }}</div>
                    <div>ID: {{ $warranty->customer->customer_number ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                <i data-lucide="cpu" class="w-4 h-4 text-teal-600"></i>
                <span>Machine / Asset</span>
            </h3>
            <div>
                <div class="text-base font-bold text-slate-900">{{ $warranty->asset->custom_name ?? ($warranty->asset->product->name ?? 'Equipment / Asset') }}</div>
                <div class="text-xs text-slate-600 mt-1 space-y-1 font-mono">
                    <div>S/N: {{ $warranty->asset->serial_number ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                <i data-lucide="calendar" class="w-4 h-4 text-indigo-600"></i>
                <span>Coverage Period</span>
            </h3>
            <div class="text-xs text-slate-700 space-y-1">
                <div><strong>Duration:</strong> {{ $warranty->duration_months }} Months</div>
                <div class="font-mono"><strong>Start:</strong> {{ \Carbon\Carbon::parse($warranty->start_date)->format('d M Y') }}</div>
                <div class="font-mono"><strong>Expires:</strong> {{ \Carbon\Carbon::parse($warranty->end_date)->format('d M Y') }}</div>
            </div>
        </div>
    </div>

    <!-- Scheduled Maintenance Visits for this Warranty -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-6 space-y-4">
        <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
            <i data-lucide="calendar-check" class="w-5 h-5 text-indigo-600"></i>
            <span>Quarterly Maintenance Schedules</span>
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                        <th class="p-3">Schedule #</th>
                        <th class="p-3">Target Month</th>
                        <th class="p-3">Planned Date</th>
                        <th class="p-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($warranty->schedules as $sch)
                        <tr>
                            <td class="p-3 font-mono font-bold text-slate-900">Visit {{ $sch->schedule_index }} of {{ $sch->total_schedules }}</td>
                            <td class="p-3 font-mono">{{ $sch->target_month }}</td>
                            <td class="p-3 font-mono font-semibold">{{ \Carbon\Carbon::parse($sch->planned_date)->format('d M Y') }}</td>
                            <td class="p-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                    {{ $sch->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-4 text-center text-slate-400">No scheduled visits generated.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Warranty Claim & Event History -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-600"></i>
                <span>Claims &amp; Event History</span>
            </h3>
            <button onclick="CRM.openModal('modalLogWarrantyEvent')" class="btn-outline py-1 px-2.5 text-xs">
                + Log Event
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                        <th class="p-3">Date</th>
                        <th class="p-3">Event Type</th>
                        <th class="p-3">Description</th>
                        <th class="p-3">Resolution</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($warranty->events as $ev)
                        <tr>
                            <td class="p-3 font-mono text-slate-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($ev->event_date)->format('d M Y') }}</td>
                            <td class="p-3 font-bold text-slate-900">{{ $ev->event_type }}</td>
                            <td class="p-3 text-slate-700">{{ $ev->description }}</td>
                            <td class="p-3 text-slate-600">{{ $ev->resolution ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-slate-400">No claims or repair events recorded for this warranty.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Log Warranty Event -->
<div id="modalLogWarrantyEvent" class="modal-backdrop hidden">
    <div class="modal-box max-w-md">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 text-lg flex items-center gap-2">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-600"></i>
                <span>Log Warranty Claim / Event</span>
            </h3>
            <button onclick="CRM.closeModal('modalLogWarrantyEvent')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="formLogWarrantyEvent" action="{{ route('warranties.event', $warranty->id) }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Event Type <span class="text-rose-500">*</span></label>
                <select name="event_type" required class="form-input text-xs">
                    <option value="REPAIR_CLAIM">Breakdown / Repair Claim</option>
                    <option value="PART_REPLACEMENT">Under-Warranty Part Replacement</option>
                    <option value="INSPECTION">Inspection Visit</option>
                    <option value="WARRANTY_EXTENDED">Warranty Term Extended</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Issue Description <span class="text-rose-500">*</span></label>
                <textarea name="description" rows="2" required placeholder="Describe customer claim or breakdown..." class="form-input text-xs"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Resolution / Action Taken</label>
                <textarea name="resolution" rows="2" placeholder="Repairs done, parts provided under warranty..." class="form-input text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="CRM.closeModal('modalLogWarrantyEvent')" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary">Save Event</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    CRM.handleAjaxForm('#formLogWarrantyEvent', function(res) {
        CRM.toast(res.message || 'Warranty claim logged!');
        setTimeout(() => location.reload(), 600);
    });
});
</script>
@endsection
