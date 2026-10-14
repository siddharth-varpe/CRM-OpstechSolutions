<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerCustomLabel;
use App\Models\BusinessSequence;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers with filters and pagination
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', 'ALL');
        $type = $request->input('type', 'ALL');
        $label = $request->input('label', 'ALL');
        $perPage = (int) $request->input('per_page', 10);

        $query = Customer::with(['primaryAddress', 'customLabel', 'assets', 'invoices'])
            ->whereNull('archived_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('customer_number', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status !== 'ALL') {
            $query->where('status', $status);
        }

        if ($type !== 'ALL') {
            $query->where('customer_type', $type);
        }

        if ($label !== 'ALL') {
            $query->where('customer_label', $label);
        }

        // Export to CSV if requested
        if ($request->input('export') === 'csv') {
            return $this->exportCsv($query->get());
        }

        $customers = $query->latest()->paginate($perPage)->withQueryString();

        // Calculate summary cards
        $totalCount = Customer::whereNull('archived_at')->count();
        $activeCount = Customer::whereNull('archived_at')->where('status', 'ACTIVE')->count();
        $commercialCount = Customer::whereNull('archived_at')->where('customer_type', 'COMMERCIAL')->count();
        $individualCount = Customer::whereNull('archived_at')->where('customer_type', 'INDIVIDUAL')->count();

        $customLabels = CustomerCustomLabel::all();

        return view('customers.index', [
            'customers' => $customers,
            'search' => $search,
            'status' => $status,
            'type' => $type,
            'label' => $label,
            'stats' => [
                'total' => $totalCount,
                'active' => $activeCount,
                'commercial' => $commercialCount,
                'individual' => $individualCount,
            ],
            'customLabels' => $customLabels,
        ]);
    }

    /**
     * Display single customer profile details
     */
    public function show($id)
    {
        $customer = Customer::with([
            'addresses',
            'customLabel',
            'assets.product',
            'assets.warranties',
            'invoices.items',
            'invoices.payments',
            'services.technician',
            'payments.invoice',
            'rentals.asset',
        ])->findOrFail($id);

        return view('customers.show', [
            'customer' => $customer,
        ]);
    }

    /**
     * Store a newly created customer
     */
    public function store(Request $request)
    {
        if (!$request->has('customer_type')) {
            $request->merge(['customer_type' => 'INDIVIDUAL']);
        }
        if (!$request->has('status')) {
            $request->merge(['status' => 'ACTIVE']);
        }
        if (!$request->has('state')) {
            $request->merge(['state' => 'Maharashtra']);
        }
        if (!$request->has('postal_code') && $request->has('pincode')) {
            $request->merge(['postal_code' => $request->input('pincode')]);
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'customer_type' => 'required|in:INDIVIDUAL,COMMERCIAL',
            'company_name' => 'nullable|string|max:255',
            'gst_number' => 'nullable|string|max:50',
            'status' => 'required|in:ACTIVE,INACTIVE',
            'customer_label' => 'nullable|in:GOOD,BAD',
            'custom_label_id' => 'nullable|exists:customer_custom_labels,id',
            'notes' => 'nullable|string',
            // Primary address fields
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'landmark' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $customerNumber = BusinessSequence::nextNumber('CUSTOMER');

            $customer = Customer::create([
                'customer_number' => $customerNumber,
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'customer_type' => $validated['customer_type'],
                'company_name' => $validated['company_name'] ?? null,
                'gst_number' => $validated['gst_number'] ?? null,
                'status' => $validated['status'],
                'customer_label' => $validated['customer_label'] ?? null,
                'custom_label_id' => $validated['custom_label_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $lat = ($request->filled('latitude') && (float)$validated['latitude'] != 0.0) ? (float)$validated['latitude'] : null;
            $lng = ($request->filled('longitude') && (float)$validated['longitude'] != 0.0) ? (float)$validated['longitude'] : null;

            CustomerAddress::create([
                'customer_id' => $customer->id,
                'address_type' => 'BOTH',
                'address_line1' => $validated['address_line1'],
                'address_line2' => $validated['address_line2'] ?? null,
                'landmark' => $validated['landmark'] ?? null,
                'city' => $validated['city'],
                'state' => $validated['state'],
                'postal_code' => $validated['postal_code'],
                'latitude' => $lat,
                'longitude' => $lng,
                'is_default' => true,
            ]);

            AuditLog::log('CREATE', 'CUSTOMER', $customer->id, null, ['name' => $customer->full_name]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Customer {$customer->full_name} created successfully.",
                    'redirect' => route('customers.show', $customer->id),
                ]);
            }

            return redirect()->route('customers.show', $customer->id)
                ->with('success', "Customer {$customer->full_name} created successfully.");
        });
    }

    /**
     * Update customer profile and address coordinates
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'customer_type' => 'required|in:INDIVIDUAL,COMMERCIAL',
            'company_name' => 'nullable|string|max:255',
            'gst_number' => 'nullable|string|max:50',
            'status' => 'required|in:ACTIVE,INACTIVE',
            'customer_label' => 'nullable|in:GOOD,BAD',
            'notes' => 'nullable|string',
            // Primary address fields (optional in update, but updated if provided)
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'landmark' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $customer->update([
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'customer_type' => $validated['customer_type'],
            'company_name' => $validated['company_name'] ?? null,
            'gst_number' => $validated['gst_number'] ?? null,
            'status' => $validated['status'],
            'customer_label' => $validated['customer_label'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $hasAddressInput = $request->has('address_line1') || $request->has('latitude') || $request->has('longitude') || $request->has('city');
        if ($hasAddressInput) {
            $primaryAddr = $customer->primaryAddress ?? $customer->addresses->first();
            $addrData = [];
            if ($request->filled('address_line1')) $addrData['address_line1'] = $validated['address_line1'];
            if ($request->has('address_line2')) $addrData['address_line2'] = $validated['address_line2'] ?? null;
            if ($request->has('landmark')) $addrData['landmark'] = $validated['landmark'] ?? null;
            if ($request->filled('city')) $addrData['city'] = $validated['city'];
            if ($request->filled('state')) $addrData['state'] = $validated['state'];
            if ($request->filled('postal_code')) $addrData['postal_code'] = $validated['postal_code'];

            if ($request->has('latitude')) {
                $addrData['latitude'] = ($request->filled('latitude') && (float)$validated['latitude'] != 0.0) ? (float)$validated['latitude'] : null;
            }
            if ($request->has('longitude')) {
                $addrData['longitude'] = ($request->filled('longitude') && (float)$validated['longitude'] != 0.0) ? (float)$validated['longitude'] : null;
            }

            if ($primaryAddr) {
                $primaryAddr->update($addrData);
            } else {
                $addrData['customer_id'] = $customer->id;
                $addrData['address_type'] = 'BOTH';
                $addrData['address_line1'] = $addrData['address_line1'] ?? 'Default Address';
                $addrData['city'] = $addrData['city'] ?? 'Pune';
                $addrData['state'] = $addrData['state'] ?? 'Maharashtra';
                $addrData['postal_code'] = $addrData['postal_code'] ?? '411001';
                $addrData['is_default'] = true;
                $primaryAddr = CustomerAddress::create($addrData);
            }

            // Sync active navigation destination coordinates if active navigation exists for this customer
            $this->syncActiveNavigationsForCustomer($customer->id, $primaryAddr);
        }

        AuditLog::log('UPDATE', 'CUSTOMER', $customer->id, null, $validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer profile and destination coordinates updated successfully.',
                'reload' => true,
            ]);
        }

        return back()->with('success', 'Customer profile and destination coordinates updated successfully.');
    }

    /**
     * Soft-archive / delete customer
     */
    public function archive($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update(['archived_at' => now(), 'status' => 'INACTIVE']);
        AuditLog::log('ARCHIVE', 'CUSTOMER', $customer->id);

        return redirect()->route('customers.index')->with('success', "Customer {$customer->full_name} archived.");
    }

    /**
     * Add address to customer
     */
    public function storeAddress(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'address_type' => 'required|in:BILLING,SERVICE,BOTH',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'landmark' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_default' => 'nullable|boolean',
        ]);

        if (!empty($validated['is_default'])) {
            CustomerAddress::where('customer_id', $customer->id)->update(['is_default' => false]);
        }

        $lat = ($request->filled('latitude') && (float)$validated['latitude'] != 0.0) ? (float)$validated['latitude'] : null;
        $lng = ($request->filled('longitude') && (float)$validated['longitude'] != 0.0) ? (float)$validated['longitude'] : null;

        $address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'address_type' => $validated['address_type'],
            'address_line1' => $validated['address_line1'],
            'address_line2' => $validated['address_line2'] ?? null,
            'landmark' => $validated['landmark'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'postal_code' => $validated['postal_code'],
            'latitude' => $lat,
            'longitude' => $lng,
            'is_default' => $request->boolean('is_default'),
        ]);

        if ($address->is_default) {
            $this->syncActiveNavigationsForCustomer($customer->id, $address);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'New address with destination coordinates added successfully.',
                'address' => $address,
            ]);
        }

        return back()->with('success', 'New address added successfully.');
    }

    /**
     * Update customer address and destination coordinates
     */
    public function updateAddress(Request $request, $customerId, $addressId)
    {
        $customer = Customer::findOrFail($customerId);
        $address = CustomerAddress::where('customer_id', $customer->id)->findOrFail($addressId);

        $validated = $request->validate([
            'address_type' => 'nullable|in:BILLING,SERVICE,BOTH',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'landmark' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_default' => 'nullable|boolean',
        ]);

        if (!empty($validated['is_default'])) {
            CustomerAddress::where('customer_id', $customer->id)->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $lat = ($request->filled('latitude') && (float)$validated['latitude'] != 0.0) ? (float)$validated['latitude'] : null;
        $lng = ($request->filled('longitude') && (float)$validated['longitude'] != 0.0) ? (float)$validated['longitude'] : null;

        $address->update([
            'address_type' => $validated['address_type'] ?? $address->address_type,
            'address_line1' => $validated['address_line1'],
            'address_line2' => $validated['address_line2'] ?? null,
            'landmark' => $validated['landmark'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'postal_code' => $validated['postal_code'],
            'latitude' => $lat,
            'longitude' => $lng,
            'is_default' => $request->has('is_default') ? $request->boolean('is_default') : $address->is_default,
        ]);

        if ($address->is_default || $customer->addresses->count() === 1) {
            $this->syncActiveNavigationsForCustomer($customer->id, $address);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer address and destination coordinates updated successfully.',
                'address' => $address,
            ]);
        }

        return back()->with('success', 'Customer address and destination coordinates updated successfully.');
    }

    /**
     * Address geocoding helper for search and pinpointing
     */
    public function geocodeAddress(Request $request)
    {
        $query = trim((string)$request->input('query', $request->input('address', '')));
        $landmark = trim((string)$request->input('landmark', ''));
        $area = trim((string)$request->input('area', $request->input('address_line2', '')));
        $city = trim((string)$request->input('city', 'Pune'));

        if (empty($query) && empty($landmark) && empty($area)) {
            return response()->json(['success' => false, 'message' => 'Address query is required'], 422);
        }

        $cacheKey = 'geocode:' . md5(strtolower($query . '|' . $landmark . '|' . $area . '|' . $city));
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($cached) {
            return response()->json($cached);
        }

        $allMatches = [];

        // LAYER 1: Search CRM verified knowledge base (exact buildings/societies previously pinned)
        $fullSearchString = trim($query . ' ' . $landmark . ' ' . $area);
        $crmMatches = $this->searchVerifiedCrmAddresses($fullSearchString);
        foreach ($crmMatches as $cm) {
            $allMatches[] = $cm;
        }

        // LAYER 2: Candidate decomposition including landmark and area
        $candidates = $this->buildGeocodeCandidates($query, $landmark, $area, $city);

        // Query Photon first for fine-grained landmarks, roads, and places (with Pune coordinate bias), then Nominatim
        foreach ($candidates as $candidate) {
            // 1. Try Photon (Komoot OSM Geocoder with Pune coordinate bias)
            try {
                $photonRes = \Illuminate\Support\Facades\Http::timeout(2.5)
                    ->get('https://photon.komoot.io/api/', [
                        'q' => $candidate,
                        'lat' => 18.5204,
                        'lon' => 73.8567,
                        'limit' => 5,
                    ]);

                if ($photonRes->successful() && !empty($photonRes->json()['features'])) {
                    foreach ($photonRes->json()['features'] as $feat) {
                        $props = $feat['properties'] ?? [];
                        $coords = $feat['geometry']['coordinates'] ?? [];
                        if (count($coords) >= 2) {
                            $nameParts = array_filter([
                                $props['name'] ?? null,
                                $props['street'] ?? null,
                                $props['district'] ?? null,
                                $props['locality'] ?? null,
                                $props['city'] ?? null,
                            ]);
                            $displayName = !empty($nameParts) ? implode(', ', $nameParts) : ($props['name'] ?? $candidate);
                            $rawType = $props['type'] ?? ($props['osm_value'] ?? 'place');
                            $buildingName = $props['name'] ?? '';
                            $streetName = $props['street'] ?? '';
                            $areaName = $props['district'] ?? ($props['locality'] ?? '');
                            $cityName = $props['city'] ?? 'Pune';
                            $stateName = $props['state'] ?? 'Maharashtra';
                            $postalCode = $props['postcode'] ?? '';

                            $isLandmark = in_array($rawType, ['community_centre', 'temple', 'place_of_worship', 'hospital', 'school', 'college', 'park', 'bank', 'mall']);

                            $allMatches[] = [
                                'latitude' => (float) $coords[1],
                                'longitude' => (float) $coords[0],
                                'display_name' => $displayName,
                                'matched_term' => $candidate,
                                'type' => $rawType,
                                'accuracy' => $this->classifyAccuracy($rawType),
                                'components' => [
                                    'address_line1' => $isLandmark ? ($streetName ? $streetName : $buildingName) : ($buildingName ?: $streetName),
                                    'address_line2' => $areaName ?: ($streetName ?: ''),
                                    'landmark' => $isLandmark ? $buildingName : '',
                                    'city' => $cityName,
                                    'state' => $stateName,
                                    'postal_code' => $postalCode,
                                ],
                            ];
                        }
                    }
                    if (count($allMatches) >= 4) {
                        break;
                    }
                }
            } catch (\Throwable $e) {
                // Continue
            }

            // 2. Try Nominatim (OpenStreetMap)
            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'User-Agent' => 'EnterprisesCRM/1.0',
                ])->timeout(2.5)->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $candidate,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 3,
                ]);

                if ($response->successful() && !empty($response->json())) {
                    foreach ($response->json() as $item) {
                        $rawType = $item['type'] ?? 'place';
                        $itemAddr = $item['address'] ?? [];
                        $allMatches[] = [
                            'latitude' => (float) $item['lat'],
                            'longitude' => (float) $item['lon'],
                            'display_name' => $item['display_name'] ?? $candidate,
                            'matched_term' => $candidate,
                            'type' => $rawType,
                            'accuracy' => $this->classifyAccuracy($rawType),
                            'components' => [
                                'address_line1' => $item['name'] ?? ($itemAddr['road'] ?? ''),
                                'address_line2' => $itemAddr['suburb'] ?? ($itemAddr['neighbourhood'] ?? ($itemAddr['subdistrict'] ?? '')),
                                'landmark' => $itemAddr['amenity'] ?? ($itemAddr['place_of_worship'] ?? ''),
                                'city' => $itemAddr['city'] ?? ($itemAddr['town'] ?? ($itemAddr['city_district'] ?? 'Pune')),
                                'state' => $itemAddr['state'] ?? 'Maharashtra',
                                'postal_code' => $itemAddr['postcode'] ?? '',
                            ],
                        ];
                    }
                    if (count($allMatches) >= 4) {
                        break;
                    }
                }
            } catch (\Throwable $e) {
                // Continue to next candidate
            }
        }

        if (!empty($allMatches)) {
            // Sort matches: 'exact' (CRM building) > 'high' (landmark / street within +-100m) > 'approximate' (suburb centroid)
            $rankMap = ['exact' => 1, 'high' => 2, 'approximate' => 3];
            usort($allMatches, function ($a, $b) use ($rankMap) {
                $rankA = $rankMap[$a['accuracy'] ?? 'approximate'] ?? 3;
                $rankB = $rankMap[$b['accuracy'] ?? 'approximate'] ?? 3;
                return $rankA <=> $rankB;
            });

            // Deduplicate proximate coordinates (< 50 meters apart)
            $uniqueMatches = [];
            foreach ($allMatches as $match) {
                $isDup = false;
                foreach ($uniqueMatches as $um) {
                    if (abs($um['latitude'] - $match['latitude']) < 0.0005 &&
                        abs($um['longitude'] - $match['longitude']) < 0.0005) {
                        $isDup = true;
                        break;
                    }
                }
                if (!$isDup) {
                    $uniqueMatches[] = $match;
                }
            }

            $primary = $uniqueMatches[0];
            $data = [
                'success' => true,
                'latitude' => $primary['latitude'],
                'longitude' => $primary['longitude'],
                'display_name' => $primary['display_name'],
                'matched_term' => $primary['matched_term'],
                'accuracy' => $primary['accuracy'] ?? 'approximate',
                'is_approximate' => ($primary['accuracy'] ?? '') !== 'exact',
                'components' => $primary['components'] ?? [],
                'results' => array_slice($uniqueMatches, 0, 6),
            ];

            \Illuminate\Support\Facades\Cache::put($cacheKey, $data, 86400);
            return response()->json($data);
        }

        return response()->json([
            'success' => false,
            'message' => 'No coordinates found for this address. Please place or adjust the pin manually on the map.',
        ], 404);
    }

    /**
     * Reverse geocode a latitude and longitude to get structured address components
     */
    public function reverseGeocode(Request $request)
    {
        $lat = (float) $request->input('lat', $request->input('latitude'));
        $lng = (float) $request->input('lng', $request->input('longitude'));

        if (!$lat || !$lng || abs($lat) > 90 || abs($lng) > 180) {
            return response()->json(['success' => false, 'message' => 'Valid latitude and longitude required'], 422);
        }

        $cacheKey = 'reverse_geo:' . round($lat, 5) . ':' . round($lng, 5);
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($cached) {
            return response()->json($cached);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent' => 'EnterprisesCRM/1.0',
            ])->timeout(3)->get('https://nominatim.openstreetmap.org/reverse', [
                'lat' => $lat,
                'lon' => $lng,
                'format' => 'json',
                'addressdetails' => 1,
            ]);

            if ($response->successful() && !empty($response->json())) {
                $data = $response->json();
                $addr = $data['address'] ?? [];

                $result = [
                    'success' => true,
                    'display_name' => $data['display_name'] ?? '',
                    'components' => [
                        'address_line1' => $addr['road'] ?? ($addr['building'] ?? ($addr['amenity'] ?? '')),
                        'address_line2' => $addr['suburb'] ?? ($addr['neighbourhood'] ?? ($addr['residential'] ?? '')),
                        'landmark' => $addr['amenity'] ?? ($addr['shop'] ?? ($addr['place_of_worship'] ?? '')),
                        'city' => $addr['city'] ?? ($addr['town'] ?? ($addr['city_district'] ?? 'Pune')),
                        'state' => $addr['state'] ?? 'Maharashtra',
                        'postal_code' => $addr['postcode'] ?? '',
                    ],
                ];

                \Illuminate\Support\Facades\Cache::put($cacheKey, $result, 86400);
                return response()->json($result);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Reverse geocode failed for ({$lat}, {$lng}): " . $e->getMessage());
        }

        return response()->json(['success' => false, 'message' => 'Could not reverse geocode coordinates'], 404);
    }

    /**
     * Search verified customer addresses in the CRM database for matching buildings/societies
     */
    protected function searchVerifiedCrmAddresses(string $searchString): array
    {
        $searchString = strtolower($searchString);
        $tokens = array_filter(preg_split('/[,\s\-\.\/]+/', $searchString), function ($t) {
            return strlen($t) >= 3 && !in_array($t, [
                'the', 'and', 'near', 'opp', 'opposite', 'behind', 'beside',
                'pune', 'flat', 'shop', 'phase', 'road', 'apartment', 'apartments',
                'bldg', 'building', 'colony', 'nagar', 'lane', 'chowk', 'subdistrict'
            ]);
        });

        if (empty($tokens)) {
            return [];
        }

        $addresses = \App\Models\CustomerAddress::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0.0)
            ->get();

        $matches = [];
        foreach ($addresses as $addr) {
            $haystack = strtolower(implode(' ', array_filter([
                $addr->address_line1,
                $addr->address_line2,
                $addr->landmark,
                $addr->city
            ])));

            $score = 0;
            foreach ($tokens as $token) {
                if (str_contains($haystack, $token)) {
                    $score += (strlen($token) >= 5 ? 2 : 1);
                }
            }

            if ($score >= 2) {
                $parts = array_filter([
                    $addr->address_line1,
                    $addr->address_line2,
                    $addr->landmark ? 'Near ' . $addr->landmark : null,
                    $addr->city
                ]);
                $matches[] = [
                    'latitude' => (float) $addr->latitude,
                    'longitude' => (float) $addr->longitude,
                    'display_name' => implode(', ', $parts) . ' [Verified Building in CRM]',
                    'matched_term' => $addr->address_line1,
                    'type' => 'verified_crm_building',
                    'accuracy' => 'exact',
                    'score' => $score,
                    'components' => [
                        'address_line1' => $addr->address_line1,
                        'address_line2' => $addr->address_line2 ?? '',
                        'landmark' => $addr->landmark ?? '',
                        'city' => $addr->city ?? 'Pune',
                        'state' => $addr->state ?? 'Maharashtra',
                        'postal_code' => $addr->postal_code ?? '',
                    ],
                ];
            }
        }

        usort($matches, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $matches;
    }

    /**
     * Classify geocode match accuracy level
     */
    protected function classifyAccuracy(string $type): string
    {
        $type = strtolower($type);
        if (in_array($type, ['verified_crm_building', 'building', 'house', 'apartments', 'residential', 'home'])) {
            return 'exact'; // +-10 to 30 meters
        }
        if (in_array($type, ['community_centre', 'temple', 'place_of_worship', 'hospital', 'school', 'college', 'street', 'highway', 'tertiary', 'secondary', 'primary', 'residential_street', 'commercial', 'park', 'bus_stop', 'mall', 'bank'])) {
            return 'high'; // +-50 to 100 meters
        }
        return 'approximate'; // suburb / locality centroid (~500m - 1.5km)
    }

    /**
     * Build prioritized candidate search strings from raw Indian customer address strings, landmarks, and area
     */
    protected function buildGeocodeCandidates(string $raw, string $landmark = '', string $area = '', string $city = 'Pune'): array
    {
        $raw = trim($raw);
        $candidates = [];

        $hasCity = (bool) preg_match('/\b(pune|pimpr[i\-]|chinchwad|haveli|pcmc)\b/i', $raw . ' ' . $area . ' ' . $landmark);
        $citySuffix = $hasCity ? '' : (', ' . ($city ?: 'Pune'));

        // 1. If landmark provided, prioritize landmark + area + city
        if (!empty($landmark)) {
            $cleanLandmark = trim(preg_replace('/\b(near|opp|opposite|behind|beside|adj|adjacent)\s*[:#\.\-\s]*/i', '', $landmark));
            if (!empty($cleanLandmark)) {
                if (!empty($area)) {
                    $candidates[] = $cleanLandmark . ', ' . $area . $citySuffix;
                }
                $candidates[] = $cleanLandmark . $citySuffix;
                $candidates[] = $cleanLandmark;
            }
        }

        // 2. If area / colony provided
        if (!empty($area)) {
            $candidates[] = $area . $citySuffix;
        }

        // 3. Exact raw query
        if (!empty($raw)) {
            $candidates[] = $raw;
            if (!$hasCity) {
                $candidates[] = $raw . $citySuffix;
            }
        }

        $noisePattern = '/\b(flat|shop|office|unit|room|block|apt|apartment|phase|sector|sec|plot|survey\s*no|sr\s*no|house\s*no|s\s*no|near|opp|opposite|behind|beside)\s*[:#\.\-\s]*[\w\d\-]+/i';

        // 4. Split raw into segments by comma/slash/semicolon
        $segments = array_values(array_filter(array_map(function ($s) {
            return trim(preg_replace('/\s+/', ' ', $s));
        }, preg_split('/[,;\/]+/', $raw))));

        $segCount = count($segments);

        if ($segCount > 1) {
            // Check if any segment is a landmark or society name
            foreach ($segments as $seg) {
                $cleanedSeg = trim(preg_replace('/\s+/', ' ', preg_replace($noisePattern, ' ', $seg)));
                $cleanedSeg = trim(preg_replace('/^[, \-]+|[, \-]+$/', '', $cleanedSeg));
                if (strlen($cleanedSeg) >= 4 && !preg_match('/\b(pune|pcmc|maharashtra|india)\b/i', $cleanedSeg)) {
                    $candidates[] = $cleanedSeg . $citySuffix;
                    $candidates[] = $cleanedSeg;
                }
            }

            // Locality + City
            $last = $segments[$segCount - 1];
            $isLastCity = (bool) preg_match('/\b(pune|pimpr[i\-]|chinchwad|pcmc|maharashtra|india)\b/i', $last);
            $locality = ($isLastCity && $segCount > 2) ? $segments[$segCount - 2] : $last;

            $cleanedLocality = trim(preg_replace('/\s+/', ' ', preg_replace($noisePattern, ' ', $locality)));
            if (!empty($cleanedLocality)) {
                $candidates[] = $cleanedLocality . $citySuffix;
                $candidates[] = $cleanedLocality;
            }

            // Right-side 2 segments
            $right2 = implode(', ', array_slice($segments, -2));
            $candidates[] = $right2;
            if (!$hasCity && !preg_match('/\b(pune|pcmc)\b/i', $right2)) {
                $candidates[] = $right2 . $citySuffix;
            }
        }

        // Cleaned raw query without noise
        $cleanedRaw = trim(preg_replace('/\s+/', ' ', preg_replace($noisePattern, ' ', $raw)));
        $cleanedRaw = trim(preg_replace('/^[, \-]+|[, \-]+$/', '', $cleanedRaw));
        if (!empty($cleanedRaw) && strtolower($cleanedRaw) !== strtolower($raw)) {
            $candidates[] = $cleanedRaw;
            if (!$hasCity && !preg_match('/\b(pune|pcmc)\b/i', $cleanedRaw)) {
                $candidates[] = $cleanedRaw . $citySuffix;
            }
        }

        // Deduplicate while preserving order
        $unique = [];
        foreach ($candidates as $c) {
            $k = strtolower(trim($c));
            if ($k !== '' && !isset($unique[$k])) {
                $unique[$k] = trim($c);
            }
        }

        return array_values($unique);
    }

    /**
     * Helper to synchronize active technician navigations if customer destination coordinates are updated
     */
    protected function syncActiveNavigationsForCustomer(string $customerId, CustomerAddress $address): void
    {
        try {
            $mapsTrackingService = app(\App\Services\MapsTrackingService::class);
            $serviceIds = \App\Models\Service::where('customer_id', $customerId)
                ->whereIn('status', ['ASSIGNED', 'IN_PROGRESS', 'SCHEDULED'])
                ->pluck('id')
                ->toArray();

            if (empty($serviceIds)) {
                return;
            }

            $technicians = \App\Models\Technician::whereIn('active_service_id', $serviceIds)->get();

            foreach ($technicians as $tech) {
                $techId = (string) $tech->id;
                $activeDest = $mapsTrackingService->getActiveDestination($techId);
                if ($activeDest && in_array($activeDest['serviceId'] ?? '', $serviceIds)) {
                    $activeDest['latitude'] = $address->latitude ? (float) $address->latitude : null;
                    $activeDest['longitude'] = $address->longitude ? (float) $address->longitude : null;
                    $activeDest['address'] = $address->full_address;
                    $mapsTrackingService->setActiveDestination($techId, $activeDest);
                }

                $tech->update([
                    'destination_latitude' => $address->latitude,
                    'destination_longitude' => $address->longitude,
                    'destination_address' => $address->full_address,
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to sync active navigation for customer {$customerId}: " . $e->getMessage());
        }
    }

    /**
     * Export customer records as CSV
     */
    private function exportCsv($customers)
    {
        $filename = 'customers-export-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($customers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Customer Number', 'Full Name', 'Phone', 'Email', 'Type', 'Company', 'GSTIN', 'City', 'State', 'Status', 'Label', 'Created At']);

            foreach ($customers as $c) {
                $addr = $c->primaryAddress;
                fputcsv($handle, [
                    $c->customer_number,
                    $c->full_name,
                    $c->phone,
                    $c->email ?? '',
                    $c->customer_type,
                    $c->company_name ?? '',
                    $c->gst_number ?? '',
                    $addr ? $addr->city : '',
                    $addr ? $addr->state : '',
                    $c->status,
                    $c->customer_label ?? '',
                    $c->created_at->format('Y-m-d'),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Download sample import template (CSV with name, number, address)
     */
    public function downloadSampleImport()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customers_import_sample.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['name', 'number', 'address']);
            fputcsv($handle, ['Rohan Deshmukh', '9822011223', 'Flat 102, Green Acres, Baner, Pune']);
            fputcsv($handle, ['Sunita Joshi', '', 'Plot 14, MIDC Bhosari, Pune']);
            fputcsv($handle, ['Vikas Shinde', '9822998877', '']);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Step 1: Analyze uploaded Excel or CSV file and return pre-import report
     */
    public function analyzeImport(Request $request)
    {
        @ini_set('max_execution_time', '300');
        @set_time_limit(300);

        $rawRows = [];
        $filename = 'imported_data';

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $ext = strtolower($file->getClientOriginalExtension());
            $realPath = $file->getRealPath();

            if (in_array($ext, ['csv', 'txt'])) {
                $rawRows = $this->parseCsvFile($realPath);
            } elseif (in_array($ext, ['xlsx', 'xls'])) {
                $rawRows = $this->parseExcelFile($realPath);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Unsupported file format. Please upload an Excel (.xlsx, .xls) or CSV (.csv) file.',
                ], 422);
            }
        } elseif ($request->filled('rows')) {
            $rawRows = is_array($request->input('rows')) ? $request->input('rows') : json_decode($request->input('rows'), true);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No file or spreadsheet data received for import analysis.',
            ], 422);
        }

        if (empty($rawRows) || !is_array($rawRows)) {
            return response()->json([
                'success' => false,
                'message' => 'The uploaded file is empty or could not be read.',
            ], 422);
        }

        $headerRow = $rawRows[0] ?? [];
        $totalRawCols = count($headerRow);

        // Header list for UI mapping
        $headersList = [];
        foreach ($headerRow as $idx => $val) {
            $valTrim = trim((string)$val);
            $headersList[] = [
                'index' => $idx,
                'name' => $valTrim !== '' ? $valTrim : 'Column ' . ($idx + 1),
            ];
        }

        // Check if explicit column overrides are provided from request
        $nameCol = $request->has('name_col') && $request->input('name_col') !== '' ? (int)$request->input('name_col') : null;
        $numberCol = $request->has('number_col') && $request->input('number_col') !== '' ? (int)$request->input('number_col') : null;
        $addressCol = $request->has('address_col') && $request->input('address_col') !== '' && (int)$request->input('address_col') >= 0 ? (int)$request->input('address_col') : null;

        // Auto-detect column indices if not explicitly provided
        if ($nameCol === null || $numberCol === null) {
            foreach ($headerRow as $idx => $val) {
                $norm = strtolower(trim((string)$val));
                $norm = preg_replace('/[^a-z0-9]/', '', $norm);

                // Name detection
                if ($nameCol === null) {
                    if (in_array($norm, ['name', 'fullname', 'customername', 'customer', 'client', 'clientname', 'party', 'partyname', 'contactname', 'person', 'accountname', 'custname'])) {
                        $nameCol = $idx;
                    } elseif (str_contains($norm, 'name') && !str_contains($norm, 'file') && !str_contains($norm, 'user')) {
                        $nameCol = $idx;
                    }
                }

                // Phone/Number detection
                if ($numberCol === null) {
                    if (in_array($norm, ['number', 'phone', 'phonenumber', 'mobile', 'mobilenumber', 'primaryphone', 'phone1', 'mobile1', 'contact', 'contactnumber', 'contactno', 'tel', 'cell', 'cellular', 'whatsapp', 'mobileno', 'phoneno', 'telephone'])) {
                        $numberCol = $idx;
                    } elseif (str_contains($norm, 'phone') || str_contains($norm, 'mobile') || str_contains($norm, 'contact')) {
                        $numberCol = $idx;
                    }
                }

                // Address detection
                if ($addressCol === null) {
                    if (in_array($norm, ['address', 'location', 'addr', 'fulladdress', 'addressline', 'addressline1', 'city', 'residence', 'area', 'society', 'flat', 'street', 'siteaddress', 'customeraddress', 'custaddress'])) {
                        $addressCol = $idx;
                    } elseif (str_contains($norm, 'address') || str_contains($norm, 'location')) {
                        $addressCol = $idx;
                    }
                }
            }
        }

        $hasHeader = ($nameCol !== null || $numberCol !== null || $addressCol !== null);
        $startRow = $hasHeader ? 1 : 0;

        // Safe fallbacks if auto-detection couldn't find columns
        if ($nameCol === null) {
            $nameCol = 0;
        }
        if ($numberCol === null) {
            $numberCol = ($nameCol === 1 ? 0 : 1);
        }
        // Note: $addressCol stays null if not detected, meaning address defaults to empty rather than stealing another column

        $analyzedRows = [];
        $completeCount = 0;
        $partialCount = 0;
        $blankCount = 0;

        for ($i = $startRow; $i < count($rawRows); $i++) {
            $row = $rawRows[$i];
            if (!is_array($row)) {
                continue;
            }

            $name = ($nameCol !== null && isset($row[$nameCol])) ? trim((string)$row[$nameCol]) : '';
            $number = ($numberCol !== null && isset($row[$numberCol])) ? trim((string)$row[$numberCol]) : '';
            $address = ($addressCol !== null && isset($row[$addressCol])) ? trim((string)$row[$addressCol]) : '';

            // Clean numerical phone if exported as float e.g. 9822011223.0
            if (preg_match('/^\d+\.0$/', $number)) {
                $number = substr($number, 0, -2);
            }

            // If entire row is empty, count as blank and skip
            if ($name === '' && $number === '' && $address === '') {
                $blankCount++;
                continue;
            }

            $missing = [];
            if ($name === '') $missing[] = 'Name';
            if ($number === '') $missing[] = 'Number';
            if ($address === '') $missing[] = 'Address';

            $status = empty($missing) ? 'COMPLETE' : 'PARTIAL';
            if ($status === 'COMPLETE') {
                $completeCount++;
            } else {
                $partialCount++;
            }

            $analyzedRows[] = [
                'row_num' => $i + 1,
                'name' => $name,
                'number' => $number,
                'address' => $address,
                'status' => $status,
                'missing' => $missing,
            ];
        }

        if (empty($analyzedRows)) {
            return response()->json([
                'success' => false,
                'message' => 'No importable rows found in the uploaded file.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'report' => [
                'filename' => $filename,
                'total_rows' => count($analyzedRows),
                'complete_count' => $completeCount,
                'partial_count' => $partialCount,
                'blank_count' => $blankCount,
                'headers' => $headersList,
                'detected_cols' => [
                    'name' => $nameCol,
                    'number' => $numberCol,
                    'address' => $addressCol !== null ? $addressCol : -1,
                ],
                'columns' => [
                    'name_header' => $hasHeader && isset($headerRow[$nameCol]) ? $headerRow[$nameCol] : 'Col ' . ($nameCol + 1),
                    'number_header' => $hasHeader && isset($headerRow[$numberCol]) ? $headerRow[$numberCol] : 'Col ' . ($numberCol + 1),
                    'address_header' => ($addressCol !== null && isset($headerRow[$addressCol])) ? $headerRow[$addressCol] : ($addressCol !== null ? 'Col ' . ($addressCol + 1) : 'None (Empty)'),
                ],
                'preview' => array_slice($analyzedRows, 0, 50),
                'rows' => $analyzedRows,
            ]
        ]);
    }

    /**
     * Step 2: High-performance commit of analyzed customer rows into the CRM
     */
    public function commitImport(Request $request)
    {
        @ini_set('max_execution_time', '300');
        @set_time_limit(300);

        $request->validate([
            'rows' => 'required|array|min:1',
        ]);

        $rawItems = $request->input('rows');
        $cleanItems = [];

        foreach ($rawItems as $item) {
            $name = trim((string)($item['name'] ?? ''));
            $number = trim((string)($item['number'] ?? ''));
            $address = trim((string)($item['address'] ?? ''));

            if ($name === '' && $number === '' && $address === '') {
                continue;
            }

            $cleanItems[] = [
                'name' => $name,
                'number' => $number,
                'address' => $address,
            ];
        }

        $totalClean = count($cleanItems);
        if ($totalClean === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No valid customer rows found to import.',
            ], 422);
        }

        // Reserve unique sequential customer numbers in bulk with zero lock contention
        $customerNumbers = BusinessSequence::nextNumbers('CUSTOMER', $totalClean);

        $userId = auth()->id();
        $now = now()->toDateTimeString();

        $customerInserts = [];
        $addressInserts = [];

        for ($i = 0; $i < $totalClean; $i++) {
            $item = $cleanItems[$i];
            $customerId = (string) Str::uuid();

            $customerInserts[] = [
                'id' => $customerId,
                'customer_number' => $customerNumbers[$i] ?? ('CUST-' . date('Y') . '-' . str_pad((string)($i + 1), 5, '0', STR_PAD_LEFT)),
                'full_name' => $item['name'],
                'phone' => $item['number'],
                'customer_type' => 'INDIVIDUAL',
                'status' => 'ACTIVE',
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $addressInserts[] = [
                'id' => (string) Str::uuid(),
                'customer_id' => $customerId,
                'address_type' => 'BOTH',
                'address_line1' => $item['address'],
                'city' => '',
                'state' => '',
                'postal_code' => '',
                'is_default' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Fast chunked database insertion (500 per batch)
        DB::transaction(function () use ($customerInserts, $addressInserts, $totalClean) {
            foreach (array_chunk($customerInserts, 500) as $chunk) {
                DB::table('customers')->insert($chunk);
            }
            foreach (array_chunk($addressInserts, 500) as $chunk) {
                DB::table('customer_addresses')->insert($chunk);
            }

            AuditLog::log('IMPORT', 'CUSTOMER', null, null, [
                'imported_count' => $totalClean,
                'timestamp' => now()->toIso8601String(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Successfully imported {$totalClean} customer" . ($totalClean === 1 ? '' : 's') . ".",
            'imported_count' => $totalClean,
            'redirect' => route('customers.index'),
        ]);
    }

    /**
     * Parse CSV file into rows with encoding and delimiter auto-detection
     */
    private function parseCsvFile(string $filePath): array
    {
        $rows = [];
        $content = file_get_contents($filePath);
        if ($content === false || strlen($content) === 0) {
            return [];
        }

        // Handle encoding conversion (Windows-1252 / ISO-8859-1 to UTF-8)
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1, Windows-1252, auto');
        }

        // Strip UTF-8 BOM if present
        $bom = pack('H*','EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);

        // Auto-detect delimiter from first line
        $firstLine = strtok($content, "\r\n");
        $delimiter = ',';
        if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ';';
        } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
            $delimiter = "\t";
        }

        $lines = preg_split('/\r\n|\r|\n/', $content);
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $parsed = str_getcsv($line, $delimiter);
            if (!empty($parsed)) {
                $rows[] = array_map(function ($val) {
                    $s = trim((string)$val);
                    if (preg_match('/^\d+\.0$/', $s)) {
                        $s = substr($s, 0, -2);
                    }
                    return $s;
                }, $parsed);
            }
        }

        return $rows;
    }

    /**
     * Parse XLSX / XLS file using python helper with LibreOffice fallback
     */
    private function parseExcelFile(string $filePath): array
    {
        $scriptPath = base_path('scripts/parse_excel.py');
        if (!file_exists($scriptPath)) {
            return [];
        }

        $cmd = 'python3 ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($filePath);
        $output = shell_exec($cmd);
        if (!$output) {
            return [];
        }

        $decoded = json_decode($output, true);
        return is_array($decoded) ? $decoded : [];
    }
}
