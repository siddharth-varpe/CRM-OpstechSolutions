@extends('layouts.app')

@section('title', 'Live Field Technician Map & GPS Tracking — Enterprises CRM')

@section('content')
<div class="space-y-4 max-w-7xl mx-auto pb-8 select-none">
    <!-- Header with View Switcher & Live GPS Pulse Badge -->
    <div class="flex items-center justify-end gap-2">

        <!-- View Switcher: Roster Directory vs Live Field Map -->
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <button type="button"
                    id="btnRefreshMap"
                    onclick="fetchLiveTrackingData(true)"
                    class="p-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-600 rounded-xl transition-all shadow-2xs cursor-pointer"
                    title="Force Refresh Telemetry">
                <i data-lucide="refresh-cw" class="w-4 h-4" id="refreshIcon"></i>
            </button>

            <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl border border-slate-200 shadow-2xs">
                <a href="{{ route('technicians.index') }}"
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all text-slate-600 hover:text-slate-900">
                    <i data-lucide="users" class="w-3.5 h-3.5 text-slate-500"></i>
                    <span>Roster Directory</span>
                </a>
                <a href="{{ route('technicians.map') }}"
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all bg-white text-slate-900 shadow-2xs border border-slate-200/80">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <i data-lucide="navigation" class="w-3.5 h-3.5 text-emerald-600"></i>
                    <span>Live Field Map</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Map Workspace: Split Screen (Left Drawer / Cards + Right Live Map Canvas) -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-elevated overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[640px] h-[calc(100vh-140px)]">
        <!-- Left Panel: Technician Fleet Roster & Filter List (col-span-4) -->
        <div class="lg:col-span-4 border-r border-slate-200 flex flex-col h-full bg-slate-50/50">
            <!-- Search & Status Filter Bar -->
            <div class="p-3.5 border-b border-slate-200 bg-white space-y-2.5">
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text"
                           id="techSearchInput"
                           placeholder="Filter technician, phone, customer..."
                           class="w-full pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>

                <!-- Status Filter Pills -->
                <div class="flex items-center gap-1 text-[11px] font-bold font-mono overflow-x-auto pb-0.5">
                    <button type="button" onclick="setMapFilter('ALL')" class="map-filter-btn px-2.5 py-1 rounded-md transition-all bg-slate-900 text-white cursor-pointer" data-filter="ALL">
                        All (<span id="countAll">0</span>)
                    </button>
                    <button type="button" onclick="setMapFilter('NAVIGATING')" class="map-filter-btn px-2.5 py-1 rounded-md transition-all text-slate-600 hover:bg-slate-200 cursor-pointer" data-filter="NAVIGATING">
                        Live (<span id="countLive">0</span>)
                    </button>
                    <button type="button" onclick="setMapFilter('IDLE')" class="map-filter-btn px-2.5 py-1 rounded-md transition-all text-slate-600 hover:bg-slate-200 cursor-pointer" data-filter="IDLE">
                        Idle (<span id="countIdle">0</span>)
                    </button>
                    <button type="button" onclick="setMapFilter('OFFLINE')" class="map-filter-btn px-2.5 py-1 rounded-md transition-all text-slate-600 hover:bg-slate-200 cursor-pointer" data-filter="OFFLINE">
                        Offline (<span id="countOffline">0</span>)
                    </button>
                </div>
            </div>

            <!-- Technician Cards List -->
            <div id="techCardsContainer" class="flex-1 overflow-y-auto divide-y divide-slate-100 p-2 space-y-1.5 scrollbar-thin">
                <div class="p-8 text-center text-slate-400">
                    <div class="w-8 h-8 rounded-full border-2 border-primary-500 border-t-transparent animate-spin mx-auto mb-2"></div>
                    <span class="text-xs">Loading live telemetry stream...</span>
                </div>
            </div>

            <!-- Bottom Status Footer -->
            <div class="p-3 border-t border-slate-200 bg-white text-[11px] text-slate-500 flex items-center justify-between font-mono">
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Auto-syncing every 5s</span>
                </span>
                <span id="lastUpdatedLabel">Updated just now</span>
            </div>
        </div>

        <!-- Right Panel: Interactive Real-Time Map (col-span-8) -->
        <div class="lg:col-span-8 relative h-full bg-slate-100 flex flex-col">
            <!-- Map Container -->
            <div id="adminLiveMapContainer" class="w-full h-full z-10" style="min-height: 520px;">
                <div id="mapLoadingFallback" class="h-full flex flex-col items-center justify-center p-8 text-center space-y-3">
                    <div class="w-10 h-10 rounded-full border-3 border-primary-500 border-t-transparent animate-spin mx-auto"></div>
                    <p class="text-xs font-semibold text-slate-600">Connecting to Google Maps Platform & Telemetry Service...</p>
                </div>
            </div>

            <!-- Active Route Floating Telemetry HUD & Turn-by-Turn Driving Directions -->
            <div id="activeRouteBanner" class="hidden absolute top-4 left-4 z-20 max-w-xs sm:max-w-sm w-full bg-white/95 backdrop-blur-md border border-sky-300 rounded-2xl shadow-elevated p-3 space-y-2 text-xs transition-all animate-in fade-in max-h-[calc(100%-2rem)] flex flex-col">
                <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping shrink-0"></span>
                        <span class="font-bold text-slate-900 truncate text-xs" id="routeTechName">Technician</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-mono shrink-0">NAVIGATING</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button" onclick="focusCurrentRoute()" class="text-[11px] font-bold text-sky-600 hover:text-sky-800 hover:underline flex items-center gap-1 cursor-pointer shrink-0" title="Fit route in view">
                            <i data-lucide="crosshair" class="w-3.5 h-3.5"></i>
                            <span>Focus</span>
                        </button>
                    </div>
                </div>
                <div class="space-y-1 font-mono text-[11px] text-slate-600">
                    <div class="flex items-center gap-1.5 truncate">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-rose-500 shrink-0"></i>
                        <span class="text-slate-900 font-bold truncate text-xs" id="routeCustomerName">Customer</span>
                    </div>
                    <div class="truncate text-slate-500 pl-5 text-[10px]" id="routeCustomerAddress">Address</div>
                    <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-slate-700 font-semibold text-[11px]">
                        <span>Route: <b class="text-sky-700" id="routeDistance">--</b></span>
                        <span class="font-bold text-emerald-700" id="routeEta">ETA: --</span>
                    </div>
                    <!-- Route Layer Indicators -->
                    <div class="flex items-center justify-between text-[10px] pt-1 border-t border-slate-100 text-slate-500 font-sans">
                        <span class="flex items-center gap-1">
                            <span class="w-3.5 h-1 bg-sky-600 rounded-full inline-block"></span>
                            <span>Remaining Route</span>
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-3.5 h-1 bg-amber-500 rounded-full inline-block"></span>
                            <span>Observed GPS Trail</span>
                        </span>
                    </div>
                </div>

                <!-- Next Maneuver Callout Box -->
                <div id="routeNextManeuverBox" class="bg-sky-50 border border-sky-200/80 rounded-xl p-2.5 flex items-center gap-2.5 text-xs text-slate-800">
                    <div class="w-8 h-8 rounded-lg bg-sky-600 text-white flex items-center justify-center shrink-0 shadow-2xs" id="routeNextManeuverIcon">
                        <i data-lucide="navigation" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-sky-800 font-mono" id="routeNextManeuverDist">Next Maneuver</div>
                        <div class="text-[11px] font-semibold text-slate-900 truncate" id="routeNextManeuverText">Calculating road route...</div>
                    </div>
                </div>

                <!-- Turn-by-Turn Expandable Toggle Button -->
                <button type="button"
                        id="btnToggleTurnByTurn"
                        onclick="toggleTurnByTurnPanel()"
                        class="w-full py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-[11px] flex items-center justify-between transition cursor-pointer">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="list-ordered" class="w-3.5 h-3.5 text-primary-600"></i>
                        <span>Turn-by-Turn Directions (<span id="routeStepsCount">0</span> steps)</span>
                    </span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform" id="turnByTurnChevron"></i>
                </button>

                <!-- Turn-by-Turn Instructions List (Scrollable) -->
                <div id="turnByTurnContainer" class="space-y-1.5 overflow-y-auto max-h-56 pr-1 scrollbar-thin divide-y divide-slate-100">
                    <div id="turnByTurnStepsList" class="space-y-1.5 pt-1">
                        <!-- Rendered dynamically from Google Maps routing steps -->
                    </div>
                </div>
            </div>

            <!-- Floating Map Overlays & Quick Controls -->
            <div class="absolute top-4 right-4 z-20 flex flex-col gap-2">
                <button type="button"
                        onclick="fitAllMarkers()"
                        class="p-2.5 bg-white/95 backdrop-blur-xs hover:bg-white text-slate-700 rounded-xl shadow-elevated border border-slate-200 transition-all font-bold text-xs flex items-center gap-1.5 cursor-pointer hover:scale-105"
                        title="Zoom to Fit All Technicians">
                    <i data-lucide="maximize" class="w-4 h-4 text-primary-600"></i>
                    <span class="hidden sm:inline">Fit All Crew</span>
                </button>
            </div>

            <!-- Bottom Floating Legend with Two Distinct Routes -->
            <div class="absolute bottom-4 left-4 z-20 bg-white/95 backdrop-blur-xs border border-slate-200 px-3 py-2 rounded-xl shadow-elevated text-[11px] flex flex-wrap items-center gap-3.5 text-slate-600 font-medium">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-emerald-200"></span>
                    <span>Live Navigating</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <span>Aging</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                    <span>Standby</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-600"></span>
                    <span>Destination</span>
                </div>
                <div class="h-3 w-px bg-slate-200 hidden sm:block"></div>
                <div class="flex items-center gap-1.5">
                    <span class="w-4 h-1 bg-sky-600 rounded"></span>
                    <span>Planned Route</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-4 h-1 bg-amber-500 rounded"></span>
                    <span>Observed GPS Trail</span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>
