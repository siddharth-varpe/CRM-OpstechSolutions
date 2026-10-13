@extends('layouts.app')

@section('title', 'Job Cards - Enterprises CRM')

@section('content')
<div class="space-y-6 max-w-[1600px] mx-auto pb-12 select-none">
    <!-- 1. Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center shadow-xs shrink-0">
                <i data-lucide="file-check" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Job Cards &amp; Work Orders</h1>
                <p class="text-xs sm:text-sm text-slate-500 font-normal">
                    Track field work orders, technician diagnoses, replaced parts and sign-offs
                </p>
            </div>
        </div>
    </div>

    <!-- 2. Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs">
        <form method="GET" action="{{ route('job_cards.index') }}" class="flex flex-col md:flex-row gap-3 items-center justify-between">
            <div class="relative w-full md:w-96">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search job card #, customer name, phone..."
                       class="form-input pl-10">
            </div>

            <div class="flex items-center gap-2.5 w-full md:w-auto">
                <select name="status" onchange="this.form.submit()" class="form-input text-xs w-full md:w-44">
                    <option value="ALL">All Statuses</option>
                    <option value="SCHEDULED" {{ request('status') === 'SCHEDULED' ? 'selected' : '' }}>Scheduled</option>
                    <option value="ASSIGNED" {{ request('status') === 'ASSIGNED' ? 'selected' : '' }}>Assigned</option>
                    <option value="IN_PROGRESS" {{ request('status') === 'IN_PROGRESS' ? 'selected' : '' }}>In Progress</option>
                    <option value="COMPLETED" {{ request('status') === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                    <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                </select>

                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('job_cards.index') }}" class="btn-outline text-xs whitespace-nowrap">
                        Reset Filters
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- 3. Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Job Card #</th>
                        <th class="py-3.5 px-4">Customer</th>
                        <th class="py-3.5 px-4">Machine / Asset</th>
                        <th class="py-3.5 px-4">Technician</th>
                        <th class="py-3.5 px-4 text-right">Job Charges (₹)</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($jobCards as $jc)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3.5 px-4">
                                <div class="flex items-start gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200/80 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <a href="{{ route('job_cards.show', $jc->id) }}" class="font-bold text-slate-900 hover:text-indigo-600 font-mono flex items-center gap-1">
                                            {{ $jc->job_card_number }}
                                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 text-slate-400"></i>
                                        </a>
                                        <div class="text-[11px] text-slate-500 mt-0.5 font-mono">
                                            {{ $jc->service->service_number ?? '' }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div>
                                    <a href="{{ $jc->customer_id ? route('customers.show', $jc->customer_id) : '#' }}" class="font-semibold text-slate-900 hover:text-sky-600 flex items-center gap-1">
                                        <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                        {{ $jc->customer->name ?? 'Direct Customer' }}
                                    </a>
                                    <div class="text-xs text-slate-500 mt-0.5 font-mono">
                                        {{ $jc->customer->phone ?? 'N/A' }}
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-medium text-slate-800 text-xs">
                                    {{ $jc->asset->custom_name ?? $jc->asset->product->name ?? 'Equipment / Unit' }}
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5 font-mono">
                                    S/N: {{ $jc->asset->serial_number ?? 'N/A' }}
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                @if($jc->technician)
                                    <div class="font-semibold text-slate-900 text-xs flex items-center gap-1">
                                        <i data-lucide="user-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                        {{ $jc->technician->full_name }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-mono">{{ $jc->technician->phone }}</div>
                                @else
                                    <span class="text-xs text-amber-600 italic">Unassigned</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono text-xs font-bold text-slate-900">
                                ₹{{ number_format($jc->total_charges, 2) }}
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                @php
                                    $statusColors = [
                                        'COMPLETED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'IN_PROGRESS' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'ASSIGNED' => 'bg-sky-50 text-sky-700 border-sky-200',
                                        'SCHEDULED' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        'CANCELLED' => 'bg-slate-100 text-slate-600 border-slate-200',
                                    ];
                                    $st = $statusColors[$jc->status] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $st }}">
                                    {{ $jc->status }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('job_cards.show', $jc->id) }}" class="btn-outline py-1 px-3 text-xs inline-flex items-center gap-1.5">
                                    <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-500"></i>
                                    <span>Print Card</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i data-lucide="file-question" class="w-10 h-10 text-slate-300 mb-2"></i>
                                    <p class="font-medium">No job cards found</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($jobCards->hasPages())
            <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                {{ $jobCards->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
