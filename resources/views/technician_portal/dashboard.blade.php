<!DOCTYPE html>
<html lang="en" class="h-full bg-[#F4F6F8]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Field Technician Portal — Enterprises CRM</title>
    
    <!-- Official Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS with Authoritative CRM Theme -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              workspace: '#F4F6F8',
              surface: '#FFFFFF',
              sidebar: {
                DEFAULT: '#00152B',
                navy: '#00152B',
              },
              primary: {
                50: '#F0F9FF',
                100: '#E0F2FE',
                500: '#0EA5E9',
                600: '#0284C7',
                700: '#0369A1',
                800: '#075985',
              },
              brand: {
                50: '#F0FDFA',
                500: '#14B8A6',
                600: '#0D9488',
                700: '#0F766E',
              }
            },
            fontFamily: {
              sans: ['Inter', 'system-ui', 'sans-serif'],
              display: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
              mono: ['JetBrains Mono', 'monospace'],
            },
            boxShadow: {
              'card': '0 1px 2px rgba(15, 23, 42, 0.04)',
              'elevated': '0 4px 16px -2px rgba(15, 23, 42, 0.06), 0 2px 6px -1px rgba(15, 23, 42, 0.04)',
            }
          }
        }
      }
    </script>
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <script src="{{ asset('js/crm-app.js') }}"></script>
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        #canvasSignature { touch-action: none; }
    </style>