.gm-style .gm-style-iw-c {
    border-radius: 14px !important;
    padding: 0 !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
}
.gm-style .gm-style-iw-d {
    overflow: hidden !important;
    padding: 0 !important;
}
.custom-pin-svg {
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
}
</style>

<script>
let mapInstance = null;
let directionsService = null;
let directionsRenderer = null;
let infoWindow = null;
let techMarkers = {};
let destMarkers = {};
let allTechniciansData = [];
let currentFilter = 'ALL';
let selectedTechId = null;
let pollTimer = null;
let mapsConfig = null;
let currentRouteBounds = null;
let fallbackPolyline = null;
let roadRoutePolyline = null;
let lastRenderedRouteTechId = null;
let lastRenderedRouteKey = null;
let hasActiveRouteDrawn = false;

// Layer B & Turn-by-Turn Telemetry Variables
let observedTrailPolyline = null;
let observedBreadcrumbMarkers = [];
let liveObservedTrails = {};
let currentRouteSteps = [];
let isTurnByTurnOpen = true;

const PUNE_CENTER = { lat: 18.5204, lng: 73.8567 };

$(document).ready(function() {
    loadMapsConfigAndInitialize();

    // Auto-polling telemetry interval every 5 seconds
    pollTimer = setInterval(function() {
        fetchLiveTrackingData(false);
    }, 5000);

    // Filter search input
    $('#techSearchInput').on('input', function() {
        renderTechnicianCards();
    });
});

function loadMapsConfigAndInitialize() {
    $.get("{{ route('maps.admin.config') }}", function(res) {
        if (res && res.success && res.data) {
            mapsConfig = res.data;
            loadGoogleMapsScript(mapsConfig.apiKey);
        } else {
            loadGoogleMapsScript("{{ config('services.google_maps.browser_key', env('GOOGLE_MAPS_BROWSER_API_KEY', '')) }}");
        }
    }).fail(function() {
        loadGoogleMapsScript("{{ config('services.google_maps.browser_key', env('GOOGLE_MAPS_BROWSER_API_KEY', '')) }}");
    });
}

