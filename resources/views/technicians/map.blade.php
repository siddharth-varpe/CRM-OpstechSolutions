@extends('layouts.app')

@section('title', 'Live Field Technician Map & GPS Tracking — Enterprises CRM')

@section('content')
<div class="space-y-4 max-w-7xl mx-auto pb-8 select-none">
    <!-- Header with View Switcher & Live GPS Pulse Badge -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl sm:text-3xl font-display font-extrabold tracking-tight text-slate-900">
                    Live Field Workforce Map
                </h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-300 shadow-2xs font-mono">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                    GPS STREAM LIVE
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">
                Real-time technician tracking, transit telemetry, dynamic route polylines, and field ETA monitoring.
            </p>
        </div>

        <!-- View Switcher: Roster Directory vs Live Field Map -->
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <button type="button"
                    id="btnRefreshMap"
                    onclick="fetchLiveTrackingData(true)"
                    class="p-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-600 rounded-xl transition-all shadow-2xs"
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
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-elevated overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[640px] h-[calc(100vh-190px)]">
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
                    <button type="button" onclick="setMapFilter('ALL')" class="map-filter-btn px-2.5 py-1 rounded-md transition-all bg-slate-900 text-white" data-filter="ALL">
                        All (<span id="countAll">0</span>)
                    </button>
                    <button type="button" onclick="setMapFilter('NAVIGATING')" class="map-filter-btn px-2.5 py-1 rounded-md transition-all text-slate-600 hover:bg-slate-200" data-filter="NAVIGATING">
                        Live (<span id="countLive">0</span>)
                    </button>
                    <button type="button" onclick="setMapFilter('IDLE')" class="map-filter-btn px-2.5 py-1 rounded-md transition-all text-slate-600 hover:bg-slate-200" data-filter="IDLE">
                        Idle (<span id="countIdle">0</span>)
                    </button>
                    <button type="button" onclick="setMapFilter('OFFLINE')" class="map-filter-btn px-2.5 py-1 rounded-md transition-all text-slate-600 hover:bg-slate-200" data-filter="OFFLINE">
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
                    Auto-syncing every 5s
                </span>
                <span id="lastUpdatedLabel">Updated just now</span>
            </div>
        </div>

        <!-- Right Panel: Interactive Real-Time Map (col-span-8) -->
        <div class="lg:col-span-8 relative h-full bg-slate-100 flex flex-col">
            <!-- Map Container -->
            <div id="adminLiveMapContainer" class="w-full h-full z-10" style="min-height: 520px;"></div>

            <!-- Floating Map Overlays & Quick Controls -->
            <div class="absolute top-4 right-4 z-20 flex flex-col gap-2">
                <button type="button"
                        onclick="fitAllMarkers()"
                        class="p-2.5 bg-white/95 backdrop-blur-xs hover:bg-white text-slate-700 rounded-xl shadow-elevated border border-slate-200 transition-all font-bold text-xs flex items-center gap-1.5 cursor-pointer hover:scale-105"
                        title="Zoom to Fit All Technicians">
                    <i data-lucide="maximize" class="w-4 h-4 text-primary-600"></i>
                    <span class="hidden sm:inline">Fit All Crew</span>
                </button>

                <button type="button"
                        onclick="resetToPuneCenter()"
                        class="p-2.5 bg-white/95 backdrop-blur-xs hover:bg-white text-slate-700 rounded-xl shadow-elevated border border-slate-200 transition-all font-bold text-xs flex items-center gap-1.5 cursor-pointer hover:scale-105"
                        title="Center Pune City">
                    <i data-lucide="crosshair" class="w-4 h-4 text-slate-600"></i>
                    <span class="hidden sm:inline">Center Pune</span>
                </button>
            </div>

            <!-- Bottom Floating Legend -->
            <div class="absolute bottom-4 left-4 z-20 bg-white/95 backdrop-blur-xs border border-slate-200 px-3 py-2 rounded-xl shadow-elevated text-[11px] flex items-center gap-4 text-slate-600 font-medium">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-emerald-200"></span>
                    <span>Live Navigating</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <span>Warning / Delayed</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                    <span>Idle / Standby</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-600"></span>
                    <span>Customer Pin</span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let mapInstance = null;
let techMarkers = {};
let destMarkers = {};
let routePolylines = {};
let travelledPolylines = {};
let allTechniciansData = [];
let currentFilter = 'ALL';
let selectedTechId = null;
let pollTimer = null;

const PUNE_CENTER = [18.5204, 73.8567];

