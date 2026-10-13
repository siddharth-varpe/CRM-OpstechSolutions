<!DOCTYPE html>
<html lang="en" class="h-full bg-[#F4F6F8]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Active Field Navigation • {{ $service->service_number }} — Enterprises CRM</title>
    
    <!-- Official Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/apple-touch-icon-technician.png">
    
    <!-- Design System & Assets -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="/js/jquery.min.js"></script>
    <script src="/js/lucide.min.js"></script>
    <script src="/js/crm-app.js"></script>
    
    <!-- Local Leaflet Engine -->
    <link rel="stylesheet" href="/css/leaflet.css">
    <script src="/js/leaflet.js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, h4 { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }

        /* Custom pulsing marker */
        .tech-pulse-ring {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            pointer-events: none;
            animation: techPulse 2s cubic-bezier(0.24, 0, 0.38, 1) infinite;
        }
        .tech-pulse-ring.live {
            background-color: rgba(13, 148, 136, 0.35);
            border: 2px solid rgba(20, 184, 166, 0.8);
        }
        @keyframes techPulse {
            0% { transform: translate(-50%, -50%) scale(0.6); opacity: 0.9; }
            70% { transform: translate(-50%, -50%) scale(1.6); opacity: 0; }
            100% { transform: translate(-50%, -50%) scale(1.8); opacity: 0; }
        }

        .tech-marker-bubble {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 800;
            font-size: 13px;
            border: 2.5px solid #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.45);
            background: linear-gradient(135deg, #0284c7, #0d9488);
            position: relative;
            z-index: 2;
        }

        .dest-marker-bubble {
            background: linear-gradient(135deg, #e11d48, #be123c);
            color: white;
            padding: 4px 10px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 11px;
            display: flex;
            align-items: center;
            gap: 4px;
            border: 2px solid #ffffff;
            box-shadow: 0 4px 14px rgba(225, 29, 72, 0.4);
            white-space: nowrap;
        }

        /* Inverted dark mode tiles */
        .leaflet-container {
            background-color: #020617;
            outline: none;
        }
        .dark-tiles {
            filter: brightness(0.78) invert(1) contrast(3) hue-rotate(200deg) saturate(0.3) brightness(0.85);
        }
    </style>
</head>
<body class="min-h-screen bg-workspace text-slate-800 flex flex-col antialiased select-none pb-20">

    <!-- Top Navigation Header in Admin Theme -->
    <header class="bg-[#00152B] text-white border-b border-slate-800 px-4 py-3 sticky top-0 z-30 flex items-center justify-between shadow-md">
        <div class="flex items-center gap-3">
            <a href="{{ route('tech_portal.dashboard') }}" class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-slate-200 hover:text-white transition">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono font-bold text-sky-400 bg-sky-950/60 px-2 py-0.5 rounded border border-sky-500/30">
                        {{ $service->service_number }}
                    </span>
                    <span id="gpsLiveBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5 font-mono">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span id="gpsStatusText">GPS Active</span>
                    </span>
                </div>
                <div class="text-xs text-slate-300 truncate max-w-[200px] sm:max-w-xs mt-0.5 font-medium">
                    {{ $service->customer->name ?? 'Customer Navigation' }}
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ $googleMapsUrl }}" target="_blank"
               class="px-3 py-1.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold flex items-center gap-1.5 transition shadow-sm">
                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                <span class="hidden sm:inline">Google</span> Maps
            </a>
        </div>
    </header>

    <!-- Main Navigation Content -->
    <main class="flex-1 max-w-xl w-full mx-auto p-3 sm:p-4 space-y-3 pb-24">
        
        <!-- Navigation Filter Pills (Matches Admin Theme) -->
        <div class="flex items-center gap-1.5 p-1 bg-white rounded-2xl border border-slate-200/90 shadow-2xs">
            <a href="{{ route('tech_portal.dashboard') }}?tab=tasks"
               class="flex-1 py-2 px-3 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-all text-center">
                Today's Jobs
            </a>
            <span class="flex-1 py-2 px-3 rounded-xl text-xs font-bold bg-sky-600 text-white shadow-sm text-center flex items-center justify-center gap-1">
                <i data-lucide="map" class="w-3.5 h-3.5"></i>
                <span>Map Route</span>
            </span>
            <a href="{{ route('tech_portal.dashboard') }}?tab=execute&service_id={{ $service->id }}"
               class="flex-1 py-2 px-3 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-all text-center">
                Execute
            </a>
        </div>

        <!-- Live Trip Telemetry Banner -->
        <div class="grid grid-cols-3 gap-2 bg-white border border-slate-200/90 rounded-2xl p-3.5 shadow-sm">
            <div class="text-center border-r border-slate-100 pr-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Remaining</span>
                <span id="telemetryDistance" class="text-lg font-bold font-mono text-slate-900 mt-0.5 block">
                    {{ $tech->distance_km ? number_format($tech->distance_km, 1) : '--' }} <span class="text-xs text-slate-400 font-normal">km</span>
                </span>
            </div>
            <div class="text-center border-r border-slate-100 pr-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Est. Time</span>
                <span id="telemetryEta" class="text-lg font-bold font-mono text-sky-600 mt-0.5 block">
                    {{ $tech->eta_minutes ? '~' . $tech->eta_minutes : '--' }} <span class="text-xs text-slate-400 font-normal">min</span>
                </span>
            </div>
            <div class="text-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">GPS Accuracy</span>
                <span id="telemetryAccuracy" class="text-lg font-bold font-mono text-slate-700 mt-0.5 block">
                    {{ $tech->current_accuracy ? '±' . round($tech->current_accuracy) . 'm' : 'Acquiring...' }}
                </span>
            </div>
        </div>

        <!-- Location Permission Needed Banner -->
        <div id="permissionWarningBanner" class="hidden bg-amber-50 border border-amber-200 rounded-2xl p-3.5 text-xs text-amber-800 space-y-2">
            <div class="flex items-center gap-2 font-bold text-amber-900">
                <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 text-amber-600"></i>
                <span>Location Access Required</span>
            </div>
            <p class="text-amber-700 leading-relaxed text-[11px]">
                Real-time field navigation and live dispatcher tracking require device GPS permissions. Please enable location services in your browser.
            </p>
            <button type="button" onclick="requestDeviceLocation(true)"
                    class="w-full py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs flex items-center justify-center gap-1.5 transition">
                <i data-lucide="crosshair" class="w-3.5 h-3.5"></i>
                <span>Grant Location Permission</span>
            </button>
        </div>

        <!-- Interactive Field Map Card -->
        <div class="relative bg-white border border-slate-200/90 rounded-2xl overflow-hidden shadow-sm">
            <div id="navMap" class="w-full h-[380px] sm:h-[420px] z-10"></div>

            <!-- Floating Map Overlay Controls -->
            <div class="absolute top-3 right-3 z-20 flex flex-col gap-2">
                <button type="button" onclick="centerOnTech()"
                        class="p-2.5 bg-white/95 backdrop-blur-md hover:bg-slate-50 text-slate-700 rounded-xl border border-slate-200 shadow-md transition active:scale-95"
                        title="Center My Position">
                    <i data-lucide="crosshair" class="w-4 h-4 text-sky-600"></i>
                </button>
                <button type="button" onclick="fitRouteBounds()"
                        class="p-2.5 bg-white/95 backdrop-blur-md hover:bg-slate-50 text-slate-700 rounded-xl border border-slate-200 shadow-md transition active:scale-95"
                        title="Fit Full Route">
                    <i data-lucide="maximize" class="w-4 h-4 text-slate-600"></i>
                </button>
            </div>

            <!-- In-Map Destination Callout (Bottom of Map) -->
            <div class="absolute bottom-3 left-3 right-3 z-20 bg-white/95 backdrop-blur-md border border-slate-200 rounded-xl p-3 shadow-lg flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center shrink-0 border border-sky-200">
                        <i data-lucide="navigation-2" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-[11px] uppercase tracking-wider font-bold text-sky-700">Target Destination</div>
                        <div class="text-xs text-slate-900 font-medium truncate">{{ $destAddress }}</div>
                    </div>
                </div>
                <a href="{{ $googleMapsUrl }}" target="_blank"
                   class="shrink-0 bg-sky-600 hover:bg-sky-700 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg flex items-center gap-1 shadow-sm transition">
                    <span>Turn-by-Turn</span>
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>

        <!-- Customer Destination Details Card (Matches Admin Theme) -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 space-y-3 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">{{ $service->customer->name ?? 'Direct Customer' }}</h2>
                    <p class="text-xs text-slate-400">{{ $service->customer->company_name ?? 'Residential Client' }}</p>
                </div>
                @if($service->customer && $service->customer->phone)
                    <div class="flex items-center gap-2">
                        <a href="tel:{{ $service->customer->phone }}" class="p-2.5 rounded-xl bg-teal-500/10 hover:bg-teal-500/20 text-teal-400 border border-teal-500/20 text-xs font-semibold flex items-center gap-1.5 transition">
                            <i data-lucide="phone" class="w-4 h-4"></i>
                            <span class="hidden sm:inline">Call</span>
                        </a>
                        <a href="https://wa.me/91{{ preg_replace('/[^0-9]/', '', $service->customer->phone) }}" target="_blank"
                           class="p-2.5 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20 text-xs font-semibold flex items-center gap-1.5 transition">
                            <i data-lucide="message-square" class="w-4 h-4"></i>
                            <span class="hidden sm:inline">WhatsApp</span>
                        </a>
                    </div>
                @endif
            </div>

            <div class="text-xs text-slate-300 pt-2 border-t border-slate-800/80 space-y-1">
                <span class="text-slate-500 text-[10px] uppercase font-bold tracking-wider block">Customer Premise</span>
                <p class="leading-relaxed">{{ $destAddress }}</p>
            </div>

            @if($service->customer_notes)
                <div class="text-xs bg-slate-950/70 p-2.5 rounded-xl border border-slate-800/80">
                    <span class="text-amber-400 font-bold block mb-0.5 text-[10px] uppercase tracking-wider">Reported Issue:</span>
                    <span class="text-slate-300 italic">{{ $service->customer_notes }}</span>
                </div>
            @endif
        </div>
    </main>

    <!-- Bottom Sticky Navigation Action Bar -->
    <div class="fixed bottom-0 left-0 right-0 z-30 bg-slate-900/95 backdrop-blur-md border-t border-slate-800 px-4 py-3">
        <div class="max-w-xl mx-auto flex items-center gap-3">
            <button type="button" id="btnArrivedAtLocation" onclick="markArrivedAtLocation()"
                    class="flex-1 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/30 transition">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
                <span>I Have Arrived</span>
            </button>

            <a href="{{ route('tech_portal.service', $service->id) }}"
               class="bg-slate-800 hover:bg-slate-700 active:bg-slate-600 text-slate-200 font-bold py-3 px-4 rounded-xl text-sm flex items-center justify-center gap-1.5 border border-slate-700 transition">
                <i data-lucide="clipboard-pen" class="w-4 h-4 text-teal-400"></i>
                <span class="hidden sm:inline">Job Card</span>
            </a>
        </div>
    </div>

    <!-- Live Navigation Engine Script -->
    <script>
    const SERVICE_ID = "{{ $service->id }}";
    const TECH_ID = "{{ $tech->id }}";
    const DEST_LAT = {{ $destLat }};
    const DEST_LNG = {{ $destLng }};
    const CSRF_TOKEN = "{{ csrf_token() }}";
    
    // Initial Tech Coordinates (if already known from real ping)
    let currentTechLat = {{ $tech->current_latitude ? $tech->current_latitude : 'null' }};
    let currentTechLng = {{ $tech->current_longitude ? $tech->current_longitude : 'null' }};

    let navMap = null;
    let techMarker = null;
    let accuracyCircle = null;
    let destMarker = null;
    let routeLine = null;
    let watchId = null;
    let lastPingTime = 0;
    let hasAcquiredLiveFix = false;

    $(document).ready(function() {
        lucide.createIcons();
        initNavigationMap();
        startGpsTracking();
    });

    function initNavigationMap() {
        // Initial center: if tech coordinates already known use tech, otherwise center on destination
        const initialCenter = (currentTechLat && currentTechLng)
            ? [currentTechLat, currentTechLng]
            : [DEST_LAT, DEST_LNG];
        
        navMap = L.map('navMap', {
            zoomControl: false,
            attributionControl: false
        }).setView(initialCenter, 14);

        // Carto / OSM Tile Layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            className: 'dark-tiles'
        }).addTo(navMap);

        // Zoom Control
        L.control.zoom({ position: 'bottomright' }).addTo(navMap);

        // 1. Destination Marker (Customer Premise)
        const destIcon = L.divIcon({
            className: '',
            html: `
                <div class="dest-marker-bubble">
                    <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                    <span>Customer Destination</span>
                </div>
            `,
            iconSize: [150, 30],
            iconAnchor: [75, 30]
        });
        destMarker = L.marker([DEST_LAT, DEST_LNG], { icon: destIcon }).addTo(navMap);

        // 2. If real coordinates are already recorded on server, draw initial position
        if (currentTechLat && currentTechLng) {
            createOrUpdateTechMarker(currentTechLat, currentTechLng, {{ $tech->current_accuracy ?? 15 }});
            updateRoutePolyline();
            fitRouteBounds();
        } else {
            // Focus on destination until hardware GPS lock arrives
            navMap.setView([DEST_LAT, DEST_LNG], 15);
        }
    }

    function createOrUpdateTechMarker(lat, lng, accuracy = 10) {
        const pos = [lat, lng];

        const techIcon = L.divIcon({
            className: '',
            html: `
                <div style="position: relative; width: 44px; height: 44px;">
                    <div class="tech-pulse-ring live"></div>
                    <div class="tech-marker-bubble">
                        {{ strtoupper(substr($tech->full_name, 0, 1)) }}
                    </div>
                </div>
            `,
            iconSize: [44, 44],
            iconAnchor: [22, 22]
        });

        if (!techMarker) {
            techMarker = L.marker(pos, { icon: techIcon, zIndexOffset: 1000 }).addTo(navMap);
        } else {
            techMarker.setLatLng(pos);
        }

        // Accuracy Circle
        if (!accuracyCircle) {
            accuracyCircle = L.circle(pos, {
                radius: Math.max(accuracy, 10),
                color: '#0d9488',
                fillColor: '#14b8a6',
                fillOpacity: 0.15,
                weight: 1
            }).addTo(navMap);
        } else {
            accuracyCircle.setLatLng(pos);
            accuracyCircle.setRadius(Math.max(accuracy, 10));
        }
    }

    function updateRoutePolyline() {
        if (!currentTechLat || !currentTechLng) return;

        const latlngs = [
            [currentTechLat, currentTechLng],
            [DEST_LAT, DEST_LNG]
        ];

        if (!routeLine) {
            routeLine = L.polyline(latlngs, {
                color: '#0284c7',
                weight: 4,
                opacity: 0.9,
                dashArray: '8, 6'
            }).addTo(navMap);
        } else {
            routeLine.setLatLngs(latlngs);
        }
    }

    function centerOnTech() {
        if (navMap && currentTechLat && currentTechLng) {
            navMap.setView([currentTechLat, currentTechLng], 16, { animate: true });
        } else if (navMap) {
            navMap.setView([DEST_LAT, DEST_LNG], 15, { animate: true });
        }
    }

    function fitRouteBounds() {
        if (navMap && currentTechLat && currentTechLng) {
            const bounds = L.latLngBounds([
                [currentTechLat, currentTechLng],
                [DEST_LAT, DEST_LNG]
            ]);
            navMap.fitBounds(bounds, { padding: [50, 50], animate: true });
        }
    }

    // Hardware Geolocation & Real-Time GPS Tracking
    function startGpsTracking() {
        if (!("geolocation" in navigator)) {
            $('#gpsStatusText').text('GPS Not Supported');
            $('#gpsLiveBadge').removeClass('bg-emerald-500/10 text-emerald-400 border-emerald-500/20')
                              .addClass('bg-rose-500/10 text-rose-400 border-rose-500/20');
            $('#permissionWarningBanner').removeClass('hidden');
            return;
        }

        $('#gpsStatusText').text('Acquiring GPS Signal...');

        // 1. Immediate hardware GPS fix
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                handlePositionSuccess(pos, true);
            },
            function(err) {
                handlePositionError(err);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );

        // 2. Persistent hardware watchPosition
        try {
            watchId = navigator.geolocation.watchPosition(
                function(pos) {
                    handlePositionSuccess(pos, false);
                },
                function(err) {
                    handlePositionError(err);
                },
                {
                    enableHighAccuracy: true,
                    maximumAge: 3000,
                    timeout: 15000
                }
            );
        } catch (e) {
            console.warn("Geolocation watch error:", e);
        }
    }

    function handlePositionSuccess(pos, isFirstFix) {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        const accuracy = pos.coords.accuracy || 10;
        const speed = pos.coords.speed || 0;
        const heading = pos.coords.heading || 0;

        currentTechLat = lat;
        currentTechLng = lng;

        createOrUpdateTechMarker(lat, lng, accuracy);
        updateRoutePolyline();

        $('#permissionWarningBanner').addClass('hidden');
        $('#gpsStatusText').text('GPS Live Streaming');
        $('#gpsLiveBadge').removeClass('bg-amber-500/10 text-amber-400 border-amber-500/20 bg-rose-500/10 text-rose-400 border-rose-500/20')
                          .addClass('bg-emerald-500/10 text-emerald-400 border-emerald-500/20');
        $('#telemetryAccuracy').text('±' + Math.round(accuracy) + 'm');

        if (isFirstFix || !hasAcquiredLiveFix) {
            hasAcquiredLiveFix = true;
            fitRouteBounds();
        }

        // Throttle server telemetry pings to minimum 3 seconds
        const now = Date.now();
        if (now - lastPingTime >= 3500) {
            sendGpsPingToServer(lat, lng, accuracy, speed, heading);
        }
    }

    function handlePositionError(err) {
        console.warn("Geolocation warning:", err.message, "code:", err.code);
        if (err.code === 1) { // PERMISSION_DENIED
            $('#gpsStatusText').text('Location Access Needed');
            $('#gpsLiveBadge').removeClass('bg-emerald-500/10 text-emerald-400 border-emerald-500/20')
                              .addClass('bg-amber-500/10 text-amber-400 border-amber-500/20');
            $('#permissionWarningBanner').removeClass('hidden');
        } else if (err.code === 2) { // POSITION_UNAVAILABLE
            $('#gpsStatusText').text('Weak GPS Signal');
        } else if (err.code === 3) { // TIMEOUT
            $('#gpsStatusText').text('GPS Re-acquiring');
        }
    }

    function requestDeviceLocation(isUserGesture = false) {
        if (!("geolocation" in navigator)) return;
        
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                handlePositionSuccess(pos, true);
                centerOnTech();
                if (window.showToast) window.showToast('Hardware GPS signal locked!', 'success');
            },
            function(err) {
                handlePositionError(err);
                if (isUserGesture && window.showToast) {
                    window.showToast('Please enable device location services: ' + err.message, 'warning');
                }
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    function sendGpsPingToServer(lat, lng, accuracy = 10, speed = 0, heading = 0) {
        lastPingTime = Date.now();

        $.ajax({
            url: "{{ route('tech_portal.gps') }}",
            type: "POST",
            data: {
                _token: CSRF_TOKEN,
                technician_id: TECH_ID,
                latitude: lat,
                longitude: lng,
                accuracy: accuracy,
                speed: speed,
                heading: heading
            },
            success: function(res) {
                if (res && res.success) {
                    if (res.distanceKm !== undefined && res.distanceKm !== null) {
                        $('#telemetryDistance').html(res.distanceKm + ' <span class="text-xs text-slate-400 font-normal">km</span>');
                    }
                    if (res.etaMinutes !== undefined && res.etaMinutes !== null) {
                        $('#telemetryEta').html('~' + res.etaMinutes + ' <span class="text-xs text-slate-400 font-normal">min</span>');
                    }
                }
            }
        });
    }

    // Check in as Arrived at customer premise
    function markArrivedAtLocation() {
        if (!confirm('Confirm arrival at customer premise?')) return;

        $('#btnArrivedAtLocation').prop('disabled', true).html('<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> Checking in...');
        lucide.createIcons();

        $.ajax({
            url: "{{ route('tech_portal.navigate.stop') }}",
            type: "POST",
            data: {
                _token: CSRF_TOKEN,
                status: 'ARRIVED'
            },
            success: function(res) {
                if (window.showToast) window.showToast('Arrival recorded! Proceeding to job execution.', 'success');
                setTimeout(function() {
                    window.location.href = "{{ route('tech_portal.service', $service->id) }}";
                }, 800);
            },
            error: function() {
                $('#btnArrivedAtLocation').prop('disabled', false).html('<i data-lucide="check-circle" class="w-5 h-5"></i> I Have Arrived');
                lucide.createIcons();
            }
        });
    }
    </script>
</body>
</html>