function loadGoogleMapsScript(apiKey) {
    if (window.google && window.google.maps) {
        initGoogleMap();
        return;
    }

    if (!apiKey) {
        $('#mapLoadingFallback').html(`
            <div class="p-6 bg-amber-50 rounded-2xl border border-amber-200 text-amber-800 max-w-md text-center">
                <i data-lucide="alert-triangle" class="w-8 h-8 text-amber-600 mx-auto mb-2"></i>
                <h4 class="font-bold text-sm text-amber-900 mb-1">Google Maps API Key Notice</h4>
                <p class="text-xs">Please configure <code class="font-mono bg-white px-1.5 py-0.5 rounded border">GOOGLE_MAPS_BROWSER_API_KEY</code> in Settings or .env to activate the interactive map canvas.</p>
            </div>
        `);
        if (typeof lucide !== 'undefined') lucide.createIcons();
        fetchLiveTrackingData();
        return;
    }

    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=geometry&loading=async&callback=initGoogleMap`;
    script.async = true;
    script.defer = true;
    script.onerror = function() {
        $('#mapLoadingFallback').html(`
            <div class="p-6 bg-rose-50 rounded-2xl border border-rose-200 text-rose-800 max-w-md text-center">
                <i data-lucide="wifi-off" class="w-8 h-8 text-rose-600 mx-auto mb-2"></i>
                <h4 class="font-bold text-sm text-rose-900 mb-1">Maps Connection Unavailable</h4>
                <p class="text-xs">Unable to load Google Maps Platform JavaScript. Telemetry and route cards continue to sync normally.</p>
            </div>
        `);
        if (typeof lucide !== 'undefined') lucide.createIcons();
    };
    document.head.appendChild(script);
}

window.initGoogleMap = function() {
    isGoogleMapsLoaded = true;
    const mapElement = document.getElementById('adminLiveMapContainer');
    if (!mapElement) return;

    try {
        mapInstance = new google.maps.Map(mapElement, {
            center: PUNE_CENTER,
            zoom: 12,
            mapId: (mapsConfig && mapsConfig.mapId) ? mapsConfig.mapId : undefined,
            disableDefaultUI: false,
            zoomControl: true,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
            gestureHandling: 'greedy'
        });

        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer({
            map: mapInstance,
            suppressMarkers: true,
            preserveViewport: true,
            polylineOptions: {
                strokeColor: '#0284C7',
                strokeWeight: 5,
                strokeOpacity: 0.85
            }
        });

        infoWindow = new google.maps.InfoWindow();

        // Fetch live telemetry once map is ready
        fetchLiveTrackingData();
    } catch (e) {
        console.error('Failed to initialize Google Maps:', e);
    }
};

function fetchLiveTrackingData(showSpin = false) {
    if (showSpin) {
        $('#refreshIcon').addClass('animate-spin');
    }

    $.get("{{ route('maps.admin.live') }}", function(res) {
        if (res && res.success && Array.isArray(res.data)) {
            allTechniciansData = res.data;
            if (!selectedTechId) {
                const liveNav = allTechniciansData.find(t => t.trackingStatus === 'ON_THE_WAY');
                if (liveNav) selectedTechId = liveNav.technicianId;
            }
            if (isGoogleMapsLoaded && mapInstance) {
                updateGoogleMapElements(allTechniciansData);
            }
            renderTechnicianCards();
            updateCounts();
            $('#lastUpdatedLabel').text('Updated ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
        }
    }).always(function() {
        if (showSpin) {
            setTimeout(() => $('#refreshIcon').removeClass('animate-spin'), 400);
        }
    });
}

function updateCounts() {
    let live = 0, idle = 0, offline = 0;
    allTechniciansData.forEach(t => {
        if (t.trackingStatus === 'ON_THE_WAY') live++;
        else if (t.status === 'ACTIVE') idle++;
        else offline++;
    });

    $('#countAll').text(allTechniciansData.length);
    $('#countLive').text(live);
    $('#countIdle').text(idle);
    $('#countOffline').text(offline);
}

function setMapFilter(filter) {
    currentFilter = filter;
    $('.map-filter-btn').removeClass('bg-slate-900 text-white').addClass('text-slate-600 hover:bg-slate-200');
    $(`.map-filter-btn[data-filter="${filter}"]`).removeClass('text-slate-600 hover:bg-slate-200').addClass('bg-slate-900 text-white');
    renderTechnicianCards();
}

function renderTechnicianCards() {
    const search = $('#techSearchInput').val().toLowerCase();
    let filtered = allTechniciansData.filter(t => {
        const matchesSearch = !search ||
            t.technicianName.toLowerCase().includes(search) ||
            (t.technicianPhone && t.technicianPhone.includes(search)) ||
            (t.customerName && t.customerName.toLowerCase().includes(search));

        if (!matchesSearch) return false;

        if (currentFilter === 'NAVIGATING') return t.trackingStatus === 'ON_THE_WAY';
        if (currentFilter === 'IDLE') return t.status === 'ACTIVE' && t.trackingStatus !== 'ON_THE_WAY';
        if (currentFilter === 'OFFLINE') return t.status !== 'ACTIVE' && t.trackingStatus !== 'ON_THE_WAY';
        return true;
    });

    if (filtered.length === 0) {
        $('#techCardsContainer').html(`
            <div class="p-8 text-center text-slate-400">
                <i data-lucide="user-x" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                <p class="text-xs font-semibold">No technicians matching filter.</p>
            </div>
        `);
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }

    let html = '';
    filtered.forEach(t => {
        const isSelected = selectedTechId === t.technicianId;
        const isLive = t.trackingStatus === 'ON_THE_WAY';
        const freshness = t.statusFreshness || 'OFFLINE';
        const freshnessClass = freshness === 'LIVE' ? 'text-emerald-700 bg-emerald-50 border-emerald-200' :
                              (freshness === 'WARNING' ? 'text-amber-700 bg-amber-50 border-amber-200' : 'text-slate-600 bg-slate-100 border-slate-200');
        const hasCoords = t.latitude !== null && t.longitude !== null;

        html += `
            <div onclick="selectTechnician('${t.technicianId}')"
                 class="p-3 rounded-xl border transition-all cursor-pointer ${isSelected ? 'bg-sky-50/80 border-sky-400 ring-2 ring-sky-300/40 shadow-sm' : 'bg-white hover:bg-slate-50 border-slate-200/80 shadow-2xs'}">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-sm text-white shrink-0 shadow-2xs font-mono"
                             style="background: ${isLive ? 'linear-gradient(135deg, #059669, #10B981)' : '#64748B'}">
                            ${t.technicianName.charAt(0)}
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-slate-900 text-xs truncate flex items-center gap-1.5">
                                <span>${t.technicianName}</span>
                                ${isLive ? '<span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping shrink-0"></span>' : ''}
                            </h4>
                            <p class="text-[11px] text-slate-500 font-mono flex items-center gap-1">
                                <i data-lucide="phone" class="w-3 h-3 text-slate-400"></i>
                                <span>${t.technicianPhone || 'No Phone'}</span>
                            </p>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border uppercase font-mono ${freshnessClass}">
                        ${isLive ? 'NAVIGATING' : t.status}
                    </span>
                </div>

                ${isLive && t.customerName ? `
                    <div class="mt-2.5 pt-2 border-t border-slate-100 text-[11px] space-y-1 bg-slate-50/70 p-2 rounded-lg">
                        <div class="flex items-center justify-between text-slate-800 font-semibold">
                            <span class="truncate flex items-center gap-1">
                                <i data-lucide="map-pin" class="w-3 h-3 text-rose-500 shrink-0"></i>
                                <span class="truncate">${t.customerName}</span>
                            </span>
                            <span class="text-primary-700 font-bold font-mono shrink-0">${t.distanceKm ? t.distanceKm + ' km' : ''}</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-500 font-mono text-[10px]">
                            <span class="truncate">${t.customerAddress || 'Client Location'}</span>
                            <span class="font-bold text-emerald-700">${t.etaMinutes ? 'ETA ~' + t.etaMinutes + 'm' : ''}</span>
                        </div>
                    </div>
                ` : `
                    <div class="mt-2 pt-1.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400 font-mono">
                        <span>${hasCoords ? 'GPS: Connected' : 'GPS: Standby'}</span>
                        <span>${t.serviceNumber ? t.serviceNumber : 'No Active Job'}</span>
                    </div>
                `}

                <div class="mt-2 flex items-center justify-between pt-1 text-[11px]">
                    <span class="text-slate-400 text-[10px] font-mono">
                        ${t.secondsAgo !== undefined && t.secondsAgo < 120 ? 'Active ' + t.secondsAgo + 's ago' : 'Standing by'}
                    </span>
                    <button type="button"
                            onclick="event.stopPropagation(); recalculateRoute('${t.technicianId}')"
                            class="text-xs text-primary-600 hover:text-primary-800 font-bold hover:underline flex items-center gap-1 cursor-pointer">
                        <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                        Recalc Route
                    </button>
                </div>
            </div>
        `;
    });

    $('#techCardsContainer').html(html);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function updateGoogleMapElements(technicians) {
    if (!mapInstance || !window.google || !window.google.maps) return;

    const currentTechIds = new Set(technicians.map(t => t.technicianId));

    // Remove obsolete markers
    Object.keys(techMarkers).forEach(techId => {
        if (!currentTechIds.has(techId)) {
            techMarkers[techId].setMap(null);
            delete techMarkers[techId];
        }
    });

    Object.keys(destMarkers).forEach(techId => {
        if (!currentTechIds.has(techId)) {
            destMarkers[techId].setMap(null);
            delete destMarkers[techId];
        }
    });

    // Determine active technician navigating towards a customer destination
    let activeNavigatingTech = null;

    if (selectedTechId) {
        const selected = technicians.find(t => t.technicianId === selectedTechId);
        if (selected && selected.latitude && selected.longitude && (selected.customerLatitude || selected.customerAddress)) {
            activeNavigatingTech = selected;
        }
    }
    if (!activeNavigatingTech) {
        activeNavigatingTech = technicians.find(t => (t.trackingStatus === 'ON_THE_WAY' || t.trackingStatus === 'ARRIVAL_PENDING') && t.latitude && t.longitude && (t.customerLatitude || t.customerAddress));
    }
    if (!activeNavigatingTech) {
        activeNavigatingTech = technicians.find(t => t.latitude && t.longitude && t.customerLatitude && t.customerLongitude);
    }
    if (!activeNavigatingTech && selectedTechId) {
        activeNavigatingTech = technicians.find(t => t.technicianId === selectedTechId);
    }

    technicians.forEach(t => {
        const techId = t.technicianId;
        const hasCoords = t.latitude !== null && t.longitude !== null && Number(t.latitude) !== 0;
        const hasDestCoords = t.customerLatitude !== null && t.customerLongitude !== null && Number(t.customerLatitude) !== 0 && Number(t.customerLongitude) !== 0;
        const hasDest = hasDestCoords || (t.customerAddress && t.customerAddress.trim() !== '');
        const isLive = t.trackingStatus === 'ON_THE_WAY' || t.trackingStatus === 'ARRIVAL_PENDING';

        // 1. Technician Marker
        if (hasCoords) {
            const pos = { lat: Number(t.latitude), lng: Number(t.longitude) };
            const statusColor = isLive ? '#10B981' : (t.statusFreshness === 'WARNING' ? '#F59E0B' : '#64748B');

            const markerOptions = {
                position: pos,
                map: mapInstance,
                title: `${t.technicianName} (${t.trackingStatus})`,
                zIndex: isLive ? 30 : 15,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 11,
                    fillColor: statusColor,
                    fillOpacity: 1,
                    strokeColor: '#FFFFFF',
                    strokeWeight: 2.5
                },
                label: {
                    text: t.technicianName.charAt(0),
                    color: '#FFFFFF',
                    fontWeight: 'bold',
                    fontSize: '11px'
                }
            };

            if (techMarkers[techId]) {
                techMarkers[techId].setPosition(pos);
                techMarkers[techId].setIcon(markerOptions.icon);
                techMarkers[techId].setLabel(markerOptions.label);
                techMarkers[techId].setZIndex(markerOptions.zIndex);
            } else {
                const m = new google.maps.Marker(markerOptions);
                m.addListener('click', () => selectTechnician(techId));
                techMarkers[techId] = m;
            }
        } else if (techMarkers[techId]) {
            techMarkers[techId].setMap(null);
            delete techMarkers[techId];
        }

        // 2. Customer Destination Marker
        if (hasDestCoords) {
            const destPos = { lat: Number(t.customerLatitude), lng: Number(t.customerLongitude) };
            const destOptions = {
                position: destPos,
                map: mapInstance,
                title: `Destination: ${t.customerName || 'Customer'} (Click to view premise details)`,
                cursor: 'pointer',
                zIndex: 25,
                icon: {
                    path: 'M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z',
                    fillColor: '#E11D48',
                    fillOpacity: 1,
                    strokeColor: '#FFFFFF',
                    strokeWeight: 1.5,
                    scale: 1.4,
                    anchor: new google.maps.Point(12, 22)
                }
            };

            if (destMarkers[techId]) {
                destMarkers[techId].setPosition(destPos);
                destMarkers[techId].setTitle(destOptions.title);
            } else {
                const dm = new google.maps.Marker(destOptions);
                destMarkers[techId] = dm;
            }

            // Ensure click listener opens destination description info window
            google.maps.event.clearListeners(destMarkers[techId], 'click');
            destMarkers[techId].addListener('click', function() {
                showDestinationInfoWindow(techId);
            });
        } else if (destMarkers[techId]) {
            destMarkers[techId].setMap(null);
            delete destMarkers[techId];
        }
    });

    // 3. Exact Road Route to Customer Destination
    updateRouteForTechnician(activeNavigatingTech);
}

function showDestinationInfoWindow(techId) {
    const t = allTechniciansData.find(item => item.technicianId === techId);
    if (!t || !mapInstance || !infoWindow) return;

    const marker = destMarkers[techId];
    if (!marker) return;

    const destLink = (t.customerLatitude && t.customerLongitude)
        ? `https://www.google.com/maps/dir/?api=1&destination=${t.customerLatitude},${t.customerLongitude}&travelmode=driving`
        : (t.customerAddress ? `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(t.customerAddress)}&travelmode=driving` : '#');

    const content = `
        <div style="font-family: inherit; min-width: 270px; max-width: 340px;" class="p-3.5 space-y-2.5 select-text">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <div class="flex items-center gap-1.5 min-w-0">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shrink-0"></span>
                    <span class="font-bold text-slate-900 text-xs truncate">Customer Destination</span>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase font-mono bg-rose-50 text-rose-700 border border-rose-200">
                    TARGET
                </span>
            </div>
            <div class="space-y-1.5 text-xs text-slate-700">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 font-mono block">Customer Name</span>
                    <span class="font-bold text-slate-900 text-sm">${escapeHtml(t.customerName || 'Customer Destination')}</span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 font-mono block">Premise Address</span>
                    <span class="text-slate-600 text-xs leading-relaxed">${escapeHtml(t.customerAddress || 'Client Premise Address')}</span>
                </div>
                <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-100 text-[11px] font-mono">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Assigned Tech</span>
                        <span class="font-semibold text-slate-800">${escapeHtml(t.technicianName || 'Technician')}</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Service Number</span>
                        <span class="font-semibold text-sky-700">${escapeHtml(t.serviceNumber || 'Active Job')}</span>
                    </div>
                </div>
                ${t.jobCardNumber ? `
                    <div class="text-[11px] font-mono text-slate-600">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Job Card</span>
                        <span>${escapeHtml(t.jobCardNumber)}</span>
                    </div>
                ` : ''}
                ${(t.distanceKm || t.etaMinutes) ? `
                    <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-[11px] font-mono bg-sky-50/70 p-1.5 rounded-lg text-sky-900 font-semibold">
                        <span>Distance: ${t.distanceKm ? t.distanceKm + ' km' : '--'}</span>
                        <span class="text-emerald-700">ETA: ${t.etaMinutes ? '~' + t.etaMinutes + ' min' : '--'}</span>
                    </div>
                ` : ''}
                <div class="pt-1.5 flex items-center justify-between text-[11px] border-t border-slate-100">
                    <span class="text-slate-400 font-mono text-[10px]">Lat: ${Number(t.customerLatitude).toFixed(4)}, Lng: ${Number(t.customerLongitude).toFixed(4)}</span>
                    <a href="${destLink}" target="_blank" rel="noopener noreferrer" class="font-bold text-sky-600 hover:text-sky-800 hover:underline flex items-center gap-1">
                        Open in Google Maps &rarr;
                    </a>
                </div>
            </div>
        </div>
    `;

    infoWindow.setContent(content);
    infoWindow.open(mapInstance, marker);
}

