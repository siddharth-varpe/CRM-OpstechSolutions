@extends('layouts.app')

@section('title', 'Warranty & AMC Management - Enterprises CRM')

@section('content')
<div class="space-y-6 max-w-[1600px] mx-auto pb-12 select-none">
    <!-- 1. Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shadow-xs shrink-0">
                <i data-lucide="shield-check" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Warranty &amp; AMC Management</h1>
                <p class="text-xs sm:text-sm text-slate-500 font-normal">
                    Monitor customer equipment warranties, extended AMC coverage and renewal timelines
                </p>
            </div>
        </div>
    </div>

    <!-- 2. KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Warranties</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-emerald-600 font-mono">
                {{ $totalActive }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Currently protected systems</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Expiring in 30 Days</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-amber-600 font-mono">
                {{ $expiringSoon }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Target for AMC contract renewals</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Expired Warranties</span>
                <div class="w-8 h-8 rounded-lg bg-slate-50 text-slate-600 flex items-center justify-center">
                    <i data-lucide="shield-alert" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-slate-700 font-mono">
                {{ $totalExpired }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Out-of-warranty coverage</div>
        </div>
    </div>

    <!-- 3. Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs">
        <form method="GET" action="{{ route('warranties.index') }}" class="flex flex-col md:flex-row gap-3 items-center justify-between">
            <div class="relative w-full md:w-96">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search warranty #, customer name, serial #..."
                       class="form-input pl-10">
            </div>

            <div class="flex items-center gap-2.5 w-full md:w-auto">
                <select name="status" onchange="this.form.submit()" class="form-input text-xs w-full md:w-44">
                    <option value="ALL">All Statuses</option>
                    <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                    <option value="EXPIRED" {{ request('status') === 'EXPIRED' ? 'selected' : '' }}>Expired</option>
                    <option value="VOIDED" {{ request('status') === 'VOIDED' ? 'selected' : '' }}>Voided</option>
                </select>

                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('warranties.index') }}" class="btn-outline text-xs whitespace-nowrap">
                        Reset Filters
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- 4. Warranties Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Warranty #</th>
                        <th class="py-3.5 px-4">Customer</th>
                        <th class="py-3.5 px-4">Asset / Machine</th>
                        <th class="py-3.5 px-4">Coverage Duration</th>
                        <th class="py-3.5 px-4">Valid Until</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Remaining</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($warranties as $w)
                        @php
                            $now = now();
                            $endDate = \Carbon\Carbon::parse($w->end_date);
                            $diffDays = $now->diffInDays($endDate, false);
                            $isExpired = $diffDays < 0;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3.5 px-4">
                                <div class="flex items-start gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/80 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                                        <i data-lucide="shield" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 font-mono">{{ $w->warranty_number }}</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">
                                            {{ $w->warranty_type ?? 'STANDARD' }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div>
                                    <a href="{{ $w->customer_id ? route('customers.show', $w->customer_id) : '#' }}" class="font-semibold text-slate-900 hover:text-sky-600 flex items-center gap-1">
                                        <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                        {{ $w->customer->name ?? 'Walk-in Customer' }}
                                    </a>
                                    <div class="text-xs text-slate-500 mt-0.5 font-mono">
                                        {{ $w->customer->phone ?? 'N/A' }}
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-medium text-slate-800 text-xs">
                                    {{ $w->asset->custom_name ?? ($w->asset->product->name ?? 'Equipment / Asset') }}
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5 font-mono">
                                    S/N: {{ $w->asset->serial_number ?? 'N/A' }}
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-xs font-mono">
                                {{ $w->duration_months }} Months
                            </td>

                            <td class="py-3.5 px-4 font-mono text-xs">
                                <div class="font-semibold {{ $isExpired ? 'text-slate-400' : 'text-slate-900' }}">
                                    {{ $endDate->format('d M Y') }}
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    From {{ \Carbon\Carbon::parse($w->start_date)->format('d M Y') }}
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                @php
                                    $badge = $w->status === 'ACTIVE'
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        : 'bg-slate-100 text-slate-600 border-slate-200';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $badge }}">
                                    {{ $w->status }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono text-xs font-bold">
                                @if(!$isExpired && $w->status === 'ACTIVE')
                                    <span class="{{ $diffDays <= 30 ? 'text-amber-600' : 'text-emerald-600' }}">
                                        {{ ceil($diffDays) }} days left
                                    </span>
                                @else
                                    <span class="text-slate-400 font-normal">Expired</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i data-lucide="shield-alert" class="w-10 h-10 text-slate-300 mb-2"></i>
                                    <p class="font-medium">No warranty records found</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($warranties->hasPages())
            <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                {{ $warranties->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
