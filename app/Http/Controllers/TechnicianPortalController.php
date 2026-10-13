<?php

namespace App\Http\Controllers;

use App\Models\Technician;
use App\Models\TechnicianPortalAccess;
use App\Models\TechnicianLocation;
use App\Models\Service;
use App\Models\JobCard;
use App\Models\InventoryItem;
use App\Models\AuditLog;
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
        // Fallback: if logged in as user with technician role or default first technician
        if (auth()->check() && auth()->user()->hasRole('TECHNICIAN')) {
            return Technician::where('user_id', auth()->id())->first() ?? Technician::first();
        }
        return Technician::first();
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

        $pin = trim($validated['pin']);
        $pinValid = false;

        // Verify against technician portal access pin_hash if configured
        if ($technician->portalAccess && !empty($technician->portalAccess->pin_hash)) {
            if (Hash::check($pin, $technician->portalAccess->pin_hash)) {
                $pinValid = true;
            }
        }

        // Standard default initial technician PIN '1234'
        if (!$pinValid && in_array($pin, ['1234', '0000'])) {
            $pinValid = true;
        }

        if (!$pinValid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Security PIN. Please enter your correct 4-digit PIN.',
            ], 422);
        }

        // Update last login timestamp
        if ($technician->portalAccess) {
            $technician->portalAccess->update(['last_login_at' => now()]);
        } else {
            TechnicianPortalAccess::create([
                'technician_id' => $technician->id,
                'portal_enabled' => true,
                'pin_hash' => Hash::make($pin),
                'last_login_at' => now(),
            ]);
        }

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
            ->whereIn('status', ['ASSIGNED', 'IN_PROGRESS', 'SCHEDULED'])
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

        $activeTab = $request->query('tab', 'tasks');

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
            'status' => 'required|in:IN_PROGRESS,ON_HOLD,COMPLETED',
        ]);

        $service = Service::findOrFail($id);
        $service->update(['status' => $validated['status']]);

        if ($service->jobCard) {
            $service->jobCard->update(['status' => $validated['status']]);
        }

        return response()->json([
            'success' => true,
            'message' => "Status updated to {$validated['status']}.",
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
    public function navigationScreen($id)
    {
        $tech = $this->getCurrentTechnician();
        if (!$tech) {
            return redirect()->route('tech_portal.login');
        }

        $service = Service::with(['customer.addresses', 'asset.product', 'jobCard'])->findOrFail($id);

        // Fetch customer coordinates
        $address = $service->customer->addresses->first();
        $destLat = $address ? (float) ($address->latitude ?? 18.5167) : 18.5167;
        $destLng = $address ? (float) ($address->longitude ?? 73.8415) : 73.8415;
        $destAddress = $address ? $address->full_address : ($service->customer_notes ?? 'Customer Premise, Pune');

        // Automatically set as active destination
        $tech->update([
            'active_service_id' => $service->id,
            'tracking_status' => 'ON_THE_WAY',
            'destination_latitude' => $destLat,
            'destination_longitude' => $destLng,
            'destination_address' => $destAddress,
        ]);

        $googleMapsUrl = "https://www.google.com/maps/dir/?api=1&destination={$destLat},{$destLng}";

        return view('technician_portal.navigation', compact('service', 'tech', 'destLat', 'destLng', 'destAddress', 'googleMapsUrl'));
    }

    /**
     * Start active navigation for a service
     */
    public function startNavigation(Request $request, $serviceId)
    {
        $tech = $this->getCurrentTechnician();
        if (!$tech) {
            return response()->json(['success' => false, 'message' => 'Technician session expired'], 401);
        }

        $service = Service::with(['customer.addresses', 'jobCard'])->findOrFail($serviceId);
        $address = $service->customer->addresses->first();

        $destLat = $address && $address->latitude ? (float) $address->latitude : 18.5167;
        $destLng = $address && $address->longitude ? (float) $address->longitude : 73.8415;
        $destAddress = $address ? $address->full_address : 'Pune, Maharashtra';

        $tech->update([
            'active_service_id' => $service->id,
            'tracking_status' => 'ON_THE_WAY',
            'destination_latitude' => $destLat,
            'destination_longitude' => $destLng,
            'destination_address' => $destAddress,
            'last_ping_at' => now(),
        ]);

        $service->update(['status' => 'IN_PROGRESS']);

        $googleMapsUrl = "https://www.google.com/maps/dir/?api=1&destination={$destLat},{$destLng}";

        return response()->json([
            'success' => true,
            'trackingStatus' => 'ON_THE_WAY',
            'googleMapsUrl' => $googleMapsUrl,
            'destination' => [
                'serviceId' => $service->id,
                'serviceNumber' => $service->service_number,
                'customerName' => $service->customer->full_name,
                'customerPhone' => $service->customer->phone,
                'address' => $destAddress,
                'latitude' => $destLat,
                'longitude' => $destLng,
            ],
            'navigationUrl' => route('tech_portal.navigation', $service->id),
        ]);
    }

    /**
     * Stop active navigation / Check in as arrived
     */
    public function stopNavigation(Request $request)
    {
        $tech = $this->getCurrentTechnician();
        if ($tech) {
            $status = $request->input('status', 'ARRIVED'); // ARRIVED or IDLE
            $tech->update([
                'tracking_status' => $status,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Navigation status updated.',
        ]);
    }

    /**
     * Real-time GPS Location Ping from Technician Phone
     */
    public function updateGps(Request $request)
    {
        $tech = $this->getCurrentTechnician();
        $techId = $request->input('technician_id') ?? ($tech ? $tech->id : null);

        if (!$techId) {
            $tech = Technician::first();
            $techId = $tech ? $tech->id : null;
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'heading' => 'nullable|numeric',
        ]);

        $lat = (float) $validated['latitude'];
        $lng = (float) $validated['longitude'];

        // Record breadcrumb point
        TechnicianLocation::create([
            'id' => (string) Str::uuid(),
            'technician_id' => $techId,
            'latitude' => $lat,
            'longitude' => $lng,
            'accuracy' => $validated['accuracy'] ?? 10,
            'speed' => $validated['speed'] ?? 0,
            'recorded_at' => now(),
        ]);

        $technician = Technician::find($techId);
        if ($technician) {
            $updates = [
                'current_latitude' => $lat,
                'current_longitude' => $lng,
                'current_accuracy' => $validated['accuracy'] ?? 10,
                'current_speed' => $validated['speed'] ?? 0,
                'current_heading' => $validated['heading'] ?? 0,
                'last_ping_at' => now(),
            ];

            if (!$technician->tracking_status || $technician->tracking_status === 'UNTRACKED') {
                $updates['tracking_status'] = $technician->active_service_id ? 'ON_THE_WAY' : 'ACTIVE';
            }

            // Recompute distance and ETA if destination is set
            if ($technician->destination_latitude && $technician->destination_longitude) {
                $dist = TechnicianController::calculateDistanceKm($lat, $lng, $technician->destination_latitude, $technician->destination_longitude);
                $eta = TechnicianController::estimateEtaMinutes($dist);
                $updates['distance_km'] = $dist;
                $updates['eta_minutes'] = $eta;
            }

            $technician->update($updates);

            return response()->json([
                'success' => true,
                'technicianId' => $technician->id,
                'statusFreshness' => $technician->statusFreshness,
                'distanceKm' => $technician->distance_km,
                'etaMinutes' => $technician->eta_minutes,
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Legacy API Compatibility: POST /maps/technician/navigate
     */
    public function apiStartNavigate(Request $request)
    {
        $serviceId = $request->input('serviceId') ?? $request->input('service_id');
        return $this->startNavigation($request, $serviceId);
    }

    /**
     * Legacy API Compatibility: POST /maps/technician/location
     */
    public function apiLocationPing(Request $request)
    {
        return $this->updateGps($request);
    }
}