function toggleTurnByTurnPanel() {
    isTurnByTurnOpen = !isTurnByTurnOpen;
    if (isTurnByTurnOpen) {
        $('#turnByTurnContainer').removeClass('hidden');
        $('#turnByTurnChevron').addClass('rotate-180');
    } else {
        $('#turnByTurnContainer').addClass('hidden');
        $('#turnByTurnChevron').removeClass('rotate-180');
    }
}

function getManeuverIcon(maneuver, instruction) {
    maneuver = (maneuver || '').toLowerCase();
    const text = (instruction || '').toLowerCase();

    if (maneuver.includes('right') || text.includes('turn right') || text.includes('slight right') || text.includes('keep right')) {
        return 'corner-up-right';
    }
    if (maneuver.includes('left') || text.includes('turn left') || text.includes('slight left') || text.includes('keep left')) {
        return 'corner-up-left';
    }
    if (maneuver.includes('uturn') || text.includes('u-turn') || text.includes('make a u-turn')) {
        return 'rotate-ccw';
    }
    if (maneuver.includes('roundabout') || text.includes('roundabout')) {
        return 'rotate-cw';
    }
    if (text.includes('destination') || text.includes('arrive')) {
        return 'map-pin';
    }
    return 'arrow-up';
}

function sanitizeInstructionHtml(html) {
    if (!html) return '';
    return html.replace(/<(?!\/?(b|strong|span|div)\b)[^>]+>/gi, '');
}

