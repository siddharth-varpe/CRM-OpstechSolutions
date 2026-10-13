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
        <a href="{{ route('tech_portal.dashboard') }}?tab=tasks" class="flex items-center gap-2 text-slate-300 hover:text-white transition">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
            <span class="text-xs font-semibold">Back to Tasks</span>
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
                $addrStr = "{$addr->address_line1}, {$addr->city} {$addr->pincode}";
            }
        @endphp
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-sm space-y-3">
            <div class="flex justify-between items-start gap-2">
                <div>
                    <h1 class="text-base font-bold text-slate-900">{{ $service->customer->name ?? 'Direct Customer' }}</h1>
                    <div class="text-xs text-sky-700 font-medium mt-0.5">{{ $service->asset->custom_name ?? $service->asset->product->name ?? 'Equipment / Machine' }}</div>
                    <div class="text-[11px] text-slate-400 font-mono">S/N: {{ $service->asset->serial_number ?? 'N/A' }}</div>
                </div>
                @if($addrStr)
                    <a href="https://www.google.com/maps/dir/?api=1&destination={{ urlencode($addrStr) }}" target="_blank"
                       class="p-2 rounded-xl bg-sky-50 text-sky-700 border border-sky-200/80 hover:bg-sky-100 flex items-center gap-1.5 text-xs font-semibold transition">
                        <i data-lucide="navigation" class="w-3.5 h-3.5"></i>
                        <span>Maps</span>
                    </a>
                @endif
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

        <!-- Live GPS Field Navigation Action Banner -->
        <div class="bg-sky-50 border border-sky-200/90 rounded-2xl p-4 shadow-sm flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center shrink-0">
                    <i data-lucide="navigation-2" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 leading-tight">Field GPS Navigation</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Live turn-by-turn map for this customer</p>
                </div>
            </div>
            <a href="{{ route('tech_portal.navigation', $service->id) }}"
               class="px-3.5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold flex items-center gap-1.5 shadow-sm transition shrink-0">
                <i data-lucide="map" class="w-4 h-4"></i>
                <span>Open Map</span>
            </a>
        </div>

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
    </script>
</body>
</html>
