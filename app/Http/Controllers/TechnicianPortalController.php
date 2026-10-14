<?php

namespace App\Http\Controllers;

use App\Models\Technician;
use App\Models\TechnicianPortalAccess;
use App\Models\TechnicianLocation;
use App\Models\Service;
use App\Models\JobCard;
use App\Models\InventoryItem;
use App\Models\AuditLog;
use App\Services\MapsTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TechnicianPortalController extends Controller
{
    private function getCurrentTechnician()
    {
        $techId = session('technician_id');
        if ($techId) {
            return Technician::find($techId);
        }
        if (auth()->check()) {
            if (auth()->user()->hasRole('TECHNICIAN')) {
                return Technician::where('user_id', auth()->id())->first() ?? Technician::first();
            }
            return Technician::first();
        }
        return null;
    }

    public function login()
    {
        $technicians = Technician::where('status', 'ACTIVE')->get();
        return view('technician_portal.login', compact('technicians'));
    }

    /**
     * Authenticate field technician using phone and Security PIN
     */
    public function authenticate(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'pin' => 'required|string',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);
        $cleanPhone = strlen($phone) >= 10 ? substr($phone, -10) : $phone;
        $technician = Technician::where('phone', 'like', "%{$cleanPhone}%")->first();

        if (!$technician) {
            return response()->json([
                'success' => false,
                'message' => 'Technician phone number not found in directory.',
            ], 422);
        }

        if ($technician->portalAccess && !$technician->portalAccess->portal_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Your portal access is disabled. Please contact administrator.',
            ], 403);
        }

        // Verify that the technician has an admin-assigned PIN configured
        if (!$technician->portalAccess || empty($technician->portalAccess->pin_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'No Security PIN has been assigned to your account yet. Please contact your administrator to set up your PIN.',
            ], 422);
        }

        $pin = trim($validated['pin']);

        // Check PIN strictly against the admin-assigned hash for this technician
        if (!Hash::check($pin, $technician->portalAccess->pin_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Security PIN. Please enter your correct PIN or contact your administrator.',
            ], 422);
        }

        // Update last login timestamp
        $technician->portalAccess->update(['last_login_at' => now()]);

        // Establish session
        session(['technician_id' => $technician->id]);

        return response()->json([
            'success' => true,
            'message' => "Welcome, {$technician->full_name}!",
            'redirect' => route('tech_portal.dashboard'),
        ]);
    }

    public function logout()
    {
        session()->forget('technician_id');
        return redirect()->route('tech_portal.login');
    }

    public function dashboard(Request $request)
    {
        $technician = $this->getCurrentTechnician();
        if (!$technician) {
            return redirect()->route('tech_portal.login');
        }

        $assignedServices = Service::where('technician_id', $technician->id)
            ->with(['customer.addresses', 'asset.product', 'jobCard', 'warranty'])
            ->whereIn('status', ['ASSIGNED', 'IN_PROGRESS', 'SCHEDULED', 'ON_HOLD'])
            ->orderBy('scheduled_date', 'asc')
            ->get();

        $completedServices = Service::where('technician_id', $technician->id)
            ->with(['customer.addresses', 'asset.product', 'jobCard', 'warranty'])
            ->where('status', 'COMPLETED')
            ->orderBy('completed_at', 'desc')
            ->take(20)
            ->get();

        $completedToday = Service::where('technician_id', $technician->id)
            ->where('status', 'COMPLETED')
            ->whereDate('completed_at', now()->today())
            ->count();

        $spares = InventoryItem::where('status', 'ACTIVE')->orderBy('name')->get();
        $allTechnicians = Technician::where('status', 'ACTIVE')->get();

        // Active service for navigation / execution tabs
        $selectedServiceId = $request->query('service_id');
        $activeService = null;
        if ($selectedServiceId) {
            $activeService = Service::with(['customer.addresses', 'asset.product', 'jobCard', 'warranty'])->find($selectedServiceId);
        }
        if (!$activeService) {
            $activeService = $assignedServices->where('status', 'IN_PROGRESS')->first() ?? $assignedServices->first();
        }

        $activeTab = $request->query('tab', 'assigned');
        if (in_array($activeTab, ['tasks', 'execute'])) {
            $activeTab = 'assigned';
        }

        return view('technician_portal.dashboard', compact(
            'technician',
            'assignedServices',
            'completedServices',
            'completedToday',
            'spares',
            'allTechnicians',
            'activeService',
            'activeTab'
        ));
    }

    public function serviceDetail($id)
    {
        $technician = $this->getCurrentTechnician();
        $service = Service::with(['customer.addresses', 'asset.product', 'jobCard', 'warranty'])
            ->findOrFail($id);

        $spares = InventoryItem::where('status', 'ACTIVE')->orderBy('name')->get();

        return view('technician_portal.service_detail', compact('technician', 'service', 'spares'));
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:IN_PROGRESS,ON_HOLD,COMPLETED,SCHEDULED',
            'notes' => 'nullable|string',
            'reason' => 'nullable|string',
        ]);

        $service = Service::findOrFail($id);
        $note = $validated['reason'] ?? $validated['notes'] ?? null;

        $serviceData = ['status' => $validated['status']];
        if ($note) {
            $timestamp = now()->format('d M Y, h:i A');
            $statusLabel = $validated['status'] === 'ON_HOLD' ? 'ON HOLD' : $validated['status'];
            $serviceData['internal_notes'] = trim(($service->internal_notes ? $service->internal_notes . "\n" : '') . "[{$timestamp} - {$statusLabel}]: " . $note);
        }
        $service->update($serviceData);

        if ($service->jobCard) {
            $cardData = ['status' => $validated['status']];
            if ($note) {
                $timestamp = now()->format('d M Y, h:i A');
                $statusLabel = $validated['status'] === 'ON_HOLD' ? 'ON HOLD' : $validated['status'];
                $cardData['technician_notes'] = trim(($service->jobCard->technician_notes ? $service->jobCard->technician_notes . "\n" : '') . "[{$timestamp} - {$statusLabel}]: " . $note);
            }
            $service->jobCard->update($cardData);
        }

        $displayStatus = $validated['status'] === 'ON_HOLD' ? 'On Hold' : ($validated['status'] === 'IN_PROGRESS' ? 'In Progress' : $validated['status']);

        return response()->json([
            'success' => true,
            'message' => "Job status updated to {$displayStatus}.",
            'status' => $validated['status'],
        ]);
    }

    public function completeService(Request $request, $id)
    {
        if (!$request->has('diagnosis')) {
            $request->merge(['diagnosis' => $request->input('work_notes', 'Periodic Preventive Maintenance & Filter Inspection')]);
        }
        if (!$request->has('work_performed')) {
            $request->merge(['work_performed' => $request->input('work_notes', 'Filter replacement, membrane flushing & sanitization')]);
        }

        $validated = $request->validate([
            'diagnosis' => 'required|string',
            'work_performed' => 'required|string',
            'inlet_tds' => 'nullable|numeric',
            'outlet_tds' => 'nullable|numeric',
            'parts_replaced' => 'nullable|array',
            'labor_charges' => 'nullable|numeric|min:0',
            'parts_charges' => 'nullable|numeric|min:0',
            'technician_notes' => 'nullable|string',
            'customer_remarks' => 'nullable|string',
            'signature_data' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $id) {
            $service = Service::with(['jobCard', 'asset'])->findOrFail($id);

            $labor = (float)($validated['labor_charges'] ?? 0);
            $parts = (float)($validated['parts_charges'] ?? 0);
            $total = $labor + $parts;

            $service->update([
                'status' => 'COMPLETED',
                'completed_at' => now(),
            ]);

            if ($service->jobCard) {
                $service->jobCard->update([
                    'diagnosis' => $validated['diagnosis'] . (!empty($validated['inlet_tds']) ? " [Inlet TDS: {$validated['inlet_tds']}, Outlet TDS: {$validated['outlet_tds']}]" : ''),
                    'work_performed' => $validated['work_performed'],
                    'parts_replaced' => !empty($validated['parts_replaced']) ? json_encode($validated['parts_replaced']) : null,
                    'labor_charges' => $labor,
                    'parts_charges' => $parts,
                    'total_charges' => $total,
                    'technician_notes' => $validated['technician_notes'] ?? null,
                    'customer_remarks' => $validated['customer_remarks'] ?? null,
                    'completed_at' => now(),
                    'status' => 'COMPLETED',
                ]);
            }

            if ($service->asset) {
                $service->asset->update([
                    'last_service_date' => now(),
                    'next_service_due_date' => now()->addMonths(3),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Job Card completed with customer signature.',
                'redirect' => route('tech_portal.dashboard'),
            ]);
        });
    }

    /**
     * Mobile Turn-by-Turn GPS Navigation Screen for Field Technician
     */
    public function navigationScreen($id, MapsTrackingService $mapsTrackingService)
    {
        $tech = $this->getCurrentTechnician();
        if (!$tech) {
            return redirect()->route('tech_portal.login');
        }

        $service = Service::with(['customer.addresses', 'asset.product', 'jobCard'])->findOrFail($id);

        // If live coordinates provided in request, update technician's current position immediately
        if (request()->filled('lat') && request()->filled('lng')) {
            $currentLat = (float) request()->input('lat');
            $currentLng = (float) request()->input('lng');
            $accuracy = (float) request()->input('accuracy', 10);
            $speed = (float) request()->input('speed', 0);
            $heading = (float) request()->input('heading', 0);

            if ($currentLat >= -90 && $currentLat <= 90 && $currentLng >= -180 && $currentLng <= 180) {
                $mapsTrackingService->updateLocation((string) $tech->id, $tech->full_name, $tech->phone, [
                    'latitude' => $currentLat,
                    'longitude' => $currentLng,
                    'accuracy' => $accuracy,
                    'speed' => $speed,
                    'heading' => $heading,
                ]);
            }
        } elseif ($tech->current_latitude === null || $tech->current_longitude === null) {
            // Initialize with default base coordinates if technician has never pinged GPS yet
            $mapsTrackingService->updateLocation((string) $tech->id, $tech->full_name, $tech->phone, [
                'latitude' => 18.5204,
                'longitude' => 73.8567,
                'accuracy' => 10,
                'speed' => 0,
                'heading' => 0,
            ]);
        }

        // Fetch customer coordinates and address
        $address = $service->customer ? ($service->customer->primaryAddress ?? $service->customer->addresses->first()) : null;
        $destLat = ($address && $address->latitude !== null && (float) $address->latitude != 0.0 && (float) $address->latitude >= -90 && (float) $address->latitude <= 90) ? (float) $address->latitude : null;
        $destLng = ($address && $address->longitude !== null && (float) $address->longitude != 0.0 && (float) $address->longitude >= -180 && (float) $address->longitude <= 180) ? (float) $address->longitude : null;
        $destAddress = ($address && !empty(trim($address->full_address)))
            ? trim($address->full_address)
            : null;

        $hasCoords = ($destLat !== null && $destLng !== null);
        $hasAddress = !empty($destAddress);

        if (!$hasCoords && !$hasAddress) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'CUSTOMER_COORDINATES_MISSING',
                        'message' => 'Customer premise destination coordinates and address are not configured. Please set the customer location pin in Customer Details.',
                    ],
                ], 400);
            }
            return redirect()->back()->with('error', 'Customer premise destination coordinates and address are not configured.');
        }

        $googleMapsUrl = $hasCoords
            ? "https://www.google.com/maps/dir/?api=1&destination={$destLat},{$destLng}&travelmode=driving"
            : "https://www.google.com/maps/dir/?api=1&destination=" . urlencode($destAddress) . "&travelmode=driving";

        if ($hasCoords || $hasAddress) {
            $mapsTrackingService->setActiveDestination((string) $tech->id, [
                'serviceId' => (string) $service->id,
                'serviceNumber' => $service->service_number,
                'jobCardId' => $service->jobCard ? (string) $service->jobCard->id : null,
                'jobCardNumber' => $service->jobCard ? $service->jobCard->job_card_number : null,
                'customerId' => (string) $service->customer->id,
                'customerName' => $service->customer->full_name,
                'customerPhone' => $service->customer->phone,
                'address' => $destAddress,
                'latitude' => $destLat,
                'longitude' => $destLng,
                'scheduledDate' => $service->scheduled_date,
                'scheduledTimeSlot' => $service->scheduled_time_slot,
            ]);
        }

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => $googleMapsUrl,
                'googleMapsUrl' => $googleMapsUrl,
            ]);
        }

        return redirect()->away($googleMapsUrl);
    }

    /**
     * Start active navigation for a service
     */
    public function startNavigation(Request $request, $serviceId, MapsTrackingService $mapsTrackingService)
    {
        $tech = $this->getCurrentTechnician();
        if (!$tech) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Technician session expired or unauthorized',
                ],
            ], 401);
        }

        $service = Service::with(['customer.addresses', 'jobCard'])->findOrFail($serviceId);

        // Authorization check: service must be assigned to this technician
        if ($service->technician_id !== $tech->id && (!auth()->check() || !auth()->user()->hasRole('ADMIN'))) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Service is not assigned to the authenticated technician',
                ],
            ], 403);
        }

        $address = $service->customer ? ($service->customer->primaryAddress ?? $service->customer->addresses->first()) : null;
        $destLat = ($address && $address->latitude !== null && (float) $address->latitude != 0.0 && (float) $address->latitude >= -90 && (float) $address->latitude <= 90) ? (float) $address->latitude : null;
        $destLng = ($address && $address->longitude !== null && (float) $address->longitude != 0.0 && (float) $address->longitude >= -180 && (float) $address->longitude <= 180) ? (float) $address->longitude : null;
        $destAddress = ($address && !empty(trim($address->full_address)))
            ? trim($address->full_address)
            : null;

        $hasCoords = ($destLat !== null && $destLng !== null);
        $hasAddress = !empty($destAddress);

        if (!$hasCoords && !$hasAddress) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CUSTOMER_COORDINATES_MISSING',
                    'message' => 'Customer premise destination coordinates and address are not configured. Please set the customer location pin in Customer Details before starting navigation.',
                ],
            ], 400);
        }

        // Concurrency check: ONE technician = ONE active destination
        $existing = $mapsTrackingService->getActiveDestination((string) $tech->id);
        if ($existing && $existing['serviceId'] !== (string) $serviceId && !$request->boolean('confirmSwitch')) {
            return response()->json([
                'success' => false,
                'conflict' => true,
                'activeServiceId' => $existing['serviceId'],
                'activeServiceNumber' => $existing['serviceNumber'] ?? 'Previous Service',
                'message' => "You are currently navigating to Service {$existing['serviceNumber']}. Confirm switching active destination?",
                'error' => [
                    'code' => 'ACTIVE_NAVIGATION_EXISTS',
                    'message' => "Technician already has an active navigation destination: " . ($existing['customerName'] ?? 'Another Destination'),
                    'activeDestination' => $existing,
                ],
            ], 409);
        }

        $destinationRecord = $mapsTrackingService->setActiveDestination((string) $tech->id, [
            'serviceId' => (string) $service->id,
            'serviceNumber' => $service->service_number,
            'jobCardId' => $service->jobCard ? (string) $service->jobCard->id : null,
            'jobCardNumber' => $service->jobCard ? $service->jobCard->job_card_number : null,
            'customerId' => (string) $service->customer->id,
            'customerName' => $service->customer->full_name,
            'customerPhone' => $service->customer->phone,
            'address' => $destAddress,
            'latitude' => $destLat,
            'longitude' => $destLng,
            'scheduledDate' => $service->scheduled_date,
            'scheduledTimeSlot' => $service->scheduled_time_slot,
        ]);

        $googleMapsUrl = $hasCoords
            ? "https://www.google.com/maps/dir/?api=1&destination={$destLat},{$destLng}&travelmode=driving"
            : "https://www.google.com/maps/dir/?api=1&destination=" . urlencode($destAddress) . "&travelmode=driving";

        return response()->json([
            'success' => true,
            'trackingStatus' => 'ON_THE_WAY',
            'googleMapsUrl' => $googleMapsUrl,
            'destination' => $destinationRecord,
            'navigationUrl' => route('tech_portal.navigation', $service->id),
            'data' => [
                'trackingStatus' => 'ON_THE_WAY',
                'googleMapsUrl' => $googleMapsUrl,
                'destination' => $destinationRecord,
                'navigationUrl' => route('tech_portal.navigation', $service->id),
            ],
        ]);
    }

    /**
     * Start active navigation via POST body (API compatible)
     */
    public function startNavigationViaPost(Request $request, MapsTrackingService $mapsTrackingService)
    {
        $serviceId = $request->input('serviceId') ?? $request->input('service_id');
        if (!$serviceId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Service ID is required',
                ],
            ], 422);
        }

        return $this->startNavigation($request, $serviceId, $mapsTrackingService);
    }

    /**
     * Save authoritative coordinates resolved by Google Maps client for the customer premise
     */
    public function saveDestinationCoordinates(Request $request, $serviceId, MapsTrackingService $mapsTrackingService)
    {
        $tech = $this->getCurrentTechnician();
        if (!$tech && (!auth()->check() || !auth()->user()->hasRole('ADMIN'))) {
            return response()->json(['success' => false, 'error' => 'UNAUTHORIZED'], 401);
        }

        $service = Service::with(['customer.addresses'])->findOrFail($serviceId);
        $lat = $request->input('latitude') !== null ? (float) $request->input('latitude') : null;
        $lng = $request->input('longitude') !== null ? (float) $request->input('longitude') : null;

        if ($lat !== null && $lng !== null && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
            $address = $service->customer ? ($service->customer->primaryAddress ?? $service->customer->addresses->first()) : null;
            if ($address) {
                $address->update([
                    'latitude' => $lat,
                    'longitude' => $lng,
                ]);
            }

            // Also update active destination in temporary cache
            $techId = $tech ? (string) $tech->id : ($service->technician_id ? (string) $service->technician_id : null);
            if ($techId) {
                $activeDest = $mapsTrackingService->getActiveDestination($techId);
                if ($activeDest && ($activeDest['serviceId'] ?? null) === (string) $service->id) {
                    $activeDest['latitude'] = $lat;
                    $activeDest['longitude'] = $lng;
                    $mapsTrackingService->setActiveDestination($techId, $activeDest);
                }
            }

            return response()->json(['success' => true, 'latitude' => $lat, 'longitude' => $lng]);
        }

        return response()->json(['success' => false, 'error' => 'INVALID_COORDINATES'], 422);
    }

    /**
     * Stop active navigation / Check in as arrived
     */
    public function stopNavigation(Request $request, MapsTrackingService $mapsTrackingService)
    {
        $tech = $this->getCurrentTechnician();
        if ($tech) {
            $status = $request->input('status', 'ARRIVED'); // ARRIVED or IDLE
            $mapsTrackingService->clearTracking((string) $tech->id);

            if ($status === 'ARRIVED') {
                $tech->update(['tracking_status' => 'ARRIVED']);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Tracking terminated and temporary location state removed.',
        ]);
    }

    /**
     * Real-time GPS Location Ping from Technician Device
     */
    public function updateGps(Request $request, MapsTrackingService $mapsTrackingService)
    {
        $tech = $this->getCurrentTechnician();

        // Prevent unauthorized technician spoofing
        if ($request->filled('technician_id') && $tech && (string) $request->input('technician_id') !== (string) $tech->id) {
            if (!auth()->check() || !auth()->user()->hasRole('ADMIN')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED_SPOOFING_REJECTED',
                        'message' => 'Technicians cannot publish location updates for another technician.',
                    ],
                ], 403);
            }
        }

        if (!$tech && $request->filled('technician_id')) {
            $tech = Technician::find($request->input('technician_id'));
        }

        if (!$tech) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Technician session is required to publish GPS coordinates',
                ],
            ], 401);
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'speed' => 'nullable|numeric',
            'heading' => 'nullable|numeric',
            'timestamp' => 'nullable|numeric',
        ]);

        $record = $mapsTrackingService->updateLocation(
            (string) $tech->id,
            $tech->full_name,
            $tech->phone,
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $record,
            'technicianId' => $tech->id,
            'statusFreshness' => $record['statusFreshness'] ?? 'LIVE',
            'distanceKm' => $record['distanceKm'] ?? null,
            'etaMinutes' => $record['etaMinutes'] ?? null,
        ]);
    }

    /**
     * GET /api/v1/maps/technician/active
     */
    public function activeTracking(Request $request, MapsTrackingService $mapsTrackingService)
    {
        $tech = $this->getCurrentTechnician();
        if (!$tech) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Technician session required',
                ],
            ], 401);
        }

        $record = $mapsTrackingService->getLocation((string) $tech->id);

        return response()->json([
            'success' => true,
            'data' => $record,
        ]);
    }

    /**
     * Legacy API Compatibility: POST /maps/technician/navigate
     */
    public function apiStartNavigate(Request $request, MapsTrackingService $mapsTrackingService)
    {
        return $this->startNavigationViaPost($request, $mapsTrackingService);
    }

    /**
     * Legacy API Compatibility: POST /maps/technician/location
     */
    public function apiLocationPing(Request $request, MapsTrackingService $mapsTrackingService)
    {
        return $this->updateGps($request, $mapsTrackingService);
    }
}