function renderTurnByTurnInstructions(leg) {
    if (!leg || !Array.isArray(leg.steps) || leg.steps.length === 0) {
        $('#turnByTurnContainer').addClass('hidden');
        $('#routeStepsCount').text('0');
        $('#routeNextManeuverBox').addClass('hidden');
        return;
    }

    const steps = leg.steps;
    currentRouteSteps = steps;
    $('#routeStepsCount').text(steps.length);
    $('#routeNextManeuverBox').removeClass('hidden');
    if (isTurnByTurnOpen) {
        $('#turnByTurnContainer').removeClass('hidden');
        $('#turnByTurnChevron').addClass('rotate-180');
    } else {
        $('#turnByTurnContainer').addClass('hidden');
        $('#turnByTurnChevron').removeClass('rotate-180');
    }

    // Next Maneuver Callout
    const firstStep = steps[0];
    const nextIconName = getManeuverIcon(firstStep.maneuver, firstStep.instructions);
    $('#routeNextManeuverIcon').html(`<i data-lucide="${nextIconName}" class="w-4 h-4"></i>`);
    const nextDist = firstStep.distance ? firstStep.distance.text : '';
    $('#routeNextManeuverDist').text(nextDist ? `Next in ${nextDist}` : 'Next Maneuver');
    $('#routeNextManeuverText').html(sanitizeInstructionHtml(firstStep.instructions));

    // Full Ordered Sequence of Route Steps
    let stepsHtml = '';
    steps.forEach((step, idx) => {
        const iconName = getManeuverIcon(step.maneuver, step.instructions);
        const distText = step.distance ? step.distance.text : '';
        const durText = step.duration ? step.duration.text : '';

        stepsHtml += `
            <div class="flex items-start gap-2.5 p-2 rounded-xl transition ${idx === 0 ? 'bg-sky-50/70 border border-sky-200' : 'hover:bg-slate-50 border border-transparent'}">
                <div class="w-6 h-6 rounded-lg ${idx === 0 ? 'bg-sky-600 text-white' : 'bg-slate-100 text-slate-600'} flex items-center justify-center shrink-0 text-[10px] font-bold font-mono mt-0.5">
                    ${idx + 1}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[11px] text-slate-800 leading-snug">
                        ${sanitizeInstructionHtml(step.instructions)}
                    </div>
                    <div class="text-[10px] text-slate-400 font-mono mt-0.5 flex items-center gap-1">
                        <i data-lucide="${iconName}" class="w-3 h-3 text-sky-600"></i>
                        <span>${distText}</span>
                        ${durText ? `<span>•</span><span>${durText}</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    });

    $('#turnByTurnStepsList').html(stepsHtml);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function renderObservedTrail(tech) {
    if (!mapInstance || !window.google || !window.google.maps) return;

    const techId = tech.technicianId;
    if (!liveObservedTrails[techId]) {
        liveObservedTrails[techId] = [];
        if (Array.isArray(tech.travelledPath)) {
            tech.travelledPath.forEach(pt => {
                if (pt.latitude && pt.longitude) {
                    liveObservedTrails[techId].push({ lat: Number(pt.latitude), lng: Number(pt.longitude) });
                }
            });
        }
    }

    // Append current position if moved from last recorded point
    if (tech.latitude && tech.longitude) {
        const curPos = { lat: Number(tech.latitude), lng: Number(tech.longitude) };
        const trail = liveObservedTrails[techId];
        let shouldAdd = true;
        if (trail.length > 0) {
            const last = trail[trail.length - 1];
            if (Math.abs(last.lat - curPos.lat) < 0.00005 && Math.abs(last.lng - curPos.lng) < 0.00005) {
                shouldAdd = false;
            }
        }
        if (shouldAdd) {
            trail.push(curPos);
            if (trail.length > 50) trail.shift();
        }
    }

    const points = liveObservedTrails[techId] || [];

    if (observedTrailPolyline) {
        observedTrailPolyline.setMap(null);
        observedTrailPolyline = null;
    }
    observedBreadcrumbMarkers.forEach(m => m.setMap(null));
    observedBreadcrumbMarkers = [];

    if (points.length >= 2) {
        // Layer B: Contrasting Amber/Orange Polyline for Observed GPS Trail
        observedTrailPolyline = new google.maps.Polyline({
            path: points,
            geodesic: true,
            strokeColor: '#D97706',
            strokeOpacity: 0.95,
            strokeWeight: 4,
            zIndex: 15,
            map: mapInstance
        });

        // Breadcrumb markers at recorded historical fixes
        points.forEach((pt, idx) => {
            if (idx % 2 === 0 || idx === points.length - 1) {
                const marker = new google.maps.Marker({
                    position: pt,
                    map: mapInstance,
                    title: `Observed GPS Fix #${idx + 1}`,
                    zIndex: 14,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 3,
                        fillColor: '#F59E0B',
                        fillOpacity: 1,
                        strokeColor: '#78350F',
                        strokeWeight: 1
                    }
                });
                observedBreadcrumbMarkers.push(marker);
            }
        });
    }
}

