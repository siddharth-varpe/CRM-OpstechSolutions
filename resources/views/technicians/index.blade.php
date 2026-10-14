@extends('layouts.app')

@section('title', 'Technicians & Field Workforce — Enterprises CRM')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12 select-none">
    <!-- Page Header & View Mode Switcher -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-extrabold tracking-tight text-slate-900">
                Technicians &amp; Field Workforce
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">
                Manage field service engineers, skill sets, live job dispatch, and operational availability.
            </p>
        </div>

        <!-- View Switcher: Roster Directory vs Live Field Map -->
        <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl border border-slate-200 self-start sm:self-auto shadow-2xs">
            <a href="{{ route('technicians.index') }}"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all {{ $viewMode === 'roster' ? 'bg-white text-slate-900 shadow-2xs border border-slate-200/80' : 'text-slate-600 hover:text-slate-900' }}">
                <i data-lucide="users" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Roster Directory</span>
            </a>
            <a href="{{ route('technicians.map') }}"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all {{ $viewMode === 'map' ? 'bg-white text-slate-900 shadow-2xs border border-slate-200/80' : 'text-slate-600 hover:text-slate-900' }}">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <i data-lucide="navigation" class="w-3.5 h-3.5 text-emerald-600"></i>
                <span>Live Field Map</span>
            </a>
        </div>
    </div>

    <!-- KPI Workforce Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 select-none">
        <!-- Total Workforce -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-card hover:shadow-elevated transition-all">
            <div class="flex items-start justify-between gap-2">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block font-mono">Total Team</span>
                    <span class="text-2xl lg:text-3xl font-display font-extrabold text-slate-900">{{ $kpis['totalTechnicians'] }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0 border border-sky-100">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-500 text-[11px]">Registered crew</span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 font-mono">Total</span>
            </div>
        </div>

        <!-- Active & Ready -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-card hover:shadow-elevated transition-all">
            <div class="flex items-start justify-between gap-2">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block font-mono">Ready &amp; Active</span>
                    <span class="text-2xl lg:text-3xl font-display font-extrabold text-emerald-900">{{ $kpis['activeTechnicians'] }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                    <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-500 text-[11px]">Available for dispatch</span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-mono">Online</span>
            </div>
        </div>

        <!-- On Leave -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-card hover:shadow-elevated transition-all">
            <div class="flex items-start justify-between gap-2">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider block font-mono">On Leave</span>
                    <span class="text-2xl lg:text-3xl font-display font-extrabold text-amber-900">{{ $kpis['onLeave'] }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-500 text-[11px]">Temporarily away</span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-mono">Away</span>
            </div>
        </div>

        <!-- Navigating in Field -->
        <div class="bg-white rounded-xl border border-emerald-300 p-4 shadow-card hover:shadow-elevated transition-all bg-gradient-to-br from-emerald-50/40 to-white">
            <div class="flex items-start justify-between gap-2">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block font-mono flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                        Live in Field
                    </span>
                    <span class="text-2xl lg:text-3xl font-display font-extrabold text-slate-900">{{ $kpis['navigatingCount'] }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-md">
                    <i data-lucide="navigation" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-emerald-100 flex items-center justify-between text-xs">
                <a href="{{ route('technicians.map') }}" class="text-emerald-700 font-bold hover:underline text-[11px] flex items-center gap-1">
                    <span>Track on Map</span>
                    <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </a>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-mono">GPS Active</span>
            </div>
        </div>
    </div>

    <!-- Toolbar: Search, Filters & Action Buttons -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-card flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
        <!-- Search & Status Filters -->
        <form method="GET" action="{{ route('technicians.index') }}" class="flex-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search technician by name, phone, email, address..."
                       class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            <!-- Status Dropdown Filter -->
            <select name="status" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="ALL" {{ $status === 'ALL' ? 'selected' : '' }}>All Technicians</option>
                <option value="ACTIVE" {{ $status === 'ACTIVE' ? 'selected' : '' }}>Active &amp; Ready</option>
                <option value="ON_LEAVE" {{ $status === 'ON_LEAVE' ? 'selected' : '' }}>On Leave</option>
                <option value="INACTIVE" {{ $status === 'INACTIVE' ? 'selected' : '' }}>Inactive / Suspended</option>
            </select>
        </form>

        <!-- Right Action Buttons -->
        <div class="flex items-center gap-2.5">
            <a href="{{ route('technicians.map') }}"
               class="flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-2xs">
                <i data-lucide="map-pin" class="w-4 h-4"></i>
                <span>Open Live Map</span>
            </a>

            <button type="button"
                    onclick="openModal('addTechnicianModal')"
                    class="flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-xs font-bold transition-all shadow-2xs">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Add Technician</span>
            </button>
        </div>
    </div>

    <!-- Technicians Table -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/90 text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
                        <th class="py-3 px-4">Technician</th>
                        <th class="py-3 px-4">Contact Info</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-center">Portal Access</th>
                        <th class="py-3 px-4">Skills &amp; Specialization</th>
                        <th class="py-3 px-4 text-center">Active Jobs</th>
                        <th class="py-3 px-4 text-center">Completed</th>
                        <th class="py-3 px-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($technicians as $tech)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <!-- Technician Name & Avatar -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-700 font-bold text-sm flex items-center justify-center shrink-0 border border-sky-200/80 shadow-2xs font-mono">
                                        {{ substr($tech->full_name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('technicians.show', $tech->id) }}" class="font-bold text-slate-900 hover:text-primary-600 hover:underline flex items-center gap-1.5">
                                            <span>{{ $tech->full_name }}</span>
                                            @if($tech->tracking_status === 'ON_THE_WAY')
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 uppercase font-mono">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                                    NAVIGATING
                                                </span>
                                            @endif
                                        </a>
                                        <div class="text-[11px] text-slate-500 font-mono">
                                            Joined {{ $tech->created_at->format('M Y') }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Contact Info -->
                            <td class="py-3.5 px-4">
                                <div class="space-y-0.5 font-mono text-xs">
                                    <div class="font-semibold text-slate-800 flex items-center gap-1.5">
                                        <i data-lucide="phone" class="w-3 h-3 text-slate-400"></i>
                                        <span>{{ $tech->phone }}</span>
                                    </div>
                                    @if($tech->email)
                                        <div class="text-slate-500 text-[11px] flex items-center gap-1.5">
                                            <i data-lucide="mail" class="w-3 h-3 text-slate-400"></i>
                                            <span class="truncate max-w-[150px]">{{ $tech->email }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Availability Status -->
                            <td class="py-3.5 px-4">
                                @if($tech->status === 'ACTIVE')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        ACTIVE
                                    </span>
                                @elseif($tech->status === 'ON_LEAVE')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200 font-mono">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        ON LEAVE
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        INACTIVE
                                    </span>
                                @endif
                            </td>

                            <!-- Portal Access Badge -->
                            <td class="py-3.5 px-4 text-center">
                                @if($tech->portalAccess && $tech->portalAccess->portal_enabled && !empty($tech->portalAccess->pin_hash))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono" title="Unique PIN Configured">
                                        <i data-lucide="shield-check" class="w-3 h-3"></i>
                                        PIN ACTIVE
                                    </span>
                                @elseif($tech->portalAccess && $tech->portalAccess->portal_enabled)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 font-mono" title="PIN Not Yet Assigned">
                                        <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                                        NO PIN
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 font-mono">
                                        DISABLED
                                    </span>
                                @endif
                            </td>

                            <!-- Skills & Specializations -->
                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @if(!empty($tech->skills) && is_array($tech->skills))
                                        @foreach(array_slice($tech->skills, 0, 3) as $skill)
                                            <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md text-[10px] font-semibold border border-slate-200/60">
                                                {{ $skill }}
                                            </span>
                                        @endforeach
                                        @if(count($tech->skills) > 3)
                                            <span class="text-[10px] text-slate-500 font-bold font-mono">+{{ count($tech->skills) - 3 }}</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">General Field Tech</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Active Jobs -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-mono text-xs font-bold {{ $tech->activeJobsCount > 0 ? 'bg-sky-50 text-sky-800 border border-sky-200' : 'bg-slate-100 text-slate-600' }}">
                                    <i data-lucide="wrench" class="w-3 h-3"></i>
                                    {{ $tech->activeJobsCount }}
                                </span>
                            </td>

                            <!-- Completed Jobs -->
                            <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-800">
                                {{ $tech->completedJobsCount }}
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Quick Detail Drawer -->
                                    <button type="button"
                                            onclick="openTechDetailDrawer('{{ $tech->id }}', '{{ addslashes($tech->full_name) }}', '{{ $tech->phone }}', '{{ $tech->email }}', '{{ $tech->status }}', {{ $tech->portalAccess && $tech->portalAccess->portal_enabled ? 'true' : 'false' }})"
                                            class="p-1.5 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition-colors"
                                            title="Quick Details Drawer">
                                        <i data-lucide="panel-right" class="w-4 h-4"></i>
                                    </button>

                                    <!-- View 360 Profile -->
                                    <a href="{{ route('technicians.show', $tech->id) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-primary-700 bg-primary-50 hover:bg-primary-100 rounded-lg transition-colors border border-primary-200"
                                       title="View 360° Profile">
                                        <i data-lucide="user" class="w-3.5 h-3.5"></i>
                                        <span>Profile</span>
                                    </a>

                                    <!-- View on Map -->
                                    <a href="{{ route('technicians.map') }}"
                                       class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                       title="Track on Live Map">
                                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                                    </a>

                                    <!-- Edit Profile Modal Trigger -->
                                    <button type="button"
                                            onclick="openEditTechnicianModal('{{ $tech->id }}', '{{ addslashes($tech->full_name) }}', '{{ $tech->phone }}', '{{ $tech->email }}', '{{ $tech->status }}')"
                                            class="p-1.5 text-slate-400 hover:text-primary-600 hover:bg-slate-100 rounded-lg transition-colors"
                                            title="Edit Profile">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </button>

                                    <!-- Toggle Portal Access -->
                                    <button type="button"
                                            onclick="togglePortalAccess('{{ $tech->id }}', {{ $tech->portalAccess && $tech->portalAccess->portal_enabled ? 'false' : 'true' }})"
                                            class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                            title="Toggle Mobile Access">
                                        <i data-lucide="key" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-12 text-center text-slate-500">
                                <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center mx-auto mb-3 border border-sky-100">
                                    <i data-lucide="user-x" class="w-6 h-6"></i>
                                </div>
                                <h3 class="text-sm font-bold text-slate-900">No Technicians Found</h3>
                                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                                    No field service technicians match your search or filter. You can add a new technician above.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($technicians->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $technicians->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: Add New Technician -->
<div id="addTechnicianModal" class="hidden fixed inset-0 z-50 modal-backdrop items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-modal max-w-lg w-full p-6 animate-scale-up border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center border border-primary-100">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Add Field Technician</h3>
                    <p class="text-xs text-slate-500">Register technician and set up mobile login access</p>
                </div>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-600" onclick="closeModal('addTechnicianModal')">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form action="{{ route('technicians.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Full Name *</label>
                <input type="text" name="full_name" required placeholder="e.g. Ramesh Shinde"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Mobile Phone *</label>
                    <input type="tel" name="phone" required placeholder="10-digit number"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Assign Portal PIN (4-6 digits) *</label>
                    <input type="password" name="portal_pin" required minlength="4" maxlength="6" placeholder="Assign unique PIN (e.g. 5821)"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Email (Optional)</label>
                    <input type="email" name="email" placeholder="tech@example.com"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Availability Status</label>
                    <select name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="ACTIVE" selected>Active &amp; Ready</option>
                        <option value="ON_LEAVE">On Leave</option>
                        <option value="INACTIVE">Inactive</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Residential Address / Base Location</label>
                <input type="text" name="address" placeholder="e.g. Baner, Pune"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Emergency Contact</label>
                <input type="tel" name="emergency_contact" placeholder="Emergency phone"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" class="btn btn-secondary text-xs" onclick="closeModal('addTechnicianModal')">Cancel</button>
                <button type="submit" class="btn btn-primary text-xs">Save Technician</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Technician -->
<div id="editTechnicianModal" class="hidden fixed inset-0 z-50 modal-backdrop items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-modal max-w-lg w-full p-6 animate-scale-up border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Edit Technician Profile</h3>
            <button type="button" class="text-slate-400 hover:text-slate-600" onclick="closeModal('editTechnicianModal')">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="editTechnicianForm" method="POST" class="mt-4 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Full Name</label>
                <input type="text" id="editTechName" name="full_name" required
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Phone</label>
                    <input type="tel" id="editTechPhone" name="phone" required
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Status</label>
                    <select id="editTechStatus" name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="ACTIVE">ACTIVE</option>
                        <option value="ON_LEAVE">ON_LEAVE</option>
                        <option value="INACTIVE">INACTIVE</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Email</label>
                <input type="email" id="editTechEmail" name="email"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" class="btn btn-secondary text-xs" onclick="closeModal('editTechnicianModal')">Cancel</button>
                <button type="submit" class="btn btn-primary text-xs">Update Profile</button>
            </div>
        </form>
    </div>
</div>

<!-- Slide-Over Drawer: Technician Quick Detail Drawer -->
<div id="techDetailDrawer" class="hidden fixed inset-0 z-50 overflow-hidden">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-xs transition-opacity" onclick="closeDrawer('techDetailDrawer')"></div>

    <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
        <div class="w-screen max-w-md bg-white shadow-2xl flex flex-col border-l border-slate-200">
            <!-- Header -->
            <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <div class="flex items-center gap-3">
                    <div id="drawerAvatar" class="w-10 h-10 rounded-xl bg-sky-50 text-sky-700 font-bold flex items-center justify-center font-mono border border-sky-200">
                        S
                    </div>
                    <div>
                        <h3 id="drawerName" class="text-base font-bold text-slate-900 leading-tight">Technician</h3>
                        <p id="drawerPhone" class="text-xs font-mono text-slate-500 mt-0.5">Phone</p>
                    </div>
                </div>
                <button type="button" onclick="closeDrawer('techDetailDrawer')" class="p-1 rounded-lg text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6 text-sm">
                <!-- Open Complete 360 Profile Button -->
                <a id="drawerProfileLink" href="#"
                   class="w-full py-2.5 px-4 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-2 shadow-2xs transition-colors">
                    <i data-lucide="user" class="w-4 h-4"></i>
                    <span>Open Complete 360° Profile Page</span>
                </a>

                <!-- Status Quick Toggle -->
                <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-2xl space-y-2">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wide block font-mono">
                        Availability Status
                    </span>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" onclick="drawerUpdateStatus('ACTIVE')" class="drawer-status-btn py-1.5 px-2 rounded-xl text-xs font-bold border transition-all text-center" data-status="ACTIVE">
                            ACTIVE
                        </button>
                        <button type="button" onclick="drawerUpdateStatus('ON_LEAVE')" class="drawer-status-btn py-1.5 px-2 rounded-xl text-xs font-bold border transition-all text-center" data-status="ON_LEAVE">
                            ON LEAVE
                        </button>
                        <button type="button" onclick="drawerUpdateStatus('INACTIVE')" class="drawer-status-btn py-1.5 px-2 rounded-xl text-xs font-bold border transition-all text-center" data-status="INACTIVE">
                            INACTIVE
                        </button>
                    </div>
                </div>

                <!-- Portal Access Control -->
                <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-2xl space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wide block flex items-center gap-1.5">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-sky-600"></i>
                                Technician Mobile Portal Access
                            </span>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Mobile login and field assignment access.
                            </p>
                        </div>
                        <span id="drawerPortalBadge" class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border font-mono">
                            ENABLED
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <button type="button" id="drawerEnablePortalBtn" onclick="drawerTogglePortal(true)"
                                class="py-1.5 px-3 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all border border-slate-200 bg-white text-slate-700 hover:bg-emerald-50 hover:text-emerald-700">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            Enable Access
                        </button>
                        <button type="button" id="drawerDisablePortalBtn" onclick="drawerTogglePortal(false)"
                                class="py-1.5 px-3 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all border border-slate-200 bg-white text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                            <i data-lucide="ban" class="w-3.5 h-3.5"></i>
                            Disable Access
                        </button>
                    </div>
                </div>

                <!-- Track on Map Button -->
                <a href="{{ route('technicians.map') }}"
                   class="w-full py-2.5 px-4 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-colors">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <i data-lucide="navigation" class="w-4 h-4 text-emerald-600"></i>
                    <span>Track Technician on Live Field Map</span>
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentDrawerTechId = null;

function openTechDetailDrawer(id, name, phone, email, status, portalEnabled) {
    currentDrawerTechId = id;
    $('#drawerAvatar').text(name.charAt(0).toUpperCase());
    $('#drawerName').text(name);
    $('#drawerPhone').text(phone);
    $('#drawerProfileLink').attr('href', '/technicians/' + id);

    // Status Buttons
    $('.drawer-status-btn').removeClass('bg-slate-900 text-white border-slate-900 bg-emerald-600 bg-amber-500')
                           .addClass('bg-white text-slate-700 border-slate-200 hover:bg-slate-50');
    $(`.drawer-status-btn[data-status="${status}"]`).removeClass('bg-white text-slate-700 border-slate-200 hover:bg-slate-50')
                                                   .addClass('bg-slate-900 text-white border-slate-900');

    // Portal Badge
    if (portalEnabled) {
        $('#drawerPortalBadge').removeClass('bg-slate-100 text-slate-600 border-slate-200')
                               .addClass('bg-emerald-50 text-emerald-700 border-emerald-200')
                               .text('ENABLED');
    } else {
        $('#drawerPortalBadge').removeClass('bg-emerald-50 text-emerald-700 border-emerald-200')
                               .addClass('bg-slate-100 text-slate-600 border-slate-200')
                               .text('DISABLED');
    }

    $('#techDetailDrawer').removeClass('hidden');
    lucide.createIcons();
}

function closeDrawer(drawerId) {
    $(`#${drawerId}`).addClass('hidden');
}

function drawerUpdateStatus(newStatus) {
    if (!currentDrawerTechId) return;

    $.post('/technicians/' + currentDrawerTechId + '/status', {
        _token: '{{ csrf_token() }}',
        status: newStatus
    }, function(res) {
        showToast(res.message || 'Status updated.', 'success');
        setTimeout(() => location.reload(), 400);
    }).fail(function() {
        showToast('Failed to update status.', 'error');
    });
}

function drawerTogglePortal(enable) {
    if (!currentDrawerTechId) return;

    $.post('/technicians/' + currentDrawerTechId + '/portal-access', {
        _token: '{{ csrf_token() }}',
        portal_enabled: enable ? 1 : 0
    }, function(res) {
        showToast(res.message || 'Portal access updated.', 'success');
        setTimeout(() => location.reload(), 400);
    }).fail(function() {
        showToast('Failed to update portal access.', 'error');
    });
}

function openEditTechnicianModal(id, name, phone, email, status) {
    $('#editTechnicianForm').attr('action', '/technicians/' + id);
    $('#editTechName').val(name);
    $('#editTechPhone').val(phone);
    $('#editTechEmail').val(email || '');
    $('#editTechStatus').val(status);
    openModal('editTechnicianModal');
}

function togglePortalAccess(id, enabled) {
    if (!confirm('Are you sure you want to ' + (enabled ? 'enable' : 'disable') + ' mobile portal access for this technician?')) {
        return;
    }

    $.post('/technicians/' + id + '/portal-access', {
        _token: '{{ csrf_token() }}',
        portal_enabled: enabled ? 1 : 0
    }, function(res) {
        showToast(res.message, 'success');
        setTimeout(() => location.reload(), 600);
    }).fail(function(xhr) {
        showToast('Failed to update portal access.', 'error');
    });
}
</script>
@endpush
@endsection
