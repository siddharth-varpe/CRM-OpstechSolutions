@extends('layouts.app')

@section('title', $technician->full_name . ' — Technician 360° Profile — Enterprises CRM')

@section('content')
<div class="space-y-6 max-w-[1400px] mx-auto pb-16 select-none animate-in fade-in duration-150">
    <!-- 1. Breadcrumbs & Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('technicians.index') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors mb-2">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Back to Technicians Roster</span>
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-display font-extrabold tracking-tight text-slate-900">
                    Technician 360° Profile
                </h1>
                <span class="text-xs font-mono font-bold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                    ID: {{ substr($technician->id, 0, 8) }}
                </span>
            </div>
            <nav class="flex items-center gap-1.5 text-xs text-slate-500 mt-1 font-medium">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-900">Home</a>
                <span>&gt;</span>
                <a href="{{ route('technicians.index') }}" class="hover:text-slate-900">Technicians</a>
                <span>&gt;</span>
                <span class="text-slate-900 font-bold">{{ $technician->full_name }}</span>
            </nav>
        </div>

        <!-- Top Header Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('technicians.map') }}"
               class="px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-bold flex items-center gap-1.5 transition shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <i data-lucide="navigation" class="w-3.5 h-3.5 text-emerald-600"></i>
                <span>Track on Map</span>
            </a>

            <button type="button"
                    onclick="openEditTechnicianModal('{{ $technician->id }}', '{{ addslashes($technician->full_name) }}', '{{ $technician->phone }}', '{{ $technician->email }}', '{{ $technician->status }}')"
                    class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-bold flex items-center gap-1.5 transition shadow-2xs">
                <i data-lucide="edit-2" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Edit Profile</span>
            </button>

            <button type="button"
                    onclick="openResetPinModal('{{ $technician->id }}', '{{ addslashes($technician->full_name) }}')"
                    class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-bold flex items-center gap-1.5 transition shadow-2xs">
                <i data-lucide="key" class="w-3.5 h-3.5 text-indigo-500"></i>
                <span>Reset PIN</span>
            </button>

            @php
                $isPortalActive = (bool) ($technician->portalAccess && $technician->portalAccess->portal_enabled);
            @endphp
            <button type="button"
                    id="btnTogglePortalProfile"
                    onclick="togglePortalAccess('{{ $technician->id }}', {{ $isPortalActive ? 'false' : 'true' }})"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition shadow-2xs {{ $isPortalActive ? 'bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100' : 'bg-primary-600 text-white hover:bg-primary-700' }}">
                @if($isPortalActive)
                    <i data-lucide="shield-off" class="w-3.5 h-3.5 text-rose-600"></i>
                    <span>Disable Portal</span>
                @else
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-white"></i>
                    <span>Enable Portal Access</span>
                @endif
            </button>
        </div>
    </div>

    <!-- 2. Master Hero Card -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-card p-6 overflow-hidden relative">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <!-- Left Info: Avatar, Name, Contacts, Status -->
            <div class="flex items-start sm:items-center gap-4">
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-tr from-sky-600 to-teal-500 text-white font-extrabold text-2xl sm:text-3xl flex items-center justify-center shrink-0 shadow-md font-mono border-2 border-white">
                    {{ strtoupper(substr($technician->full_name, 0, 1)) }}
                </div>

                <div class="space-y-1.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            {{ $technician->full_name }}
                        </h2>

                        <!-- Availability Status Badge -->
                        @if($workSummary['availability'] === 'AVAILABLE')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Available for Jobs
                            </span>
                        @elseif($workSummary['availability'] === 'BUSY')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200 font-mono">
                                <span class="w-2 h-2 rounded-full bg-sky-500 animate-pulse"></span>
                                Currently On A Job
                            </span>
                        @elseif($workSummary['availability'] === 'ON_LEAVE')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 font-mono">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                On Leave
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                Off Duty / Inactive
                            </span>
                        @endif

                        <!-- Portal Access Pill -->
                        @if($isPortalActive)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-teal-50 text-teal-800 border border-teal-200 font-mono">
                                <i data-lucide="shield-check" class="w-3 h-3 text-teal-600"></i>
                                Mobile App Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200 font-mono">
                                <i data-lucide="shield-off" class="w-3 h-3 text-slate-400"></i>
                                App Access Disabled
                            </span>
                        @endif
                    </div>

                    <!-- Contact & Location Metadata -->
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600">
                        <a href="tel:{{ $technician->phone }}" class="flex items-center gap-1.5 font-mono font-semibold text-slate-800 hover:text-primary-600">
                            <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400"></i>
                            <span>{{ $technician->phone }}</span>
                        </a>

                        <a href="https://wa.me/91{{ preg_replace('/[^0-9]/', '', $technician->phone) }}" target="_blank"
                           class="flex items-center gap-1 text-emerald-600 font-semibold hover:underline">
                            <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                            <span>WhatsApp</span>
                        </a>

                        @if($technician->email)
                            <a href="mailto:{{ $technician->email }}" class="flex items-center gap-1.5 text-slate-600 hover:text-slate-900">
                                <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span>{{ $technician->email }}</span>
                            </a>
                        @endif

                        @if($technician->address)
                            <div class="flex items-center gap-1.5 text-slate-500">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span class="truncate max-w-xs">{{ $technician->address }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Skills Chips in Hero -->
                    <div class="flex flex-wrap items-center gap-1 pt-1">
                        <span class="text-[10px] uppercase font-bold text-slate-400 mr-1 font-mono">Skills:</span>
                        @if(!empty($technician->skills) && is_array($technician->skills))
                            @foreach($technician->skills as $skill)
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200/80">
                                    {{ $skill }}
                                </span>
                            @endforeach
                        @else
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200/80">
                                Field Equipment Service &amp; Installation
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Telemetry & Status Quick Card -->
            <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3.5 min-w-[240px] text-xs space-y-2">
                <div class="flex items-center justify-between border-b border-slate-200/60 pb-2">
                    <span class="font-bold text-slate-600 uppercase text-[10px] font-mono tracking-wider">Field Telemetry</span>
                    @if($technician->statusFreshness === 'LIVE')
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 font-mono flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span> LIVE GPS
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 font-mono">
                            {{ $technician->statusFreshness }}
                        </span>
                    @endif
                </div>

                <div class="font-mono text-[11px] space-y-1 text-slate-600">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Status:</span>
                        <span class="font-bold text-slate-800">{{ $technician->tracking_status ?? 'STANDBY' }}</span>
                    </div>
                    @if($technician->current_latitude && $technician->current_longitude)
                        <div class="flex justify-between">
                            <span class="text-slate-400">Position:</span>
                            <span class="font-bold text-slate-800">{{ number_format($technician->current_latitude, 4) }}, {{ number_format($technician->current_longitude, 4) }}</span>
                        </div>
                    @endif
                    @if($technician->distance_km)
                        <div class="flex justify-between">
                            <span class="text-slate-400">Distance to Target:</span>
                            <span class="font-bold text-teal-700">{{ $technician->distance_km }} km (ETA ~{{ $technician->eta_minutes }}m)</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-slate-400">Last Ping:</span>
                        <span>{{ $technician->last_ping_at ? $technician->last_ping_at->diffForHumans() : 'No signal recorded' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Four Master KPI Metric Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 select-none">
        <!-- 1. Total Assigned Jobs -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-card hover:shadow-elevated transition-all">
            <div class="flex items-start justify-between gap-2">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block font-mono">Assigned Jobs</span>
                    <span class="text-2xl lg:text-3xl font-display font-extrabold text-slate-900">{{ $workSummary['totalAssigned'] }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0 border border-sky-100">
                    <i data-lucide="wrench" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-500 text-[11px]">{{ $workSummary['scheduledCount'] }} scheduled / queued</span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 font-mono">Lifetime</span>
            </div>
        </div>

        <!-- 2. In Progress / Active Workload -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-card hover:shadow-elevated transition-all">
            <div class="flex items-start justify-between gap-2">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider block font-mono">In Progress</span>
                    <span class="text-2xl lg:text-3xl font-display font-extrabold text-amber-900">{{ $workSummary['inProgressCount'] }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-500 text-[11px]">{{ $workSummary['inProgressCount'] }} active • {{ $workSummary['onHoldCount'] }} on hold</span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $workSummary['onHoldCount'] > 0 ? 'bg-amber-100 text-amber-800' : 'bg-amber-50 text-amber-700' }} font-mono">
                    {{ $workSummary['onHoldCount'] > 0 ? $workSummary['onHoldCount'].' On Hold' : 'Active' }}
                </span>
            </div>
        </div>

        <!-- 3. Completed Jobs & Completion Rate -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-card hover:shadow-elevated transition-all">
            <div class="flex items-start justify-between gap-2">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block font-mono">Completed Visits</span>
                    <span class="text-2xl lg:text-3xl font-display font-extrabold text-emerald-900">{{ $workSummary['completedCount'] }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                    <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-500 text-[11px]">{{ $workSummary['completionRate'] }}% resolution rate</span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-mono">Resolved</span>
            </div>
        </div>

        <!-- 4. Total Revenue & Labor Generated -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-card hover:shadow-elevated transition-all">
            <div class="flex items-start justify-between gap-2">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider block font-mono">Billing &amp; Revenue</span>
                    <span class="text-xl sm:text-2xl lg:text-3xl font-display font-extrabold text-indigo-900 font-mono">₹{{ number_format($financialSummary['totalRevenue'], 0) }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100">
                    <i data-lucide="indian-rupee" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs font-mono text-[11px]">
                <span class="text-slate-500">Labor: ₹{{ number_format($financialSummary['totalLaborBilled'], 0) }}</span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">Parts: ₹{{ number_format($financialSummary['totalPartsBilled'], 0) }}</span>
            </div>
        </div>
    </div>

    <!-- 4. Tab Navigation Strip (9 Dedicated Sections) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-card p-1.5 overflow-x-auto">
        <div class="flex items-center gap-1 text-xs font-semibold whitespace-nowrap min-w-max">
            <button type="button" onclick="switchTechTab('overview')"
                    class="tech-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg transition-all bg-primary-600 text-white shadow-2xs font-bold" data-tab="overview">
                <i data-lucide="user" class="w-4 h-4"></i>
                <span>Overview</span>
            </button>

            <button type="button" onclick="switchTechTab('services')"
                    class="tech-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg transition-all text-slate-600 hover:text-slate-900 hover:bg-slate-100" data-tab="services">
                <i data-lucide="wrench" class="w-4 h-4"></i>
                <span>Assigned Services ({{ $assignedServices->count() }})</span>
            </button>

            <button type="button" onclick="switchTechTab('onHold')"
                    class="tech-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg transition-all text-slate-600 hover:text-slate-900 hover:bg-slate-100" data-tab="onHold">
                <span class="w-2 h-2 rounded-full bg-amber-500 {{ $onHoldWork->count() > 0 ? 'animate-pulse' : '' }}"></span>
                <i data-lucide="pause-circle" class="w-4 h-4 text-amber-600"></i>
                <span>On Hold ({{ $onHoldWork->count() }})</span>
            </button>

            <button type="button" onclick="switchTechTab('jobCards')"
                    class="tech-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg transition-all text-slate-600 hover:text-slate-900 hover:bg-slate-100" data-tab="jobCards">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span>Job Cards ({{ $jobCards->count() }})</span>
            </button>

            <button type="button" onclick="switchTechTab('currentWork')"
                    class="tech-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg transition-all text-slate-600 hover:text-slate-900 hover:bg-slate-100" data-tab="currentWork">
                <span class="w-2 h-2 rounded-full bg-emerald-500 {{ $currentWork->count() > 0 ? 'animate-ping' : '' }}"></span>
                <i data-lucide="navigation" class="w-4 h-4 text-emerald-600"></i>
                <span>Current Field Work ({{ $currentWork->count() }})</span>
            </button>

            <button type="button" onclick="switchTechTab('completed')"
                    class="tech-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg transition-all text-slate-600 hover:text-slate-900 hover:bg-slate-100" data-tab="completed">
                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                <span>Completed ({{ $completedServices->count() }})</span>
            </button>

            <button type="button" onclick="switchTechTab('customers')"
                    class="tech-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg transition-all text-slate-600 hover:text-slate-900 hover:bg-slate-100" data-tab="customers">
                <i data-lucide="users" class="w-4 h-4"></i>
                <span>Customers Handled ({{ count($customersHandled) }})</span>
            </button>

            <button type="button" onclick="switchTechTab('assets')"
                    class="tech-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg transition-all text-slate-600 hover:text-slate-900 hover:bg-slate-100" data-tab="assets">
                <i data-lucide="cpu" class="w-4 h-4"></i>
                <span>Machines / Assets ({{ count($assetsHandled) }})</span>
            </button>

            <button type="button" onclick="switchTechTab('parts')"
                    class="tech-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg transition-all text-slate-600 hover:text-slate-900 hover:bg-slate-100" data-tab="parts">
                <i data-lucide="layers" class="w-4 h-4"></i>
                <span>Parts Consumed ({{ count($partsUsed) }})</span>
            </button>

            <button type="button" onclick="switchTechTab('payments')"
                    class="tech-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg transition-all text-slate-600 hover:text-slate-900 hover:bg-slate-100" data-tab="payments">
                <i data-lucide="receipt" class="w-4 h-4"></i>
                <span>Collections ({{ $payments->count() }})</span>
            </button>
        </div>
    </div>

    <!-- 5. Tab Panels -->

    <!-- TAB 1: OVERVIEW -->
    <div id="tab-overview" class="tech-tab-panel space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Technician Credentials & Portal Config (col-span-1) -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-card p-5 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider font-mono flex items-center gap-2 border-b border-slate-100 pb-3">
                    <i data-lucide="shield-check" class="w-4 h-4 text-sky-600"></i>
                    <span>Technician Portal Credentials</span>
                </h3>

                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-slate-500 block text-[11px] font-semibold mb-1">Mobile Portal Login URL:</span>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 font-mono text-[11px] text-slate-700 flex items-center justify-between">
                            <span class="truncate">{{ url('/tech-portal/login') }}</span>
                            <a href="{{ route('tech_portal.login') }}" target="_blank" class="text-primary-600 hover:underline shrink-0 ml-2 font-bold">Open</a>
                        </div>
                    </div>

                    <div>
                        <span class="text-slate-500 block text-[11px] font-semibold mb-1">Authorized Phone:</span>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 font-mono text-sm font-bold text-slate-900">
                            {{ $technician->phone }}
                        </div>
                    </div>

                    <div>
                        <span class="text-slate-500 block text-[11px] font-semibold mb-1">Portal Access State:</span>
                        <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl border border-slate-200">
                            <span class="font-bold {{ $isPortalActive ? 'text-emerald-700' : 'text-slate-500' }}">
                                {{ $isPortalActive ? 'ENABLED (Allowed to Login)' : 'DISABLED (Login Blocked)' }}
                            </span>
                            <button type="button"
                                    onclick="togglePortalAccess('{{ $technician->id }}', {{ $isPortalActive ? 'false' : 'true' }})"
                                    class="text-xs font-bold text-primary-600 hover:underline">
                                Toggle
                            </button>
                        </div>
                    </div>

                    <div>
                        <span class="text-slate-500 block text-[11px] font-semibold mb-1">Access PIN:</span>
                        <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl border border-slate-200 font-mono">
                            @if($technician->portalAccess && !empty($technician->portalAccess->pin_hash))
                                <span class="text-emerald-700 font-semibold flex items-center gap-1.5">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                    •••• (Assigned)
                                </span>
                            @else
                                <span class="text-amber-600 font-semibold flex items-center gap-1.5">
                                    <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                    No PIN Assigned
                                </span>
                            @endif
                            <button type="button"
                                    onclick="openResetPinModal('{{ $technician->id }}', '{{ addslashes($technician->full_name) }}')"
                                    class="text-xs font-bold text-indigo-600 hover:underline">
                                {{ $technician->portalAccess && !empty($technician->portalAccess->pin_hash) ? 'Change PIN' : 'Assign PIN' }}
                            </button>
                        </div>
                    </div>

                    @if($technician->portalAccess && $technician->portalAccess->last_login_at)
                        <div class="text-[11px] text-slate-500 pt-1">
                            Last app login: {{ $technician->portalAccess->last_login_at->format('d M Y, h:i A') }} ({{ $technician->portalAccess->last_login_at->diffForHumans() }})
                        </div>
                    @endif
                </div>
            </div>

            <!-- Middle: Emergency Contact & Personal Details (col-span-1) -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-card p-5 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider font-mono flex items-center gap-2 border-b border-slate-100 pb-3">
                    <i data-lucide="user-check" class="w-4 h-4 text-emerald-600"></i>
                    <span>Engineer Identity &amp; Contact</span>
                </h3>

                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-slate-500 block text-[11px] font-semibold mb-0.5">Residential Base Address:</span>
                        <p class="text-slate-800 font-medium leading-relaxed bg-slate-50 p-2.5 rounded-xl border border-slate-200/80">
                            {{ $technician->address ?: 'Pune Operational Territory, Maharashtra' }}
                        </p>
                    </div>

                    <div>
                        <span class="text-slate-500 block text-[11px] font-semibold mb-0.5">Emergency Contact:</span>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 font-mono text-slate-800 font-semibold flex items-center justify-between">
                            <span>{{ $technician->emergency_contact ?: 'Not provided' }}</span>
                            @if($technician->emergency_contact)
                                <a href="tel:{{ $technician->emergency_contact }}" class="text-primary-600 hover:underline">Call</a>
                            @endif
                        </div>
                    </div>

                    <div>
                        <span class="text-slate-500 block text-[11px] font-semibold mb-0.5">Primary Service Specializations:</span>
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            @if(!empty($technician->skills) && is_array($technician->skills))
                                @foreach($technician->skills as $sk)
                                    <span class="px-2.5 py-1 bg-sky-50 text-sky-800 rounded-lg text-xs font-semibold border border-sky-200">
                                        {{ $sk }}
                                    </span>
                                @endforeach
                            @else
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold">Equipment Diagnostics</span>
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold">Preventive Maintenance</span>
                            @endif
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                        <span>Profile Created:</span>
                        <span class="font-mono">{{ $technician->created_at->format('d M Y') }}</span>
                    </div>
                </div>
            </div>

            <!-- Right: Financial Summary & Billing Snapshot (col-span-1) -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-card p-5 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider font-mono flex items-center gap-2 border-b border-slate-100 pb-3">
                    <i data-lucide="badge-percent" class="w-4 h-4 text-indigo-600"></i>
                    <span>Commercial &amp; Billing Contribution</span>
                </h3>

                <div class="space-y-3 text-xs">
                    <div class="p-3 bg-indigo-50/50 rounded-xl border border-indigo-100 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600">Labor Revenue:</span>
                            <span class="font-bold font-mono text-slate-900">₹{{ number_format($financialSummary['totalLaborBilled'], 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600">Spare Parts Revenue:</span>
                            <span class="font-bold font-mono text-slate-900">₹{{ number_format($financialSummary['totalPartsBilled'], 2) }}</span>
                        </div>
                        <div class="pt-2 border-t border-indigo-200 flex justify-between items-center font-bold text-sm">
                            <span class="text-indigo-900">Total Billed:</span>
                            <span class="font-mono text-indigo-700">₹{{ number_format($financialSummary['totalRevenue'], 2) }}</span>
                        </div>
                    </div>

                    <div class="p-3 bg-emerald-50/50 rounded-xl border border-emerald-100 flex justify-between items-center">
                        <div>
                            <span class="text-[11px] text-slate-500 block uppercase font-bold">On-Site Collections:</span>
                            <span class="text-xs text-slate-700">Verified receipts &amp; payments</span>
                        </div>
                        <span class="text-base font-bold font-mono text-emerald-700">₹{{ number_format($financialSummary['totalCollections'], 2) }}</span>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 flex items-center justify-between text-xs">
                        <span class="text-slate-600">Average Job Turnaround:</span>
                        <span class="font-bold font-mono text-slate-800">~45 minutes</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: ASSIGNED SERVICES -->
    <div id="tab-services" class="tech-tab-panel hidden space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-card overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">All Field Services Assigned to {{ $technician->full_name }}</h3>
                    <p class="text-xs text-slate-500">Comprehensive log of scheduled, in-progress, and historical visits.</p>
                </div>
                <div class="text-xs font-mono font-bold text-slate-600">
                    Total: {{ $assignedServices->count() }} Tickets
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
                            <th class="py-3 px-4">Service No</th>
                            <th class="py-3 px-4">Customer</th>
                            <th class="py-3 px-4">Machine / Asset</th>
                            <th class="py-3 px-4">Scheduled Slot</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($assignedServices as $srv)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-primary-600">
                                    <a href="{{ route('services.show', $srv->id) }}" class="hover:underline">
                                        {{ $srv->service_number }}
                                    </a>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $srv->customer->name ?? 'Direct Customer' }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $srv->customer->phone ?? '' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-800">{{ $srv->asset->custom_name ?? ($srv->asset->product->name ?? 'Equipment / Unit') }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono">S/N: {{ $srv->asset->serial_number ?? 'N/A' }}</div>
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-700">
                                    {{ $srv->scheduled_date ? $srv->scheduled_date->format('d M Y') : 'Immediate' }}
                                    @if($srv->scheduled_time_slot)
                                        <div class="text-slate-500">{{ $srv->scheduled_time_slot }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($srv->status === 'COMPLETED')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                                            COMPLETED
                                        </span>
                                    @elseif($srv->status === 'IN_PROGRESS')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 font-mono">
                                            IN PROGRESS
                                        </span>
                                    @elseif($srv->status === 'ON_HOLD')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300 font-mono">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            ON HOLD
                                        </span>
                                    @elseif($srv->status === 'ASSIGNED')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200 font-mono">
                                            ASSIGNED
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 font-mono">
                                            {{ $srv->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <a href="{{ route('services.show', $srv->id) }}"
                                       class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-primary-50 hover:text-primary-600 font-bold text-xs inline-flex items-center gap-1 transition">
                                        <span>Details</span>
                                        <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">
                                    <i data-lucide="wrench" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p class="font-semibold text-xs">No service visits assigned to this technician yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB: ON HOLD SERVICES -->
    <div id="tab-onHold" class="tech-tab-panel hidden space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-card overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center">
                        <i data-lucide="pause-circle" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Services on Hold ({{ $onHoldWork->count() }})</h3>
                        <p class="text-xs text-slate-500">Tickets assigned to {{ $technician->full_name }} that are currently paused or waiting on parts/customer.</p>
                    </div>
                </div>
                <div class="text-xs font-mono font-bold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200">
                    {{ $onHoldWork->count() }} Paused Tickets
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
                            <th class="py-3 px-4">Service No</th>
                            <th class="py-3 px-4">Customer</th>
                            <th class="py-3 px-4">Machine / Asset</th>
                            <th class="py-3 px-4">Hold Notes &amp; History</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($onHoldWork as $srv)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-primary-600">
                                    <a href="{{ route('services.show', $srv->id) }}" class="hover:underline">
                                        {{ $srv->service_number }}
                                    </a>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $srv->customer->name ?? 'Direct Customer' }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $srv->customer->phone ?? '' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-800">{{ $srv->asset->custom_name ?? ($srv->asset->product->name ?? 'Equipment / Unit') }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono">S/N: {{ $srv->asset->serial_number ?? 'N/A' }}</div>
                                </td>
                                <td class="py-3 px-4 text-xs text-slate-600 max-w-xs">
                                    @php
                                        $notes = $srv->internal_notes ?: ($srv->jobCard ? $srv->jobCard->technician_notes : null);
                                    @endphp
                                    @if($notes)
                                        <div class="p-2 rounded-lg bg-amber-50/60 border border-amber-200 text-amber-900 font-mono text-[11px] whitespace-pre-line">
                                            {{ \Illuminate\Support\Str::limit($notes, 150) }}
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic">No hold notes recorded</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300 font-mono">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        ON HOLD
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('services.show', $srv->id) }}"
                                           class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-primary-50 hover:text-primary-600 font-bold text-xs inline-flex items-center gap-1 transition">
                                            <span>Manage</span>
                                            <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">
                                    <i data-lucide="check-circle" class="w-8 h-8 mx-auto mb-2 text-emerald-500 opacity-60"></i>
                                    <p class="font-semibold text-xs">No services currently on hold for this technician.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: JOB CARDS -->
    <div id="tab-jobCards" class="tech-tab-panel hidden space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-card overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Executed Job Cards ({{ $jobCards->count() }})</h3>
                    <p class="text-xs text-slate-500">Technical work reports, TDS observations, and customer sign-offs.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
                            <th class="py-3 px-4">Job Card No</th>
                            <th class="py-3 px-4">Customer</th>
                            <th class="py-3 px-4">Diagnosis &amp; Work</th>
                            <th class="py-3 px-4 font-mono text-right">Labor</th>
                            <th class="py-3 px-4 font-mono text-right">Parts</th>
                            <th class="py-3 px-4 font-mono text-right">Total</th>
                            <th class="py-3 px-4 text-center">Completed At</th>
                            <th class="py-3 px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($jobCards as $jc)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-primary-600">
                                    <a href="{{ route('job_cards.show', $jc->id) }}" class="hover:underline">
                                        {{ $jc->job_card_number }}
                                    </a>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $jc->service->customer->name ?? 'Customer' }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono">{{ $jc->service->service_number ?? '' }}</div>
                                </td>
                                <td class="py-3 px-4 max-w-xs">
                                    <div class="font-semibold text-slate-800 truncate">{{ $jc->diagnosis ?: 'Preventive Maintenance' }}</div>
                                    <div class="text-[11px] text-slate-500 truncate">{{ $jc->work_performed }}</div>
                                </td>
                                <td class="py-3 px-4 font-mono text-right font-semibold text-slate-700">
                                    ₹{{ number_format($jc->labor_charges, 2) }}
                                </td>
                                <td class="py-3 px-4 font-mono text-right font-semibold text-slate-700">
                                    ₹{{ number_format($jc->parts_charges, 2) }}
                                </td>
                                <td class="py-3 px-4 font-mono text-right font-bold text-emerald-700">
                                    ₹{{ number_format($jc->total_charges, 2) }}
                                </td>
                                <td class="py-3 px-4 text-center font-mono text-[11px] text-slate-500">
                                    {{ $jc->completed_at ? $jc->completed_at->format('d M Y, h:i A') : 'Pending' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <a href="{{ route('job_cards.show', $jc->id) }}"
                                       class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-primary-50 hover:text-primary-600 font-bold text-xs inline-flex items-center gap-1 transition">
                                        <span>View</span>
                                        <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-slate-400">
                                    <i data-lucide="file-text" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p class="font-semibold text-xs">No job cards executed yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 4: CURRENT FIELD WORK & LIVE TRACKING -->
    <div id="tab-currentWork" class="tech-tab-panel hidden space-y-4">
        @if($technician->activeService)
            @php $act = $technician->activeService; @endphp
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-card p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-200">
                            <i data-lucide="navigation-2" class="w-6 h-6 animate-pulse"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded font-mono">
                                LIVE FIELD DISPATCH ACTIVE
                            </span>
                            <h3 class="text-lg font-bold text-slate-900 mt-0.5">
                                {{ $act->service_number }} • {{ $act->customer->name ?? 'Customer Premise' }}
                            </h3>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('technicians.map') }}" class="btn-primary text-xs flex items-center gap-1.5">
                            <i data-lucide="map" class="w-4 h-4"></i>
                            <span>View Full Dispatcher Map</span>
                        </a>
                    </div>
                </div>

                <!-- Live Route Details Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block font-mono">Distance to Premise</span>
                        <span class="text-xl font-bold font-mono text-slate-900 mt-1 block">
                            {{ $technician->distance_km ? $technician->distance_km . ' km' : 'Calculating...' }}
                        </span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block font-mono">Estimated Arrival (ETA)</span>
                        <span class="text-xl font-bold font-mono text-teal-700 mt-1 block">
                            {{ $technician->eta_minutes ? '~' . $technician->eta_minutes . ' mins' : 'Transit underway' }}
                        </span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block font-mono">Target Address</span>
                        <p class="text-xs text-slate-800 font-medium mt-1 truncate">
                            {{ $technician->destination_address ?: ($act->customer && $act->customer->addresses->first() ? $act->customer->addresses->first()->full_address : 'Pune, Maharashtra') }}
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-card p-12 text-center text-slate-400 space-y-2">
                <i data-lucide="compass" class="w-10 h-10 mx-auto text-slate-300"></i>
                <h4 class="font-bold text-slate-700 text-sm">No Active Field Dispatch</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    {{ $technician->full_name }} is currently not actively navigating to a client site. Technician status is standby / available.
                </p>
                <div class="pt-2">
                    <a href="{{ route('services.index') }}" class="btn-primary text-xs inline-flex items-center gap-1.5">
                        <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                        <span>Assign Service Ticket</span>
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- TAB 5: COMPLETED VISITS -->
    <div id="tab-completed" class="tech-tab-panel hidden space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-card overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Completed Service Visits ({{ $completedServices->count() }})</h3>
                    <p class="text-xs text-slate-500">History of closed customer appointments and verified jobs.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
                            <th class="py-3 px-4">Service No</th>
                            <th class="py-3 px-4">Customer</th>
                            <th class="py-3 px-4">Equipment / Model</th>
                            <th class="py-3 px-4 text-center">Completed At</th>
                            <th class="py-3 px-4 text-center">Job Card</th>
                            <th class="py-3 px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($completedServices as $srv)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-primary-600">
                                    <a href="{{ route('services.show', $srv->id) }}" class="hover:underline">
                                        {{ $srv->service_number }}
                                    </a>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $srv->customer->name ?? 'Customer' }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $srv->customer->phone ?? '' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-800">{{ $srv->asset->custom_name ?? ($srv->asset->product->name ?? 'Equipment / Unit') }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono">S/N: {{ $srv->asset->serial_number ?? 'N/A' }}</div>
                                </td>
                                <td class="py-3 px-4 text-center font-mono text-[11px] text-slate-600">
                                    {{ $srv->completed_at ? $srv->completed_at->format('d M Y, h:i A') : 'Completed' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($srv->jobCard)
                                        <a href="{{ route('job_cards.show', $srv->jobCard->id) }}" class="font-mono text-emerald-700 font-bold hover:underline">
                                            {{ $srv->jobCard->job_card_number }}
                                        </a>
                                    @else
                                        <span class="text-slate-400 font-mono text-[11px]">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <a href="{{ route('services.show', $srv->id) }}"
                                       class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-primary-50 hover:text-primary-600 font-bold text-xs inline-flex items-center gap-1 transition">
                                        <span>View</span>
                                        <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">
                                    <i data-lucide="check-circle-2" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p class="font-semibold text-xs">No completed visits recorded yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 6: CUSTOMERS HANDLED -->
    <div id="tab-customers" class="tech-tab-panel hidden space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-card overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Distinct Customer Premises Handled ({{ count($customersHandled) }})</h3>
                    <p class="text-xs text-slate-500">Clients serviced on site by {{ $technician->full_name }}.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
                            <th class="py-3 px-4">Customer Name</th>
                            <th class="py-3 px-4">Contact</th>
                            <th class="py-3 px-4">Premise Location</th>
                            <th class="py-3 px-4 text-center">Visits Handled</th>
                            <th class="py-3 px-4 text-center">Last Serviced</th>
                            <th class="py-3 px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($customersHandled as $c)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    <a href="{{ route('customers.show', $c['customer']->id) }}" class="hover:text-primary-600 hover:underline">
                                        {{ $c['customer']->name }}
                                    </a>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-700">
                                    {{ $c['customer']->phone }}
                                </td>
                                <td class="py-3 px-4 max-w-xs text-slate-600 truncate">
                                    {{ $c['customer']->addresses->first() ? $c['customer']->addresses->first()->full_address : 'Pune, Maharashtra' }}
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-slate-800">
                                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-800">
                                        {{ $c['visitCount'] }} visits
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center font-mono text-slate-500 text-[11px]">
                                    {{ $c['lastVisit']->format('d M Y') }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <a href="{{ route('customers.show', $c['customer']->id) }}"
                                       class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-primary-50 hover:text-primary-600 font-bold text-xs inline-flex items-center gap-1 transition">
                                        <span>360 Profile</span>
                                        <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">
                                    <i data-lucide="users" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p class="font-semibold text-xs">No client premises serviced yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 7: MACHINES & ASSETS HANDLED -->
    <div id="tab-assets" class="tech-tab-panel hidden space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-card overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Commercial Purifiers &amp; Assets Serviced ({{ count($assetsHandled) }})</h3>
                    <p class="text-xs text-slate-500">Equipment maintained, membranes replaced, and TDS tuned by this engineer.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
                            <th class="py-3 px-4">Equipment Model</th>
                            <th class="py-3 px-4">Serial Number</th>
                            <th class="py-3 px-4">Owner / Customer</th>
                            <th class="py-3 px-4 text-center">Services Performed</th>
                            <th class="py-3 px-4 text-center">Last Service Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($assetsHandled as $a)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    {{ $a['asset']->custom_name ?? ($a['asset']->product->name ?? 'Equipment / Unit') }}
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-teal-700">
                                    {{ $a['asset']->serial_number }}
                                </td>
                                <td class="py-3 px-4">
                                    {{ $a['customer']->name ?? 'Direct Customer' }}
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-slate-800">
                                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-800">
                                        {{ $a['serviceCount'] }} times
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center font-mono text-[11px] text-slate-500">
                                    {{ $a['lastService']->format('d M Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-slate-400">
                                    <i data-lucide="cpu" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p class="font-semibold text-xs">No equipment service records found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 8: PARTS CONSUMED -->
    <div id="tab-parts" class="tech-tab-panel hidden space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-card overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Spare Parts &amp; Consumables Installed ({{ count($partsUsed) }})</h3>
                    <p class="text-xs text-slate-500">Filters, membranes, and replacement components billed across job cards.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
                            <th class="py-3 px-4">Component / Part Description</th>
                            <th class="py-3 px-4 text-center">Total Quantity Installed</th>
                            <th class="py-3 px-4 text-center">Jobs Used In</th>
                            <th class="py-3 px-4 font-mono text-right">Total Billing Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($partsUsed as $part)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    {{ $part['name'] }}
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-slate-800">
                                    {{ $part['quantity'] }} units
                                </td>
                                <td class="py-3 px-4 text-center font-mono text-slate-600">
                                    {{ $part['jobsCount'] }} job cards
                                </td>
                                <td class="py-3 px-4 font-mono text-right font-bold text-emerald-700">
                                    ₹{{ number_format($part['totalCost'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-8 text-center text-slate-400">
                                    <i data-lucide="layers" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p class="font-semibold text-xs">No spare parts recorded across completed job cards yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 9: COLLECTIONS & PAYMENTS -->
    <div id="tab-payments" class="tech-tab-panel hidden space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-card overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Payments &amp; Collections Handled ({{ $payments->count() }})</h3>
                    <p class="text-xs text-slate-500">Customer payments and dues collected on site.</p>
                </div>
                <div class="text-xs font-mono font-bold text-emerald-700">
                    Total: ₹{{ number_format($financialSummary['totalCollections'], 2) }}
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
                            <th class="py-3 px-4">Payment No</th>
                            <th class="py-3 px-4">Customer</th>
                            <th class="py-3 px-4">Payment Method</th>
                            <th class="py-3 px-4 font-mono text-right">Amount</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Recorded Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($payments as $pay)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-primary-600">
                                    {{ $pay->payment_number }}
                                </td>
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    {{ $pay->customer->name ?? 'Customer' }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase font-mono bg-slate-100 text-slate-700">
                                        {{ $pay->payment_method }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono text-right font-bold text-emerald-700">
                                    ₹{{ number_format($pay->amount, 2) }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono {{ $pay->status === 'SUCCESS' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                        {{ $pay->status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center font-mono text-[11px] text-slate-500">
                                    {{ $pay->payment_date ? $pay->payment_date->format('d M Y') : $pay->created_at->format('d M Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">
                                    <i data-lucide="receipt" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p class="font-semibold text-xs">No collections recorded for this technician yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Technician Modal -->
<div id="editTechnicianModal" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-elevated border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900">Edit Technician Profile</h3>
            <button type="button" onclick="closeModal('editTechnicianModal')" class="p-1 rounded-lg text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="editTechForm" onsubmit="submitEditTechnician(event)">
            @csrf
            <input type="hidden" id="editTechId" value="{{ $technician->id }}">

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Full Name *</label>
                    <input type="text" id="editTechName" required value="{{ $technician->full_name }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Phone Number *</label>
                        <input type="text" id="editTechPhone" required value="{{ $technician->phone }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs font-mono focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Email Address</label>
                        <input type="email" id="editTechEmail" value="{{ $technician->email }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Availability Status *</label>
                    <select id="editTechStatus" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="ACTIVE" {{ $technician->status === 'ACTIVE' ? 'selected' : '' }}>ACTIVE (Ready for field dispatch)</option>
                        <option value="ON_LEAVE" {{ $technician->status === 'ON_LEAVE' ? 'selected' : '' }}>ON LEAVE (Temporarily away)</option>
                        <option value="INACTIVE" {{ $technician->status === 'INACTIVE' ? 'selected' : '' }}>INACTIVE (Suspended / Off Duty)</option>
                    </select>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('editTechnicianModal')" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" id="btnSubmitEditTech" class="btn-primary text-xs">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reset PIN Modal -->
<div id="resetPinModal" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full p-6 space-y-4 shadow-elevated border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900">Set Mobile Portal PIN</h3>
            <button type="button" onclick="closeModal('resetPinModal')" class="p-1 rounded-lg text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="resetPinForm" onsubmit="submitResetPin(event)">
            @csrf
            <div class="space-y-3 text-xs">
                <p class="text-slate-500">
                    Enter a new 4 to 6 digit security PIN for <strong id="resetPinTechName">{{ $technician->full_name }}</strong> to access the field technician mobile application.
                </p>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Unique Security PIN * (4 to 6 digits)</label>
                    <input type="password" id="inputNewPin" required maxlength="6" minlength="4" placeholder="e.g. 5821"
                           class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-base font-mono tracking-widest text-center focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('resetPinModal')" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" id="btnSubmitPin" class="btn-primary text-xs">
                    Update PIN
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    lucide.createIcons();
});

function switchTechTab(tabId) {
    $('.tech-tab-panel').addClass('hidden');
    $(`#tab-${tabId}`).removeClass('hidden');

    $('.tech-tab-btn').removeClass('bg-primary-600 text-white font-bold shadow-2xs')
                      .addClass('text-slate-600 hover:text-slate-900 hover:bg-slate-100');

    $(`.tech-tab-btn[data-tab="${tabId}"]`).removeClass('text-slate-600 hover:text-slate-900 hover:bg-slate-100')
                                          .addClass('bg-primary-600 text-white font-bold shadow-2xs');

    lucide.createIcons();
}

function openEditTechnicianModal(id, name, phone, email, status) {
    $('#editTechId').val(id);
    $('#editTechName').val(name);
    $('#editTechPhone').val(phone);
    $('#editTechEmail').val(email);
    $('#editTechStatus').val(status);
    $('#editTechnicianModal').removeClass('hidden');
    lucide.createIcons();
}

function submitEditTechnician(e) {
    e.preventDefault();
    const id = $('#editTechId').val();
    const btn = $('#btnSubmitEditTech');
    btn.prop('disabled', true).text('Saving...');

    $.ajax({
        url: `/technicians/${id}`,
        type: 'PUT',
        data: {
            _token: '{{ csrf_token() }}',
            full_name: $('#editTechName').val(),
            phone: $('#editTechPhone').val(),
            email: $('#editTechEmail').val(),
            status: $('#editTechStatus').val()
        },
        success: function(res) {
            if (window.showToast) window.showToast('Technician profile updated successfully.', 'success');
            setTimeout(() => window.location.reload(), 400);
        },
        error: function(xhr) {
            btn.prop('disabled', false).text('Save Changes');
            const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to update technician.';
            if (window.showToast) window.showToast(msg, 'error');
            else alert(msg);
        }
    });
}

function openResetPinModal(id, name) {
    $('#resetPinTechName').text(name);
    $('#inputNewPin').val('');
    $('#resetPinModal').removeClass('hidden');
}

function submitResetPin(e) {
    e.preventDefault();
    const pin = $('#inputNewPin').val();
    const btn = $('#btnSubmitPin');
    btn.prop('disabled', true).text('Updating...');

    $.post(`{{ url('/technicians') }}/{{ $technician->id }}/reset-pin`, {
        _token: '{{ csrf_token() }}',
        pin: pin
    }, function(res) {
        if (window.showToast) window.showToast(res.message || 'PIN updated successfully.', 'success');
        closeModal('resetPinModal');
        btn.prop('disabled', false).text('Update PIN');
    }).fail(function(xhr) {
        btn.prop('disabled', false).text('Update PIN');
        const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to update PIN.';
        if (window.showToast) window.showToast(msg, 'error');
        else alert(msg);
    });
}

function togglePortalAccess(id, enable) {
    $.post(`/technicians/${id}/portal-access`, {
        _token: '{{ csrf_token() }}',
        portal_enabled: enable ? 1 : 0
    }, function(res) {
        if (window.showToast) window.showToast(res.message || 'Portal access updated.', 'success');
        setTimeout(() => window.location.reload(), 400);
    }).fail(function() {
        if (window.showToast) window.showToast('Failed to toggle portal access.', 'error');
    });
}

function closeModal(modalId) {
    $(`#${modalId}`).addClass('hidden');
}
</script>
@endpush
@endsection
