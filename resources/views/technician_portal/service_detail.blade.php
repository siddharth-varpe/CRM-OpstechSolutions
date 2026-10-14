<!DOCTYPE html>
<html lang="en" class="h-full bg-[#F4F6F8]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Job Execution {{ $service->service_number }} — Enterprises CRM</title>
    
    <!-- Official Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <script src="{{ asset('js/crm-app.js') }}"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3 { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        #sigCanvas { touch-action: none; }
    </style>
</head>
<body class="min-h-screen bg-[#F4F6F8] text-slate-800 flex flex-col antialiased select-none pb-20">
    <div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"></div>

    <!-- Top Header in Admin Theme -->
    <header class="bg-[#00152B] text-white border-b border-slate-800 px-4 py-3 sticky top-0 z-30 flex items-center justify-between shadow-md">
        <a href="{{ route('tech_portal.dashboard') }}?tab=assigned" class="flex items-center gap-2 text-slate-300 hover:text-white transition">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
            <span class="text-xs font-semibold">Back to Assigned</span>
        </a>
        <div class="text-right">
            <span class="font-mono text-xs font-bold text-sky-400 bg-sky-950/80 px-2 py-0.5 rounded border border-sky-500/30">{{ $service->service_number }}</span>
        </div>
    </header>

    <!-- Execution Form Container -->
    <main class="flex-1 max-w-xl w-full mx-auto p-4 space-y-4 pb-24">
        <!-- Customer & Location Card -->
        @php
            $addrStr = '';
            if ($service->customer && $service->customer->addresses->isNotEmpty()) {
                $addr = $service->customer->addresses->first();
                $addrStr = $addr->full_address ?: "{$addr->address_line1}, {$addr->city} {$addr->postal_code}";
            }
        @endphp
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-sm space-y-3">
            <div class="flex justify-between items-start gap-2">
                <div>
                    <h1 class="text-base font-bold text-slate-900">{{ $service->customer->name ?? 'Direct Customer' }}</h1>
                    <div class="text-xs text-sky-700 font-medium mt-0.5">{{ $service->asset->custom_name ?? $service->asset->product->name ?? 'Equipment / Machine' }}</div>
                    <div class="text-[11px] text-slate-400 font-mono">S/N: {{ $service->asset->serial_number ?? 'N/A' }}</div>
                </div>
                <a href="{{ route('tech_portal.navigation', $service->id) }}" target="_blank" onclick="openGoogleMapsNavigation('{{ $service->id }}', event)"
                   class="p-2 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200/80 hover:bg-emerald-100 flex items-center gap-1.5 text-xs font-semibold transition" title="Open Google Maps">
                    <i data-lucide="navigation" class="w-3.5 h-3.5"></i>
                    <span>Navigate</span>
                </a>
            </div>

            @if($service->customer && $service->customer->phone)
                <div class="pt-2 border-t border-slate-100 flex justify-between items-center text-xs">
                    <span class="text-slate-500">Phone: {{ $service->customer->phone }}</span>
                    <a href="tel:{{ $service->customer->phone }}" class="text-sky-600 font-bold flex items-center gap-1 hover:text-sky-700">
                        <i data-lucide="phone" class="w-3.5 h-3.5"></i> Call Customer
                    </a>
                </div>
            @endif

            @if($addrStr)
                <div class="text-xs text-slate-600 pt-1 border-t border-slate-100 flex items-start gap-1.5">
                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5"></i>
                    <span>{{ $addrStr }}</span>
                </div>
            @endif
        </div>

        <!-- Google Maps Navigation Action Banner -->
        <div class="bg-emerald-50 border border-emerald-200/90 rounded-2xl p-4 shadow-sm flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <i data-lucide="navigation" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 leading-tight">Customer Location</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $addrStr ?: 'Customer registered address' }}</p>
                </div>
            </div>
            <a href="{{ route('tech_portal.navigation', $service->id) }}" target="_blank" onclick="openGoogleMapsNavigation('{{ $service->id }}', event)"
               class="px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center gap-1.5 shadow-sm transition shrink-0">
                <i data-lucide="navigation" class="w-4 h-4"></i>
                <span>Navigate in Google Maps</span>
            </a>
        </div>

        @if($service->status === 'ON_HOLD')
            <!-- On Hold Alert Banner -->
            <div class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center shrink-0">
                        <i data-lucide="pause-circle" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-800 bg-amber-200/70 px-2 py-0.5 rounded font-mono">Job Currently On Hold</span>
                        </div>
                        <p class="text-xs text-amber-900 mt-1">
                            @if($service->internal_notes)
                                {{ \Illuminate\Support\Str::limit($service->internal_notes, 120) }}
                            @else
                                This job is currently paused. Resume when ready to proceed with customer on-site execution.
                            @endif
                        </p>
                    </div>
                </div>
                <button type="button" onclick="resumeService()" class="px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white text-xs font-bold flex items-center justify-center gap-1.5 shadow-sm transition shrink-0 cursor-pointer">
                    <i data-lucide="play-circle" class="w-4 h-4"></i>
                    <span>Resume Job (In Progress)</span>
                </button>
            </div>
        @else
            <!-- Keep on Hold Action Strip -->
            <div class="flex items-center justify-between gap-2 bg-white border border-slate-200/90 rounded-2xl p-3 shadow-2xs">
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <i data-lucide="info" class="w-4 h-4 text-slate-400"></i>
                    <span>Need to pause this service visit?</span>
                </div>
                <button type="button" onclick="openHoldModal()" class="px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-amber-50 text-slate-700 hover:text-amber-800 border border-slate-200 hover:border-amber-300 text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer">
                    <i data-lucide="pause-circle" class="w-3.5 h-3.5 text-amber-600"></i>
                    <span>Keep Job on Hold</span>
                </button>
            </div>
        @endif

        <!-- Service Execution Form -->
        <form id="formCompleteTechJob" action="{{ route('tech_portal.complete', $service->id) }}" method="POST" class="space-y-4">
            @csrf

            <!-- 1. Water Quality Measurements -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-4 space-y-3 shadow-sm">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                    <i data-lucide="droplet" class="w-4 h-4 text-sky-600"></i>
                    <span>TDS &amp; Water Quality Check</span>
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Inlet Raw Water TDS (PPM)</label>
                        <input type="number" name="inlet_tds" value="650" placeholder="e.g. 650" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 font-mono focus:outline-none focus:bg-white focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pure Outlet TDS (PPM)</label>
                        <input type="number" name="outlet_tds" value="75" placeholder="e.g. 75" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-emerald-600 font-bold font-mono focus:outline-none focus:bg-white focus:border-sky-500">
                    </div>
                </div>
            </div>

            <!-- 2. Technical Observations & Action Taken -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-4 space-y-3 shadow-sm">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                    <i data-lucide="clipboard-check" class="w-4 h-4 text-sky-600"></i>
                    <span>Diagnosis &amp; Work Performed</span>
                </h3>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Diagnosis / Root Cause <span class="text-rose-500">*</span></label>
                    <textarea name="diagnosis" rows="2" required placeholder="Observations on pump pressure, filter clogging, leakages..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:bg-white focus:border-sky-500">Normal periodic maintenance check completed. Sediment filter replaced.</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Work Performed / Corrective Action <span class="text-rose-500">*</span></label>
                    <textarea name="work_performed" rows="2" required placeholder="Specific service steps executed..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:bg-white focus:border-sky-500">Cleaned housing, serviced internal components, inspected unit and verified output flow rate.</textarea>
                </div>
            </div>

            <!-- 3. Spare Parts Replaced -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-4 space-y-3 shadow-sm">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                    <i data-lucide="layers" class="w-4 h-4 text-sky-600"></i>
                    <span>Parts Replaced On-Site</span>
                </h3>

                <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                    @forelse($spares as $sp)
                        <label class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 cursor-pointer hover:border-sky-300 transition">
                            <div class="flex items-center gap-2.5">
                                <input type="checkbox" name="parts_replaced[]" value="{{ $sp->name }}" class="w-4 h-4 rounded text-sky-600 border-slate-300">
                                <span class="text-xs font-medium text-slate-800">{{ $sp->name }}</span>
                            </div>
                            <span class="text-xs font-mono font-semibold text-slate-600">₹{{ number_format($sp->selling_price, 2) }}</span>
                        </label>
                    @empty
                        <div class="text-xs text-slate-400">No catalogue spare parts listed.</div>
                    @endforelse
                </div>
            </div>

            <!-- 4. Job Charges -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-4 space-y-3 shadow-sm">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                    <i data-lucide="receipt" class="w-4 h-4 text-sky-600"></i>
                    <span>Service Charges</span>
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Labor / Visit Fee (₹)</label>
                        <input type="number" name="labor_charges" value="350" min="0" step="0.01" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 font-mono focus:outline-none focus:bg-white focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Parts Charges (₹)</label>
                        <input type="number" name="parts_charges" value="0" min="0" step="0.01" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 font-mono focus:outline-none focus:bg-white focus:border-sky-500">
                    </div>
                </div>
            </div>

            <!-- 5. Digital Customer Signature Canvas -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-4 space-y-3 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                        <i data-lucide="pen-tool" class="w-4 h-4 text-sky-600"></i>
                        <span>Customer Digital Signature</span>
                    </h3>
                    <button type="button" id="btnClearSig" class="text-xs text-rose-600 hover:text-rose-700 font-semibold">
                        Clear Pad
                    </button>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-xl overflow-hidden relative p-1">
                    <canvas id="sigCanvas" width="400" height="140" class="w-full bg-white rounded-lg border border-dashed border-slate-300 block cursor-crosshair"></canvas>
                    <span class="absolute bottom-2 left-3 text-[10px] text-slate-400 pointer-events-none">
                        Sign with finger or stylus inside box
                    </span>
                </div>
                <input type="hidden" name="signature_data" id="sigDataInput">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Customer Feedback</label>
                    <input type="text" name="customer_remarks" value="Service completed satisfactorily" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:bg-white focus:border-sky-500">
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full bg-sky-600 hover:bg-sky-700 active:bg-sky-800 text-white font-bold py-3.5 px-4 rounded-xl text-sm transition flex items-center justify-center gap-2 shadow-sm">
                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                <span>Complete Service &amp; Close Job</span>
            </button>
        </form>
    </main>

    <script>
    $(document).ready(function() {
        lucide.createIcons();

        const canvas = document.getElementById('sigCanvas');
        const ctx = canvas.getContext('2d');
        let isDrawing = false;

        ctx.strokeStyle = '#0F172A';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return {
                x: (clientX - rect.left) * (canvas.width / rect.width),
                y: (clientY - rect.top) * (canvas.height / rect.height)
            };
        }

        function startDraw(e) {
            e.preventDefault();
            isDrawing = true;
            const pos = getPos(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        }

        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();
            const pos = getPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
        }

        function stopDraw(e) {
            if (!isDrawing) return;
            isDrawing = false;
            $('#sigDataInput').val(canvas.toDataURL());
        }

        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', draw);
        window.addEventListener('mouseup', stopDraw);

        canvas.addEventListener('touchstart', startDraw, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        window.addEventListener('touchend', stopDraw);

        $('#btnClearSig').on('click', function() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            $('#sigDataInput').val('');
        });

        CRM.handleAjaxForm('#formCompleteTechJob', function(res) {
            CRM.toast(res.message || 'Job completed successfully!', 'success');
            setTimeout(() => window.location.href = res.redirect || "{{ route('tech_portal.dashboard') }}", 500);
        });
    });

    function openHoldModal() {
        $('#holdReason').val('Awaiting specialized spare part');
        $('#modalHoldJob').removeClass('hidden');
        lucide.createIcons();
    }

    function closeHoldModal() {
        $('#modalHoldJob').addClass('hidden');
    }

    function submitHoldJob(e) {
        e.preventDefault();
        const reason = $('#holdReason').val() || 'Job kept on hold by technician';
        updateServiceStatus('ON_HOLD', reason);
        closeHoldModal();
    }

    function resumeService() {
        updateServiceStatus('IN_PROGRESS', 'Resumed by technician');
    }

    function updateServiceStatus(status, reason) {
        $.ajax({
            url: "{{ route('tech_portal.status', $service->id) }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                status: status,
                reason: reason || ''
            },
            success: function(res) {
                CRM.toast(res.message || 'Status updated.', 'success');
                setTimeout(() => window.location.reload(), 500);
            },
            error: function(xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to update status.';
                CRM.toast(msg, 'error');
            }
        });
    }

    let activeNavServiceId = null;
    let activeNavWatchId = null;
    let lastNavPingTime = 0;

    $(document).ready(function() {
        checkActiveNavigationSession();
    });

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

        // External Google Maps directions URL launches in new tab
    }

    function startContinuousGpsWatch(serviceId, destination, triggerInitialPing = true) {
        activeNavServiceId = serviceId;

        const srvNumber = destination.serviceNumber || '{{ $service->service_number }}';
        const custName = destination.customerName || '{{ addslashes($service->customer->name ?? "Direct Customer") }}';
        const address = destination.address || '{{ addslashes($addrStr ?? "Customer premise address") }}';
        const mapsUrl = (destination.latitude && destination.longitude)
            ? `https://www.google.com/maps/dir/?api=1&destination=${destination.latitude},${destination.longitude}`
            : (destination.address ? `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(destination.address)}` : `/tech-portal/services/${serviceId}/navigate`);

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

        if (activeNavWatchId !== null) {
            navigator.geolocation.clearWatch(activeNavWatchId);
            activeNavWatchId = null;
        }

        if (triggerInitialPing) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                transmitGpsUpdate(pos);
            }, function(err) {
                handleNavGpsError(err);
            }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 });
        }

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
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        const accuracy = pos.coords.accuracy || 10;
        const speed = pos.coords.speed || 0;
        const heading = pos.coords.heading || 0;

        $('#activeNavGpsStatus').text('±' + Math.round(accuracy) + 'm');

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
                latitude: lat,
                longitude: lng,
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
        if (err.code === 1) {
            $('#activeNavGpsStatus').text('GPS Denied');
        } else if (err.code === 2) {
            $('#activeNavGpsStatus').text('GPS Weak');
        } else if (err.code === 3) {
            $('#activeNavGpsStatus').text('Re-acquiring...');
        }
    }

    function stopActiveNavigation() {
        if (!confirm('Confirm arrival at customer location? This will stop active GPS navigation.')) return;

        if (activeNavWatchId !== null) {
            navigator.geolocation.clearWatch(activeNavWatchId);
            activeNavWatchId = null;
        }
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
            },
            error: function() {
                CRM.toast('Failed to stop navigation on server.', 'error');
            }
        });
    }
    </script>

    <!-- Active Navigation Floating HUD for Field Detail -->
    <div id="activeNavHud" class="hidden fixed bottom-6 left-3 right-3 sm:left-auto sm:right-6 sm:max-w-md z-40 bg-[#00152B]/95 text-white backdrop-blur-md border border-sky-500/40 rounded-2xl p-3.5 shadow-2xl space-y-2 animate-in fade-in slide-in-from-bottom-4 duration-200">
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

    <!-- Modal: Put Job On Hold -->
    <div id="modalHoldJob" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-sm w-full p-5 space-y-4 shadow-xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2 text-amber-600">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                        <i data-lucide="pause-circle" class="w-5 h-5"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">Keep Job on Hold</h3>
                </div>
                <button type="button" onclick="closeHoldModal()" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form onsubmit="submitHoldJob(event)" class="space-y-3">
                <p class="text-xs text-slate-600">
                    Provide a reason for putting <span class="font-mono font-bold text-sky-700">{{ $service->service_number }}</span> on hold. The status will immediately update on the admin panel.
                </p>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reason for Hold</label>
                    <textarea id="holdReason" rows="3" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-800 focus:outline-none focus:bg-white focus:border-amber-500" placeholder="e.g. Waiting for specialized replacement filter cartridge..."></textarea>
                </div>

                <div class="flex flex-wrap gap-1.5 pt-1">
                    <button type="button" onclick="$('#holdReason').val('Awaiting spare parts from warehouse')" class="text-[10px] px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700">Spare Parts Awaited</button>
                    <button type="button" onclick="$('#holdReason').val('Customer not at premise / Requested reschedule')" class="text-[10px] px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700">Customer Unavailable</button>
                    <button type="button" onclick="$('#holdReason').val('Requires senior engineer inspection')" class="text-[10px] px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700">Senior Inspection</button>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeHoldModal()" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white transition flex items-center gap-1.5 shadow-2xs">
                        <i data-lucide="pause-circle" class="w-3.5 h-3.5"></i>
                        <span>Confirm Hold</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
