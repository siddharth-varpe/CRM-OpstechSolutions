@extends('layouts.app')

@section('title', 'Services & Field Visits - Enterprises CRM')

@section('content')
<div class="space-y-6 max-w-[1600px] mx-auto pb-12 select-none">
    <!-- 1. Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center shadow-xs shrink-0">
                <i data-lucide="wrench" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Services &amp; Field Visits</h1>
                <p class="text-xs sm:text-sm text-slate-500 font-normal">
                    Schedule customer appointments, assign field technicians &amp; execute job cards
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <button onclick="CRM.openModal('modalScheduleService')" class="btn-primary flex items-center gap-2">
                <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                <span>Schedule Service</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Scheduled / Assigned</span>
                <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                    <i data-lucide="calendar-clock" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-sky-600 font-mono">
                {{ $totalScheduled }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Upcoming appointments</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">In Progress</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="cog" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-amber-600 font-mono">
                {{ $totalInProgress }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Technician currently on site</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Completed</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-emerald-600 font-mono">
                {{ $totalCompleted }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Successfully closed visits</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Overdue</span>
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-rose-600 font-mono">
                {{ $totalOverdue }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Passed scheduled visit time</div>
        </div>
    </div>

    <!-- 2.5 Service Schedule Heatmap & Month Calendar -->
    <div id="serviceHeatmapContainer" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <!-- Calendar Header Row -->
        <div class="p-3.5 sm:p-4 border-b border-slate-200/80 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-slate-50/50">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center shrink-0">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Daily Service Schedule Heatmap</span>
                        <span id="calendarTotalBadge" class="text-[11px] px-2 py-0.5 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 font-mono font-bold">0 visits</span>
                    </h3>
                    <p class="text-[11px] text-slate-500">Visual service density heatmap · Click any date to view scheduled field visits below</p>
                </div>
            </div>

            <!-- Heatmap Legend & Month Controls -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Heatmap Color Intensity Legend -->
                <div class="flex items-center gap-1.5 text-[10px] text-slate-500 font-medium">
                    <span class="mr-1 text-slate-400">Intensity:</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-slate-100 border border-slate-200 inline-block"></span> 0</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-sky-50 border border-sky-200 inline-block"></span> 1-2</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-sky-200 border border-sky-300 inline-block"></span> 3-4</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-sky-600 border border-sky-700 text-white inline-block"></span> 5+</span>
                </div>

                <!-- Navigation Controls -->
                <div class="inline-flex items-center bg-white border border-slate-200 rounded-lg p-0.5 shadow-2xs">
                    <button type="button" id="btnCalPrevMonth" class="p-1 rounded-md hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition" title="Previous Month">
                        <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                    </button>
                    <span id="calMonthLabel" class="px-2 font-display font-bold text-[11px] text-slate-900 uppercase tracking-wider min-w-[110px] text-center">
                        {{ $heatmapData['month_name'] }}
                    </span>
                    <button type="button" id="btnCalNextMonth" class="p-1 rounded-md hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition" title="Next Month">
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </button>
                    <button type="button" id="btnCalToday" class="ml-1 px-2 py-0.5 text-[10px] font-bold text-indigo-600 hover:bg-indigo-50 rounded-md transition border-l border-slate-200">
                        Today
                    </button>
                </div>
            </div>
        </div>

        <!-- Month Calendar Grid Container -->
        <div class="p-3 sm:p-4">
            <!-- Weekday Headers -->
            <div class="grid grid-cols-7 gap-1 mb-1 text-center">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 py-0.5">Mon</div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 py-0.5">Tue</div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 py-0.5">Wed</div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 py-0.5">Thu</div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 py-0.5">Fri</div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 py-0.5">Sat</div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-rose-400 py-0.5">Sun</div>
            </div>

            <!-- Calendar Days Grid -->
            <div id="calendarDaysGrid" class="grid grid-cols-7 gap-1">
                <!-- Dynamically populated via JS and initial payload -->
            </div>
        </div>

        <!-- Selected Date Services Drill-Down Panel (shown below calendar) -->
        <div id="selectedDateServicesSection" class="border-t border-slate-200/90 bg-slate-50/70 p-3 sm:p-4 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-md bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                        <i data-lucide="calendar-check" class="w-3.5 h-3.5"></i>
                    </div>
                    <div>
                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2">
                            <span>Services for <span id="selectedDateTitle" class="text-indigo-600">Selected Date</span></span>
                            <span id="selectedDateCountBadge" class="text-[11px] px-2 py-0.5 rounded-full bg-slate-200 text-slate-800 font-mono font-semibold">0 visits</span>
                        </h4>
                        <p class="text-[10px] text-slate-500">Inspections, PMs, and emergency visits scheduled on this date</p>
                    </div>
                </div>

                <button type="button" id="btnScheduleForDate" onclick="openScheduleForSelectedDate()" class="btn-outline text-[11px] py-1 px-2.5 inline-flex items-center gap-1.5 bg-white shadow-2xs">
                    <i data-lucide="calendar-plus" class="w-3 h-3 text-indigo-600"></i>
                    <span>+ Schedule for this date</span>
                </button>
            </div>

            <!-- Services List Container -->
            <div id="selectedDateServicesList" class="space-y-2">
                <!-- Dynamically populated when a date is clicked -->
            </div>
        </div>
    </div>

    <!-- 3. Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs">
        <form method="GET" action="{{ route('services.index') }}" class="flex flex-col md:flex-row gap-3 items-center justify-between">
            <div class="relative w-full md:w-96">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search service #, customer, serial #..."
                       class="form-input pl-10">
            </div>

            <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                <select name="status" onchange="this.form.submit()" class="form-input text-xs w-full md:w-36">
                    <option value="ALL">All Statuses</option>
                    <option value="SCHEDULED" {{ request('status') === 'SCHEDULED' ? 'selected' : '' }}>Scheduled</option>
                    <option value="ASSIGNED" {{ request('status') === 'ASSIGNED' ? 'selected' : '' }}>Assigned</option>
                    <option value="IN_PROGRESS" {{ request('status') === 'IN_PROGRESS' ? 'selected' : '' }}>In Progress</option>
                    <option value="ON_HOLD" {{ request('status') === 'ON_HOLD' ? 'selected' : '' }}>On Hold</option>
                    <option value="COMPLETED" {{ request('status') === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                    <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                </select>

                <select name="technician_id" onchange="this.form.submit()" class="form-input text-xs w-full md:w-44">
                    <option value="">All Technicians</option>
                    @foreach($technicians as $t)
                        <option value="{{ $t->id }}" {{ request('technician_id') == $t->id ? 'selected' : '' }}>
                            {{ $t->full_name }}
                        </option>
                    @endforeach
                </select>

                @if(request()->hasAny(['search', 'status', 'technician_id']))
                    <a href="{{ route('services.index') }}" class="btn-outline text-xs whitespace-nowrap">
                        Reset Filters
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- 4. Services Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Service #</th>
                        <th class="py-3.5 px-4">Customer</th>
                        <th class="py-3.5 px-4">Asset / Machine</th>
                        <th class="py-3.5 px-4">Scheduled For</th>
                        <th class="py-3.5 px-4">Technician</th>
                        <th class="py-3.5 px-4 text-center">Priority</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($services as $s)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3.5 px-4">
                                <div class="flex items-start gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200/80 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                                        <i data-lucide="wrench" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <a href="{{ route('services.show', $s->id) }}" class="font-bold text-slate-900 hover:text-indigo-600 font-mono flex items-center gap-1">
                                            {{ $s->service_number }}
                                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 text-slate-400"></i>
                                        </a>
                                        <div class="text-[11px] text-slate-500 mt-0.5 uppercase tracking-wider font-semibold">
                                            {{ str_replace('_', ' ', $s->service_type) }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div>
                                    <a href="{{ $s->customer_id ? route('customers.show', $s->customer_id) : '#' }}" class="font-semibold text-slate-900 hover:text-sky-600 flex items-center gap-1">
                                        <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                        {{ $s->customer->name ?? 'Direct Customer' }}
                                    </a>
                                    <div class="text-xs text-slate-500 mt-0.5 font-mono">
                                        {{ $s->customer->phone ?? 'N/A' }}
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-medium text-slate-800 text-xs">
                                    {{ $s->asset->custom_name ?? $s->asset->product->name ?? 'Machine / Asset' }}
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5 font-mono">
                                    S/N: {{ $s->asset->serial_number ?? 'N/A' }}
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="text-xs font-mono font-semibold text-slate-900">
                                    {{ \Carbon\Carbon::parse($s->scheduled_date)->format('d M Y') }}
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    {{ $s->scheduled_time_slot ?? 'Flexible Slot' }}
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                @if($s->technician)
                                    <div class="font-semibold text-slate-900 text-xs flex items-center gap-1">
                                        <i data-lucide="user-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                        {{ $s->technician->full_name }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-mono">{{ $s->technician->phone }}</div>
                                @else
                                    <span class="text-xs text-amber-600 italic font-medium">Unassigned</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                @php
                                    $prioColors = [
                                        'URGENT' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        'HIGH' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'NORMAL' => 'bg-slate-50 text-slate-700 border-slate-200',
                                        'LOW' => 'bg-slate-50 text-slate-500 border-slate-200',
                                    ];
                                    $prio = $prioColors[$s->priority] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $prio }}">
                                    {{ $s->priority }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                @php
                                    $statusColors = [
                                        'COMPLETED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'IN_PROGRESS' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'ON_HOLD' => 'bg-amber-100 text-amber-800 border-amber-300',
                                        'ASSIGNED' => 'bg-sky-50 text-sky-700 border-sky-200',
                                        'SCHEDULED' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        'CANCELLED' => 'bg-slate-100 text-slate-600 border-slate-200',
                                    ];
                                    $st = $statusColors[$s->status] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $st }}">
                                    @if($s->status === 'ON_HOLD')
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    @endif
                                    {{ $s->status === 'ON_HOLD' ? 'ON HOLD' : $s->status }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <a href="{{ route('services.show', $s->id) }}" class="btn-outline py-1 px-2.5 text-xs inline-flex items-center gap-1" title="View Details">
                                        <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-500"></i>
                                        <span>Details</span>
                                    </a>
                                    <button type="button" 
                                            onclick="confirmDeleteService('{{ $s->id }}', '{{ $s->service_number }}')" 
                                            class="btn-outline py-1 px-2 text-xs inline-flex items-center gap-1 text-rose-600 hover:text-rose-700 hover:bg-rose-50 border-rose-200 transition-colors" 
                                            title="Delete Scheduled Service">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i data-lucide="wrench" class="w-10 h-10 text-slate-300 mb-2"></i>
                                    <p class="font-medium">No service visits found</p>
                                    <p class="text-xs text-slate-400 mt-1">Schedule service visits to dispatch technicians and maintain installations.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($services->hasPages())
            <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                {{ $services->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: Schedule New Service -->
<div id="modalScheduleService" class="modal-backdrop hidden">
    <div class="modal-box max-w-lg">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 text-lg flex items-center gap-2">
                <i data-lucide="calendar-plus" class="w-5 h-5 text-indigo-600"></i>
                <span>Schedule Service Visit</span>
            </h3>
            <button onclick="CRM.closeModal('modalScheduleService')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="formScheduleService" action="{{ route('services.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                    Customer <span class="text-rose-500">*</span>
                </label>

                <!-- Search Bar for Registered Customers -->
                <div class="relative mb-2">
                    <input type="text" 
                           id="serviceCustomerSearch" 
                           placeholder="Search customer by name, phone, or customer #..." 
                           autocomplete="off"
                           class="form-input text-sm pl-9 pr-8"
                           aria-label="Search registered customers">
                    <div class="absolute left-3 top-2.5 text-slate-400 pointer-events-none">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <button type="button" 
                            id="btnClearServiceCustomerSearch" 
                            class="hidden absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 focus:outline-none"
                            title="Clear search">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- Customer Dropdown just below it -->
                <select name="customer_id" id="serviceCustomerSelect" required class="form-input text-sm">
                    <option value="">Select Customer</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}"
                                data-search="{{ strtolower(($c->full_name ?? $c->name) . ' ' . $c->phone . ' ' . ($c->customer_number ?? '') . ' ' . ($c->company_name ?? '')) }}"
                                {{ (request('customer_id') == $c->id) ? 'selected' : '' }}>
                            {{ $c->full_name ?? $c->name }} ({{ $c->phone }}){{ $c->company_name ? ' - ' . $c->company_name : ($c->customer_number ? ' [' . $c->customer_number . ']' : '') }}
                        </option>
                    @endforeach
                </select>
                <div id="serviceCustomerSearchHelp" class="text-[11px] text-slate-500 mt-1 hidden"></div>
            </div>

            <!-- Machine / Asset Selection & Direct Quick-Add -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-slate-700">
                        Machine / Asset <span class="text-rose-500">*</span>
                    </label>
                    <span id="assetStatusBadge" class="text-[11px] font-medium text-slate-500"></span>
                </div>

                <!-- Existing Customer Assets Dropdown -->
                <div>
                    <select name="asset_id" id="serviceAssetSelect" class="form-input text-sm">
                        <option value="">Select a customer first to view machines</option>
                    </select>
                </div>

                <!-- Direct Asset Input Field (Admin can directly type asset name) -->
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label for="customAssetNameInput" class="text-xs font-semibold text-slate-700 flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Or type asset / machine name directly:</span>
                        </label>
                        <span class="text-[10px] font-bold text-indigo-600 bg-indigo-50 border border-indigo-200 px-1.5 py-0.5 rounded">Admin Quick Add</span>
                    </div>
                    <div class="relative">
                        <input type="text" 
                               name="custom_asset_name" 
                               id="customAssetNameInput" 
                               placeholder="e.g. Commercial Unit / 50 LPH Plant" 
                               class="form-input text-sm pl-8 bg-white">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="wrench" class="w-3.5 h-3.5"></i>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-500">
                        If the customer has no registered machines or has a different unit, typing here will automatically create and attach it for this service.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Service Type <span class="text-rose-500">*</span></label>
                    <select name="service_type" required class="form-input text-xs">
                        <option value="PERIODIC_MAINTENANCE">Periodic Maintenance</option>
                        <option value="INSTALLATION">New Installation</option>
                        <option value="REPAIR">Repair / Breakdown</option>
                        <option value="SPARE_REPLACEMENT">Spare Replacement</option>
                        <option value="EMERGENCY">Emergency Visit</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Priority</label>
                    <select name="priority" class="form-input text-xs">
                        <option value="NORMAL">Normal</option>
                        <option value="HIGH">High</option>
                        <option value="URGENT">Urgent</option>
                        <option value="LOW">Low</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Scheduled Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="scheduled_date" value="{{ date('Y-m-d') }}" required class="form-input text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Time Slot</label>
                    <select name="scheduled_time_slot" class="form-input text-xs">
                        <option value="10:00 AM - 01:00 PM">Morning (10:00 AM - 01:00 PM)</option>
                        <option value="02:00 PM - 05:00 PM">Afternoon (02:00 PM - 05:00 PM)</option>
                        <option value="05:00 PM - 08:00 PM">Evening (05:00 PM - 08:00 PM)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Assign Technician (Optional)</label>
                <select name="technician_id" class="form-input text-xs">
                    <option value="">Assign Later</option>
                    @foreach($technicians as $t)
                        <option value="{{ $t->id }}">{{ $t->full_name }} ({{ $t->phone }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Customer Reported Issue / Notes</label>
                <textarea name="customer_notes" rows="2" placeholder="e.g. Filter clogging, low water output, leakage near pump..." class="form-input text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="CRM.closeModal('modalScheduleService')" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Confirm Schedule</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Global Heatmap Calendar State
window.HEATMAP_STATE = {
    year: {{ $heatmapData['year'] }},
    month: {{ $heatmapData['month'] }},
    data: @json($heatmapData),
    selectedDate: null
};

function getTodayDateString() {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function formatDateDisplay(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    const d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
    return d.toLocaleDateString('en-GB', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
}

function renderHeatmapCalendar(data) {
    const year = data.year;
    const month = data.month;
    const daysInMonth = data.days_in_month;
    const firstDayOfWeek = data.first_day_of_week; // 1 = Monday, 7 = Sunday
    const counts = data.counts || {};
    const services = data.services || {};
    const todayStr = getTodayDateString();

    // Update Header labels
    $('#calMonthLabel').text(data.month_name);
    
    let totalMonthVisits = 0;
    Object.values(counts).forEach(c => totalMonthVisits += c);
    $('#calendarTotalBadge').text(`${totalMonthVisits} ${totalMonthVisits === 1 ? 'visit' : 'visits'}`);

    const $grid = $('#calendarDaysGrid');
    $grid.empty();

    // 1. Previous month padding days
    const leadingEmptyDays = firstDayOfWeek - 1;
    for (let i = 0; i < leadingEmptyDays; i++) {
        $grid.append(`
            <div class="h-16 sm:h-[76px] p-1.5 rounded-lg bg-slate-50/30 border border-slate-100/60 opacity-40 select-none pointer-events-none"></div>
        `);
    }

    // 2. Month Days
    for (let day = 1; day <= daysInMonth; day++) {
        const dayFormatted = String(day).padStart(2, '0');
        const monthFormatted = String(month).padStart(2, '0');
        const dateStr = `${year}-${monthFormatted}-${dayFormatted}`;
        const count = counts[dateStr] || 0;
        const isToday = (dateStr === todayStr);
        const isSelected = (HEATMAP_STATE.selectedDate === dateStr);

        // Heatmap color intensity class
        let cellColorClass = 'bg-slate-50/80 border-slate-200/80 text-slate-700 hover:bg-slate-100';
        let countPillHtml = `<span class="text-[9px] text-slate-300 font-mono">-</span>`;

        if (count >= 5) {
            cellColorClass = 'bg-sky-600 border-sky-700 text-white font-bold hover:bg-sky-700 shadow-xs';
            countPillHtml = `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-white text-sky-800 font-mono">${count} visits</span>`;
        } else if (count >= 3) {
            cellColorClass = 'bg-sky-200 border-sky-300 text-sky-950 font-bold hover:bg-sky-300 shadow-2xs';
            countPillHtml = `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-sky-400 text-sky-950 font-mono">${count} visits</span>`;
        } else if (count >= 1) {
            cellColorClass = 'bg-sky-50 border-sky-200 text-sky-900 font-medium hover:bg-sky-100 shadow-2xs';
            countPillHtml = `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-sky-200/80 text-sky-800 font-mono">${count} ${count === 1 ? 'visit' : 'visits'}</span>`;
        }

        const todayIndicatorHtml = isToday 
            ? `<span class="inline-flex items-center text-[8px] font-extrabold uppercase px-1 py-0.5 rounded ${count >= 5 ? 'bg-sky-800 text-white' : 'bg-indigo-600 text-white'}">Today</span>` 
            : '';

        const selectedRingClass = isSelected ? 'ring-2 ring-indigo-600 ring-offset-2 z-10 shadow-md scale-[1.02]' : '';

        const $cell = $(`
            <div class="cal-day-cell h-16 sm:h-[76px] p-1.5 rounded-lg border flex flex-col justify-between transition-all cursor-pointer relative ${cellColorClass} ${selectedRingClass}" 
                 data-date="${dateStr}" 
                 data-count="${count}"
                 title="${count} services scheduled on ${formatDateDisplay(dateStr)}">
                <div class="flex items-center justify-between w-full">
                    <span class="text-xs font-bold font-mono">${day}</span>
                    ${todayIndicatorHtml}
                </div>
                <div class="flex items-end justify-between w-full mt-auto">
                    ${countPillHtml}
                    ${count > 0 ? '<span class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse"></span>' : ''}
                </div>
            </div>
        `);

        $cell.on('click', function() {
            selectHeatmapDate(dateStr);
        });

        $grid.append($cell);
    }

    // 3. Trailing padding cells to complete weekly rows
    const totalRendered = leadingEmptyDays + daysInMonth;
    const trailingEmptyDays = (7 - (totalRendered % 7)) % 7;
    for (let i = 0; i < trailingEmptyDays; i++) {
        $grid.append(`
            <div class="h-16 sm:h-[76px] p-1.5 rounded-lg bg-slate-50/30 border border-slate-100/60 opacity-40 select-none pointer-events-none"></div>
        `);
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function selectHeatmapDate(dateStr) {
    HEATMAP_STATE.selectedDate = dateStr;

    // Highlight selected cell
    $('.cal-day-cell').removeClass('ring-2 ring-indigo-600 ring-offset-2 z-10 shadow-md scale-[1.02]');
    $(`.cal-day-cell[data-date="${dateStr}"]`).addClass('ring-2 ring-indigo-600 ring-offset-2 z-10 shadow-md scale-[1.02]');

    // Update panel header
    const formattedTitle = formatDateDisplay(dateStr);
    $('#selectedDateTitle').text(formattedTitle);

    const servicesForDate = (HEATMAP_STATE.data.services && HEATMAP_STATE.data.services[dateStr]) ? HEATMAP_STATE.data.services[dateStr] : [];
    const count = servicesForDate.length;

    $('#selectedDateCountBadge').text(`${count} ${count === 1 ? 'visit' : 'visits'}`)
        .removeClass('bg-slate-200 text-slate-800 bg-sky-100 text-sky-800')
        .addClass(count > 0 ? 'bg-sky-100 text-sky-800 font-bold' : 'bg-slate-200 text-slate-800');

    // Populate service cards
    const $list = $('#selectedDateServicesList');
    $list.empty();

    if (count === 0) {
        $list.html(`
            <div class="bg-white rounded-xl border border-dashed border-slate-200 p-8 text-center">
                <div class="w-10 h-10 rounded-full bg-slate-50 border border-slate-200 text-slate-400 flex items-center justify-center mx-auto mb-2">
                    <i data-lucide="calendar" class="w-5 h-5"></i>
                </div>
                <p class="text-xs font-bold text-slate-700">No services scheduled on ${formattedTitle}</p>
                <p class="text-[11px] text-slate-400 mt-0.5">Technicians have open schedule availability for field visits.</p>
                <button type="button" onclick="openScheduleForSelectedDate()" class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Schedule visit for this date</span>
                </button>
            </div>
        `);
    } else {
        servicesForDate.forEach(function(s) {
            let prioBadge = 'bg-slate-50 text-slate-700 border-slate-200';
            if (s.priority === 'URGENT') prioBadge = 'bg-rose-50 text-rose-700 border-rose-200';
            else if (s.priority === 'HIGH') prioBadge = 'bg-amber-50 text-amber-700 border-amber-200';
            else if (s.priority === 'NORMAL') prioBadge = 'bg-sky-50 text-sky-700 border-sky-200';

            let statusBadge = 'bg-slate-50 text-slate-700 border-slate-200';
            if (s.status === 'COMPLETED') statusBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
            else if (s.status === 'IN_PROGRESS') statusBadge = 'bg-amber-50 text-amber-700 border-amber-200';
            else if (s.status === 'ASSIGNED') statusBadge = 'bg-sky-50 text-sky-700 border-sky-200';
            else if (s.status === 'SCHEDULED') statusBadge = 'bg-indigo-50 text-indigo-700 border-indigo-200';

            $list.append(`
                <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs hover:border-indigo-300 transition flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center shrink-0 mt-0.5">
                            <i data-lucide="wrench" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="${s.url}" class="font-bold text-slate-900 hover:text-indigo-600 font-mono text-sm flex items-center gap-1">
                                    ${s.service_number}
                                    <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 text-slate-400"></i>
                                </a>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border ${prioBadge}">${s.priority}</span>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full border ${statusBadge}">${s.status}</span>
                            </div>
                            <div class="text-xs font-semibold text-slate-800 mt-1">
                                ${s.formatted_type} · <span class="font-normal text-slate-500">${s.asset_name}</span>
                            </div>
                            <div class="text-xs text-slate-600 mt-1 flex flex-wrap items-center gap-3">
                                <span class="inline-flex items-center gap-1 font-medium text-slate-800">
                                    <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                    ${s.customer_name} ${s.customer_phone ? '<span class="font-mono text-slate-400">(' + s.customer_phone + ')</span>' : ''}
                                </span>
                                <span class="inline-flex items-center gap-1 font-mono text-[11px] text-slate-500">
                                    <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                                    ${s.scheduled_time_slot}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between md:justify-end gap-3 shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
                        <div class="text-left md:text-right">
                            <div class="text-xs font-semibold text-slate-900 flex items-center md:justify-end gap-1">
                                <i data-lucide="user-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                ${s.technician_name ? s.technician_name : '<span class="text-amber-600 italic">Unassigned</span>'}
                            </div>
                            <div class="text-[10px] text-slate-400 font-mono">${s.technician_phone || 'No direct phone'}</div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <a href="${s.url}" class="btn-outline text-xs py-1.5 px-3 inline-flex items-center gap-1 bg-white hover:bg-slate-50">
                                <span>Details</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                            <button type="button" 
                                    onclick="confirmDeleteService('${s.id}', '${s.service_number}')" 
                                    class="btn-outline text-xs py-1.5 px-2.5 inline-flex items-center gap-1 text-rose-600 hover:text-rose-700 hover:bg-rose-50 border-rose-200 bg-white transition-colors" 
                                    title="Delete Scheduled Service">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                <span>Delete</span>
                            </button>
                        </div>
                    </div>
                </div>
            `);
        });
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function fetchHeatmapMonth(year, month) {
    $('#calMonthLabel').html('<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin inline mr-1"></i> Loading...');
    if (typeof lucide !== 'undefined') lucide.createIcons();

    $.ajax({
        url: "{{ route('services.calendar_heatmap') }}",
        type: 'GET',
        data: { year: year, month: month },
        success: function(res) {
            if (res.success && res.data) {
                HEATMAP_STATE.year = res.data.year;
                HEATMAP_STATE.month = res.data.month;
                HEATMAP_STATE.data = res.data;

                renderHeatmapCalendar(res.data);

                // Auto-select date in new month
                autoSelectInitialDate();
            }
        },
        error: function() {
            CRM.toast('Failed to load calendar data', 'danger');
            $('#calMonthLabel').text(`${year}-${month}`);
        }
    });
}

function autoSelectInitialDate() {
    const todayStr = getTodayDateString();
    const data = HEATMAP_STATE.data;
    const currentYear = data.year;
    const currentMonth = data.month;
    const todayYear = new Date().getFullYear();
    const todayMonth = new Date().getMonth() + 1;

    let targetDate = null;

    // 1. If viewing current real-time month, prefer today
    if (currentYear === todayYear && currentMonth === todayMonth) {
        targetDate = todayStr;
    }

    // 2. Or prefer the first date with scheduled services
    if (!targetDate || !(data.counts && data.counts[targetDate])) {
        const scheduledDates = Object.keys(data.counts || {}).filter(d => data.counts[d] > 0);
        if (scheduledDates.length > 0) {
            targetDate = scheduledDates.sort()[0];
        }
    }

    // 3. Fallback to 1st of month
    if (!targetDate) {
        targetDate = `${currentYear}-${String(currentMonth).padStart(2, '0')}-01`;
    }

    selectHeatmapDate(targetDate);
}

function openScheduleForSelectedDate() {
    if (HEATMAP_STATE.selectedDate) {
        $('input[name="scheduled_date"]').val(HEATMAP_STATE.selectedDate);
    }
    CRM.openModal('modalScheduleService');
}

$(document).ready(function() {
    // 1. Initialize Heatmap & Calendar on load
    if (window.HEATMAP_STATE && window.HEATMAP_STATE.data) {
        renderHeatmapCalendar(window.HEATMAP_STATE.data);
        autoSelectInitialDate();
    }

    // 2. Navigation buttons
    $('#btnCalPrevMonth').on('click', function() {
        let y = HEATMAP_STATE.year;
        let m = HEATMAP_STATE.month - 1;
        if (m < 1) {
            m = 12;
            y -= 1;
        }
        fetchHeatmapMonth(y, m);
    });

    $('#btnCalNextMonth').on('click', function() {
        let y = HEATMAP_STATE.year;
        let m = HEATMAP_STATE.month + 1;
        if (m > 12) {
            m = 1;
            y += 1;
        }
        fetchHeatmapMonth(y, m);
    });

    $('#btnCalToday').on('click', function() {
        const d = new Date();
        fetchHeatmapMonth(d.getFullYear(), d.getMonth() + 1);
    });

    // 3. Customer asset dynamic retrieval and mutual field management
    function loadCustomerAssets(custId, selectedAssetId = null) {
        const $assetSelect = $('#serviceAssetSelect');
        const $statusBadge = $('#assetStatusBadge');

        if (!custId) {
            $assetSelect.empty().append('<option value="">Select a customer first to view machines</option>');
            $statusBadge.empty();
            return;
        }

        $assetSelect.empty().append('<option value="">Loading machines for customer...</option>');
        $statusBadge.html('<span class="text-indigo-600 font-medium">Fetching machines...</span>');

        $.ajax({
            url: `/customers/${custId}/assets`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                const assets = res.assets || [];
                $assetSelect.empty();

                if (assets.length > 0) {
                    $assetSelect.append('<option value="">-- Select Registered Machine / Asset --</option>');
                    assets.forEach(function(a) {
                        const isSel = (selectedAssetId && selectedAssetId === a.id) ? 'selected' : '';
                        $assetSelect.append(`
                            <option value="${a.id}" ${isSel} data-warranty="${a.has_warranty ? 1 : 0}">
                                ${a.display_name}
                            </option>
                        `);
                    });
                    $statusBadge.html(`<span class="text-emerald-600 font-semibold">${assets.length} machine${assets.length > 1 ? 's' : ''} on file</span>`);
                } else {
                    $assetSelect.append('<option value="">No existing machines registered for this customer</option>');
                    $statusBadge.html('<span class="text-amber-600 font-semibold">No registered machines</span>');
                    $('#customAssetNameInput').attr('placeholder', 'Type machine name directly (e.g. Commercial Unit, Plant 50 LPH)...');
                }
            },
            error: function() {
                $assetSelect.empty().append('<option value="">Could not load machines — type name directly below</option>');
                $statusBadge.html('<span class="text-rose-500 font-medium">Error loading machines</span>');
            }
        });
    }

    // 3. Customer search & dynamic asset retrieval
    const $customerSelect = $('#serviceCustomerSelect');
    const $customerSearch = $('#serviceCustomerSearch');
    const $clearCustomerSearch = $('#btnClearServiceCustomerSearch');
    const $customerSearchHelp = $('#serviceCustomerSearchHelp');

    const customerOptionsList = [];
    $customerSelect.find('option').each(function() {
        const val = $(this).val();
        customerOptionsList.push({
            value: val,
            text: $(this).text(),
            search: ($(this).data('search') || $(this).text()).toString().toLowerCase(),
            isPlaceholder: (val === '')
        });
    });

    function filterCustomerSelect(query) {
        const currentVal = $customerSelect.val();
        query = (query || '').trim().toLowerCase();

        if (query.length > 0) {
            $clearCustomerSearch.removeClass('hidden');
        } else {
            $clearCustomerSearch.addClass('hidden');
            $customerSearchHelp.addClass('hidden').html('');
        }

        const placeholder = customerOptionsList.find(o => o.isPlaceholder) || { value: '', text: 'Select Customer' };

        if (!query) {
            $customerSelect.empty();
            $customerSelect.append(new Option(placeholder.text, placeholder.value));
            customerOptionsList.filter(o => !o.isPlaceholder).forEach(function(opt) {
                const isSelected = (opt.value === currentVal && currentVal !== '');
                $customerSelect.append(new Option(opt.text, opt.value, isSelected, isSelected));
            });
            if (currentVal) {
                $customerSelect.val(currentVal);
            }
            return;
        }

        const matches = customerOptionsList.filter(function(opt) {
            return !opt.isPlaceholder && opt.search.includes(query);
        });

        $customerSelect.empty();

        if (matches.length === 0) {
            $customerSelect.append(new Option('No registered customers match "' + query + '"', '', true, true));
            $customerSearchHelp.removeClass('hidden').html(
                '<span class="text-rose-500 font-medium">No registered customers found matching "' + $('<div>').text(query).html() + '"</span>'
            );
            loadCustomerAssets(null);
        } else {
            $customerSelect.append(new Option('-- Select Customer (' + matches.length + ' matching) --', ''));
            let matchedPrev = false;
            matches.forEach(function(opt) {
                const isSelected = (opt.value === currentVal && currentVal !== '');
                if (isSelected) matchedPrev = true;
                $customerSelect.append(new Option(opt.text, opt.value, isSelected, isSelected));
            });

            if (matches.length === 1) {
                $customerSelect.val(matches[0].value);
                loadCustomerAssets(matches[0].value);
            } else if (!matchedPrev) {
                $customerSelect.val('');
                loadCustomerAssets(null);
            }

            $customerSearchHelp.removeClass('hidden').html(
                '<span class="text-slate-500">Found ' + matches.length + ' matching registered customer' + (matches.length > 1 ? 's' : '') + '</span>'
            );
        }
    }

    $customerSearch.on('input', function() {
        filterCustomerSelect($(this).val());
    });

    $customerSearch.on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $customerSelect.focus();
        }
    });

    $clearCustomerSearch.on('click', function() {
        $customerSearch.val('');
        filterCustomerSelect('');
        $customerSearch.focus();
    });

    $customerSelect.on('change', function() {
        const custId = $(this).val();
        loadCustomerAssets(custId);
    });

    // Handle URL action=schedule/create & customer_id pre-selection
    const serviceUrlParams = new URLSearchParams(window.location.search);
    if (serviceUrlParams.get('action') === 'schedule' || serviceUrlParams.get('action') === 'create') {
        CRM.openModal('modalScheduleService');
        const cid = serviceUrlParams.get('customer_id');
        const aid = serviceUrlParams.get('asset_id');
        if (cid) {
            $customerSelect.val(cid);
            loadCustomerAssets(cid, aid);
        }
    } else if (serviceUrlParams.get('customer_id')) {
        const cid = serviceUrlParams.get('customer_id');
        $customerSelect.val(cid);
        loadCustomerAssets(cid);
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Clear custom asset input if dropdown option is selected
    $('#serviceAssetSelect').on('change', function() {
        if ($(this).val()) {
            $('#customAssetNameInput').val('');
        }
    });

    // Clear dropdown if custom asset input is typed into
    $('#customAssetNameInput').on('input', function() {
        if ($(this).val().trim().length > 0) {
            $('#serviceAssetSelect').val('');
        }
    });

    // Validate before submission that either an asset is selected or custom name is typed
    $('#formScheduleService').on('submit', function(e) {
        const assetId = $('#serviceAssetSelect').val();
        const customName = $('#customAssetNameInput').val().trim();

        if (!assetId && !customName) {
            e.preventDefault();
            e.stopImmediatePropagation();
            if (typeof CRM !== 'undefined' && CRM.toast) {
                CRM.toast('Please select an asset or type the asset name for this service.', 'error');
            } else {
                alert('Please select an asset or type the asset name for this service.');
            }
            $('#customAssetNameInput').focus();
            return false;
        }
    });

    // 4. Modal schedule AJAX submission
    CRM.handleAjaxForm('#formScheduleService', function(res) {
        CRM.toast(res.message || 'Service scheduled successfully!');
        setTimeout(() => location.reload(), 600);
    });
});

window.confirmDeleteService = function(serviceId, serviceNumber) {
    if (!confirm(`Are you sure you want to delete scheduled service ${serviceNumber}? This action cannot be undone.`)) {
        return;
    }

    $.ajax({
        url: `/services/${serviceId}`,
        type: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(res) {
            if (window.showToast) {
                window.showToast(res.message || `Scheduled service ${serviceNumber} deleted successfully.`, 'success');
            } else if (typeof CRM !== 'undefined' && CRM.toast) {
                CRM.toast(res.message || `Scheduled service ${serviceNumber} deleted successfully.`, 'success');
            } else {
                alert(res.message || `Scheduled service ${serviceNumber} deleted successfully.`);
            }
            setTimeout(function() {
                window.location.reload();
            }, 400);
        },
        error: function(xhr) {
            const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to delete scheduled service.';
            if (window.showToast) {
                window.showToast(msg, 'danger');
            } else if (typeof CRM !== 'undefined' && CRM.toast) {
                CRM.toast(msg, 'error');
            } else {
                alert(msg);
            }
        }
    });
};
</script>
@endsection