</head>
<body class="min-h-screen bg-workspace text-slate-800 flex flex-col antialiased select-none pb-20 md:pb-6">
    <div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"></div>

    <!-- Official Top Navigation Bar in Admin Theme -->
    <header class="bg-[#00152B] text-white sticky top-0 z-40 border-b border-slate-800/80 shadow-md">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/20 p-1 flex items-center justify-center shrink-0">
                    <img src="{{ asset('assets/crm-logo.png') }}" alt="Enterprises CRM Logo" class="w-full h-full object-contain">
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-display font-extrabold tracking-wider text-white uppercase">ENTERPRISES</span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="text-xs font-bold text-slate-200">{{ $technician->full_name }}</span>
                        <span id="topGpsStatus" class="hidden"></span>
                    </div>
                </div>
            </div>

            <!-- Quick Action Links -->
            <div class="flex items-center gap-2">
                <a href="{{ route('tech_portal.logout') }}" class="p-2 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 hover:text-rose-200 transition flex items-center gap-1.5 text-xs font-semibold" title="Logout">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Sign Out</span>
                </a>
            </div>
        </div>

    </header>

    <!-- Main Container Area -->
    <main class="flex-1 max-w-6xl w-full mx-auto p-4 sm:p-6 space-y-6">

        <!-- ========================================== -->
        <!-- TAB 1: ASSIGNED SERVICES                   -->
        <!-- ========================================== -->
        <section id="tabPane-assigned" class="tab-pane space-y-5">
            <!-- Key Metric Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3">
                <div class="bg-white border border-slate-200/90 rounded-xl p-2.5 sm:p-3 shadow-2xs">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pending Visits</span>
                    <div class="text-lg sm:text-xl font-bold font-mono text-slate-900 mt-0.5">{{ $assignedServices->count() }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Assigned visits</div>
                </div>

                <div class="bg-white border border-slate-200/90 rounded-xl p-2.5 sm:p-3 shadow-2xs">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">In Progress</span>
                    <div class="text-lg sm:text-xl font-bold font-mono text-amber-600 mt-0.5">{{ $assignedServices->where('status', 'IN_PROGRESS')->count() }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Under execution</div>
                </div>

                <div class="bg-white border border-slate-200/90 rounded-xl p-2.5 sm:p-3 shadow-2xs">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Completed Today</span>
                    <div class="text-lg sm:text-xl font-bold font-mono text-emerald-600 mt-0.5">{{ $completedToday }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Closed visits</div>
                </div>

                <div class="bg-white border border-slate-200/90 rounded-xl p-2.5 sm:p-3 shadow-2xs">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">On Hold</span>
                    <div class="text-lg sm:text-xl font-bold font-mono text-amber-600 mt-0.5">{{ $assignedServices->where('status', 'ON_HOLD')->count() }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Paused tickets</div>
                </div>
            </div>

            <!-- Header & Filter Strip -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                <div>
                    <h2 class="text-base font-display font-extrabold text-slate-900 flex items-center gap-2">
                        <i data-lucide="clipboard-list" class="w-5 h-5 text-sky-600"></i>
                        <span>Assigned Services</span>
                    </h2>
                    <p class="text-xs text-slate-500">All services assigned to you. Select any assigned service to navigate via Google Maps or execute the job card.</p>
                </div>

                <div class="flex items-center gap-1.5 self-start sm:self-auto bg-slate-100 p-1 rounded-xl">
                    <button type="button" onclick="filterTaskCards('ALL')" class="task-filter-btn px-3 py-1 rounded-lg text-xs font-semibold bg-white text-slate-900 shadow-xs" data-filter="ALL">All ({{ $assignedServices->count() }})</button>
                    <button type="button" onclick="filterTaskCards('IN_PROGRESS')" class="task-filter-btn px-3 py-1 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900" data-filter="IN_PROGRESS">In Progress ({{ $assignedServices->where('status', 'IN_PROGRESS')->count() }})</button>
                    <button type="button" onclick="filterTaskCards('ON_HOLD')" class="task-filter-btn px-3 py-1 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900" data-filter="ON_HOLD">On Hold ({{ $assignedServices->where('status', 'ON_HOLD')->count() }})</button>
                    <button type="button" onclick="filterTaskCards('SCHEDULED')" class="task-filter-btn px-3 py-1 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900" data-filter="SCHEDULED">Scheduled</button>
                </div>
            </div>

            <!-- Task Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($assignedServices as $srv)
                    @php
                        $addrStr = '';
                        $lat = null;
                        $lng = null;
                        $addr = null;
                        if ($srv->customer && $srv->customer->addresses && $srv->customer->addresses->isNotEmpty()) {
                            $addr = $srv->customer->addresses->firstWhere('is_default', true) ?? $srv->customer->addresses->first();
                        } elseif ($srv->asset && $srv->asset->installationAddress && (!$srv->customer_id || $srv->asset->customer_id === $srv->customer_id)) {
                            $addr = $srv->asset->installationAddress;
                        }
                        if ($addr) {
                            $addrStr = $addr->full_address ?: "{$addr->address_line1}, {$addr->city} {$addr->postal_code}";
                            $lat = $addr->latitude !== null ? (float)$addr->latitude : null;
                            $lng = $addr->longitude !== null ? (float)$addr->longitude : null;
                        }
                    @endphp
                    <div class="task-card bg-white border border-slate-200/90 rounded-2xl p-5 shadow-sm space-y-4 hover:border-sky-300 transition" data-status="{{ $srv->status }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-mono font-bold text-sky-700 bg-sky-50 px-2.5 py-0.5 rounded-lg border border-sky-100">
                                        {{ $srv->service_number }}
                                    </span>
                                    <span class="text-[11px] font-mono text-slate-400">
                                        {{ \Carbon\Carbon::parse($srv->scheduled_date)->format('d M, h:i A') }}
                                    </span>
                                </div>
                                <h3 class="text-base font-bold text-slate-900 mt-1.5 break-words">{{ $srv->customer->name ?? 'Direct Customer' }}</h3>
                                <p class="text-xs font-medium text-slate-500 flex items-center gap-1.5 mt-0.5 break-words">
                                    <i data-lucide="cpu" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                    <span class="break-words">{{ $srv->asset->custom_name ?? $srv->asset->product->name ?? 'Equipment / Unit' }}</span>
                                    @if(!empty($srv->asset->serial_number))
                                        <span class="font-mono text-[10px] text-slate-400 shrink-0">({{ $srv->asset->serial_number }})</span>
                                    @endif
                                </p>
                            </div>

                            @if($srv->status === 'ON_HOLD')
                                <span class="text-xs px-2.5 py-1 rounded-full font-semibold border shrink-0 bg-amber-50 text-amber-800 border-amber-300 flex items-center gap-1 font-mono">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    <span>On Hold</span>
                                </span>
                            @elseif($srv->status === 'IN_PROGRESS')
                                <span class="text-xs px-2.5 py-1 rounded-full font-semibold border shrink-0 bg-amber-50 text-amber-700 border-amber-200">
                                    In Progress
                                </span>
                            @else
                                <span class="text-xs px-2.5 py-1 rounded-full font-semibold border shrink-0 bg-sky-50 text-sky-700 border-sky-200">
                                    Scheduled
                                </span>
                            @endif
                        </div>

                        <!-- Customer Address & Contact Info -->
                        <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200/70 space-y-2 text-xs text-slate-700">
                            @if($srv->customer && $srv->customer->phone)
                                <div class="flex flex-wrap items-center justify-between gap-1">
                                    <span class="text-slate-500 font-medium flex items-center gap-1.5">
                                        <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>Customer Contact</span>
                                    </span>
                                    <div class="flex items-center gap-2 font-mono font-semibold">
                                        <a href="tel:{{ $srv->customer->phone }}" class="text-sky-600 hover:text-sky-700 flex items-center gap-1">
                                            <span>{{ $srv->customer->phone }}</span>
                                        </a>
                                        <a href="https://wa.me/91{{ preg_replace('/[^0-9]/', '', $srv->customer->phone) }}" target="_blank" class="text-emerald-600 hover:text-emerald-700 p-1" title="WhatsApp Reminder">
                                            <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </div>
                                </div>
                            @endif

                            <div class="flex items-start gap-2 pt-1 border-t border-slate-200/60">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5"></i>
                                <span class="leading-relaxed break-words min-w-0 flex-1">{{ $addrStr ?: 'Customer Address Pending' }}</span>
                            </div>
                        </div>

                        <!-- Action Buttons in Admin Theme -->
                        <div class="space-y-2 pt-1">
                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('tech_portal.navigation', $srv->id) }}" target="_blank" onclick="openGoogleMapsNavigation('{{ $srv->id }}', event)" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80 font-bold py-2.5 px-3 rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-2xs" title="Open in Google Maps">
                                    <i data-lucide="navigation" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span>Navigate</span>
                                </a>

                                <a href="{{ route('tech_portal.service', $srv->id) }}" class="bg-sky-600 hover:bg-sky-700 text-white font-bold py-2.5 px-3 rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-2xs" title="Open Job Execution Form">
                                    <i data-lucide="clipboard-check" class="w-3.5 h-3.5"></i>
                                    <span>Execute Job</span>
                                </a>
                            </div>

                            @if($srv->status === 'ON_HOLD')
                                <button type="button" onclick="setJobStatus('{{ $srv->id }}', 'IN_PROGRESS', 'Resumed by technician')" class="w-full bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 font-bold py-2 px-3 rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-2xs cursor-pointer">
                                    <i data-lucide="play-circle" class="w-4 h-4 text-amber-600"></i>
                                    <span>Resume Job (Currently On Hold)</span>
                                </button>
                            @else
                                <button type="button" onclick="promptHoldJob('{{ $srv->id }}', '{{ $srv->service_number }}')" class="w-full bg-slate-50 hover:bg-amber-50 text-slate-600 hover:text-amber-800 border border-slate-200 hover:border-amber-300 font-bold py-2 px-3 rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-2xs cursor-pointer">
                                    <i data-lucide="pause-circle" class="w-4 h-4 text-slate-400 group-hover:text-amber-600"></i>
                                    <span>Keep Job on Hold</span>
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-white border border-slate-200/90 rounded-2xl p-10 text-center space-y-3">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto">
                            <i data-lucide="check-circle" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-900">No Pending Assigned Services</h3>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto">You have no pending assigned services at the moment. You may review past records in History or stand by for new assignments.</p>
                    </div>
                @endforelse
            </div>
        </section>





        <!-- ========================================== -->
        <!-- TAB 4: COMPLETED HISTORY                   -->
        <!-- ========================================== -->
        <section id="tabPane-history" class="tab-pane hidden space-y-4">
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="text-base font-display font-extrabold text-slate-900 flex items-center gap-2">
                            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                            <span>Completed Visits Log</span>
                        </h2>
                        <p class="text-xs text-slate-500">History of verified job cards and services executed by you.</p>
                    </div>
                    <span class="text-xs font-mono font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
                        {{ $completedServices->count() }} Records
                    </span>
                </div>

                <div class="space-y-3">
                    @forelse($completedServices as $cs)
                        <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/50 hover:bg-white hover:border-sky-300 transition space-y-2">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-mono font-bold text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200">
                                            {{ $cs->service_number }}
                                        </span>
                                        <span class="text-[11px] text-slate-400 font-mono">
                                            {{ $cs->completed_at ? \Carbon\Carbon::parse($cs->completed_at)->format('d M Y, h:i A') : 'Completed' }}
                                        </span>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-900 mt-1">{{ $cs->customer->name ?? 'Customer' }}</h4>
                                    <p class="text-xs text-slate-500 font-mono">{{ $cs->asset->custom_name ?? $cs->asset->product->name ?? 'Equipment / Unit' }}</p>
                                </div>

                                <div class="text-right">
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">
                                        Closed
                                    </span>
                                    @if($cs->jobCard && $cs->jobCard->total_charges > 0)
                                        <div class="text-xs font-mono font-bold text-slate-900 mt-1">₹{{ number_format($cs->jobCard->total_charges, 2) }}</div>
                                    @endif
                                </div>
                            </div>

                            @if($cs->jobCard)
                                <div class="text-xs text-slate-600 bg-white p-2.5 rounded-lg border border-slate-200/60 leading-relaxed">
                                    <strong>Work Done:</strong> {{ $cs->jobCard->work_performed }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400 text-xs">
                            No past completed visits logged in this session yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>


        <!-- ========================================== -->
        <!-- TAB 5: TECHNICIAN PROFILE & DUTY           -->
        <!-- ========================================== -->
        <section id="tabPane-profile" class="tab-pane hidden space-y-4">
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-7 shadow-sm space-y-6">
                <!-- Profile Header Card -->
                <div class="flex items-center gap-4 pb-6 border-b border-slate-100">
                    <div class="w-16 h-16 rounded-2xl bg-sky-100 text-sky-700 flex items-center justify-center font-display font-extrabold text-2xl shadow-xs">
                        {{ substr($technician->full_name, 0, 1) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg sm:text-xl font-display font-extrabold text-slate-900">{{ $technician->full_name }}</h2>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200">Certified Executive</span>
                        </div>
                        <p class="text-xs text-slate-500 font-mono mt-0.5">+91 {{ $technician->phone }} • {{ $technician->email ?? 'technician@enterprisescrm.com' }}</p>
                        <div class="flex items-center gap-1.5 mt-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="text-xs font-semibold text-emerald-700">Mobile Portal Active</span>
                        </div>
                    </div>
                </div>

                <!-- Contact & Technical Skills -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 space-y-2 text-xs">
                        <span class="font-bold text-slate-400 uppercase tracking-wider text-[10px]">Registered Workshop Base</span>
                        <p class="text-slate-800 font-medium leading-relaxed">{{ $technician->address ?? 'Plot 45, MIDC Industrial Area, Pune, Maharashtra' }}</p>
                        <div class="pt-2 border-t border-slate-200/60 text-slate-600">
                            <strong>Emergency Contact:</strong> {{ $technician->emergency_contact ?? '+91 98220 99999' }}
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 space-y-2">
                        <span class="font-bold text-slate-400 uppercase tracking-wider text-[10px]">Certified Field Skills</span>
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            @if(!empty($technician->skills))
                                @foreach((array)$technician->skills as $sk)
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-white text-slate-700 border border-slate-200/80 shadow-2xs">{{ $sk }}</span>
                                @endforeach
                            @else
                                <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-white text-slate-700 border border-slate-200">Equipment Installation</span>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-white text-slate-700 border border-slate-200">Component Replacement</span>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-white text-slate-700 border border-slate-200">Preventive Maintenance</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Live GPS Status & Diagnostic Card -->
                <div class="bg-sky-50/60 rounded-2xl p-4 border border-sky-100 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-sky-950 uppercase tracking-wider flex items-center gap-1.5">
                            <i data-lucide="satellite" class="w-4 h-4 text-sky-600"></i>
                            <span>GPS Telemetry Diagnostic</span>
                        </span>
                        <button type="button" onclick="syncDeviceLocation(true)" class="text-xs font-semibold text-sky-700 hover:text-sky-800 underline underline-offset-2">Ping GPS Now</button>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs font-mono text-slate-700">
                        <div class="bg-white p-2.5 rounded-xl border border-sky-100">
                            <span class="text-[10px] text-slate-400 block font-sans">Latitude</span>
                            <span id="profLat">{{ number_format($technician->current_latitude ?? 18.5204, 5) }}</span>
                        </div>
                        <div class="bg-white p-2.5 rounded-xl border border-sky-100">
                            <span class="text-[10px] text-slate-400 block font-sans">Longitude</span>
                            <span id="profLng">{{ number_format($technician->current_longitude ?? 73.8567, 5) }}</span>
                        </div>
                        <div class="bg-white p-2.5 rounded-xl border border-sky-100">
                            <span class="text-[10px] text-slate-400 block font-sans">Accuracy</span>
                            <span id="profAcc">±{{ round($technician->current_accuracy ?? 12) }}m</span>
                        </div>
                        <div class="bg-white p-2.5 rounded-xl border border-sky-100">
                            <span class="text-[10px] text-slate-400 block font-sans">Last Ping</span>
                            <span id="profPing">{{ $technician->last_ping_at ? \Carbon\Carbon::parse($technician->last_ping_at)->diffForHumans() : 'Just now' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Sign Out -->
                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row gap-3 justify-end">
                    <a href="{{ route('tech_portal.logout') }}" class="px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs text-center transition">
                        End Field Session &amp; Logout
                    </a>
                </div>
            </div>
        </section>

    </main>

    <footer class="py-4 text-center text-[11px] text-slate-400 font-mono print:hidden">
        <span>Enterprises CRM &bull; Field Technician Portal v{{ config('app.version', '1.0.0') }}</span>
    </footer>

    <!-- ========================================== -->
    <!-- MODAL: KEEP JOB ON HOLD                    -->
    <!-- ========================================== -->
    <div id="modalHoldJob" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-sm w-full p-5 space-y-4 shadow-xl border border-slate-200">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i data-lucide="pause-circle" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Keep Job on Hold</h3>
                        <p class="text-[11px] text-slate-500 font-mono" id="holdServiceNumber">SRV-XXXX</p>
                    </div>
                </div>
                <button type="button" onclick="closeHoldModal()" class="text-slate-400 hover:text-slate-600 p-1">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form id="formHoldJob" onsubmit="submitHoldJob(event)" class="space-y-3">
                <input type="hidden" id="holdServiceId" name="service_id">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reason for Hold</label>
                    <textarea id="holdReason" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-800 focus:outline-none focus:bg-white focus:border-amber-500" placeholder="e.g. Waiting for spare membrane, customer not at home, power cut..."></textarea>
                </div>
                <div class="flex flex-wrap gap-1">
                    <button type="button" onclick="$('#holdReason').val('Customer not at premise')" class="text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-600 px-2 py-0.5 rounded">Not at home</button>
                    <button type="button" onclick="$('#holdReason').val('Awaiting specialized spare part')" class="text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-600 px-2 py-0.5 rounded">Awaiting spares</button>
                    <button type="button" onclick="$('#holdReason').val('Customer requested reschedule')" class="text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-600 px-2 py-0.5 rounded">Reschedule asked</button>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-2">
                    <button type="button" onclick="closeHoldModal()" class="py-2.5 px-3 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit" id="btnConfirmHold" class="py-2.5 px-3 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-2xs">
                        <i data-lucide="pause-circle" class="w-3.5 h-3.5"></i>
                        <span>Confirm Hold</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- ========================================== -->
    <!-- ACTIVE NAVIGATION FLOATING HUD             -->
    <!-- ========================================== -->
    <div id="activeNavHud" class="hidden fixed bottom-16 sm:bottom-6 left-3 right-3 sm:left-auto sm:right-6 sm:max-w-md z-40 bg-[#00152B]/95 text-white backdrop-blur-md border border-sky-500/40 rounded-2xl p-3.5 shadow-2xl space-y-2 animate-in fade-in slide-in-from-bottom-4 duration-200">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
                <span class="text-[10px] font-mono font-bold tracking-wider text-emerald-400 uppercase">ACTIVE NAVIGATION</span>
                <span id="activeNavServiceNum" class="text-[10px] font-mono bg-sky-950/80 text-sky-300 border border-sky-500/30 px-1.5 py-0.5 rounded">SRV-XXXX</span>
            </div>
            <span id="activeNavGpsStatus" class="text-[10px] text-slate-300 font-mono">GPS Live</span>
        </div>

        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <div id="activeNavCustomerName" class="text-xs font-bold text-white truncate">Customer Destination</div>
                <div id="activeNavAddress" class="text-[11px] text-slate-300 truncate">Customer premise address</div>
            </div>
            <div class="text-right shrink-0">
                <div id="activeNavDistance" class="text-xs font-mono font-bold text-sky-400">-- km</div>
                <div id="activeNavEta" class="text-[10px] font-mono text-slate-300">-- min</div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-800">
            <a id="btnActiveNavOpenMaps" href="#" target="_blank" class="py-2 px-3 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs flex items-center justify-center gap-1.5 transition">
                <i data-lucide="external-link" class="w-3.5 h-3.5 text-sky-400"></i>
                <span>Open Maps</span>
            </a>
            <button type="button" onclick="stopActiveNavigation()" class="py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-sm transition cursor-pointer">
                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                <span>I Have Arrived</span>
            </button>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- BOTTOM NAVIGATION APP BAR (ALL SCREENS)    -->
    <!-- ========================================== -->
    <nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200/90 shadow-lg px-4 py-2 flex items-center justify-around sm:justify-center sm:gap-16 select-none">
        <button type="button" onclick="switchTab('assigned')" id="botNav-assigned" class="bot-nav-btn flex flex-col items-center gap-1 py-1 px-4 rounded-xl text-sky-600 font-bold transition">
            <div class="relative">
                <i data-lucide="clipboard-list" class="w-5 h-5"></i>
                @if($assignedServices->count() > 0)
                    <span class="absolute -top-1 -right-2 px-1.5 py-0.2 rounded-full text-[9px] font-mono font-bold bg-sky-600 text-white">
                        {{ $assignedServices->count() }}
                    </span>
                @endif
            </div>
            <span class="text-[11px] font-bold">Assigned</span>
        </button>

        <button type="button" onclick="switchTab('history')" id="botNav-history" class="bot-nav-btn flex flex-col items-center gap-1 py-1 px-4 rounded-xl text-slate-400 hover:text-slate-700 transition">
            <div class="relative">
                <i data-lucide="history" class="w-5 h-5"></i>
                @if($completedToday > 0)
                    <span class="absolute -top-1 -right-2 px-1.5 py-0.2 rounded-full text-[9px] font-mono font-bold bg-emerald-600 text-white">
                        {{ $completedToday }}
                    </span>
                @endif
            </div>
            <span class="text-[11px] font-semibold">History</span>
        </button>

        <button type="button" onclick="switchTab('profile')" id="botNav-profile" class="bot-nav-btn flex flex-col items-center gap-1 py-1 px-4 rounded-xl text-slate-400 hover:text-slate-700 transition">
            <i data-lucide="user" class="w-5 h-5"></i>
            <span class="text-[11px] font-semibold">Profile</span>
        </button>
    </nav>

    <!-- Client-Side JavaScript Logic -->
    <script>
    let curTechLat = {{ $technician->current_latitude ?? 18.5204 }};
    let curTechLng = {{ $technician->current_longitude ?? 73.8567 }};
    let activeNavServiceId = null;
    let activeNavWatchId = null;
    let lastNavPingTime = 0;

    $(document).ready(function() {
        lucide.createIcons();

        // Switch to initial tab if provided in URL (e.g. ?tab=assigned)
        const initialTab = "{{ $activeTab }}";
        if (initialTab && initialTab !== 'assigned' && initialTab !== 'tasks') {
            switchTab(initialTab);
        } else {
            switchTab('assigned');
        }

        // Restore any existing active navigation session
        checkActiveNavigationSession();

        // Check permission state before requesting GPS to avoid Chromium auto-denial
        if (navigator.permissions && navigator.permissions.query) {
            navigator.permissions.query({ name: 'geolocation' }).then(function(perm) {
                if (perm.state === 'granted') {
                    syncDeviceLocation(false);
                }
            }).catch(function() {});
        }
    });

    // Check backend for active navigation session on load/resume
    function checkActiveNavigationSession() {
        $.get('/api/v1/maps/technician/active', function(res) {
            if (res && res.success && res.data) {
                const tracking = res.data;
                const dest = tracking.activeDestination;
                if (dest && dest.serviceId && tracking.trackingStatus === 'ON_THE_WAY') {
                    startContinuousGpsWatch(dest.serviceId, dest, false);
                    if (tracking.distanceKm !== undefined && tracking.distanceKm !== null) {
                        $('#activeNavDistance').text(tracking.distanceKm + ' km');
                    }
                    if (tracking.etaMinutes !== undefined && tracking.etaMinutes !== null) {
                        $('#activeNavEta').text('~' + tracking.etaMinutes + ' min');
                    }
                }
            }
        });
    }

    // Tab Switching System
    function switchTab(tabName) {
        if (tabName === 'tasks') tabName = 'assigned';
        $('.tab-pane').addClass('hidden');
        $('#tabPane-' + tabName).removeClass('hidden');

        // Update Bottom Nav Button Styles
        $('.bot-nav-btn').removeClass('text-sky-600 font-bold').addClass('text-slate-400 font-semibold');
        $('#botNav-' + tabName).removeClass('text-slate-400 font-semibold').addClass('text-sky-600 font-bold');

        lucide.createIcons();

        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Task Filter Pills
    function filterTaskCards(filter) {
        $('.task-filter-btn').removeClass('bg-white text-slate-900 shadow-xs').addClass('text-slate-600 hover:text-slate-900');
        $(`.task-filter-btn[data-filter="${filter}"]`).removeClass('text-slate-600 hover:text-slate-900').addClass('bg-white text-slate-900 shadow-xs');

        if (filter === 'ALL') {
            $('.task-card').show();
        } else {
            $('.task-card').hide();
            $(`.task-card[data-status="${filter}"]`).show();
        }
    }

    // Coordinated Navigate Workflow: Launch Google Maps + Start Live GPS Tracking
    function openGoogleMapsNavigation(serviceId, event) {
        if (!serviceId) return;

        // Concurrency check: single active destination enforcement
        if (activeNavServiceId && String(activeNavServiceId) !== String(serviceId)) {
            if (!confirm("You are currently navigating to another customer destination. Switch active navigation to this service?")) {
                if (event) event.preventDefault();
                return;
            }
        }

        const fallbackUrl = `/tech-portal/services/${serviceId}/navigate`;

        // Start internal navigation session and continuous GPS watch
        $.ajax({
            url: `/tech-portal/services/${serviceId}/navigate`,
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                confirmSwitch: 1
            },
            success: function(res) {
                const destination = res.destination || (res.data ? res.data.destination : null) || {};
                startContinuousGpsWatch(serviceId, destination, true);
                CRM.toast('Live GPS tracking started. Google Maps launched.', 'success');
            },
            error: function(xhr) {
                console.warn("Backend navigation initiation notice:", xhr.status);
            }
        });

        // Let the normal link navigation open Google Maps without popup blocking
        // If event was passed, the browser will open href in new tab synchronously
    }

    function startContinuousGpsWatch(serviceId, destination, triggerInitialPing = true) {
        activeNavServiceId = serviceId;

        // Update HUD
        const srvNumber = destination.serviceNumber || 'SRV-' + serviceId;
        const custName = destination.customerName || 'Customer Destination';
        const mapsUrl = destination.address
            ? `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(destination.address)}&travelmode=driving`
            : ((destination.latitude && destination.longitude)
                ? `https://www.google.com/maps/dir/?api=1&destination=${destination.latitude},${destination.longitude}&travelmode=driving`
                : `/tech-portal/services/${serviceId}/navigate`);

        $('#activeNavServiceNum').text(srvNumber);
        $('#activeNavCustomerName').text(custName);
        $('#activeNavAddress').text(address);
        $('#btnActiveNavOpenMaps').attr('href', mapsUrl);
        $('#activeNavHud').removeClass('hidden');
        lucide.createIcons();

        if (!("geolocation" in navigator)) {
            $('#activeNavGpsStatus').text('No GPS');
            return;
        }

        // Avoid duplicate watchers
        if (activeNavWatchId !== null) {
            navigator.geolocation.clearWatch(activeNavWatchId);
            activeNavWatchId = null;
        }

        // Trigger immediate position fix
        if (triggerInitialPing) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                transmitGpsUpdate(pos);
            }, function(err) {
                handleNavGpsError(err);
            }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 });
        }

        // Continuous watch
        try {
            activeNavWatchId = navigator.geolocation.watchPosition(
                function(pos) {
                    transmitGpsUpdate(pos);
                },
                function(err) {
                    handleNavGpsError(err);
                },
                {
                    enableHighAccuracy: true,
                    maximumAge: 3000,
                    timeout: 15000
                }
            );
        } catch (e) {
            console.warn("watchPosition error:", e);
        }
    }

    function transmitGpsUpdate(pos) {
        curTechLat = pos.coords.latitude;
        curTechLng = pos.coords.longitude;
        const accuracy = pos.coords.accuracy || 10;
        const speed = pos.coords.speed || 0;
        const heading = pos.coords.heading || 0;

        $('#activeNavGpsStatus').text('±' + Math.round(accuracy) + 'm');
        $('#topGpsStatus').text('GPS Live');

        // Throttle server telemetry pings to minimum 3.5 seconds
        const now = Date.now();
        if (now - lastNavPingTime < 3500) {
            return;
        }
        lastNavPingTime = now;

        $.ajax({
            url: "{{ route('tech_portal.gps') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                technician_id: "{{ $technician->id }}",
                latitude: curTechLat,
                longitude: curTechLng,
                accuracy: accuracy,
                speed: speed,
                heading: heading,
                timestamp: Math.round(Date.now() / 1000)
            },
            success: function(res) {
                if (res && res.success) {
                    if (res.distanceKm !== undefined && res.distanceKm !== null) {
                        $('#activeNavDistance').text(res.distanceKm + ' km');
                    }
                    if (res.etaMinutes !== undefined && res.etaMinutes !== null) {
                        $('#activeNavEta').text('~' + res.etaMinutes + ' min');
                    }
                }
            }
        });
    }

    function handleNavGpsError(err) {
        if (err.code === 1) { // PERMISSION_DENIED
            $('#activeNavGpsStatus').text('GPS Denied');
        } else if (err.code === 2) { // POSITION_UNAVAILABLE
            $('#activeNavGpsStatus').text('GPS Weak');
        } else if (err.code === 3) { // TIMEOUT
            $('#activeNavGpsStatus').text('Re-acquiring...');
        }
    }

    // Stop Navigation / Mark Arrived Handler
    function stopActiveNavigation() {
        if (!confirm('Confirm arrival at customer location? This will stop active GPS navigation.')) return;

        // Clear continuous watcher
        if (activeNavWatchId !== null) {
            navigator.geolocation.clearWatch(activeNavWatchId);
            activeNavWatchId = null;
        }

        const stoppedServiceId = activeNavServiceId;
        activeNavServiceId = null;

        $.ajax({
            url: "{{ route('tech_portal.navigate.stop') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                status: 'ARRIVED'
            },
            success: function(res) {
                $('#activeNavHud').addClass('hidden');
                CRM.toast('Active navigation completed. Arrival recorded.', 'success');
                if (stoppedServiceId) {
                    setTimeout(() => {
                        window.location.href = `/tech-portal/services/${stoppedServiceId}`;
                    }, 500);
                }
            },
            error: function() {
                CRM.toast('Failed to stop navigation on server.', 'error');
            }
        });
    }

    function loadNavigationForService(serviceId) {
        openGoogleMapsNavigation(serviceId);
    }

    // Hold Job Handlers
    function promptHoldJob(serviceId, serviceNumber) {
        $('#holdServiceId').val(serviceId);
        $('#holdServiceNumber').text(serviceNumber);
        $('#holdReason').val('Awaiting specialized spare part');
        $('#modalHoldJob').removeClass('hidden');
        lucide.createIcons();
    }

    function closeHoldModal() {
        $('#modalHoldJob').addClass('hidden');
    }

    function submitHoldJob(e) {
        e.preventDefault();
        const serviceId = $('#holdServiceId').val();
        const reason = $('#holdReason').val() || 'Job kept on hold by technician';
        setJobStatus(serviceId, 'ON_HOLD', reason);
        closeHoldModal();
    }

    function setJobStatus(serviceId, status, reasonOrNote) {
        $.ajax({
            url: `/tech-portal/services/${serviceId}/status`,
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                status: status,
                reason: reasonOrNote || ''
            },
            success: function(res) {
                CRM.toast(res.message || 'Status updated.', 'success');
                setTimeout(() => window.location.reload(), 500);
            },
            error: function(xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to update job status.';
                CRM.toast(msg, 'error');
            }
        });
    }

    // Geolocation Streaming Handler
    function syncDeviceLocation(isManual) {
        if (!("geolocation" in navigator)) {
            if (isManual) CRM.toast("Geolocation is unavailable on this device/browser.", "error");
            return;
        }

        navigator.geolocation.getCurrentPosition(function(pos) {
            curTechLat = pos.coords.latitude;
            curTechLng = pos.coords.longitude;

            $.post("{{ route('tech_portal.gps') }}", {
                _token: "{{ csrf_token() }}",
                technician_id: "{{ $technician->id }}",
                latitude: curTechLat,
                longitude: curTechLng,
                accuracy: pos.coords.accuracy,
                speed: pos.coords.speed
            }, function(res) {
                $('#topGpsStatus').text('GPS Live');
                $('#profLat').text(curTechLat.toFixed(5));
                $('#profLng').text(curTechLng.toFixed(5));
                $('#profAcc').text('±' + Math.round(pos.coords.accuracy) + 'm');
                $('#profPing').text('Just now');
                if (isManual) CRM.toast('GPS coordinates updated.', 'success');
            });
        }, function(err) {
            console.log("GPS Notice:", err.message);
            if (isManual) {
                CRM.toast('Could not acquire device GPS: ' + err.message, 'warning');
            }
        }, { enableHighAccuracy: true, timeout: 10000 });
    }
    </script>
</body>
</html>
