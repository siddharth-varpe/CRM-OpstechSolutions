@php
    $p = $prefix ?? 'loc';
    $lat = isset($initialLat) && $initialLat !== null && (float)$initialLat != 0.0 ? (float)$initialLat : null;
    $lng = isset($initialLng) && $initialLng !== null && (float)$initialLng != 0.0 ? (float)$initialLng : null;
    $hasInitialCoords = ($lat !== null && $lng !== null);
    $defaultCenterLat = $hasInitialCoords ? $lat : 18.5204;
    $defaultCenterLng = $hasInitialCoords ? $lng : 73.8567;
@endphp

<div class="space-y-2.5 pt-2 border-t border-slate-100" id="{{ $p }}_location_picker_root">
    <div class="flex items-center justify-between">
        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700">
            <i data-lucide="map-pin" class="w-3.5 h-3.5 inline mr-1 text-rose-500"></i>
            Exact Premise GPS Location Pin
        </label>
        <span id="{{ $p }}_status_badge" class="px-2 py-0.5 rounded text-[10px] font-bold font-mono border {{ $hasInitialCoords ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
            {{ $hasInitialCoords ? 'Verified Saved Pin' : 'Pin Not Set' }}
        </span>
    </div>

    <!-- Quick Location Search Bar & Geocoding -->
    <div class="space-y-1.5">
        <div class="flex items-center gap-1.5">
            <div class="relative flex-1">
                <input type="text" id="{{ $p }}_search_input" placeholder="Search society, building, landmark, road or area..." class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:ring-2 focus:ring-primary-500 focus:outline-none">
                <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5"></i>
            </div>
            <button type="button" id="{{ $p }}_btn_locate" class="px-3 py-1.5 bg-sky-50 hover:bg-sky-100 border border-sky-200 text-sky-700 rounded-lg text-xs font-bold transition flex items-center gap-1 shrink-0" title="Locate typed address on map">
                <i data-lucide="navigation" class="w-3.5 h-3.5 text-sky-600"></i>
                <span>Locate Address</span>
            </button>
            <button type="button" id="{{ $p }}_btn_gps" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 rounded-lg text-xs font-bold transition flex items-center gap-1 shrink-0" title="Use current device GPS">
                <i data-lucide="crosshair" class="w-3.5 h-3.5 text-slate-600"></i>
                <span class="hidden sm:inline">My GPS</span>
            </button>
        </div>

        <!-- Inline Status / Feedback Notification -->
        <div id="{{ $p }}_search_feedback" class="hidden"></div>

        <!-- Multiple Match Suggestions Dropdown / Chip List -->
        <div id="{{ $p }}_suggestions_box" class="hidden"></div>
    </div>

    <!-- Map Canvas Container -->
    <div class="relative rounded-xl overflow-hidden border border-slate-200 bg-slate-100 shadow-2xs">
        <div id="{{ $p }}_map_canvas" class="w-full h-52 z-0"></div>
        <div class="absolute bottom-1.5 left-2 right-2 z-10 pointer-events-none flex items-center justify-between">
            <span class="bg-white/95 backdrop-blur-xs text-[10px] text-slate-700 px-2 py-0.5 rounded shadow-xs font-medium border border-slate-200/80 pointer-events-auto">
                <i data-lucide="info" class="w-3 h-3 inline mr-0.5 text-primary-600"></i>
                Drag pin or click map to place exact entrance marker
            </span>
            <button type="button" id="{{ $p }}_btn_recenter" class="bg-white/95 hover:bg-white text-[10px] text-slate-700 px-2 py-0.5 rounded shadow-xs font-semibold border border-slate-200 pointer-events-auto transition">
                Center Pin
            </button>
        </div>
    </div>

    <!-- Precision Latitude & Longitude Inputs -->
    <div class="grid grid-cols-2 gap-2.5">
        <div>
            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-0.5 font-mono">Latitude (±90.000000)</label>
            <input type="number" step="any" min="-90" max="90" name="latitude" id="{{ $p }}_input_lat" value="{{ $lat !== null ? number_format($lat, 7, '.', '') : '' }}" placeholder="e.g. 18.520430" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-semibold focus:bg-white focus:ring-2 focus:ring-primary-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-0.5 font-mono">Longitude (±180.000000)</label>
            <input type="number" step="any" min="-180" max="180" name="longitude" id="{{ $p }}_input_lng" value="{{ $lng !== null ? number_format($lng, 7, '.', '') : '' }}" placeholder="e.g. 73.856743" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-semibold focus:bg-white focus:ring-2 focus:ring-primary-500 focus:outline-none">
        </div>
    </div>
