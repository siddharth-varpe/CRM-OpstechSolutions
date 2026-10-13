<?php

namespace App\Http\Controllers;

use App\Models\Technician;
use App\Models\TechnicianLocation;
use App\Models\TechnicianPortalAccess;
use App\Models\Service;
use App\Models\JobCard;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TechnicianController extends Controller
{
    /**
     * Technicians Directory & Workforce Overview
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'ALL');
        $search = trim((string) $request->input('search', ''));
        $viewMode = $request->input('view', 'roster'); // 'roster' or 'map'

        $query = Technician::with(['portalAccess', 'activeService.customer', 'services' => function ($q) {
            $q->latest();
        }]);

        if ($status !== 'ALL') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $technicians = $query->paginate(15)->withQueryString();

        // Attach active & completed job counts
        foreach ($technicians as $tech) {
            $tech->activeJobsCount = Service::where('technician_id', $tech->id)
                ->whereIn('status', ['ASSIGNED', 'IN_PROGRESS', 'SCHEDULED'])
                ->count();
            $tech->completedJobsCount = Service::where('technician_id', $tech->id)
                ->where('status', 'COMPLETED')
                ->count();
        }

        // KPIs
        $totalCount = Technician::count();
        $activeCount = Technician::where('status', 'ACTIVE')->count();
        $onLeaveCount = Technician::where('status', 'ON_LEAVE')->count();
        $inactiveCount = Technician::where('status', 'INACTIVE')->count();
        $navigatingCount = Technician::where('tracking_status', 'ON_THE_WAY')->count();

        $kpis = [
            'totalTechnicians' => $totalCount,
            'activeTechnicians' => $activeCount,
            'onLeave' => $onLeaveCount,
            'inactiveTechnicians' => $inactiveCount,
            'navigatingCount' => $navigatingCount,
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $technicians->items(),
                'kpis' => $kpis,
            ]);
        }

        if ($viewMode === 'map') {
            return view('technicians.map', compact('technicians', 'kpis', 'status', 'search', 'viewMode'));
        }

        return view('technicians.index', compact('technicians', 'kpis', 'status', 'search', 'viewMode'));
    }

    /**
     * Dedicated Live Field Map View
     */
    public function map(Request $request)
    {
        $request->merge(['view' => 'map']);
        return $this->index($request);
    }

    /**
     * Technician 360° Profile View
     */
    public function show($id)
    {
        $technician = Technician::with([
            'portalAccess',
            'services.customer.addresses',
            'services.asset.product',
            'services.jobCard',
            'activeService.customer.addresses',
            'activeService.asset.product',
            'activeService.jobCard',
            'jobCards.service.customer',
            'jobCards.service.asset.product',
            'locations' => function ($q) {
                $q->latest('recorded_at')->take(30);
            }
        ])->findOrFail($id);

        $assignedServices = $technician->services()
            ->with(['customer.addresses', 'asset.product', 'jobCard'])
            ->orderBy('created_at', 'desc')
            ->get();

        $completedServices = $assignedServices->where('status', 'COMPLETED');
        $currentWork = $assignedServices->whereIn('status', ['IN_PROGRESS', 'ASSIGNED', 'SCHEDULED']);
        $upcomingWork = $assignedServices->where('status', 'SCHEDULED');
        $onHoldWork = $assignedServices->where('status', 'ON_HOLD');

        $jobCards = JobCard::where('technician_id', $technician->id)
            ->with(['service.customer.addresses', 'service.asset.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalAssigned = $assignedServices->count();
        $completedCount = $completedServices->count();
        $inProgressCount = $assignedServices->where('status', 'IN_PROGRESS')->count();
        $scheduledCount = $upcomingWork->count();
        $onHoldCount = $onHoldWork->count();
        $completionRate = $totalAssigned > 0 ? round(($completedCount / $totalAssigned) * 100, 1) : 0;

        $availability = 'AVAILABLE';
        if ($technician->status === 'ON_LEAVE') {
            $availability = 'ON_LEAVE';
        } elseif ($technician->status !== 'ACTIVE') {
            $availability = 'OFF_DUTY';
        } elseif ($inProgressCount > 0 || $technician->tracking_status === 'ON_THE_WAY') {
            $availability = 'BUSY';
        }

        $totalLaborBilled = (float) $jobCards->sum('labor_charges');
        $totalPartsBilled = (float) $jobCards->sum('parts_charges');
        $totalRevenue = $totalLaborBilled + $totalPartsBilled;

        // Distinct customers serviced
        $customersHandled = [];
        $custMap = [];
        foreach ($assignedServices as $srv) {
            if ($srv->customer && !isset($custMap[$srv->customer->id])) {
                $custMap[$srv->customer->id] = true;
                $visitCount = $assignedServices->where('customer_id', $srv->customer->id)->count();
                $customersHandled[] = [
                    'customer' => $srv->customer,
                    'visitCount' => $visitCount,
                    'lastVisit' => $srv->created_at,
                ];
            }
        }

        // Distinct machines/assets handled
        $assetsHandled = [];
        $assetMap = [];
        foreach ($assignedServices as $srv) {
            if ($srv->asset && !isset($assetMap[$srv->asset->id])) {
                $assetMap[$srv->asset->id] = true;
                $serviceCount = $assignedServices->where('customer_asset_id', $srv->asset->id)->count();
                $assetsHandled[] = [
                    'asset' => $srv->asset,
                    'customer' => $srv->customer,
                    'serviceCount' => $serviceCount,
                    'lastService' => $srv->created_at,
                ];
            }
        }

        // Parts consumed across job cards
        $partsUsed = [];
        foreach ($jobCards as $jc) {
            if (!empty($jc->parts_replaced)) {
                $parts = is_string($jc->parts_replaced) ? json_decode($jc->parts_replaced, true) : $jc->parts_replaced;
                if (is_array($parts)) {
                    foreach ($parts as $p) {
                        $partName = is_array($p) ? ($p['name'] ?? $p['item_name'] ?? 'Spare Part') : (string) $p;
                        $partQty = is_array($p) ? (int) ($p['qty'] ?? $p['quantity'] ?? 1) : 1;
                        $partCost = is_array($p) ? (float) ($p['cost'] ?? $p['price'] ?? 0) : 0;
                        if (!isset($partsUsed[$partName])) {
                            $partsUsed[$partName] = [
                                'name' => $partName,
                                'quantity' => 0,
                                'totalCost' => 0,
                                'jobsCount' => 0,
                            ];
                        }
                        $partsUsed[$partName]['quantity'] += $partQty;
                        $partsUsed[$partName]['totalCost'] += $partCost * $partQty;
                        $partsUsed[$partName]['jobsCount'] += 1;
                    }
                }
            }
        }

        // Payments associated with services / customers
        $customerIds = $assignedServices->pluck('customer_id')->filter()->unique();
        $payments = \App\Models\Payment::whereHas('invoice', function ($q) use ($customerIds) {
            $q->whereIn('customer_id', $customerIds);
        })->with(['customer', 'invoice'])->latest()->take(25)->get();

        $totalCollections = (float) $payments->where('status', 'SUCCESS')->sum('amount');

        $workSummary = [
            'totalAssigned' => $totalAssigned,
            'completedCount' => $completedCount,
            'inProgressCount' => $inProgressCount,
            'scheduledCount' => $scheduledCount,
            'onHoldCount' => $onHoldCount,
            'completionRate' => $completionRate,
            'availability' => $availability,
        ];

        $financialSummary = [
            'totalLaborBilled' => $totalLaborBilled,
            'totalPartsBilled' => $totalPartsBilled,
            'totalRevenue' => $totalRevenue,
            'totalCollections' => $totalCollections,
        ];

        return view('technicians.show', compact(
            'technician',
            'workSummary',
            'financialSummary',
            'assignedServices',
            'completedServices',
            'currentWork',
            'upcomingWork',
            'onHoldWork',
            'jobCards',
            'customersHandled',
            'assetsHandled',
            'partsUsed',
            'payments'
        ));
    }

    /**
     * Reset or generate technician PIN
     */
    public function resetPin(Request $request, $id)
    {
        $validated = $request->validate([
            'pin' => 'required|string|min:4|max:6',
        ]);

        $access = TechnicianPortalAccess::firstOrCreate(
            ['technician_id' => $id],
            ['portal_enabled' => true]
        );

        $access->update([
            'pin_hash' => Hash::make($validated['pin']),
            'portal_enabled' => true,
        ]);

        AuditLog::log('UPDATE', 'TECHNICIAN_PIN_RESET', $id, null, ['pin' => '******']);

        return response()->json([
            'success' => true,
            'message' => 'Technician Portal PIN updated successfully.',
        ]);
    }

    /**
     * API 360 Profile Compatibility: GET /api/v1/technicians/:id/360
     */
    public function api360Profile($id)
    {
        $technician = Technician::with(['portalAccess', 'activeService.customer', 'jobCards'])->findOrFail($id);
        $assignedServices = Service::where('technician_id', $id)->with(['customer.addresses', 'asset.product', 'jobCard'])->get();
        $jobCards = JobCard::where('technician_id', $id)->with(['service.customer'])->get();

        return response()->json([
            'success' => true,
            'data' => [
                'technician' => [
                    'id' => $technician->id,
                    'fullName' => $technician->full_name,
                    'phone' => $technician->phone,
                    'email' => $technician->email,
                    'status' => $technician->status,
                    'skills' => $technician->skills,
                    'address' => $technician->address,
                    'emergencyContact' => $technician->emergency_contact,
                    'portalEnabled' => (bool) ($technician->portalAccess && $technician->portalAccess->portal_enabled),
                    'createdAt' => $technician->created_at,
                    'updatedAt' => $technician->updated_at,
                ],
                'workSummary' => [
                    'totalAssigned' => $assignedServices->count(),
                    'completedCount' => $assignedServices->where('status', 'COMPLETED')->count(),
                    'inProgressCount' => $assignedServices->where('status', 'IN_PROGRESS')->count(),
                    'scheduledCount' => $assignedServices->where('status', 'SCHEDULED')->count(),
                    'onHoldCount' => $assignedServices->where('status', 'ON_HOLD')->count(),
                ],
                'financialSummary' => [
                    'totalLaborBilled' => (float) $jobCards->sum('labor_charges'),
                    'totalPartsBilled' => (float) $jobCards->sum('parts_charges'),
                    'totalRevenue' => (float) ($jobCards->sum('labor_charges') + $jobCards->sum('parts_charges')),
                ],
                'assignedServices' => $assignedServices,
                'jobCards' => $jobCards,
            ],
        ]);
    }

    /**
     * GET /maps/admin/live-technicians
     * Real-time GPS snapshot of all technicians, locations, and active routes
     */
    public function liveTechnicians(Request $request)
    {
        $technicians = Technician::with(['activeService.customer.addresses', 'activeService.jobCard'])
            ->get();

        $result = [];

        foreach ($technicians as $t) {
            $hasCoords = ($t->current_latitude !== null && $t->current_longitude !== null);
            $hasDest = ($t->destination_latitude !== null && $t->destination_longitude !== null);

            // Fetch travelled breadcrumbs (last 15 recorded coordinates)
            $travelledPath = [];
            if ($hasCoords) {
                $travelled = TechnicianLocation::where('technician_id', $t->id)
                    ->where('recorded_at', '>=', now()->subHours(8))
                    ->orderBy('recorded_at', 'asc')
                    ->take(30)
                    ->get(['latitude', 'longitude', 'recorded_at']);

                foreach ($travelled as $p) {
                    $travelledPath[] = [
                        'latitude' => (float) $p->latitude,
                        'longitude' => (float) $p->longitude,
                        'timestamp' => $p->recorded_at ? $p->recorded_at->timestamp * 1000 : null,
                    ];
                }
            }

            $service = $t->activeService;
            $customer = $service ? $service->customer : null;
            $jobCard = $service ? $service->jobCard : null;

            $result[] = [
                'technicianId' => (string) $t->id,
                'technicianName' => $t->full_name,
                'technicianPhone' => $t->phone,
                'status' => $t->status,
                'trackingStatus' => $t->tracking_status ?? 'UNTRACKED',
                'statusFreshness' => $t->statusFreshness,
                'secondsAgo' => $t->secondsAgo,
                'latitude' => $hasCoords ? (float) $t->current_latitude : null,
                'longitude' => $hasCoords ? (float) $t->current_longitude : null,
                'accuracy' => (float) ($t->current_accuracy ?? 10),
                'heading' => (float) ($t->current_heading ?? 0),
                'speed' => (float) ($t->current_speed ?? 0),
                'serviceId' => $service ? (string) $service->id : null,
                'serviceNumber' => $service ? $service->service_number : null,
                'jobCardId' => $jobCard ? (string) $jobCard->id : null,
                'jobCardNumber' => $jobCard ? $jobCard->job_card_number : null,
                'customerName' => $customer ? $customer->full_name : ($service->customer_notes ?? 'Client Location'),
                'customerAddress' => $t->destination_address ?? ($customer && $customer->addresses->first() ? $customer->addresses->first()->full_address : 'Pune, Maharashtra'),
                'customerLatitude' => $hasDest ? (float) $t->destination_latitude : ($customer && $customer->addresses->first() && $customer->addresses->first()->latitude ? (float) $customer->addresses->first()->latitude : null),
                'customerLongitude' => $hasDest ? (float) $t->destination_longitude : ($customer && $customer->addresses->first() && $customer->addresses->first()->longitude ? (float) $customer->addresses->first()->longitude : null),
                'scheduledTime' => $service ? $service->scheduled_time_slot : null,
                'distanceKm' => $t->distance_km !== null ? (float) $t->distance_km : ($hasCoords && $hasDest ? self::calculateDistanceKm($t->current_latitude, $t->current_longitude, $t->destination_latitude, $t->destination_longitude) : null),
                'etaMinutes' => $t->eta_minutes !== null ? (int) $t->eta_minutes : ($hasCoords && $hasDest ? self::estimateEtaMinutes(self::calculateDistanceKm($t->current_latitude, $t->current_longitude, $t->destination_latitude, $t->destination_longitude)) : null),
                'routeStatus' => $hasCoords && $hasDest ? 'SUCCESS' : 'UNAVAILABLE',
                'travelledPath' => $travelledPath,
                'lastUpdated' => $t->last_ping_at ? $t->last_ping_at->toIso8601String() : now()->toIso8601String(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * GET /maps/admin/config
     */
    public function mapsConfig()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'apiKey' => config('services.google_maps.browser_key', env('GOOGLE_MAPS_BROWSER_API_KEY', '')),
                'mapId' => config('services.google_maps.map_id', env('GOOGLE_MAPS_MAP_ID', '')),
            ],
        ]);
    }

    /**
     * POST /maps/admin/recalculate-route
     */
    public function recalculateRoute(Request $request)
    {
        $techId = $request->input('technicianId') ?? $request->input('technician_id');
        if (!$techId) {
            return response()->json(['success' => false, 'message' => 'technicianId is required'], 422);
        }

        $tech = Technician::findOrFail($techId);

        if ($tech->current_latitude && $tech->current_longitude && $tech->destination_latitude && $tech->destination_longitude) {
            $distKm = self::calculateDistanceKm($tech->current_latitude, $tech->current_longitude, $tech->destination_latitude, $tech->destination_longitude);
            $etaMins = self::estimateEtaMinutes($distKm);

            $tech->update([
                'distance_km' => $distKm,
                'eta_minutes' => $etaMins,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'technicianId' => $tech->id,
                    'routeStatus' => 'SUCCESS',
                    'distanceKm' => $distKm,
                    'etaMinutes' => $etaMins,
                    'distanceText' => "{$distKm} km",
                    'etaText' => "{$etaMins} mins",
                    'routeCalculatedAt' => now()->timestamp * 1000,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'technicianId' => $tech->id,
                'routeStatus' => 'UNAVAILABLE',
                'distanceKm' => null,
                'etaMinutes' => null,
            ],
        ]);
    }

    /**
     * Store new technician
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:technicians,phone',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'skills' => 'nullable|array',
            'status' => 'required|in:ACTIVE,ON_LEAVE,INACTIVE',
            'emergency_contact' => 'nullable|string|max:20',
            'portal_pin' => 'nullable|string|min:4|max:6',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $tech = Technician::create([
                'full_name' => $validated['full_name'],
                'phone' => preg_replace('/[^0-9]/', '', $validated['phone']),
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'skills' => $validated['skills'] ?? ['Equipment Installation', 'Preventive Maintenance'],
                'status' => $validated['status'],
                'emergency_contact' => $validated['emergency_contact'] ?? null,
                'tracking_status' => 'IDLE',
            ]);

            // Create portal access
            TechnicianPortalAccess::create([
                'technician_id' => $tech->id,
                'portal_enabled' => true,
                'pin_hash' => !empty($validated['portal_pin']) ? Hash::make($validated['portal_pin']) : Hash::make('1234'),
            ]);

            AuditLog::log('CREATE', 'TECHNICIAN', $tech->id, null, ['name' => $tech->full_name]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Technician {$tech->full_name} created successfully.",
                    'data' => $tech,
                ]);
            }

            return redirect()->route('technicians.index')
                ->with('success', "Technician {$tech->full_name} added to workforce.");
        });
    }

    /**
     * Update technician
     */
    public function update(Request $request, $id)
    {
        $tech = Technician::findOrFail($id);

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:technicians,phone,' . $tech->id,
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'skills' => 'nullable|array',
            'status' => 'required|in:ACTIVE,ON_LEAVE,INACTIVE',
            'emergency_contact' => 'nullable|string|max:20',
        ]);

        $tech->update([
            'full_name' => $validated['full_name'],
            'phone' => preg_replace('/[^0-9]/', '', $validated['phone']),
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'skills' => $validated['skills'] ?? $tech->skills,
            'status' => $validated['status'],
            'emergency_contact' => $validated['emergency_contact'] ?? null,
        ]);

        AuditLog::log('UPDATE', 'TECHNICIAN', $tech->id, null, ['status' => $tech->status]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Technician profile updated.",
                'data' => $tech,
            ]);
        }

        return back()->with('success', "Technician profile updated.");
    }

    /**
     * Quick status update AJAX
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:ACTIVE,ON_LEAVE,INACTIVE',
        ]);

        $tech = Technician::findOrFail($id);
        $tech->update(['status' => $validated['status']]);

        AuditLog::log('UPDATE', 'TECHNICIAN_STATUS', $tech->id, null, ['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => "Status changed to {$validated['status']}.",
        ]);
    }

    /**
     * Portal access toggle
     */
    public function updatePortalAccess(Request $request, $id)
    {
        $validated = $request->validate([
            'portal_enabled' => 'required|boolean',
            'pin' => 'nullable|string|min:4',
        ]);

        $access = TechnicianPortalAccess::firstOrCreate(
            ['technician_id' => $id],
            ['portal_enabled' => true]
        );

        $updates = ['portal_enabled' => $validated['portal_enabled']];
        if (!empty($validated['pin'])) {
            $updates['pin_hash'] = Hash::make($validated['pin']);
        }

        $access->update($updates);

        return response()->json([
            'success' => true,
            'message' => $validated['portal_enabled'] ? "Technician portal access enabled." : "Technician portal access disabled.",
        ]);
    }

    /**
     * Delete technician
     */
    public function destroy($id)
    {
        $tech = Technician::findOrFail($id);

        $activeServices = Service::where('technician_id', $tech->id)
            ->whereIn('status', ['ASSIGNED', 'IN_PROGRESS', 'SCHEDULED'])
            ->count();

        if ($activeServices > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete technician with {$activeServices} active assigned job(s). Please reassign tickets first.",
            ], 422);
        }

        $tech->delete();

        return response()->json([
            'success' => true,
            'message' => "Technician {$tech->full_name} deleted successfully.",
        ]);
    }

    /**
     * Helper: Haversine distance in KM
     */
    public static function calculateDistanceKm($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Helper: Estimate driving ETA minutes based on urban city traffic (approx 22 km/h avg)
     */
    public static function estimateEtaMinutes(float $distanceKm): int
    {
        $avgSpeedKmPerHour = 22.0;
        $hours = $distanceKm / $avgSpeedKmPerHour;
        $minutes = (int) ceil($hours * 60) + 2; // buffer 2 mins
        return max(3, $minutes);
    }
}