function clearObservedTrail() {
    if (observedTrailPolyline) {
        observedTrailPolyline.setMap(null);
        observedTrailPolyline = null;
    }
    observedBreadcrumbMarkers.forEach(m => m.setMap(null));
    observedBreadcrumbMarkers = [];
}

function clearRoadRoutePolyline() {
    if (roadRoutePolyline) {
        roadRoutePolyline.setMap(null);
        roadRoutePolyline = null;
    }
    if (fallbackPolyline) {
        fallbackPolyline.setMap(null);
        fallbackPolyline = null;
    }
}

function clearActiveRouteVisuals() {
    clearRoadRoutePolyline();
    if (directionsRenderer) {
        try { directionsRenderer.setMap(null); } catch (e) {}
    }
    clearObservedTrail();
    hasActiveRouteDrawn = false;
    lastRenderedRouteKey = null;
    currentRouteBounds = null;
    lastRenderedRouteTechId = null;
    $('#activeRouteBanner').addClass('hidden');
    $('#turnByTurnContainer').addClass('hidden');
    $('#routeNextManeuverBox').addClass('hidden');
    $('#routeStepsCount').text('0');
}

function applyRouteTelemetry(tech, distText, etaText, destAddress) {
    $('#activeRouteBanner').removeClass('hidden');
    $('#routeTechName').text(tech.technicianName);
    $('#routeCustomerName').text(tech.customerName || 'Customer');
    $('#routeCustomerAddress').text(destAddress || tech.customerAddress || 'Customer Premise');
    $('#routeDistance').text(distText || (tech.distanceKm ? tech.distanceKm + ' km' : '--'));
    $('#routeEta').text(etaText ? (etaText.startsWith('ETA:') ? etaText : 'ETA: ~' + etaText) : (tech.etaMinutes ? 'ETA: ~' + tech.etaMinutes + 'm' : 'ETA: --'));
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function updateRouteForTechnician(tech) {
    if (!mapInstance) return;

    const hasCoords = tech && tech.latitude !== null && tech.longitude !== null && Number(tech.latitude) !== 0;
    const hasDestCoords = tech && tech.customerLatitude !== null && tech.customerLongitude !== null && Number(tech.customerLatitude) !== 0 && Number(tech.customerLongitude) !== 0;
    const hasDest = tech && (hasDestCoords || (tech.customerAddress && tech.customerAddress.trim() !== ''));

    if (hasCoords && hasDest) {
        const origin = { lat: Number(tech.latitude), lng: Number(tech.longitude) };
        const destination = hasDestCoords
            ? { lat: Number(tech.customerLatitude), lng: Number(tech.customerLongitude) }
            : (tech.customerAddress && tech.customerAddress.trim() !== '' ? tech.customerAddress.trim() : null);

        // 1. Layer B: Render Observed GPS Trail
        renderObservedTrail(tech);

        if (!destination) {
            clearActiveRouteVisuals();
            return;
        }

        // Cache key to prevent redundant re-queries if technician hasn't moved
        const originKey = `${origin.lat.toFixed(4)},${origin.lng.toFixed(4)}`;
        const destKey = typeof destination === 'object' ? `${destination.lat.toFixed(4)},${destination.lng.toFixed(4)}` : destination;
        const currentRouteKey = `${tech.technicianId}:${originKey}->${destKey}`;

        if (lastRenderedRouteKey === currentRouteKey && hasActiveRouteDrawn && roadRoutePolyline) {
            return;
        }

        // 2. Try Google Maps DirectionsService first
        if (directionsService) {
            try {
                directionsService.route({
                    origin: origin,
                    destination: destination,
                    travelMode: google.maps.TravelMode.DRIVING
                }, function(response, status) {
                    if (status === google.maps.DirectionsStatus.OK) {
                        clearRoadRoutePolyline();
                        try {
                            directionsRenderer.setMap(mapInstance);
                            directionsRenderer.setDirections(response);
                        } catch (e) {}
                        hasActiveRouteDrawn = true;
                        lastRenderedRouteKey = currentRouteKey;

                        if (response.routes && response.routes[0]) {
                            const route = response.routes[0];
                            currentRouteBounds = route.bounds;

                            if (lastRenderedRouteTechId !== tech.technicianId) {
                                mapInstance.fitBounds(route.bounds, { top: 80, right: 80, bottom: 80, left: 80 });
                                lastRenderedRouteTechId = tech.technicianId;
                            }

                            if (route.legs && route.legs[0]) {
                                const leg = route.legs[0];
                                applyRouteTelemetry(tech, leg.distance ? leg.distance.text : null, leg.duration ? leg.duration.text : null, leg.end_address);
                                renderTurnByTurnInstructions(leg);

                                // Dynamically update destination marker position if resolved by Google Maps
                                if (leg.end_location) {
                                    const techId = tech.technicianId;
                                    if (destMarkers[techId]) {
                                        destMarkers[techId].setPosition(leg.end_location);
                                    }
                                }
                            }
                        }
                        return;
                    }

                    // If DirectionsService returned non-OK (e.g. REQUEST_DENIED or OVER_QUERY_LIMIT),
                    // fetch and draw the exact road-following route from OSRM driving engine!
                    fetchAndDrawExactRoadRoute(tech, origin, destination, currentRouteKey);
                });
            } catch (err) {
                fetchAndDrawExactRoadRoute(tech, origin, destination, currentRouteKey);
            }
        } else {
            fetchAndDrawExactRoadRoute(tech, origin, destination, currentRouteKey);
        }
    } else {
        clearActiveRouteVisuals();
    }
}

function fetchAndDrawExactRoadRoute(tech, origin, destination, currentRouteKey) {
    let destLat = typeof destination === 'object' ? destination.lat : (tech.customerLatitude && Number(tech.customerLatitude) !== 0 ? Number(tech.customerLatitude) : null);
    let destLng = typeof destination === 'object' ? destination.lng : (tech.customerLongitude && Number(tech.customerLongitude) !== 0 ? Number(tech.customerLongitude) : null);

    if (!destLat || !destLng || isNaN(destLat) || isNaN(destLng) || (destLat === 0 && destLng === 0)) {
        return;
    }

    const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${origin.lng},${origin.lat};${destLng},${destLat}?overview=full&geometries=geojson&steps=true`;

    $.ajax({
        url: osrmUrl,
        type: 'GET',
        dataType: 'json',
        timeout: 4500,
        success: function(res) {
            if (res && res.code === 'Ok' && Array.isArray(res.routes) && res.routes.length > 0) {
                renderRoadRouteFromGeometry(res.routes[0], tech, currentRouteKey);
            } else {
                fetchViaBackendProxy();
            }
        },
        error: function(err) {
            fetchViaBackendProxy();
        }
    });

    function fetchViaBackendProxy() {
        $.get("{{ route('maps.admin.route_geometry') }}", {
            originLat: origin.lat,
            originLng: origin.lng,
            destLat: destLat,
            destLng: destLng
        }, function(proxyRes) {
            if (proxyRes && proxyRes.code === 'Ok' && Array.isArray(proxyRes.routes) && proxyRes.routes.length > 0) {
                renderRoadRouteFromGeometry(proxyRes.routes[0], tech, currentRouteKey);
            }
        });
    }
}

function renderRoadRouteFromGeometry(route, tech, currentRouteKey) {
    if (!route || !route.geometry || !Array.isArray(route.geometry.coordinates)) return;
    const coords = route.geometry.coordinates; // [[lng, lat], ...]
    if (coords.length < 2) return;

    const roadPath = coords.map(c => ({ lat: Number(c[1]), lng: Number(c[0]) }));

    clearRoadRoutePolyline();
    if (directionsRenderer) {
        try { directionsRenderer.setMap(null); } catch (e) {}
    }

    // Exact road-following driving polyline on the street network
    roadRoutePolyline = new google.maps.Polyline({
        path: roadPath,
        geodesic: true,
        strokeColor: '#0284C7',
        strokeOpacity: 0.95,
        strokeWeight: 6,
        zIndex: 100,
        map: mapInstance
    });

    hasActiveRouteDrawn = true;
    lastRenderedRouteKey = currentRouteKey;

    // Compute bounds for exact road path
    const bounds = new google.maps.LatLngBounds();
    roadPath.forEach(pt => bounds.extend(pt));
    currentRouteBounds = bounds;

    if (lastRenderedRouteTechId !== tech.technicianId) {
        mapInstance.fitBounds(bounds, { top: 80, right: 80, bottom: 80, left: 80 });
        lastRenderedRouteTechId = tech.technicianId;
    }

    // Format distance & ETA
    const distKm = (route.distance / 1000).toFixed(1) + ' km';
    const etaMins = Math.ceil(route.duration / 60) + ' min';

    applyRouteTelemetry(tech, distKm, etaMins, tech.customerAddress);

    // Build leg steps for turn-by-turn panel
    if (route.legs && route.legs[0] && Array.isArray(route.legs[0].steps)) {
        const osrmSteps = route.legs[0].steps.map(s => {
            const name = s.name ? `onto <b>${escapeHtml(s.name)}</b>` : '';
            const mod = s.maneuver.modifier ? s.maneuver.modifier.replace('-', ' ') : '';
            const mType = s.maneuver.type;
            let text = '';
            if (mType === 'depart') text = `Head ${mod || 'forward'} ${name}`;
            else if (mType === 'arrive') text = `Arrive at customer destination`;
            else if (mType === 'turn') text = `Turn ${mod || ''} ${name}`;
            else text = `${mType} ${mod || ''} ${name}`;

            return {
                instructions: text.trim(),
                maneuver: mType + (mod ? '-' + mod : ''),
                distance: { text: s.distance >= 1000 ? (s.distance / 1000).toFixed(1) + ' km' : Math.round(s.distance) + ' m' },
                duration: { text: Math.ceil(s.duration / 60) + ' min' }
            };
        });

        renderTurnByTurnInstructions({ steps: osrmSteps });
    }
}

function focusCurrentRoute() {
    if (currentRouteBounds && mapInstance) {
        mapInstance.fitBounds(currentRouteBounds, { top: 80, right: 80, bottom: 80, left: 80 });
    }
}

function selectTechnician(techId) {
    selectedTechId = techId;
    renderTechnicianCards();

    const tech = allTechniciansData.find(t => t.technicianId === techId);
    if (!tech || !tech.latitude || !tech.longitude || !mapInstance) return;

    const hasDest = (tech.customerLatitude && tech.customerLongitude) || (tech.customerAddress && tech.customerAddress.trim() !== '');

    if (hasDest) {
        lastRenderedRouteTechId = null; // Force fitBounds to route of newly selected technician
        hasActiveRouteDrawn = false;
        lastRenderedRouteKey = null;
        updateRouteForTechnician(tech);
    } else {
        clearActiveRouteVisuals();
        mapInstance.panTo({ lat: Number(tech.latitude), lng: Number(tech.longitude) });
        mapInstance.setZoom(15);
    }

    // Open InfoWindow
    if (infoWindow && techMarkers[techId]) {
        const destLink = (tech.customerLatitude && tech.customerLongitude)
            ? `https://www.google.com/maps/dir/?api=1&destination=${tech.customerLatitude},${tech.customerLongitude}`
            : (tech.customerAddress ? `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(tech.customerAddress)}` : null);

        const content = `
            <div style="font-family: inherit; min-width: 230px;" class="p-3.5 space-y-2">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <span class="font-bold text-slate-900 text-xs">${escapeHtml(tech.technicianName)}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase font-mono ${tech.trackingStatus === 'ON_THE_WAY' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'}">
                        ${tech.trackingStatus}
                    </span>
                </div>
                <div class="text-[11px] text-slate-600 font-mono space-y-1">
                    <div>Phone: <b>${escapeHtml(tech.technicianPhone || 'N/A')}</b></div>
                    ${tech.customerName ? `<div>Heading To: <b class="text-slate-900">${escapeHtml(tech.customerName)}</b></div>` : ''}
                    ${tech.distanceKm ? `<div>Distance: <b>${tech.distanceKm} km</b> (ETA: ~${tech.etaMinutes}m)</div>` : ''}
                </div>
                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                    <button type="button" onclick="recalculateRoute('${tech.technicianId}')" class="text-primary-600 font-bold hover:underline cursor-pointer">
                        Recalculate
                    </button>
                    ${destLink ? `
                        <a href="${destLink}" target="_blank" class="text-emerald-700 font-bold hover:underline">
                            Google Maps &rarr;
                        </a>
                    ` : ''}
                </div>
            </div>
        `;
        infoWindow.setContent(content);
        infoWindow.open(mapInstance, techMarkers[techId]);
    }
}

