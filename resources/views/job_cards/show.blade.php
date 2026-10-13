@extends('layouts.app')

@section('title', 'Job Card ' . $jobCard->job_card_number . ' - Enterprises CRM')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto pb-16">
    <!-- Action Toolbar (hidden on print) -->
    <div class="no-print flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
        <div class="flex items-center gap-3">
            <a href="{{ route('job_cards.index') }}" class="btn-outline flex items-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back</span>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="font-bold text-slate-900 text-lg font-mono">{{ $jobCard->job_card_number }}</h2>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        {{ $jobCard->status }}
                    </span>
                </div>
                <div class="text-xs text-slate-500 mt-0.5">
                    Service: <span class="font-mono font-semibold">{{ $jobCard->service->service_number ?? '' }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="btn-primary flex items-center gap-2">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Print Job Card</span>
            </button>
            @if($jobCard->service)
                <a href="{{ route('services.show', $jobCard->service_id) }}" class="btn-outline flex items-center gap-2">
                    <i data-lucide="wrench" class="w-4 h-4"></i>
                    <span>Service Visit</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Official Printable Job Card Document -->
    <div id="printableJobCard" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 sm:p-10 font-sans text-slate-800">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start gap-6 border-b border-slate-200 pb-6">
            <div class="flex items-start gap-4">
                <img src="/assets/crm-logo.png" alt="Logo" class="h-16 w-auto object-contain">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight uppercase">{{ \App\Models\SystemSetting::get('company_name', 'ENTERPRISES CRM') }}</h1>
                    <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                        Authorized Equipment Sales, Maintenance &amp; Field Services<br>
                        {{ \App\Models\SystemSetting::get('company_address', 'Plot No. 42, Industrial Area, Phase II, Pune - 411019, Maharashtra') }}<br>
                        <strong>Helpdesk:</strong> {{ \App\Models\SystemSetting::get('company_phone', \App\Models\SystemSetting::get('support_phone', '+91 98765 43210')) }} • <strong>GSTIN:</strong> {{ \App\Models\SystemSetting::get('company_gstin', \App\Models\SystemSetting::get('gst_number', '27AABCS1429B1Z')) }}
                    </p>
                </div>
            </div>

            <div class="text-left sm:text-right">
                <div class="inline-block bg-indigo-50 border border-indigo-200 text-indigo-800 text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-md mb-2">
                    FIELD SERVICE JOB CARD
                </div>
                <div class="text-xl font-bold font-mono text-slate-900">{{ $jobCard->job_card_number }}</div>
                <div class="text-xs text-slate-500 mt-1 font-mono">
                    <strong>Service Ref:</strong> {{ $jobCard->service->service_number ?? 'N/A' }}
                </div>
                <div class="text-xs text-slate-500 font-mono">
                    <strong>Date:</strong> {{ date('d F Y', strtotime($jobCard->created_at)) }}
                </div>
            </div>
        </div>

        <!-- Customer & Machine Overview Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 py-6 border-b border-slate-200 text-xs">
            <div>
                <span class="font-bold text-slate-500 uppercase tracking-wider block mb-1">Customer Information:</span>
                <div class="text-base font-bold text-slate-900">{{ $jobCard->customer->name ?? 'Direct Customer' }}</div>
                <div class="text-slate-600 mt-1 leading-relaxed">
                    @if($jobCard->customer && $jobCard->customer->addresses->isNotEmpty())
                        @php $addr = $jobCard->customer->addresses->first(); @endphp
                        {{ $addr->address_line1 }}<br>
                        {{ $addr->city }}, {{ $addr->state }} - {{ $addr->pincode }}<br>
                    @endif
                    <strong>Phone:</strong> {{ $jobCard->customer->phone ?? 'N/A' }}<br>
                    @if($jobCard->customer && $jobCard->customer->customer_number)
                        <strong>Customer ID:</strong> {{ $jobCard->customer->customer_number }}
                    @endif
                </div>
            </div>

            <div>
                <span class="font-bold text-slate-500 uppercase tracking-wider block mb-1">Equipment / Machine Details:</span>
                <div class="text-base font-bold text-slate-900">
                    {{ $jobCard->asset->custom_name ?? ($jobCard->asset->product->name ?? 'Equipment / Machine System') }}
                </div>
                <div class="text-slate-600 mt-1 leading-relaxed font-mono">
                    <strong>Serial Number:</strong> {{ $jobCard->asset->serial_number ?? 'N/A' }}<br>
                    @if($jobCard->asset && $jobCard->asset->product)
                        <strong>Model/SKU:</strong> {{ $jobCard->asset->product->sku }}<br>
                    @endif
                    <strong>Assigned Tech:</strong> {{ $jobCard->technician->full_name ?? 'Field Technician' }} ({{ $jobCard->technician->phone ?? '' }})
                </div>
            </div>
        </div>

        <!-- Problem, Diagnosis & Action Taken -->
        <div class="py-6 border-b border-slate-200 space-y-4 text-xs">
            <div>
                <span class="font-bold text-slate-500 uppercase tracking-wider block mb-1">Customer Reported Problem:</span>
                <p class="text-slate-900 bg-slate-50 p-3 rounded-lg border border-slate-200/80">
                    {{ $jobCard->problem_reported ?? 'Routine preventive maintenance and filter check.' }}
                </p>
            </div>

            <div>
                <span class="font-bold text-slate-500 uppercase tracking-wider block mb-1">Technician Diagnosis / Root Cause:</span>
                <p class="text-slate-900 bg-slate-50 p-3 rounded-lg border border-slate-200/80">
                    {{ $jobCard->diagnosis ?? 'Standard inspection carried out on membrane pressure, pump voltage, and filter condition.' }}
                </p>
            </div>

            <div>
                <span class="font-bold text-slate-500 uppercase tracking-wider block mb-1">Work Performed / Corrective Action:</span>
                <p class="text-slate-900 bg-slate-50 p-3 rounded-lg border border-slate-200/80">
                    {{ $jobCard->work_performed ?? 'Service visit in execution.' }}
                </p>
            </div>

            @if($jobCard->parts_replaced)
                <div>
                    <span class="font-bold text-slate-500 uppercase tracking-wider block mb-1">Spare Parts Installed:</span>
                    <p class="text-slate-900 bg-slate-50 p-3 rounded-lg border border-slate-200/80 font-mono">
                        {{ is_array($jobCard->parts_replaced) ? implode(', ', $jobCard->parts_replaced) : $jobCard->parts_replaced }}
                    </p>
                </div>
            @endif
        </div>

        <!-- Financial Summary -->
        <div class="py-6 border-b border-slate-200">
            <div class="flex justify-between items-center text-xs">
                <div class="space-y-1 text-slate-600">
                    <div><strong>Labor Charges:</strong> ₹{{ number_format($jobCard->labor_charges, 2) }}</div>
                    <div><strong>Parts &amp; Components:</strong> ₹{{ number_format($jobCard->parts_charges, 2) }}</div>
                </div>

                <div class="bg-indigo-50 border border-indigo-200 p-4 rounded-xl text-right">
                    <span class="text-xs uppercase font-bold text-indigo-900 block">Total Job Amount</span>
                    <span class="text-2xl font-black font-mono text-indigo-700">₹{{ number_format($jobCard->total_charges, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Physical Sign-off Section -->
        <div class="pt-8 grid grid-cols-2 gap-12 text-xs">
            <div class="border border-dashed border-slate-300 rounded-xl p-6 text-center">
                <div class="h-20 flex items-end justify-center">
                    <div class="w-48 border-b border-slate-900"></div>
                </div>
                <div class="font-bold text-slate-900 mt-2">Technician Signature</div>
                <div class="text-slate-500">{{ $jobCard->technician->full_name ?? 'Service Executive' }}</div>
            </div>

            <div class="border border-dashed border-slate-300 rounded-xl p-6 text-center">
                <div class="h-20 flex items-end justify-center">
                    <div class="w-48 border-b border-slate-900"></div>
                </div>
                <div class="font-bold text-slate-900 mt-2">Customer Signature &amp; Stamp</div>
                <div class="text-slate-500">"Work completed to my full satisfaction"</div>
            </div>
        </div>
    </div>
</div>
@endsection