// Initialize Map
$(document).ready(function() {
    initLeafletMap();
    fetchLiveTrackingData();

    // Polling interval every 5 seconds
    pollTimer = setInterval(function() {
        fetchLiveTrackingData(false);
    }, 5000);

    // Filter search input
    $('#techSearchInput').on('input', function() {
        renderTechnicianCards();
    });
});

function initLeafletMap() {
    if (mapInstance) return;

    try {
        mapInstance = L.map('adminLiveMapContainer', {
            center: PUNE_CENTER,
            zoom: 12,
            zoomControl: true
        });

        // Add OpenStreetMap Tile Layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors | Enterprises CRM',
            maxZoom: 19
        }).addTo(mapInstance);

        // Delay invalidating size for layout rendering
        setTimeout(function() {
            mapInstance.invalidateSize();
        }, 300);
    } catch (e) {
        console.error('Failed to initialize Leaflet map:', e);
    }
}

function fetchLiveTrackingData(showSpin = false) {
    if (showSpin) {
        $('#refreshIcon').addClass('animate-spin');
    }

    $.get("{{ route('maps.admin.live') }}", function(res) {
        if (res && res.success && Array.isArray(res.data)) {
            allTechniciansData = res.data;
            updateMapElements(allTechniciansData);
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
        lucide.createIcons();
        return;
    }

    let html = '';
    filtered.forEach(t => {
        const isSelected = selectedTechId === t.technicianId;
        const isLive = t.trackingStatus === 'ON_THE_WAY';
        const freshnessClass = isLive ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-slate-600 bg-slate-100 border-slate-200';
        const hasCoords = t.latitude && t.longitude;

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
                            <span class="truncate">${t.customerAddress || 'Pune Location'}</span>
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
                            class="text-xs text-primary-600 hover:text-primary-800 font-bold hover:underline flex items-center gap-1">
                        <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                        Recalc Route
                    </button>
                </div>
            </div>
        `;
    });

    $('#techCardsContainer').html(html);
    lucide.createIcons();
}

function updateMapElements(technicians) {
    if (!mapInstance) return;

    technicians.forEach(t => {
        const techId = t.technicianId;
        const hasCoords = t.latitude !== null && t.longitude !== null;
        const hasDest = t.customerLatitude !== null && t.customerLongitude !== null;

        // 1. Technician Marker
        if (hasCoords) {
            const techPos = [t.latitude, t.longitude];
            const isLive = t.trackingStatus === 'ON_THE_WAY';
            const bubbleClass = isLive ? 'live' : (t.statusFreshness === 'WARNING' ? 'warning' : (t.statusFreshness === 'STALE' ? 'stale' : 'offline'));

            const iconHtml = `
                <div class="custom-tech-pin">
                    ${isLive ? `<div class="tech-pulse-ring live"></div>` : ''}
                    <div class="tech-marker-bubble ${bubbleClass}">
                        ${t.technicianName.charAt(0)}
                    </div>
                </div>
            `;

            const customIcon = L.divIcon({
                className: '',
                html: iconHtml,
                iconSize: [40, 40],
                iconAnchor: [20, 20],
                popupAnchor: [0, -22]
            });

            if (techMarkers[techId]) {
                techMarkers[techId].setLatLng(techPos);
                techMarkers[techId].setIcon(customIcon);
            } else {
                techMarkers[techId] = L.marker(techPos, { icon: customIcon }).addTo(mapInstance);
                techMarkers[techId].on('click', () => selectTechnician(techId));
            }

            // Update Popup Content
            const popupContent = `
                <div class="p-3.5 space-y-2 min-w-[220px]">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <span class="font-bold text-slate-900 text-xs">${t.technicianName}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase font-mono ${isLive ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'}">
                            ${t.trackingStatus}
                        </span>
                    </div>
                    <div class="text-[11px] text-slate-600 font-mono space-y-1">
                        <div>Phone: <b>${t.technicianPhone || 'N/A'}</b></div>
                        ${t.customerName ? `<div>Heading To: <b class="text-slate-900">${t.customerName}</b></div>` : ''}
                        ${t.distanceKm ? `<div>Distance: <b>${t.distanceKm} km</b> (ETA: ~${t.etaMinutes}m)</div>` : ''}
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                        <button type="button" onclick="recalculateRoute('${t.technicianId}')" class="text-primary-600 font-bold hover:underline">
                            Recalculate
                        </button>
                        ${t.customerLatitude && t.customerLongitude ? `
                            <a href="https://www.google.com/maps/dir/?api=1&destination=${t.customerLatitude},${t.customerLongitude}" target="_blank" class="text-emerald-700 font-bold hover:underline">
                                Google Maps &rarr;
                            </a>
                        ` : ''}
                    </div>
                </div>
            `;
            techMarkers[techId].bindPopup(popupContent);
        } else if (techMarkers[techId]) {
            mapInstance.removeLayer(techMarkers[techId]);
            delete techMarkers[techId];
        }

        // 2. Customer Destination Marker
        if (hasDest && t.trackingStatus === 'ON_THE_WAY') {
            const destPos = [t.customerLatitude, t.customerLongitude];
            const destHtml = `
                <div class="custom-dest-pin">
                    <div class="dest-pin-badge">
                        <i data-lucide="map-pin" class="w-3 h-3 text-white"></i>
                        <span>${t.customerName || 'Destination'}</span>
                    </div>
                    <div class="dest-pin-stem"></div>
                </div>
            `;
            const destIcon = L.divIcon({
                className: '',
                html: destHtml,
                iconSize: [120, 36],
                iconAnchor: [60, 36]
            });

            if (destMarkers[techId]) {
                destMarkers[techId].setLatLng(destPos);
            } else {
                destMarkers[techId] = L.marker(destPos, { icon: destIcon }).addTo(mapInstance);
            }
        } else if (destMarkers[techId]) {
            mapInstance.removeLayer(destMarkers[techId]);
            delete destMarkers[techId];
        }

        // 3. Planned Driving Polyline
        if (hasCoords && hasDest && t.trackingStatus === 'ON_THE_WAY') {
            const linePoints = [
                [t.latitude, t.longitude],
                [t.customerLatitude, t.customerLongitude]
            ];

            if (routePolylines[techId]) {
                routePolylines[techId].setLatLngs(linePoints);
            } else {
                routePolylines[techId] = L.polyline(linePoints, {
                    color: '#0284C7',
                    weight: 4,
                    opacity: 0.85,
                    dashArray: '6, 8'
                }).addTo(mapInstance);
            }
        } else if (routePolylines[techId]) {
            mapInstance.removeLayer(routePolylines[techId]);
            delete routePolylines[techId];
        }

        // 4. Travelled Path Breadcrumbs
        if (t.travelledPath && t.travelledPath.length > 1) {
            const breadcrumbs = t.travelledPath.map(p => [p.latitude, p.longitude]);
            if (travelledPolylines[techId]) {
                travelledPolylines[techId].setLatLngs(breadcrumbs);
            } else {
                travelledPolylines[techId] = L.polyline(breadcrumbs, {
                    color: '#10B981',
                    weight: 3,
                    opacity: 0.6
                }).addTo(mapInstance);
            }
        }
    });

    lucide.createIcons();
}

function selectTechnician(techId) {
    selectedTechId = techId;
    renderTechnicianCards();

    const tech = allTechniciansData.find(t => t.technicianId === techId);
    if (!tech || !tech.latitude || !tech.longitude || !mapInstance) return;

    mapInstance.setView([tech.latitude, tech.longitude], 14, { animate: true });

    if (techMarkers[techId]) {
        techMarkers[techId].openPopup();
    }
}

function recalculateRoute(techId) {
    $.post("{{ route('maps.admin.recalculate') }}", {
        _token: '{{ csrf_token() }}',
        technicianId: techId
    }, function(res) {
        if (res && res.success) {
            showToast('Route and ETA refreshed: ' + res.data.distanceText + ' (~' + res.data.etaText + ')', 'success');
            fetchLiveTrackingData();
        }
    }).fail(function() {
        showToast('Route recalculation failed.', 'error');
    });
}

function fitAllMarkers() {
    if (!mapInstance) return;
    const allCoords = [];
    allTechniciansData.forEach(t => {
        if (t.latitude && t.longitude) allCoords.push([t.latitude, t.longitude]);
        if (t.customerLatitude && t.customerLongitude && t.trackingStatus === 'ON_THE_WAY') {
            allCoords.push([t.customerLatitude, t.customerLongitude]);
        }
    });

    if (allCoords.length > 0) {
        mapInstance.fitBounds(L.latLngBounds(allCoords), { padding: [40, 40] });
    } else {
        mapInstance.setView(PUNE_CENTER, 12);
    }
}

function resetToPuneCenter() {
    if (mapInstance) {
        mapInstance.setView(PUNE_CENTER, 12, { animate: true });
    }
}
</script>
@endpush
@endsection