</div>

<script>
window.initLocationPicker = window.initLocationPicker || function(config) {
    const p = config.prefix;
    const canvasId = p + '_map_canvas';
    const canvasEl = document.getElementById(canvasId);
    if (!canvasEl) return null;

    let initialLat = config.initialLat !== undefined && config.initialLat !== null && Number(config.initialLat) !== 0 ? Number(config.initialLat) : null;
    let initialLng = config.initialLng !== undefined && config.initialLng !== null && Number(config.initialLng) !== 0 ? Number(config.initialLng) : null;
    const hasInitialCoords = initialLat !== null && initialLng !== null;

    const centerLat = hasInitialCoords ? initialLat : (config.defaultCenterLat || 18.5204);
    const centerLng = hasInitialCoords ? initialLng : (config.defaultCenterLng || 73.8567);
    const zoom = hasInitialCoords ? 17 : 13;

    const latInput = document.getElementById(p + '_input_lat');
    const lngInput = document.getElementById(p + '_input_lng');
    const badge = document.getElementById(p + '_status_badge');
    const searchInput = document.getElementById(p + '_search_input');
    const locateBtn = document.getElementById(p + '_btn_locate');
    const gpsBtn = document.getElementById(p + '_btn_gps');
    const recenterBtn = document.getElementById(p + '_btn_recenter');
    const feedbackEl = document.getElementById(p + '_search_feedback');
    const suggestionsEl = document.getElementById(p + '_suggestions_box');

    let debounceTimer = null;

    function updateBadge(type, text) {
        if (!badge) return;
        badge.className = 'px-2 py-0.5 rounded text-[10px] font-bold font-mono border ' +
            (type === 'exact' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
            (type === 'high' ? 'bg-sky-50 text-sky-700 border-sky-200' :
            (type === 'geocoded' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-100 text-slate-600 border-slate-200')));
        badge.innerText = text;
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function showFeedback(type, title, message) {
        if (!feedbackEl) return;
        feedbackEl.className = 'text-xs p-2.5 rounded-lg border flex items-start justify-between gap-1.5 ' +
            (type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-900' :
            (type === 'high' ? 'bg-sky-50 border-sky-200 text-sky-900' :
            (type === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-slate-50 border-slate-200 text-slate-800')));
        
        const iconName = type === 'success' ? 'check-circle-2' : (type === 'high' ? 'map-pin' : (type === 'warning' ? 'alert-triangle' : 'info'));
        const iconColor = type === 'success' ? 'text-emerald-600' : (type === 'high' ? 'text-sky-600' : (type === 'warning' ? 'text-amber-600' : 'text-slate-500'));

        feedbackEl.innerHTML = `
            <div class="flex items-start gap-2 flex-1">
                <i data-lucide="${iconName}" class="w-4 h-4 mt-0.5 shrink-0 ${iconColor}"></i>
                <div class="leading-tight">
                    <span class="font-bold text-[11px] block text-slate-800">${escapeHtml(title)}</span>
                    <span class="text-[10px] text-slate-600 mt-0.5 block">${escapeHtml(message)}</span>
                </div>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-600 text-xs px-1 font-bold" onclick="document.getElementById('${p}_search_feedback').classList.add('hidden')">&times;</button>
        `;
        feedbackEl.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function hideFeedback() {
        if (feedbackEl) feedbackEl.classList.add('hidden');
        if (suggestionsEl) suggestionsEl.classList.add('hidden');
    }

    function setCoordinates(lat, lng, badgeType = 'exact', badgeText = 'Verified Exact Pin') {
        const latNum = Number(lat);
        const lngNum = Number(lng);
        if (isNaN(latNum) || isNaN(lngNum)) return;

        if (latInput) latInput.value = latNum.toFixed(7);
        if (lngInput) lngInput.value = lngNum.toFixed(7);

        if (picker.marker) {
            picker.marker.setLatLng([latNum, lngNum]);
        }
        if (picker.map) {
            picker.map.panTo([latNum, lngNum]);
        }
        updateBadge(badgeType, badgeText);
    }

    // Initialize Leaflet Map
    if (typeof L === 'undefined') {
        console.error('Leaflet is not loaded on this page.');
        return null;
    }

    // If map already initialized on this container, clean up
    if (canvasEl._leaflet_id) {
        canvasEl._leaflet_id = null;
        canvasEl.innerHTML = '';
    }

    const map = L.map(canvasId, {
        center: [centerLat, centerLng],
        zoom: zoom,
        zoomControl: true,
        attributionControl: false
    });

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(map);

    // Custom high-visibility SVG pin icon
    const pinIcon = L.divIcon({
        className: 'custom-customer-pin',
        html: `
            <div style="position: relative; width: 32px; height: 32px; transform: translate(-50%, -100%);">
                <svg viewBox="0 0 24 24" width="32" height="32" fill="#E11D48" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.35));">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                    <circle cx="12" cy="9" r="2.5" fill="#FFFFFF"/>
                </svg>
            </div>
        `,
        iconSize: [32, 32],
        iconAnchor: [16, 32]
    });

    const marker = L.marker([centerLat, centerLng], {
        draggable: true,
        icon: pinIcon
    }).addTo(map);

    marker.on('dragend', function(e) {
        const pos = marker.getLatLng();
        setCoordinates(pos.lat, pos.lng, 'exact', 'Verified Exact Pin');
        hideFeedback();
    });

    map.on('click', function(e) {
        setCoordinates(e.latlng.lat, e.latlng.lng, 'exact', 'Verified Exact Pin');
        hideFeedback();
    });

    // Manual Coordinate inputs change
    if (latInput && lngInput) {
        const handleManualInput = function() {
            const latVal = parseFloat(latInput.value);
            const lngVal = parseFloat(lngInput.value);
            if (!isNaN(latVal) && !isNaN(lngVal) && latVal >= -90 && latVal <= 90 && lngVal >= -180 && lngVal <= 180) {
                marker.setLatLng([latVal, lngVal]);
                map.panTo([latVal, lngVal]);
                updateBadge('exact', 'Verified Exact Pin');
            }
        };
        latInput.addEventListener('input', handleManualInput);
        lngInput.addEventListener('input', handleManualInput);
    }

    // Recenter button
    if (recenterBtn) {
        recenterBtn.addEventListener('click', function() {
            const pos = marker.getLatLng();
            map.setView(pos, 17);
        });
    }

    // My GPS button
    if (gpsBtn) {
        gpsBtn.addEventListener('click', function() {
            if (!navigator.geolocation) {
                showFeedback('warning', 'GPS Unavailable:', 'Geolocation is not supported by your browser.');
                return;
            }
            gpsBtn.disabled = true;
            gpsBtn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i>';
            if (typeof lucide !== 'undefined') lucide.createIcons();

            navigator.geolocation.getCurrentPosition(function(pos) {
                gpsBtn.disabled = false;
                gpsBtn.innerHTML = '<i data-lucide="crosshair" class="w-3.5 h-3.5 text-slate-600"></i><span class="hidden sm:inline">My GPS</span>';
                if (typeof lucide !== 'undefined') lucide.createIcons();
                setCoordinates(pos.coords.latitude, pos.coords.longitude, 'exact', 'Verified Device GPS');
                map.flyTo([pos.coords.latitude, pos.coords.longitude], 18, { animate: true, duration: 1.0 });
                showFeedback('success', 'GPS Acquired:', 'Pin set to current device location. Drag pin if needed to refine entrance.');
            }, function(err) {
                gpsBtn.disabled = false;
                gpsBtn.innerHTML = '<i data-lucide="crosshair" class="w-3.5 h-3.5 text-slate-600"></i><span class="hidden sm:inline">My GPS</span>';
                if (typeof lucide !== 'undefined') lucide.createIcons();
                showFeedback('warning', 'GPS Error:', 'Could not acquire device GPS: ' + err.message);
            }, { enableHighAccuracy: true, timeout: 10000 });
        });
    }

    // Locate Address button & Search with Landmark & Accuracy Prioritization
    function performSearch() {
        let q = searchInput ? searchInput.value.trim() : '';
        const form = canvasEl.closest('form') || document.querySelector('#add-customer-modal form') || document.querySelector('#edit-customer-modal form');
        const formLandmark = form ? (form.querySelector('[name="landmark"]')?.value || '') : '';
        const formArea = form ? (form.querySelector('[name="address_line2"]')?.value || '') : '';
        const formCity = form ? (form.querySelector('[name="city"]')?.value || 'Pune') : 'Pune';

        if (!q && config.getAddressString) {
            q = config.getAddressString().trim();
            if (q && searchInput) {
                searchInput.value = q;
            }
        }
        if (!q && !formLandmark && !formArea) {
            showFeedback('warning', 'Address Required:', 'Please enter an address, society name, or landmark to search.');
            if (searchInput) searchInput.focus();
            return;
        }

        hideFeedback();
        if (locateBtn) {
            locateBtn.disabled = true;
            locateBtn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> Locating...';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        $.ajax({
            url: "{{ route('customers.geocode') }}",
            type: 'GET',
            data: {
                query: q,
                landmark: formLandmark,
                area: formArea,
                city: formCity
            },
            success: function(res) {
                if (res && res.success && res.latitude && res.longitude) {
                    const accuracy = res.accuracy || 'approximate';
                    let badgeType = 'geocoded';
                    let badgeText = 'Located Area (Adjust pin)';
                    let targetZoom = 17;
                    let feedbackType = 'high';

                    if (accuracy === 'exact') {
                        badgeType = 'exact';
                        badgeText = 'Verified Building (Exact)';
                        targetZoom = 18;
                        feedbackType = 'success';
                        showFeedback('success', '🎯 Exact Building Found:', (res.display_name || res.matched_term) + ' — Pin placed right at the building premise entrance.');
                    } else if (accuracy === 'high') {
                        badgeType = 'high';
                        badgeText = 'Landmark/Street (~50-100m)';
                        targetZoom = 17;
                        feedbackType = 'high';
                        showFeedback('high', '📍 Landmark / Street Located (~50-100m):', (res.display_name || res.matched_term) + ' — Map zoomed into street view. Drag the red pin to the exact entrance gate.');
                    } else {
                        badgeType = 'geocoded';
                        badgeText = 'Locality Centroid';
                        targetZoom = 16;
                        feedbackType = 'warning';
                        showFeedback('warning', '📍 Locality Centroid Located:', (res.display_name || res.matched_term) + ' — Map centered at this area. Drag the red pin or enter your landmark for +-100m accuracy.');
                    }

                    setCoordinates(res.latitude, res.longitude, badgeType, badgeText);
                    if (picker.map) {
                        picker.map.flyTo([res.latitude, res.longitude], targetZoom, { animate: true, duration: 1.0 });
                    }

                    // Render suggestion chips if multiple results found
                    if (suggestionsEl && res.results && res.results.length > 1) {
                        suggestionsEl.className = 'p-2 bg-slate-50 border border-slate-200 rounded-lg space-y-1.5 text-xs';
                        let chipsHtml = '<div class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">Nearby Specific Landmarks & Roads (Click to jump):</div><div class="flex flex-wrap gap-1">';
                        
                        res.results.forEach(function(item) {
                            const parts = (item.display_name || '').split(',');
                            const shortLabel = parts.slice(0, 3).join(', ');
                            const isExact = (item.accuracy === 'exact');
                            const isHigh = (item.accuracy === 'high');
                            
                            const badgeTag = isExact ? 
                                '<span class="ml-1 px-1 py-0.2 rounded text-[8px] font-bold bg-emerald-100 text-emerald-800">Exact Building</span>' :
                                (isHigh ? '<span class="ml-1 px-1 py-0.2 rounded text-[8px] font-bold bg-sky-100 text-sky-800">~50-100m</span>' : '');

                            chipsHtml += `<button type="button" class="${p}_suggestion_btn px-2 py-1 bg-white hover:bg-sky-50 border border-slate-200 hover:border-sky-300 text-slate-700 hover:text-sky-800 rounded text-[10px] font-medium transition text-left cursor-pointer flex items-center" data-lat="${item.latitude}" data-lng="${item.longitude}" data-name="${escapeHtml(item.display_name)}" data-accuracy="${item.accuracy || 'approximate'}">
                                📍 ${escapeHtml(shortLabel)}${badgeTag}
                            </button>`;
                        });
                        chipsHtml += '</div>';
                        suggestionsEl.innerHTML = chipsHtml;
                        suggestionsEl.classList.remove('hidden');

                        // Attach click handlers to chips
                        suggestionsEl.querySelectorAll('.' + p + '_suggestion_btn').forEach(function(btn) {
                            btn.addEventListener('click', function() {
                                const bLat = parseFloat(this.getAttribute('data-lat'));
                                const bLng = parseFloat(this.getAttribute('data-lng'));
                                const bName = this.getAttribute('data-name');
                                const bAcc = this.getAttribute('data-accuracy');

                                const cZoom = (bAcc === 'exact' ? 18 : (bAcc === 'high' ? 17 : 16));
                                const cBadge = (bAcc === 'exact' ? 'exact' : (bAcc === 'high' ? 'high' : 'geocoded'));
                                const cText = (bAcc === 'exact' ? 'Verified Building (Exact)' : (bAcc === 'high' ? 'Landmark/Street (~50-100m)' : 'Located Area'));

                                setCoordinates(bLat, bLng, cBadge, cText);
                                if (picker.map) {
                                    picker.map.flyTo([bLat, bLng], cZoom, { animate: true, duration: 0.8 });
                                }
                                showFeedback(bAcc === 'exact' ? 'success' : 'high', 'Jumped to:', bName + ' — Drag the pin to pinpoint customer entrance.');
                                suggestionsEl.classList.add('hidden');
                            });
                        });
                    }
                } else {
                    showFeedback('warning', 'Location Not Found:', 'Could not locate coordinates. You can drag or click the map to place the pin manually.');
                }
            },
            error: function(xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Address not found. Please click on the map to place the pin manually.';
                showFeedback('warning', 'Not Found:', msg);
            },
            complete: function() {
                if (locateBtn) {
                    locateBtn.disabled = false;
                    locateBtn.innerHTML = '<i data-lucide="navigation" class="w-3.5 h-3.5 text-sky-600"></i><span>Locate Address</span>';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            }
        });
    }

    if (locateBtn) {
        locateBtn.addEventListener('click', performSearch);
    }
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });
        searchInput.addEventListener('focus', function() {
            if (!this.value.trim() && config.getAddressString) {
                const autoStr = config.getAddressString().trim();
                if (autoStr) {
                    this.value = autoStr;
                }
            }
        });
    }

    // Initial coordinates setup
    if (hasInitialCoords) {
        setCoordinates(initialLat, initialLng, 'exact', 'Verified Saved Pin');
    }

    // Modal sizing helper
    setTimeout(function() {
        map.invalidateSize();
    }, 200);

    const picker = {
        map: map,
        marker: marker,
        setCoordinates: setCoordinates,
        invalidate: function() {
            setTimeout(function() {
                map.invalidateSize();
            }, 150);
        }
    };

    window['locationPicker_' + p] = picker;
    if (typeof lucide !== 'undefined') lucide.createIcons();
    return picker;
};
</script>