function recalculateRoute(techId) {
    $.post("{{ route('maps.admin.recalculate') }}", {
        _token: '{{ csrf_token() }}',
        technicianId: techId
    }, function(res) {
        if (res && res.success) {
            CRM.toast('Route and ETA refreshed: ' + res.data.distanceText + ' (~' + res.data.etaText + ')', 'success');
            fetchLiveTrackingData();
        }
    }).fail(function() {
        CRM.toast('Route recalculation failed.', 'error');
    });
}

function fitAllMarkers() {
    if (!mapInstance || !window.google || !window.google.maps) return;
    const bounds = new google.maps.LatLngBounds();
    let hasPoints = false;

    allTechniciansData.forEach(t => {
        if (t.latitude && t.longitude) {
            bounds.extend({ lat: t.latitude, lng: t.longitude });
            hasPoints = true;
        }
        if (t.customerLatitude && t.customerLongitude && t.trackingStatus === 'ON_THE_WAY') {
            bounds.extend({ lat: t.customerLatitude, lng: t.customerLongitude });
            hasPoints = true;
        }
    });

    if (hasPoints) {
        mapInstance.fitBounds(bounds, 60);
    } else {
        mapInstance.setCenter(PUNE_CENTER);
        mapInstance.setZoom(12);
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
@endpush
@endsection
