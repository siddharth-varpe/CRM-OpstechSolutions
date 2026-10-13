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
    <link rel="stylesheet" href="{{ asset('css/leaflet.css') }}">
    
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <script src="{{ asset('js/leaflet.js') }}"></script>
    <script src="{{ asset('js/crm-app.js') }}"></script>
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        #canvasSignature { touch-action: none; }
        .tech-pulse-ring {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            pointer-events: none;
            background-color: rgba(2, 132, 199, 0.25);
            border: 2px solid rgba(2, 132, 199, 0.8);
            animation: techPulse 2s cubic-bezier(0.24, 0, 0.38, 1) infinite;
        }
        @keyframes techPulse {
            0% { transform: translate(-50%, -50%) scale(0.6); opacity: 0.9; }
            70% { transform: translate(-50%, -50%) scale(1.6); opacity: 0; }
            100% { transform: translate(-50%, -50%) scale(1.8); opacity: 0; }
        }
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
                        <span class="text-[9px] font-bold text-sky-400 tracking-widest uppercase font-mono">CRM</span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="text-xs font-bold text-slate-200">{{ $technician->full_name }}</span>
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-[10px] text-emerald-400 font-semibold" id="topGpsStatus">On Duty</span>
                    </div>
                </div>
            </div>

            <!-- Quick Action Links -->
            <div class="flex items-center gap-2">
                <button type="button" onclick="syncDeviceLocation(true)" class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-slate-200 hover:text-white transition flex items-center gap-1 text-xs font-semibold" title="Sync Live GPS">
                    <i data-lucide="crosshair" class="w-4 h-4 text-sky-400"></i>
                    <span class="hidden sm:inline">Sync GPS</span>
                </button>
                <a href="{{ route('dashboard') }}" class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-slate-200 hover:text-white transition flex items-center gap-1 text-xs font-semibold" title="Return to Desktop CRM">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Desktop CRM</span>
                </a>
                <a href="{{ route('tech_portal.logout') }}" class="p-2 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 hover:text-rose-200 transition" title="Logout">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </a>
            </div>
        </div>

        <!-- Responsive Top Tab Bar (Desktop & Mobile Segmented Strip) -->
        <div class="bg-[#001020] border-t border-slate-800 px-4 sm:px-6">
            <div class="max-w-6xl mx-auto flex items-center gap-1 sm:gap-2 overflow-x-auto no-scrollbar py-2">
                <button type="button" onclick="switchTab('tasks')" id="tabBtn-tasks" class="tab-trigger flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap bg-sky-600 text-white shadow-sm">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    <span>Tasks &amp; Route</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono bg-white/20 text-white">{{ $assignedServices->count() }}</span>
                </button>

                <button type="button" onclick="switchTab('navigation')" id="tabBtn-navigation" class="tab-trigger flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap text-slate-400 hover:text-slate-200 hover:bg-white/5">
                    <i data-lucide="navigation" class="w-4 h-4"></i>
                    <span>Live Map &amp; GPS</span>
                    @if($technician->tracking_status === 'ON_THE_WAY')
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    @endif
                </button>

                <button type="button" onclick="switchTab('execute')" id="tabBtn-execute" class="tab-trigger flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap text-slate-400 hover:text-slate-200 hover:bg-white/5">
                    <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                    <span>Job Card Execution</span>
                </button>

                <button type="button" onclick="switchTab('history')" id="tabBtn-history" class="tab-trigger flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap text-slate-400 hover:text-slate-200 hover:bg-white/5">
                    <i data-lucide="history" class="w-4 h-4"></i>
                    <span>Completed History</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono bg-white/10 text-slate-300">{{ $completedToday }}</span>
                </button>

                <button type="button" onclick="switchTab('profile')" id="tabBtn-profile" class="tab-trigger flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap text-slate-400 hover:text-slate-200 hover:bg-white/5">
                    <i data-lucide="user" class="w-4 h-4"></i>
                    <span>Technician Profile</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Container Area -->
    <main class="flex-1 max-w-6xl w-full mx-auto p-4 sm:p-6 space-y-6">

        <!-- ========================================== -->
        <!-- TAB 1: TASKS & APPOINTMENTS ROUTE          -->
        <!-- ========================================== -->
        <section id="tabPane-tasks" class="tab-pane space-y-5">
            <!-- Key Metric Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-sm">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pending Visits</span>
                    <div class="text-2xl font-bold font-mono text-slate-900 mt-1">{{ $assignedServices->count() }}</div>
                    <div class="text-[11px] text-slate-500 mt-0.5">Assigned route</div>
                </div>

                <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-sm">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">In Progress</span>
                    <div class="text-2xl font-bold font-mono text-amber-600 mt-1">{{ $assignedServices->where('status', 'IN_PROGRESS')->count() }}</div>
                    <div class="text-[11px] text-slate-500 mt-0.5">Under execution</div>
                </div>

                <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-sm">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Completed Today</span>
                    <div class="text-2xl font-bold font-mono text-emerald-600 mt-1">{{ $completedToday }}</div>
                    <div class="text-[11px] text-slate-500 mt-0.5">Closed visits</div>
                </div>

                <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-sm">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">GPS Freshness</span>
                    <div class="text-sm font-bold font-mono text-slate-900 mt-2 truncate">{{ $technician->statusFreshness ?? 'Online' }}</div>
                    <div class="text-[11px] text-slate-500 mt-0.5">Telemetry active</div>
                </div>
            </div>

            <!-- Header & Filter Strip -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                <div>
                    <h2 class="text-base font-display font-extrabold text-slate-900 flex items-center gap-2">
                        <i data-lucide="route" class="w-5 h-5 text-sky-600"></i>
                        <span>Today's Field Appointments</span>
                    </h2>
                    <p class="text-xs text-slate-500">Pick any job to begin turn-by-turn navigation or execute the job card.</p>
                </div>

                <div class="flex items-center gap-1.5 self-start sm:self-auto bg-slate-100 p-1 rounded-xl">
                    <button type="button" onclick="filterTaskCards('ALL')" class="task-filter-btn px-3 py-1 rounded-lg text-xs font-semibold bg-white text-slate-900 shadow-xs" data-filter="ALL">All ({{ $assignedServices->count() }})</button>
                    <button type="button" onclick="filterTaskCards('IN_PROGRESS')" class="task-filter-btn px-3 py-1 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900" data-filter="IN_PROGRESS">In Progress</button>
                    <button type="button" onclick="filterTaskCards('SCHEDULED')" class="task-filter-btn px-3 py-1 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900" data-filter="SCHEDULED">Scheduled</button>
                </div>
            </div>

            <!-- Task Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($assignedServices as $srv)
                    @php
                        $addrStr = '';
                        $lat = 18.5204;
                        $lng = 73.8567;
                        if ($srv->customer && $srv->customer->addresses->isNotEmpty()) {
                            $addr = $srv->customer->addresses->first();
                            $addrStr = "{$addr->address_line1}, {$addr->city} {$addr->pincode}";
                            $lat = (float)($addr->latitude ?? 18.5204);
                            $lng = (float)($addr->longitude ?? 73.8567);
                        }
                    @endphp
                    <div class="task-card bg-white border border-slate-200/90 rounded-2xl p-5 shadow-sm space-y-4 hover:border-sky-300 transition" data-status="{{ $srv->status }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-mono font-bold text-sky-700 bg-sky-50 px-2.5 py-0.5 rounded-lg border border-sky-100">
                                        {{ $srv->service_number }}
                                    </span>
                                    <span class="text-[11px] font-mono text-slate-400">
                                        {{ \Carbon\Carbon::parse($srv->scheduled_date)->format('d M, h:i A') }}
                                    </span>
                                </div>
                                <h3 class="text-base font-bold text-slate-900 mt-1.5">{{ $srv->customer->name ?? 'Direct Customer' }}</h3>
                                <p class="text-xs font-medium text-slate-500 flex items-center gap-1.5 mt-0.5">
                                    <i data-lucide="cpu" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>{{ $srv->asset->custom_name ?? $srv->asset->product->name ?? 'Equipment / Unit' }}</span>
                                    @if(!empty($srv->asset->serial_number))
                                        <span class="font-mono text-[10px] text-slate-400">({{ $srv->asset->serial_number }})</span>
                                    @endif
                                </p>
                            </div>

                            <span class="text-xs px-2.5 py-1 rounded-full font-semibold border shrink-0 {{ $srv->status === 'IN_PROGRESS' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-sky-50 text-sky-700 border-sky-200' }}">
                                {{ $srv->status === 'IN_PROGRESS' ? 'In Progress' : 'Scheduled' }}
                            </span>
                        </div>

                        <!-- Customer Address & Contact Info -->
                        <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200/70 space-y-2 text-xs text-slate-700">
                            @if($srv->customer && $srv->customer->phone)
                                <div class="flex items-center justify-between">
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
                                <span class="leading-relaxed">{{ $addrStr ?: 'Pune, Maharashtra' }}</span>
                            </div>
                        </div>

                        <!-- Action Buttons in Admin Theme -->
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <button type="button" onclick="loadNavigationForService('{{ $srv->id }}', '{{ addslashes($srv->customer->name ?? 'Customer') }}', '{{ addslashes($addrStr) }}', {{ $lat }}, {{ $lng }})" class="bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200/80 font-bold py-2.5 px-3 rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-2xs">
                                <i data-lucide="navigation" class="w-3.5 h-3.5"></i>
                                <span>Navigate Route</span>
                            </button>

                            <button type="button" onclick="loadExecutionForService('{{ $srv->id }}')" class="bg-sky-600 hover:bg-sky-700 text-white font-bold py-2.5 px-3 rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-2xs">
                                <i data-lucide="clipboard-check" class="w-3.5 h-3.5"></i>
                                <span>Execute Job</span>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-white border border-slate-200/90 rounded-2xl p-10 text-center space-y-3">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto">
                            <i data-lucide="check-circle" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-900">All Scheduled Route Tasks Completed</h3>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto">You have no pending field appointments remaining today. You may review past records in History or stand by for new dispatches.</p>
                    </div>
                @endforelse
            </div>
        </section>


        <!-- ========================================== -->
        <!-- TAB 2: LIVE MAP & GPS NAVIGATION           -->
        <!-- ========================================== -->
        <section id="tabPane-navigation" class="tab-pane hidden space-y-4">
            <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <span class="text-[10px] font-bold text-sky-700 uppercase tracking-widest bg-sky-50 px-2 py-0.5 rounded border border-sky-100">Live Field Navigation</span>
                        <h2 class="text-base sm:text-lg font-display font-extrabold text-slate-900 mt-1 flex items-center gap-2">
                            <span id="navActiveCustName">{{ $activeService->customer->name ?? 'Select Appointment' }}</span>
                            <span id="navActiveStatusBadge" class="text-xs px-2.5 py-0.5 rounded-full font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                {{ $technician->tracking_status ?? 'STANDBY' }}
                            </span>
                        </h2>
                        <p id="navActiveAddress" class="text-xs text-slate-500 mt-0.5">
                            {{ $activeService && $activeService->customer && $activeService->customer->addresses->isNotEmpty() ? $activeService->customer->addresses->first()->full_address : 'Pune, Maharashtra' }}
                        </p>
                    </div>

                    <!-- Destination Switcher -->
                    <div class="flex items-center gap-2 self-start sm:self-auto">
                        <select id="selectNavDestination" onchange="onNavSelectChange(this.value)" class="bg-slate-50 border border-slate-200 text-xs font-semibold rounded-xl px-3 py-2 text-slate-800 focus:outline-none focus:bg-white focus:border-sky-500">
                            @foreach($assignedServices as $srv)
                                @php
                                    $a = $srv->customer->addresses->first();
                                    $sLat = (float)($a->latitude ?? 18.5204);
                                    $sLng = (float)($a->longitude ?? 73.8567);
                                    $sAddr = $a ? $a->full_address : 'Pune';
                                @endphp
                                <option value="{{ $srv->id }}" 
                                        data-name="{{ $srv->customer->name ?? 'Customer' }}"
                                        data-addr="{{ $sAddr }}"
                                        data-lat="{{ $sLat }}"
                                        data-lng="{{ $sLng }}"
                                        {{ $activeService && $activeService->id === $srv->id ? 'selected' : '' }}>
                                    {{ $srv->service_number }} — {{ $srv->customer->name ?? 'Customer' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Navigation Controls Bar -->
                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 text-xs">
                    <div class="flex items-center gap-2 font-mono">
                        <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-semibold" id="navDistancePill">
                            Distance: <strong class="text-slate-900" id="navDistanceValue">2.8 km</strong>
                        </span>
                        <span class="px-2.5 py-1 rounded-lg bg-sky-50 text-sky-800 font-semibold border border-sky-100" id="navEtaPill">
                            ETA: <strong class="text-sky-900" id="navEtaValue">10 mins</strong>
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" id="btnStartNav" onclick="handleStartNavigation()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
                            <i data-lucide="navigation" class="w-3.5 h-3.5"></i>
                            <span>Start Navigation</span>
                        </button>
                        <button type="button" id="btnStopNav" onclick="handleStopNavigation()" class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            <span>Mark Arrived</span>
                        </button>
                        <a id="btnGoogleMapsExt" href="https://www.google.com/maps" target="_blank" class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 font-semibold px-3 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs" title="Open Google Maps App">
                            <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i>
                            <span class="hidden sm:inline">Google Maps</span>
                        </a>
                    </div>
                </div>

                <!-- Leaflet Interactive Map -->
                <div class="relative w-full h-[380px] sm:h-[460px] rounded-2xl overflow-hidden border border-slate-200 shadow-inner">
                    <div id="mapPortalNav" class="w-full h-full z-10"></div>
                </div>
            </div>
        </section>


        <!-- ========================================== -->
        <!-- TAB 3: JOB CARD EXECUTION & CHECKLIST      -->
        <!-- ========================================== -->
        <section id="tabPane-execute" class="tab-pane hidden space-y-4">
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-7 shadow-sm space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                    <div>
                        <span class="text-[10px] font-bold text-sky-700 uppercase tracking-widest bg-sky-50 px-2 py-0.5 rounded border border-sky-100">Official Field Service Documentation</span>
                        <h2 class="text-lg font-display font-extrabold text-slate-900 mt-1">Execute Job Card</h2>
                        <p class="text-xs text-slate-500">Record parts replaced, TDS levels, labor fees, and customer digital signature.</p>
                    </div>

                    <!-- Job Card Target Selector -->
                    <div>
                        <select id="selectExecJob" onchange="onExecSelectChange(this.value)" class="bg-slate-50 border border-slate-200 text-xs font-semibold rounded-xl px-3.5 py-2 text-slate-800 focus:outline-none focus:bg-white focus:border-sky-500">
                            @foreach($assignedServices as $srv)
                                <option value="{{ $srv->id }}" {{ $activeService && $activeService->id === $srv->id ? 'selected' : '' }}>
                                    {{ $srv->service_number }} — {{ $srv->customer->name ?? 'Customer' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <form id="formCompleteJobCard" onsubmit="handleJobCardSubmit(event)" class="space-y-6">
                    @csrf
                    <input type="hidden" id="execServiceId" name="service_id" value="{{ $activeService->id ?? '' }}">

                    <!-- Service Snapshot Banner -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Customer Name</span>
                            <div class="font-bold text-slate-900 mt-0.5" id="execCustName">{{ $activeService->customer->name ?? '—' }}</div>
                            <div class="text-slate-500 font-mono text-[11px]" id="execCustPhone">{{ $activeService->customer->phone ?? '—' }}</div>
                        </div>

                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Model &amp; S/N</span>
                            <div class="font-bold text-slate-900 mt-0.5" id="execAssetModel">{{ $activeService->asset->custom_name ?? $activeService->asset->product->name ?? 'Equipment Unit' }}</div>
                            <div class="text-slate-500 font-mono text-[11px]" id="execAssetSerial">S/N: {{ $activeService->asset->serial_number ?? 'N/A' }}</div>
                        </div>

                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Warranty Status</span>
                            <div class="font-bold text-emerald-600 mt-0.5">Active Warranty</div>
                            <div class="text-slate-500 text-[11px]">Free periodic service check</div>
                        </div>
                    </div>

                    <!-- Diagnosis & Work Performed -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Problem Diagnosis &amp; Root Cause</label>
                            <textarea id="execDiagnosis" name="diagnosis" rows="3" required placeholder="Describe technical issue, membrane condition, pressure pump state..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:outline-none focus:bg-white focus:border-sky-500 transition">Periodic Preventive Maintenance & Filter Inspection</textarea>
                            <!-- Quick templates -->
                            <div class="flex flex-wrap gap-1 mt-1.5">
                                <button type="button" onclick="$('#execDiagnosis').val('Periodic Preventive Maintenance & Filter Inspection')" class="text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-600 px-2 py-0.5 rounded">PM Inspection</button>
                                <button type="button" onclick="$('#execDiagnosis').val('Low pure water flow, membrane choked with mineral scale')" class="text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-600 px-2 py-0.5 rounded">Low Flow / Choked</button>
                                <button type="button" onclick="$('#execDiagnosis').val('High TDS level, alkaline cartridge exhausted, taste issue')" class="text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-600 px-2 py-0.5 rounded">High TDS</button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Work Performed &amp; Rectification</label>
                            <textarea id="execWorkPerformed" name="work_performed" rows="3" required placeholder="Actions taken, membrane flushing, filter replacements, disinfection..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:outline-none focus:bg-white focus:border-sky-500 transition">Filter replacement, membrane flushing & sanitization</textarea>
                            <!-- Quick templates -->
                            <div class="flex flex-wrap gap-1 mt-1.5">
                                <button type="button" onclick="$('#execWorkPerformed').val('Replaced Sediment & Pre-Carbon Filters, flushed membrane')" class="text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-600 px-2 py-0.5 rounded">Filters Replaced</button>
                                <button type="button" onclick="$('#execWorkPerformed').val('Membrane replaced, TDS calibrated to 75 ppm, leak test OK')" class="text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-600 px-2 py-0.5 rounded">Membrane & TDS Cal</button>
                            </div>
                        </div>
                    </div>

                    <!-- Water Quality TDS Meter Readings -->
                    <div class="bg-sky-50/60 border border-sky-100 rounded-2xl p-4 space-y-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="droplet" class="w-4 h-4 text-sky-600"></i>
                            <h4 class="text-xs font-bold text-sky-950 uppercase tracking-wider">Water Quality TDS Meter Calibration</h4>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Inlet Raw Water TDS (ppm)</label>
                                <input type="number" name="inlet_tds" value="480" placeholder="e.g. 520" class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 font-mono focus:outline-none focus:border-sky-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Pure Outlet TDS (ppm)</label>
                                <input type="number" name="outlet_tds" value="65" placeholder="e.g. 60" class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 font-mono focus:outline-none focus:border-sky-500">
                            </div>
                        </div>
                    </div>

                    <!-- Spare Parts Replaced & Commercial Billing -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Inventory Spare Parts Consumed</label>
                            <button type="button" onclick="addSparePartRow()" class="text-xs font-semibold text-sky-600 hover:text-sky-700 flex items-center gap-1">
                                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                                <span>Add Part</span>
                            </button>
                        </div>

                        <div id="sparesContainer" class="space-y-2">
                            <div class="spare-row grid grid-cols-1 sm:grid-cols-12 gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-200/80 items-center">
                                <div class="sm:col-span-6">
                                    <select name="parts_replaced[0][item_id]" onchange="onSpareItemSelect(this)" class="spare-select w-full bg-white border border-slate-200 rounded-lg px-2.5 py-2 text-xs text-slate-800">
                                        <option value="">Select Spare Part...</option>
                                        @foreach($spares as $sp)
                                            <option value="{{ $sp->id }}" data-price="{{ $sp->selling_price }}">{{ $sp->name }} (₹{{ number_format($sp->selling_price, 2) }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <input type="number" name="parts_replaced[0][quantity]" value="1" min="1" onchange="recalculateCharges()" placeholder="Qty" class="spare-qty w-full bg-white border border-slate-200 rounded-lg px-2 py-2 text-xs text-center font-mono">
                                </div>
                                <div class="sm:col-span-3">
                                    <input type="number" name="parts_replaced[0][price]" value="0" step="0.01" onchange="recalculateCharges()" placeholder="Cost ₹" class="spare-price w-full bg-white border border-slate-200 rounded-lg px-2 py-2 text-xs font-mono text-right">
                                </div>
                                <div class="sm:col-span-1 text-right">
                                    <button type="button" onclick="$(this).closest('.spare-row').remove(); recalculateCharges();" class="p-1.5 text-slate-400 hover:text-rose-600">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary & Labor -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-slate-100">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Labor / Service Charge (₹)</label>
                            <input type="number" id="laborCharges" name="labor_charges" value="250" step="0.01" onchange="recalculateCharges()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono text-slate-800 focus:outline-none focus:bg-white focus:border-sky-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Total Spares Cost (₹)</label>
                            <input type="number" id="partsCharges" name="parts_charges" value="0" step="0.01" readonly class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono text-slate-700">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-900 mb-1">Total Billing Amount (₹)</label>
                            <div class="w-full bg-sky-50 border border-sky-200 text-sky-900 font-mono font-bold rounded-xl px-3 py-2 text-sm text-right" id="totalChargesDisplay">₹250.00</div>
                        </div>
                    </div>

                    <!-- Digital Customer Signature Touch Pad -->
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                <i data-lucide="pen-tool" class="w-3.5 h-3.5 text-sky-600"></i>
                                <span>Customer Digital Signature</span>
                            </label>
                            <button type="button" onclick="clearSignaturePad()" class="text-xs text-rose-600 hover:text-rose-700 font-semibold">Clear</button>
                        </div>

                        <div class="bg-slate-50 rounded-2xl border border-slate-200 p-2 shadow-inner">
                            <canvas id="canvasSignature" class="w-full h-36 bg-white rounded-xl border border-dashed border-slate-300 cursor-crosshair"></canvas>
                            <p class="text-[10px] text-slate-400 text-center mt-1.5">Customer signature confirming satisfactory installation/service</p>
                        </div>
                    </div>

                    <!-- Submission Action -->
                    <div class="pt-2">
                        <button type="submit" id="btnSubmitJobCard" class="w-full bg-sky-600 hover:bg-sky-700 active:bg-sky-800 text-white font-bold py-3.5 px-4 rounded-xl text-sm transition flex items-center justify-center gap-2 shadow-sm cursor-pointer">
                            <i data-lucide="check-check" class="w-4 h-4"></i>
                            <span id="btnSubmitJobCardText">Submit &amp; Complete Official Job Card</span>
                        </button>
                    </div>
                </form>
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
                    <a href="{{ route('dashboard') }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs text-center transition">
                        Open Full Desktop CRM
                    </a>
                    <a href="{{ route('tech_portal.logout') }}" class="px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs text-center transition">
                        End Field Session &amp; Logout
                    </a>
                </div>
            </div>
        </section>

    </main>

    <!-- ========================================== -->
    <!-- MOBILE BOTTOM NAVIGATION APP BAR           -->
    <!-- ========================================== -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-slate-200/90 shadow-lg px-2 py-1.5 flex items-center justify-around select-none">
        <button type="button" onclick="switchTab('tasks')" id="botNav-tasks" class="bot-nav-btn flex flex-col items-center gap-1 py-1 px-3 rounded-xl text-sky-600">
            <i data-lucide="calendar" class="w-5 h-5"></i>
            <span class="text-[10px] font-bold">Tasks</span>
        </button>

        <button type="button" onclick="switchTab('navigation')" id="botNav-navigation" class="bot-nav-btn flex flex-col items-center gap-1 py-1 px-3 rounded-xl text-slate-400 hover:text-slate-700">
            <i data-lucide="navigation" class="w-5 h-5"></i>
            <span class="text-[10px] font-semibold">Map</span>
        </button>

        <button type="button" onclick="switchTab('execute')" id="botNav-execute" class="bot-nav-btn flex flex-col items-center gap-1 py-1 px-3 rounded-xl text-slate-400 hover:text-slate-700">
            <i data-lucide="clipboard-check" class="w-5 h-5"></i>
            <span class="text-[10px] font-semibold">Job Card</span>
        </button>

        <button type="button" onclick="switchTab('history')" id="botNav-history" class="bot-nav-btn flex flex-col items-center gap-1 py-1 px-3 rounded-xl text-slate-400 hover:text-slate-700">
            <i data-lucide="history" class="w-5 h-5"></i>
            <span class="text-[10px] font-semibold">History</span>
        </button>

        <button type="button" onclick="switchTab('profile')" id="botNav-profile" class="bot-nav-btn flex flex-col items-center gap-1 py-1 px-3 rounded-xl text-slate-400 hover:text-slate-700">
            <i data-lucide="user" class="w-5 h-5"></i>
            <span class="text-[10px] font-semibold">Profile</span>
        </button>
    </nav>

    <!-- Client-Side JavaScript Logic -->
    <script>
    let portalMap = null;
    let techMarker = null;
    let destMarker = null;
    let routeLine = null;
    let currentNavServiceId = "{{ $activeService->id ?? '' }}";
    let curTechLat = {{ $technician->current_latitude ?? 18.5204 }};
    let curTechLng = {{ $technician->current_longitude ?? 73.8567 }};
    let curDestLat = 18.5204;
    let curDestLng = 73.8567;
    let isDrawing = false;
    let sigCanvas, sigCtx;

    $(document).ready(function() {
        lucide.createIcons();
        initSignaturePad();

        // Switch to initial tab if provided in URL (e.g. ?tab=navigation)
        const initialTab = "{{ $activeTab }}";
        if (initialTab && initialTab !== 'tasks') {
            switchTab(initialTab);
        }

        // Initialize GPS sync
        syncDeviceLocation(false);
    });

    // Tab Switching System
    function switchTab(tabName) {
        $('.tab-pane').addClass('hidden');
        $('#tabPane-' + tabName).removeClass('hidden');

        // Update Desktop/Top Tab Trigger Styles
        $('.tab-trigger').removeClass('bg-sky-600 text-white shadow-sm').addClass('text-slate-400 hover:text-slate-200 hover:bg-white/5');
        $('#tabBtn-' + tabName).removeClass('text-slate-400 hover:text-slate-200 hover:bg-white/5').addClass('bg-sky-600 text-white shadow-sm');

        // Update Mobile Bottom Nav Button Styles
        $('.bot-nav-btn').removeClass('text-sky-600 font-bold').addClass('text-slate-400 font-semibold');
        $('#botNav-' + tabName).removeClass('text-slate-400 font-semibold').addClass('text-sky-600 font-bold');

        lucide.createIcons();

        // Lazy initialize or invalidate Leaflet map if switching to Navigation
        if (tabName === 'navigation') {
            setTimeout(function() {
                if (!portalMap) {
                    initPortalMap();
                } else {
                    portalMap.invalidateSize();
                }
            }, 100);
        }

        // Resize signature canvas if switching to Execute
        if (tabName === 'execute') {
            setTimeout(resizeSignatureCanvas, 100);
        }

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

    // Navigation Initialization
    function initPortalMap() {
        const $mapEl = $('#mapPortalNav');
        if ($mapEl.length === 0) return;

        portalMap = L.map('mapPortalNav', { zoomControl: true }).setView([curTechLat, curTechLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap • Enterprises CRM'
        }).addTo(portalMap);

        // Custom pulsing marker for technician
        const techIcon = L.divIcon({
            className: 'tech-marker-wrapper relative',
            html: '<div class="tech-pulse-ring"></div><div class="w-8 h-8 rounded-full bg-[#0284C7] text-white flex items-center justify-center font-bold text-xs shadow-md border-2 border-white">🛵</div>',
            iconSize: [36, 36],
            iconAnchor: [18, 18]
        });

        techMarker = L.marker([curTechLat, curTechLng], { icon: techIcon }).addTo(portalMap)
            .bindPopup("<strong>{{ $technician->full_name }}</strong><br>Your Live Location");

        // Customer Destination Marker
        const destIcon = L.divIcon({
            className: 'dest-marker-wrapper',
            html: '<div class="w-8 h-8 rounded-full bg-rose-600 text-white flex items-center justify-center font-bold text-xs shadow-md border-2 border-white">📍</div>',
            iconSize: [36, 36],
            iconAnchor: [18, 36]
        });

        destMarker = L.marker([curDestLat, curDestLng], { icon: destIcon }).addTo(portalMap)
            .bindPopup("<strong>Customer Location</strong>");

        // Route Polyline
        routeLine = L.polyline([[curTechLat, curTechLng], [curDestLat, curDestLng]], {
            color: '#0284C7',
            weight: 5,
            opacity: 0.85,
            dashArray: '8, 8'
        }).addTo(portalMap);

        // Fit bounds
        fitMapBounds();
    }

    function fitMapBounds() {
        if (!portalMap || !techMarker || !destMarker) return;
        const group = new L.featureGroup([techMarker, destMarker]);
        portalMap.fitBounds(group.getBounds().pad(0.3));
    }

    function loadNavigationForService(serviceId, name, address, lat, lng) {
        currentNavServiceId = serviceId;
        curDestLat = lat || 18.5204;
        curDestLng = lng || 73.8567;

        $('#navActiveCustName').text(name);
        $('#navActiveAddress').text(address || 'Pune, Maharashtra');
        $('#selectNavDestination').val(serviceId);
        $('#btnGoogleMapsExt').attr('href', `https://www.google.com/maps/dir/?api=1&destination=${curDestLat},${curDestLng}`);

        switchTab('navigation');

        if (portalMap) {
            destMarker.setLatLng([curDestLat, curDestLng]);
            routeLine.setLatLngs([[curTechLat, curTechLng], [curDestLat, curDestLng]]);
            fitMapBounds();
        }

        // Calculate distance
        updateDistanceDisplay();
    }

    function onNavSelectChange(serviceId) {
        const $opt = $(`#selectNavDestination option[value="${serviceId}"]`);
        if ($opt.length) {
            loadNavigationForService(
                serviceId, 
                $opt.data('name'), 
                $opt.data('addr'), 
                parseFloat($opt.data('lat')), 
                parseFloat($opt.data('lng'))
            );
        }
    }

    function updateDistanceDisplay() {
        const rad = Math.PI / 180;
        const dLat = (curDestLat - curTechLat) * rad;
        const dLon = (curDestLng - curTechLng) * rad;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(curTechLat * rad) * Math.cos(curDestLat * rad) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        const distKm = (6371 * c).toFixed(1);
        const etaMins = Math.round(distKm * 3.5);

        $('#navDistanceValue').text(distKm + ' km');
        $('#navEtaValue').text(etaMins + ' mins');
    }

    function handleStartNavigation() {
        if (!currentNavServiceId) {
            CRM.toast('Please select an active service.', 'error');
            return;
        }

        $.post(`/tech-portal/services/${currentNavServiceId}/navigate`, {
            _token: "{{ csrf_token() }}"
        }, function(res) {
            CRM.toast('Turn-by-turn navigation started. Telemetry active.', 'success');
            $('#navActiveStatusBadge').text('ON_THE_WAY').removeClass('bg-emerald-50 text-emerald-700').addClass('bg-amber-50 text-amber-700');
        });
    }

    function handleStopNavigation() {
        $.post("{{ route('tech_portal.navigate.stop') }}", {
            _token: "{{ csrf_token() }}",
            status: 'ARRIVED'
        }, function(res) {
            CRM.toast('Marked as Arrived at customer premises.', 'success');
            $('#navActiveStatusBadge').text('ARRIVED').removeClass('bg-amber-50 text-amber-700').addClass('bg-emerald-50 text-emerald-700');
        });
    }

    // Job Card Execution Setup
    function loadExecutionForService(serviceId) {
        $('#selectExecJob').val(serviceId);
        $('#execServiceId').val(serviceId);
        onExecSelectChange(serviceId);
        switchTab('execute');
    }

    function onExecSelectChange(serviceId) {
        $('#execServiceId').val(serviceId);
        // Find matching card data
        const $card = $(`.task-card button[onclick*="${serviceId}"]`).closest('.task-card');
        if ($card.length) {
            $('#execCustName').text($card.find('h3').text().trim());
        }
    }

    function addSparePartRow() {
        const index = $('.spare-row').length;
        const html = `
            <div class="spare-row grid grid-cols-1 sm:grid-cols-12 gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-200/80 items-center">
                <div class="sm:col-span-6">
                    <select name="parts_replaced[${index}][item_id]" onchange="onSpareItemSelect(this)" class="spare-select w-full bg-white border border-slate-200 rounded-lg px-2.5 py-2 text-xs text-slate-800">
                        <option value="">Select Spare Part...</option>
                        @foreach($spares as $sp)
                            <option value="{{ $sp->id }}" data-price="{{ $sp->selling_price }}">{{ $sp->name }} (₹{{ number_format($sp->selling_price, 2) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <input type="number" name="parts_replaced[${index}][quantity]" value="1" min="1" onchange="recalculateCharges()" placeholder="Qty" class="spare-qty w-full bg-white border border-slate-200 rounded-lg px-2 py-2 text-xs text-center font-mono">
                </div>
                <div class="sm:col-span-3">
                    <input type="number" name="parts_replaced[${index}][price]" value="0" step="0.01" onchange="recalculateCharges()" placeholder="Cost ₹" class="spare-price w-full bg-white border border-slate-200 rounded-lg px-2 py-2 text-xs font-mono text-right">
                </div>
                <div class="sm:col-span-1 text-right">
                    <button type="button" onclick="$(this).closest('.spare-row').remove(); recalculateCharges();" class="p-1.5 text-slate-400 hover:text-rose-600">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>`;
        $('#sparesContainer').append(html);
        lucide.createIcons();
    }

    function onSpareItemSelect(selectEl) {
        const $row = $(selectEl).closest('.spare-row');
        const price = $(selectEl).find('option:selected').data('price') || 0;
        $row.find('.spare-price').val(price);
        recalculateCharges();
    }

    function recalculateCharges() {
        let partsTotal = 0;
        $('.spare-row').each(function() {
            const qty = parseFloat($(this).find('.spare-qty').val()) || 0;
            const price = parseFloat($(this).find('.spare-price').val()) || 0;
            partsTotal += (qty * price);
        });

        const labor = parseFloat($('#laborCharges').val()) || 0;
        const grandTotal = labor + partsTotal;

        $('#partsCharges').val(partsTotal.toFixed(2));
        $('#totalChargesDisplay').text('₹' + grandTotal.toFixed(2));
    }

    // Touch Signature Pad
    function initSignaturePad() {
        sigCanvas = document.getElementById('canvasSignature');
        if (!sigCanvas) return;
        sigCtx = sigCanvas.getContext('2d');

        function start(e) {
            isDrawing = true;
            sigCtx.beginPath();
            const rect = sigCanvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            sigCtx.moveTo(clientX - rect.left, clientY - rect.top);
            e.preventDefault();
        }

        function draw(e) {
            if (!isDrawing) return;
            sigCtx.lineWidth = 2.5;
            sigCtx.lineCap = 'round';
            sigCtx.strokeStyle = '#0F172A';
            const rect = sigCanvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            sigCtx.lineTo(clientX - rect.left, clientY - rect.top);
            sigCtx.stroke();
            e.preventDefault();
        }

        function stop() { isDrawing = false; }

        sigCanvas.addEventListener('mousedown', start);
        sigCanvas.addEventListener('mousemove', draw);
        window.addEventListener('mouseup', stop);

        sigCanvas.addEventListener('touchstart', start, { passive: false });
        sigCanvas.addEventListener('touchmove', draw, { passive: false });
        window.addEventListener('touchend', stop);

        resizeSignatureCanvas();
    }

    function resizeSignatureCanvas() {
        if (!sigCanvas) return;
        const rect = sigCanvas.parentElement.getBoundingClientRect();
        sigCanvas.width = rect.width - 16;
        sigCanvas.height = 130;
    }

    function clearSignaturePad() {
        if (!sigCtx) return;
        sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
    }

    function handleJobCardSubmit(e) {
        e.preventDefault();
        const srvId = $('#execServiceId').val();
        if (!srvId) {
            CRM.toast('Please select an active service to complete.', 'error');
            return;
        }

        const $btn = $('#btnSubmitJobCard');
        $btn.prop('disabled', true).addClass('opacity-75');
        $('#btnSubmitJobCardText').text('Completing Job Card...');

        const formData = new FormData(document.getElementById('formCompleteJobCard'));
        if (sigCanvas) {
            formData.append('signature_data', sigCanvas.toDataURL());
        }

        $.ajax({
            url: `/tech-portal/services/${srvId}/complete`,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                CRM.toast(res.message || 'Job Card successfully closed!', 'success');
                setTimeout(() => window.location.reload(), 600);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).removeClass('opacity-75');
                $('#btnSubmitJobCardText').text('Submit & Complete Official Job Card');
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to complete job card.';
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

                if (techMarker) {
                    techMarker.setLatLng([curTechLat, curTechLng]);
                    routeLine.setLatLngs([[curTechLat, curTechLng], [curDestLat, curDestLng]]);
                }
                if (isManual) CRM.toast('GPS coordinates updated.', 'success');
            });
        }, function(err) {
            console.log("GPS Notice:", err.message);
            // Local dev fallback if permission denied or non-secure origin
            if (isManual) {
                $.post("{{ route('tech_portal.gps') }}", {
                    _token: "{{ csrf_token() }}",
                    technician_id: "{{ $technician->id }}",
                    latitude: 18.5204,
                    longitude: 73.8567,
                    accuracy: 10,
                    speed: 0
                }, function(res) {
                    CRM.toast('GPS calibrated for Pune field area.', 'info');
                });
            }
        }, { enableHighAccuracy: true, timeout: 10000 });
    }
    </script>
</body>
</html>
