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
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 flex flex-col antialiased select-none pb-20">

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
                    <span id="gpsLiveBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-700 text-slate-300 border border-slate-600 flex items-center gap-1.5 font-mono">
                        <span id="gpsLiveDot" class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                        <span id="gpsStatusText">GPS Standby</span>
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

        <!-- Insecure Origin Warning Banner (HTTPS requirement for mobile browsers) -->
        <div id="insecureOriginBanner" class="hidden bg-rose-50 border border-rose-200 rounded-2xl p-3.5 text-xs text-rose-800 space-y-1.5">
            <div class="flex items-center gap-2 font-bold text-rose-900">
                <i data-lucide="shield-alert" class="w-4 h-4 shrink-0 text-rose-600"></i>
                <span>Secure Origin Required for Mobile GPS</span>
            </div>
            <p class="text-rose-700 leading-relaxed text-[11px]">
                Mobile browsers strictly block device Geolocation over unencrypted HTTP LAN connections. Use <strong>HTTPS</strong> or access via <strong>localhost</strong> on this device to enable live hardware GPS.
            </p>
        </div>

        <!-- Missing Destination Address Banner (Only if neither address nor coordinates exist) -->
        @if(empty(trim($destAddress)) && (!$destLat || !$destLng))
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-3.5 text-xs text-amber-800 space-y-1">
                <div class="flex items-center gap-2 font-bold text-amber-900">
                    <i data-lucide="map-pin-off" class="w-4 h-4 shrink-0 text-amber-600"></i>
                    <span>Customer Premise Address Missing</span>
                </div>
                <p class="text-amber-700 leading-relaxed text-[11px]">
                    No premise address or GPS coordinates are configured for this customer.
                </p>
            </div>
        @endif

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
                    {{ $tech->current_accuracy ? '±' . round($tech->current_accuracy) . 'm' : 'Standby' }}
                </span>
            </div>
        </div>

        <!-- Location Permission Needed Banner -->
        <div id="permissionWarningBanner" class="hidden bg-amber-50 border border-amber-200 rounded-2xl p-3.5 text-xs text-amber-800 space-y-2">
            <div class="flex items-center gap-2 font-bold text-amber-900">
                <i data-lucide="navigation" class="w-4 h-4 shrink-0 text-amber-600"></i>
                <span id="permissionBannerTitle">Enable GPS Live Tracking</span>
            </div>
            <p id="permissionBannerMessage" class="text-amber-700 leading-relaxed text-[11px]">
                Real-time field navigation and live dispatcher tracking require device GPS permissions. Tap below to start continuous tracking.
            </p>
            <button type="button" id="btnRequestPermission" onclick="requestDeviceLocation(true)"
                    class="w-full py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs flex items-center justify-center gap-1.5 transition">
                <i data-lucide="crosshair" class="w-3.5 h-3.5"></i>
                <span>Activate GPS Location</span>
            </button>
        </div>

        <!-- Interactive Field Map Card -->
        <div class="relative bg-white border border-slate-200/90 rounded-2xl overflow-hidden shadow-sm">
            <div id="navMap" class="w-full h-[380px] sm:h-[420px] z-10 bg-slate-100 flex items-center justify-center">
                <div id="mapLoadingFallback" class="text-slate-400 text-xs flex flex-col items-center gap-2">
                    <i data-lucide="loader-2" class="w-6 h-6 animate-spin text-sky-600"></i>
                    <span>Initializing Google Maps Navigation...</span>
                </div>
            </div>

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
                        <a href="tel:{{ $service->customer->phone }}" class="p-2.5 rounded-xl bg-teal-500/10 hover:bg-teal-500/20 text-teal-700 border border-teal-500/20 text-xs font-semibold flex items-center gap-1.5 transition">
                            <i data-lucide="phone" class="w-4 h-4"></i>
                            <span class="hidden sm:inline">Call</span>
                        </a>
                        <a href="https://wa.me/91{{ preg_replace('/[^0-9]/', '', $service->customer->phone) }}" target="_blank"
                           class="p-2.5 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 border border-emerald-500/20 text-xs font-semibold flex items-center gap-1.5 transition">
                            <i data-lucide="message-square" class="w-4 h-4"></i>
                            <span class="hidden sm:inline">WhatsApp</span>
                        </a>
                    </div>
                @endif
            </div>

            <div class="text-xs text-slate-600 pt-2 border-t border-slate-100 space-y-1">
                <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider block">Customer Premise</span>
                <p class="leading-relaxed">{{ $destAddress }}</p>
            </div>

            @if($service->customer_notes)
                <div class="text-xs bg-slate-50 p-2.5 rounded-xl border border-slate-200">
                    <span class="text-amber-600 font-bold block mb-0.5 text-[10px] uppercase tracking-wider">Reported Issue:</span>
                    <span class="text-slate-700 italic">{{ $service->customer_notes }}</span>
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

    <!-- Live Google Maps Navigation Engine Script -->
    <script>
    const SERVICE_ID = "{{ $service->id }}";
    const TECH_ID = "{{ $tech->id }}";
    const DEST_ADDRESS = {!! json_encode($destAddress) !!};
    let destLat = {{ $destLat !== null ? $destLat : 'null' }};
    let destLng = {{ $destLng !== null ? $destLng : 'null' }};
    const CSRF_TOKEN = "{{ csrf_token() }}";
    const GOOGLE_MAPS_KEY = "{{ config('services.google_maps.browser_key', env('GOOGLE_MAPS_BROWSER_API_KEY', '')) }}";
    
    // Initial Technician Coordinates (if previously recorded)
    let currentTechLat = {{ $tech->current_latitude ? $tech->current_latitude : 'null' }};
    let currentTechLng = {{ $tech->current_longitude ? $tech->current_longitude : 'null' }};

    let googleMap = null;
    let directionsService = null;
    let directionsRenderer = null;
    let techMarker = null;
    let accuracyCircle = null;
    let destMarker = null;
    let watchId = null;
    let lastPingTime = 0;
    let hasAcquiredLiveFix = false;
    let lastRouteCalcOrigin = null;

    $(document).ready(function() {
        lucide.createIcons();
        checkSecureContext();
        loadGoogleMapsScript();
        checkPermissionAndStartTracking();
    });

    // Check secure context for mobile browser GPS capability
    function checkSecureContext() {
        if (!window.isSecureContext && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
            $('#insecureOriginBanner').removeClass('hidden');
        }
    }

    // Dynamic Google Maps Platform JavaScript Loader
    function loadGoogleMapsScript() {
        if (window.google && window.google.maps) {
            initGoogleNavigationMap();
            return;
        }

        if (!GOOGLE_MAPS_KEY) {
            $('#mapLoadingFallback').html(`
                <div class="p-4 bg-amber-50 rounded-xl border border-amber-200 text-amber-800 text-center max-w-sm">
                    <p class="font-bold mb-1">Google Maps Key Required</p>
                    <p class="text-[11px]">Configure GOOGLE_MAPS_BROWSER_API_KEY to activate interactive road navigation.</p>
                </div>
            `);
            lucide.createIcons();
            return;
        }

        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${GOOGLE_MAPS_KEY}&libraries=geometry&loading=async&callback=initGoogleNavigationMap`;
        script.async = true;
        script.defer = true;
        script.onerror = function() {
            $('#mapLoadingFallback').html(`
                <div class="p-4 bg-rose-50 rounded-xl border border-rose-200 text-rose-800 text-center max-w-sm">
                    <p class="font-bold mb-1">Map Loading Failed</p>
                    <p class="text-[11px]">Unable to load Google Maps script. External Google Maps turn-by-turn navigation remains available.</p>
                </div>
            `);
            lucide.createIcons();
        };
        document.head.appendChild(script);
    }

    window.initGoogleNavigationMap = function() {
        const mapEl = document.getElementById('navMap');
        if (!mapEl) return;

        // Determine initial center
        const defaultLat = (currentTechLat && currentTechLng) ? currentTechLat : (destLat || 18.5204);
        const defaultLng = (currentTechLat && currentTechLng) ? currentTechLng : (destLng || 73.8567);

        googleMap = new google.maps.Map(mapEl, {
            center: { lat: defaultLat, lng: defaultLng },
            zoom: (currentTechLat && (destLat || DEST_ADDRESS)) ? 13 : 15,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
            zoomControl: true,
            zoomControlOptions: {
                position: google.maps.ControlPosition.RIGHT_BOTTOM
            },
            styles: [
                { featureType: "poi", elementType: "labels", stylers: [{ visibility: "off" }] }
            ]
        });

        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer({
            map: googleMap,
            suppressMarkers: true,
            polylineOptions: {
                strokeColor: '#0284c7',
                strokeWeight: 5,
                strokeOpacity: 0.85
            }
        });

        // Destination Marker (Customer Premise)
        if (destLat && destLng) {
            updateDestMarkerOnMap(destLat, destLng);
        }

        // Draw initial technician position if already known
        if (currentTechLat && currentTechLng) {
            updateTechMarkerOnMap(currentTechLat, currentTechLng, {{ $tech->current_accuracy ?? 15 }});
            calculateAndRenderRoute(currentTechLat, currentTechLng);
        } else if (DEST_ADDRESS) {
            // Geocode/render route as soon as tech location is acquired
        }
    };

    function updateTechMarkerOnMap(lat, lng, accuracy = 10) {
        if (!googleMap) return;

        const pos = new google.maps.LatLng(lat, lng);

        if (!techMarker) {
            techMarker = new google.maps.Marker({
                position: pos,
                map: googleMap,
                title: "Your Location: {{ addslashes($tech->full_name) }}",
                zIndex: 999,
                icon: {
                    url: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36"><circle cx="18" cy="18" r="16" fill="%230284C7" stroke="%23FFFFFF" stroke-width="3"/><circle cx="18" cy="18" r="6" fill="%23FFFFFF"/></svg>',
                    scaledSize: new google.maps.Size(36, 36),
                    anchor: new google.maps.Point(18, 18)
                }
            });
        } else {
            techMarker.setPosition(pos);
        }

        if (!accuracyCircle) {
            accuracyCircle = new google.maps.Circle({
                map: googleMap,
                center: pos,
                radius: Math.max(accuracy, 10),
                fillColor: '#0ea5e9',
                fillOpacity: 0.15,
                strokeColor: '#0284c7',
                strokeOpacity: 0.5,
                strokeWeight: 1
            });
        } else {
            accuracyCircle.setCenter(pos);
            accuracyCircle.setRadius(Math.max(accuracy, 10));
        }
    }

    function updateDestMarkerOnMap(lat, lng) {
        if (!googleMap) return;
        const pos = new google.maps.LatLng(lat, lng);

        if (!destMarker) {
            destMarker = new google.maps.Marker({
                position: pos,
                map: googleMap,
                title: "Customer Destination: " + DEST_ADDRESS,
                icon: {
                    url: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="34" height="42" viewBox="0 0 34 42"><path fill="%23E11D48" stroke="%23FFFFFF" stroke-width="2.5" d="M17 1C8.163 1 1 8.163 1 17c0 11.5 16 24 16 24s16-12.5 16-24c0-8.837-7.163-16-16-16z"/><circle cx="17" cy="17" r="6" fill="%23FFFFFF"/></svg>',
                    scaledSize: new google.maps.Size(34, 42),
                    anchor: new google.maps.Point(17, 42)
                }
            });
        } else {
            destMarker.setPosition(pos);
        }
    }

    function calculateAndRenderRoute(techLat, techLng) {
        if (!directionsService || !directionsRenderer) return;

        const originTarget = new google.maps.LatLng(techLat, techLng);
        const destinationTarget = (destLat && destLng)
            ? new google.maps.LatLng(destLat, destLng)
            : (DEST_ADDRESS || null);

        if (!destinationTarget) return;

        // Skip excessive recalculations if moved less than 30 meters
        if (lastRouteCalcOrigin && window.google && window.google.maps && window.google.maps.geometry) {
            const lastPos = new google.maps.LatLng(lastRouteCalcOrigin.lat, lastRouteCalcOrigin.lng);
            const currentPos = new google.maps.LatLng(techLat, techLng);
            const movedDist = google.maps.geometry.spherical.computeDistanceBetween(lastPos, currentPos);
            if (movedDist < 30) return;
        }

        lastRouteCalcOrigin = { lat: techLat, lng: techLng };

        directionsService.route({
            origin: originTarget,
            destination: destinationTarget,
            travelMode: google.maps.TravelMode.DRIVING
        }, function(response, status) {
            if (status === google.maps.DirectionsStatus.OK) {
                directionsRenderer.setDirections(response);
                const route = response.routes[0];
                if (route && route.legs && route.legs[0]) {
                    const leg = route.legs[0];
                    if (leg.distance && leg.distance.text) {
                        $('#telemetryDistance').html(leg.distance.text.replace(' km', '') + ' <span class="text-xs text-slate-400 font-normal">km</span>');
                    }
                    if (leg.duration && leg.duration.text) {
                        $('#telemetryEta').html('~' + leg.duration.text.replace(' mins', '').replace(' min', '') + ' <span class="text-xs text-slate-400 font-normal">min</span>');
                    }

                    // Dynamically position destination pin at resolved address coordinates
                    if (leg.end_location) {
                        const resolvedLat = leg.end_location.lat();
                        const resolvedLng = leg.end_location.lng();
                        updateDestMarkerOnMap(resolvedLat, resolvedLng);

                        // If coordinates were previously null in DB, persist resolved coordinates
                        if (!destLat || !destLng) {
                            destLat = resolvedLat;
                            destLng = resolvedLng;
                            persistResolvedCoordinates(resolvedLat, resolvedLng);
                        }
                    }
                }
            } else {
                console.warn("Google Directions routing notice:", status);
            }
        });
    }

    function persistResolvedCoordinates(lat, lng) {
        $.post("{{ route('tech_portal.destination_coordinates', $service->id) }}", {
            _token: CSRF_TOKEN,
            latitude: lat,
            longitude: lng
        }).done(function(res) {
            console.log("Customer address coordinates persisted to DB:", res);
        });
    }

    function centerOnTech() {
        if (googleMap && currentTechLat && currentTechLng) {
            googleMap.panTo(new google.maps.LatLng(currentTechLat, currentTechLng));
            googleMap.setZoom(16);
        } else if (googleMap && destLat && destLng) {
            googleMap.panTo(new google.maps.LatLng(destLat, destLng));
            googleMap.setZoom(15);
        }
    }

    function fitRouteBounds() {
        if (!googleMap) return;

        if (directionsRenderer && directionsRenderer.getDirections()) {
            const directions = directionsRenderer.getDirections();
            if (directions.routes && directions.routes[0] && directions.routes[0].bounds) {
                googleMap.fitBounds(directions.routes[0].bounds, { top: 50, right: 50, bottom: 50, left: 50 });
                return;
            }
        }

        const bounds = new google.maps.LatLngBounds();
        let pointsCount = 0;

        if (currentTechLat && currentTechLng) {
            bounds.extend(new google.maps.LatLng(currentTechLat, currentTechLng));
            pointsCount++;
        }
        if (destLat && destLng) {
            bounds.extend(new google.maps.LatLng(destLat, destLng));
            pointsCount++;
        }

        if (pointsCount > 0) {
            googleMap.fitBounds(bounds, { top: 50, right: 50, bottom: 50, left: 50 });
        }
    }

    // Geolocation Capability & Permission Lifecycle
    async function checkPermissionAndStartTracking() {
        if (!("geolocation" in navigator)) {
            $('#gpsStatusText').text('GPS Not Supported');
            $('#gpsLiveBadge').removeClass('bg-emerald-500/10 text-emerald-400 border-emerald-500/20 bg-slate-700 text-slate-300 border-slate-600')
                              .addClass('bg-rose-500/10 text-rose-500 border-rose-500/20');
            $('#permissionWarningBanner').removeClass('hidden');
            $('#permissionBannerTitle').text('Geolocation Not Supported');
            $('#permissionBannerMessage').text('This browser or device does not support geolocation.');
            $('#btnRequestPermission').addClass('hidden');
            return;
        }

        if (navigator.permissions && navigator.permissions.query) {
            try {
                const status = await navigator.permissions.query({ name: 'geolocation' });
                if (status.state === 'granted') {
                    startContinuousTracking();
                } else if (status.state === 'prompt') {
                    $('#permissionWarningBanner').removeClass('hidden');
                    $('#gpsStatusText').text('Awaiting GPS Permission');
                } else if (status.state === 'denied') {
                    handlePermissionDenied();
                }

                status.onchange = function() {
                    if (status.state === 'granted') {
                        $('#permissionWarningBanner').addClass('hidden');
                        startContinuousTracking();
                    } else if (status.state === 'denied') {
                        handlePermissionDenied();
                    }
                };
                return;
            } catch (e) {
                // Permissions API fallback
            }
        }

        // Direct activation
        startContinuousTracking();
    }

    function handlePermissionDenied() {
        $('#permissionWarningBanner').removeClass('hidden');
        $('#permissionBannerTitle').text('Location Permission Denied');
        $('#permissionBannerMessage').text('Location permission was denied. Please allow location access in your browser site settings to enable live field navigation.');
        $('#btnRequestPermission').text('Check Permission Again');
        $('#gpsStatusText').text('GPS Blocked');
        $('#gpsLiveBadge').removeClass('bg-emerald-500/10 text-emerald-400 border-emerald-500/20 bg-slate-700 text-slate-300 border-slate-600')
                          .addClass('bg-amber-500/10 text-amber-500 border-amber-500/20');
    }

    function requestDeviceLocation(isUserGesture = false) {
        if (!("geolocation" in navigator)) return;

        navigator.geolocation.getCurrentPosition(
            function(pos) {
                handlePositionSuccess(pos, true);
                startContinuousTracking();
                centerOnTech();
                if (window.showToast) window.showToast('Hardware GPS signal locked!', 'success');
            },
            function(err) {
                handlePositionError(err);
                if (isUserGesture && window.showToast) {
                    window.showToast('Please enable device location: ' + err.message, 'warning');
                }
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    function startContinuousTracking() {
        if (watchId !== null) return; // Prevent duplicate watchers

        $('#gpsStatusText').text('Acquiring Signal...');

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

        updateTechMarkerOnMap(lat, lng, accuracy);

        $('#permissionWarningBanner').addClass('hidden');
        $('#gpsStatusText').text('GPS Live Streaming');
        $('#gpsLiveDot').removeClass('bg-slate-400 bg-amber-400 bg-rose-400').addClass('bg-emerald-400 animate-pulse');
        $('#gpsLiveBadge').removeClass('bg-slate-700 text-slate-300 border-slate-600 bg-amber-500/10 text-amber-500 border-amber-500/20 bg-rose-500/10 text-rose-500 border-rose-500/20')
                          .addClass('bg-emerald-500/10 text-emerald-400 border-emerald-500/20');
        $('#telemetryAccuracy').text('±' + Math.round(accuracy) + 'm');

        if (isFirstFix || !hasAcquiredLiveFix) {
            hasAcquiredLiveFix = true;
            calculateAndRenderRoute(lat, lng);
            fitRouteBounds();
        } else {
            calculateAndRenderRoute(lat, lng);
        }

        // Throttle server telemetry pings to minimum 3.5 seconds
        const now = Date.now();
        if (now - lastPingTime >= 3500) {
            sendGpsPingToServer(lat, lng, accuracy, speed, heading);
        }
    }

    function handlePositionError(err) {
        console.warn("Geolocation notice:", err.message, "code:", err.code);
        if (err.code === 1) { // PERMISSION_DENIED
            handlePermissionDenied();
        } else if (err.code === 2) { // POSITION_UNAVAILABLE
            $('#gpsStatusText').text('Weak GPS Signal');
            $('#gpsLiveDot').removeClass('bg-emerald-400').addClass('bg-amber-400');
        } else if (err.code === 3) { // TIMEOUT
            $('#gpsStatusText').text('GPS Re-acquiring');
        }
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

        // Clear watcher
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }

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
                }, 600);
            },
            error: function() {
                $('#btnArrivedAtLocation').prop('disabled', false).html('<i data-lucide="check-circle" class="w-5 h-5"></i> I Have Arrived');
                lucide.createIcons();
            }
        });
    }

    // Component Cleanup on window unload
    window.addEventListener('beforeunload', function() {
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }
    });
    </script>
</body>
</html>
