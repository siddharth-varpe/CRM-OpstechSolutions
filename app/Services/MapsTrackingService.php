<?php

namespace App\Services;

use App\Events\TechnicianLocationUpdated;
use App\Events\TechnicianTrackingStopped;
use App\Models\Technician;
use App\Models\TechnicianLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MapsTrackingService
{
    public const STATE_TTL_SECONDS = 7200; // 2 hours rolling TTL
    public const CACHE_TTL_ROUTES_SECONDS = 90; // 90 seconds route cache

    public const KEY_PREFIX_LOCATION = 'tech_location:';
    public const KEY_PREFIX_DESTINATION = 'tech_active_dest:';
    public const KEY_ACTIVE_SET = 'tech_active_tracking_set';

    public const GEOFENCE_RADIUS_METERS = 100;

    /**
     * Determine staleness classification for a given timestamp
     */
    public function evaluateFreshness($lastUpdate): string
    {
        if (!$lastUpdate) {
            return 'OFFLINE';
        }

        $timestampMs = $lastUpdate instanceof Carbon
            ? $lastUpdate->timestamp * 1000
            : (is_numeric($lastUpdate) ? (int) $lastUpdate : strtotime((string) $lastUpdate) * 1000);

        // If timestamp is in seconds, convert to ms
        if ($timestampMs < 10000000000) {
            $timestampMs *= 1000;
        }

        $nowMs = (int) (microtime(true) * 1000);
        $ageSeconds = max(0, (int) floor(($nowMs - $timestampMs) / 1000));

        if ($ageSeconds <= 30) {
            return 'LIVE';
        }
        if ($ageSeconds <= 90) {
            return 'WARNING';
        }
        if ($ageSeconds < 900) {
            return 'STALE';
        }

        return 'OFFLINE';
    }

    /**
     * Calculate Haversine straight-line distance between two points in meters
     */
    public function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $R = 6371000; // Earth radius in meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return (int) round($R * $c);
    }

    /**
     * Format distance in meters to user-friendly string
     */
    public function formatDistance(float $meters): string
    {
        if ($meters < 1000) {
            return round($meters) . ' m';
        }

        return number_format($meters / 1000, 1) . ' km';
    }

    /**
     * Format duration in seconds to user-friendly string
     */
    public function formatDuration(int $seconds): string
    {
        $mins = (int) round($seconds / 60);
        if ($mins < 1) {
            return '< 1 min';
        }
        if ($mins < 60) {
            return $mins . ' min' . ($mins === 1 ? '' : 's');
        }

        $hours = (int) floor($mins / 60);
        $remMins = $mins % 60;

        return $remMins > 0 ? "{$hours} hr {$remMins} min" : "{$hours} hr";
    }

    /**
     * Compute route road distance and ETA between origin and destination.
     * Uses Google Routes API with caching and Haversine fallback.
     */
    public function computeRoute(float $originLat, float $originLng, float $destLat, float $destLng): array
    {
        $cacheKey = sprintf(
            'tech_routes_cache:%.3f,%.3f:%.3f,%.3f',
            $originLat,
            $originLng,
            $destLat,
            $destLng
        );

        $cached = Cache::get($cacheKey);
        if ($cached && is_array($cached)) {
            return $cached;
        }

        $straightLineMeters = $this->calculateHaversineDistance($originLat, $originLng, $destLat, $destLng);

        $serverKey = config('services.google_maps.routes_key', env('ROUTES_API_KEY'));

        if (!empty($serverKey)) {
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Goog-Api-Key' => $serverKey,
                    'X-Goog-FieldMask' => 'routes.duration,routes.distanceMeters',
                ])->timeout(3.5)->post('https://routes.googleapis.com/directions/v2:computeRoutes', [
                    'origin' => [
                        'location' => [
                            'latLng' => [
                                'latitude' => $originLat,
                                'longitude' => $originLng,
                            ],
                        ],
                    ],
                    'destination' => [
                        'location' => [
                            'latLng' => [
                                'latitude' => $destLat,
                                'longitude' => $destLng,
                            ],
                        ],
                    ],
                    'travelMode' => 'DRIVE',
                    'routingPreference' => 'TRAFFIC_AWARE',
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $route = $json['routes'][0] ?? null;
                    if ($route) {
                        $distanceMeters = (int) ($route['distanceMeters'] ?? $straightLineMeters);
                        $durationRaw = $route['duration'] ?? '0s';
                        $durationSeconds = (int) str_replace('s', '', $durationRaw);
                        if ($durationSeconds <= 0) {
                            $durationSeconds = (int) round($distanceMeters / 6.94); // ~25 km/h
                        }

                        $result = [
                            'distanceMeters' => $distanceMeters,
                            'durationSeconds' => $durationSeconds,
                            'distanceText' => $this->formatDistance($distanceMeters),
                            'durationText' => $this->formatDuration($durationSeconds),
                            'distanceKm' => round($distanceMeters / 1000, 1),
                            'etaMinutes' => (int) ceil($durationSeconds / 60),
                            'source' => 'GOOGLE_ROUTES_API',
                        ];

                        Cache::put($cacheKey, $result, self::CACHE_TTL_ROUTES_SECONDS);
                        return $result;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[MapsTrackingService] Google Routes API warning: ' . $e->getMessage());
            }
        }

        // Safe Fallback: Urban road factor ~1.3x straight-line, 25 km/h driving speed
        $estimatedRoadMeters = (int) round($straightLineMeters * 1.3);
        $estimatedSeconds = max(60, (int) round($estimatedRoadMeters / 6.94));

        $fallback = [
            'distanceMeters' => $estimatedRoadMeters,
            'durationSeconds' => $estimatedSeconds,
            'distanceText' => $this->formatDistance($estimatedRoadMeters),
            'durationText' => $this->formatDuration($estimatedSeconds),
            'distanceKm' => round($estimatedRoadMeters / 1000, 1),
            'etaMinutes' => (int) ceil($estimatedSeconds / 60),
            'source' => 'HAVERSINE_ESTIMATE',
        ];

        Cache::put($cacheKey, $fallback, self::CACHE_TTL_ROUTES_SECONDS);
        return $fallback;
    }

    /**
     * Fetch road-following route geometry between origin and destination.
     * Queries OSRM with caching.
     */
    public function fetchRoadGeometry(float $originLat, float $originLng, float $destLat, float $destLng): array
    {
        $cacheKey = sprintf('tech_road_geom:%.4f,%.4f:%.4f,%.4f', $originLat, $originLng, $destLat, $destLng);
        $cached = Cache::get($cacheKey);
        if ($cached && is_array($cached)) {
            return $cached;
        }

        try {
            $osrmUrl = "https://router.project-osrm.org/route/v1/driving/{$originLng},{$originLat};{$destLng},{$destLat}?overview=full&geometries=geojson&steps=true";
            $res = Http::timeout(6)->withHeaders(['User-Agent' => 'EnterprisesCRM/1.0'])->get($osrmUrl);
            if ($res->successful()) {
                $json = $res->json();
                if ($json && ($json['code'] ?? '') === 'Ok') {
                    Cache::put($cacheKey, $json, 300); // 5-minute cache
                    return $json;
                }
            }
        } catch (\Throwable $e) {
            \Log::warning("fetchRoadGeometry error: " . $e->getMessage());
        }

        return ['code' => 'NoRoute', 'routes' => []];
    }

    /**
     * Set active navigation destination for an authenticated technician.
     * Enforces: ONE technician = ONE active destination.
     */
    public function setActiveDestination(string $technicianId, array $destination): array
    {
        $destKey = self::KEY_PREFIX_DESTINATION . $technicianId;
        $locKey = self::KEY_PREFIX_LOCATION . $technicianId;

        $currentRecord = Cache::get($locKey);

        $nowMs = (int) (microtime(true) * 1000);

        $destLat = (isset($destination['latitude']) && $destination['latitude'] !== null && $destination['latitude'] !== '')
            ? (float) $destination['latitude']
            : null;
        $destLng = (isset($destination['longitude']) && $destination['longitude'] !== null && $destination['longitude'] !== '')
            ? (float) $destination['longitude']
            : null;

        $destinationRecord = [
            'serviceId' => (string) ($destination['serviceId'] ?? ''),
            'serviceNumber' => (string) ($destination['serviceNumber'] ?? ''),
            'jobCardId' => $destination['jobCardId'] ?? null,
            'jobCardNumber' => $destination['jobCardNumber'] ?? null,
            'customerId' => (string) ($destination['customerId'] ?? ''),
            'customerName' => (string) ($destination['customerName'] ?? 'Customer'),
            'customerPhone' => (string) ($destination['customerPhone'] ?? ''),
            'address' => (string) ($destination['address'] ?? 'Customer Address'),
            'latitude' => $destLat,
            'longitude' => $destLng,
            'scheduledDate' => $destination['scheduledDate'] ?? null,
            'scheduledTimeSlot' => $destination['scheduledTimeSlot'] ?? null,
            'startedAt' => $nowMs,
        ];

        $tech = Technician::find($technicianId);
        $curLat = $currentRecord['latitude'] ?? ($tech && $tech->current_latitude !== null ? (float) $tech->current_latitude : 18.5204);
        $curLng = $currentRecord['longitude'] ?? ($tech && $tech->current_longitude !== null ? (float) $tech->current_longitude : 73.8567);

        // Compute route distance & ETA if destination coordinates are available
        $distMeters = null;
        $distText = null;
        $distKm = null;
        $etaSec = null;
        $etaText = null;
        $etaMins = null;

        if ($destLat !== null && $destLng !== null) {
            $route = $this->computeRoute($curLat, $curLng, $destLat, $destLng);
            $distMeters = $route['distanceMeters'] ?? null;
            $distText = $route['distanceText'] ?? null;
            $distKm = $route['distanceKm'] ?? null;
            $etaSec = $route['durationSeconds'] ?? null;
            $etaText = $route['durationText'] ?? null;
            $etaMins = $route['etaMinutes'] ?? null;
        }

        $updatedRecord = [
            'technicianId' => $technicianId,
            'technicianName' => $tech ? $tech->full_name : ($currentRecord['technicianName'] ?? 'Technician'),
            'technicianPhone' => $tech ? $tech->phone : ($currentRecord['technicianPhone'] ?? null),
            'trackingStatus' => 'ON_THE_WAY',
            'activeDestination' => $destinationRecord,
            'currentLocation' => [
                'latitude' => $curLat,
                'longitude' => $curLng,
                'accuracy' => (float) ($currentRecord['currentLocation']['accuracy'] ?? ($tech ? $tech->current_accuracy ?? 10 : 10)),
                'heading' => (float) ($currentRecord['currentLocation']['heading'] ?? ($tech ? $tech->current_heading ?? 0 : 0)),
                'speed' => (float) ($currentRecord['currentLocation']['speed'] ?? ($tech ? $tech->current_speed ?? 0 : 0)),
                'timestamp' => $nowMs,
            ],
            'latitude' => $curLat,
            'longitude' => $curLng,
            'accuracy' => (float) ($currentRecord['accuracy'] ?? ($tech ? $tech->current_accuracy ?? 10 : 10)),
            'heading' => (float) ($currentRecord['heading'] ?? ($tech ? $tech->current_heading ?? 0 : 0)),
            'speed' => (float) ($currentRecord['speed'] ?? ($tech ? $tech->current_speed ?? 0 : 0)),
            'distanceMeters' => $distMeters ?? ($currentRecord['distanceMeters'] ?? null),
            'distanceText' => $distText ?? ($currentRecord['distanceText'] ?? null),
            'distanceKm' => $distKm ?? ($currentRecord['distanceKm'] ?? null),
            'etaSeconds' => $etaSec ?? ($currentRecord['etaSeconds'] ?? null),
            'etaText' => $etaText ?? ($currentRecord['etaText'] ?? null),
            'etaMinutes' => $etaMins ?? ($currentRecord['etaMinutes'] ?? null),
            'arrivalPendingSince' => null,
            'arrivalPendingSamples' => 0,
            'lastUpdate' => $nowMs,
            'freshness' => 'LIVE',
            'statusFreshness' => 'LIVE',
        ];

        // Store in temporary cache with 2-hour TTL
        Cache::put($destKey, $destinationRecord, self::STATE_TTL_SECONDS);
        Cache::put($locKey, $updatedRecord, self::STATE_TTL_SECONDS);
        $this->addToActiveSet($technicianId);

        // Update database technician model snapshot
        if ($tech) {
            $techUpdate = [
                'active_service_id' => $destinationRecord['serviceId'],
                'tracking_status' => 'ON_THE_WAY',
                'destination_latitude' => $destinationRecord['latitude'],
                'destination_longitude' => $destinationRecord['longitude'],
                'destination_address' => $destinationRecord['address'],
                'last_ping_at' => now(),
            ];
            if ($distKm !== null) $techUpdate['distance_km'] = $distKm;
            if ($etaMins !== null) $techUpdate['eta_minutes'] = $etaMins;
            if ($tech->current_latitude === null || $tech->current_longitude === null) {
                $techUpdate['current_latitude'] = $curLat;
                $techUpdate['current_longitude'] = $curLng;
            }
            $tech->update($techUpdate);
        }

        // Broadcast event
        event(new TechnicianLocationUpdated($updatedRecord));

        return $destinationRecord;
    }

    /**
     * Get active destination for an authenticated technician
     */
    public function getActiveDestination(string $technicianId): ?array
    {
        $destKey = self::KEY_PREFIX_DESTINATION . $technicianId;
        return Cache::get($destKey);
    }

    /**
     * Process and save a live GPS location ping for a technician.
     * Enforces temporary Redis/Cache state with 2-hour TTL, arrival detection, and route recalculation.
     */
    public function updateLocation(string $technicianId, string $technicianName, ?string $technicianPhone, array $ping): array
    {
        $locKey = self::KEY_PREFIX_LOCATION . $technicianId;

        // Retrieve active destination
        $destination = $this->getActiveDestination($technicianId);

        // Retrieve existing temporary location record
        $prevRecord = Cache::get($locKey);

        $trackingStatus = $prevRecord['trackingStatus'] ?? ($destination ? 'ON_THE_WAY' : 'IDLE');
        $distanceMeters = $prevRecord['distanceMeters'] ?? null;
        $distanceText = $prevRecord['distanceText'] ?? null;
        $distanceKm = $prevRecord['distanceKm'] ?? null;
        $etaSeconds = $prevRecord['etaSeconds'] ?? null;
        $etaText = $prevRecord['etaText'] ?? null;
        $etaMinutes = $prevRecord['etaMinutes'] ?? null;
        $arrivalPendingSince = $prevRecord['arrivalPendingSince'] ?? null;
        $arrivalPendingSamples = (int) ($prevRecord['arrivalPendingSamples'] ?? 0);

        $lat = (float) $ping['latitude'];
        $lng = (float) $ping['longitude'];
        $accuracy = (float) ($ping['accuracy'] ?? 10);
        $heading = isset($ping['heading']) ? (float) $ping['heading'] : 0.0;
        $speed = isset($ping['speed']) ? (float) $ping['speed'] : 0.0;
        $timestamp = isset($ping['timestamp']) ? (int) $ping['timestamp'] : (int) (microtime(true) * 1000);

        // Perform distance and arrival calculations if destination has valid coordinates
        if ($destination && !empty($destination['latitude']) && !empty($destination['longitude'])) {
            $destLat = (float) $destination['latitude'];
            $destLng = (float) $destination['longitude'];

            $straightLineDist = $this->calculateHaversineDistance($lat, $lng, $destLat, $destLng);

            // Arrival detection logic: Geofence ~100m
            if ($straightLineDist <= self::GEOFENCE_RADIUS_METERS) {
                if ($trackingStatus === 'ON_THE_WAY') {
                    $trackingStatus = 'ARRIVAL_PENDING';
                    $arrivalPendingSince = (int) (microtime(true) * 1000);
                    $arrivalPendingSamples = 1;
                } elseif ($trackingStatus === 'ARRIVAL_PENDING') {
                    $arrivalPendingSamples++;
                    $nowMs = (int) (microtime(true) * 1000);
                    $dwellSec = $arrivalPendingSince ? (int) floor(($nowMs - $arrivalPendingSince) / 1000) : 0;
                    if ($arrivalPendingSamples >= 2 || $dwellSec >= 8) {
                        $trackingStatus = 'AT_CUSTOMER';
                    }
                }
            } else {
                if ($trackingStatus === 'ARRIVAL_PENDING' && $straightLineDist > 150) {
                    $trackingStatus = 'ON_THE_WAY';
                    $arrivalPendingSince = null;
                    $arrivalPendingSamples = 0;
                }
            }

            // Road distance & ETA computation
            $route = $this->computeRoute($lat, $lng, $destLat, $destLng);
            $distanceMeters = $route['distanceMeters'];
            $distanceText = $route['distanceText'];
            $distanceKm = $route['distanceKm'];
            $etaSeconds = $route['durationSeconds'];
            $etaText = $route['durationText'];
            $etaMinutes = $route['etaMinutes'];
        }

        $nowMs = (int) (microtime(true) * 1000);

        $record = [
            'technicianId' => $technicianId,
            'technicianName' => $technicianName ?: ($prevRecord['technicianName'] ?? 'Technician'),
            'technicianPhone' => $technicianPhone ?? ($prevRecord['technicianPhone'] ?? null),
            'trackingStatus' => $trackingStatus,
            'activeDestination' => $destination,
            'currentLocation' => [
                'latitude' => $lat,
                'longitude' => $lng,
                'accuracy' => $accuracy,
                'heading' => $heading,
                'speed' => $speed,
                'timestamp' => $timestamp,
            ],
            'latitude' => $lat,
            'longitude' => $lng,
            'accuracy' => $accuracy,
            'heading' => $heading,
            'speed' => $speed,
            'distanceMeters' => $distanceMeters,
            'distanceText' => $distanceText,
            'distanceKm' => $distanceKm,
            'etaSeconds' => $etaSeconds,
            'etaText' => $etaText,
            'etaMinutes' => $etaMinutes,
            'arrivalPendingSince' => $arrivalPendingSince,
            'arrivalPendingSamples' => $arrivalPendingSamples,
            'lastUpdate' => $nowMs,
            'freshness' => 'LIVE',
            'statusFreshness' => 'LIVE',
        ];

        // Save in Cache with rolling 2-hour TTL
        Cache::put($locKey, $record, self::STATE_TTL_SECONDS);
        $this->addToActiveSet($technicianId);

        // Update database technician model snapshot
        $tech = Technician::find($technicianId);
        if ($tech) {
            $tech->update([
                'current_latitude' => $lat,
                'current_longitude' => $lng,
                'current_accuracy' => $accuracy,
                'current_heading' => $heading,
                'current_speed' => $speed,
                'tracking_status' => $trackingStatus,
                'distance_km' => $distanceKm,
                'eta_minutes' => $etaMinutes,
                'last_ping_at' => now(),
            ]);

            // Save recent breadcrumb point in technician_locations
            TechnicianLocation::create([
                'id' => (string) Str::uuid(),
                'technician_id' => $tech->id,
                'latitude' => $lat,
                'longitude' => $lng,
                'accuracy' => $accuracy,
                'speed' => $speed,
                'recorded_at' => now(),
            ]);
        }

        // Broadcast real-time update
        event(new TechnicianLocationUpdated($record));

        return $record;
    }

    /**
     * Get location record for a single technician
     */
    public function getLocation(string $technicianId): ?array
    {
        $locKey = self::KEY_PREFIX_LOCATION . $technicianId;
        $record = Cache::get($locKey);
        if (!$record) {
            return null;
        }

        $freshness = $this->evaluateFreshness($record['lastUpdate'] ?? null);
        $record['freshness'] = $freshness;
        $record['statusFreshness'] = $freshness;

        return $record;
    }

    /**
     * Clear temporary tracking state when navigation ends or technician arrives
     */
    public function clearTracking(string $technicianId): void
    {
        $locKey = self::KEY_PREFIX_LOCATION . $technicianId;
        $destKey = self::KEY_PREFIX_DESTINATION . $technicianId;

        Cache::forget($locKey);
        Cache::forget($destKey);
        $this->removeFromActiveSet($technicianId);

        $tech = Technician::find($technicianId);
        if ($tech) {
            $tech->update([
                'tracking_status' => 'IDLE',
                'active_service_id' => null,
                'destination_latitude' => null,
                'destination_longitude' => null,
                'destination_address' => null,
                'distance_km' => null,
                'eta_minutes' => null,
            ]);
        }

        event(new TechnicianTrackingStopped($technicianId));
    }

    /**
     * Clear all active tracking caches and sets across the system
     */
    public function clearAllTrackingCache(): void
    {
        $activeIds = Cache::get(self::KEY_ACTIVE_SET, []);
        if (is_array($activeIds)) {
            foreach ($activeIds as $techId) {
                Cache::forget(self::KEY_PREFIX_LOCATION . $techId);
                Cache::forget(self::KEY_PREFIX_DESTINATION . $techId);
            }
        }
        Cache::forget(self::KEY_ACTIVE_SET);
    }

    /**
     * Get all active and roster technicians for Admin Live Map
     */
    public function getAllActiveLocations(): array
    {
        $technicians = Technician::with(['activeService.customer.addresses', 'activeService.jobCard'])->get();
        $result = [];

        foreach ($technicians as $t) {
            $cached = $this->getLocation((string) $t->id);

            $service = $t->activeService;
            $jobCard = $service ? $service->jobCard : null;
            $customer = $service ? $service->customer : null;
            $address = $customer && $customer->addresses ? ($customer->primaryAddress ?? $customer->addresses->first()) : null;

            $hasCoords = ($cached && isset($cached['latitude']))
                ? true
                : ($t->current_latitude !== null && $t->current_longitude !== null);

            $lat = $cached['latitude'] ?? ($t->current_latitude !== null ? (float) $t->current_latitude : null);
            $lng = $cached['longitude'] ?? ($t->current_longitude !== null ? (float) $t->current_longitude : null);

            $lastPing = $cached['lastUpdate'] ?? ($t->last_ping_at ? $t->last_ping_at->timestamp * 1000 : null);
            $freshness = $cached['statusFreshness'] ?? $this->evaluateFreshness($lastPing);

            // Authoritative customer coordinates from database
            $custLat = ($address && $address->latitude !== null && (float) $address->latitude != 0.0) ? (float) $address->latitude : null;
            $custLng = ($address && $address->longitude !== null && (float) $address->longitude != 0.0) ? (float) $address->longitude : null;

            // Prioritize authoritative customer record if valid, fallback to active destination cache or tech record
            $destLat = $custLat ?? ($cached['activeDestination']['latitude'] ?? (($t->destination_latitude !== null && (float) $t->destination_latitude != 0.0) ? (float) $t->destination_latitude : null));
            $destLng = $custLng ?? ($cached['activeDestination']['longitude'] ?? (($t->destination_longitude !== null && (float) $t->destination_longitude != 0.0) ? (float) $t->destination_longitude : null));

            $destAddress = ($address && !empty(trim($address->full_address))) ? trim($address->full_address) : ($cached['activeDestination']['address'] ?? ($t->destination_address ?? null));
            $customerName = ($customer ? $customer->full_name : null) ?? ($cached['activeDestination']['customerName'] ?? null);

            $trackingStatus = $cached['trackingStatus'] ?? ($t->tracking_status ?: 'IDLE');

            $nowMs = (int) (microtime(true) * 1000);
            $secondsAgo = $lastPing ? max(0, (int) floor(($nowMs - $lastPing) / 1000)) : 999999;

            $distKm = $cached['distanceKm'] ?? ($t->distance_km !== null ? (float) $t->distance_km : null);
            $etaMins = $cached['etaMinutes'] ?? ($t->eta_minutes !== null ? (int) $t->eta_minutes : null);

            // Recent breadcrumbs
            $travelledPath = [];
            if ($hasCoords) {
                $travelled = TechnicianLocation::where('technician_id', $t->id)
                    ->where('recorded_at', '>=', now()->subHours(4))
                    ->orderBy('recorded_at', 'asc')
                    ->take(20)
                    ->get(['latitude', 'longitude', 'recorded_at']);

                foreach ($travelled as $p) {
                    $travelledPath[] = [
                        'latitude' => (float) $p->latitude,
                        'longitude' => (float) $p->longitude,
                        'timestamp' => $p->recorded_at ? $p->recorded_at->timestamp * 1000 : null,
                    ];
                }
            }

            $result[] = [
                'technicianId' => (string) $t->id,
                'technicianName' => $t->full_name,
                'technicianPhone' => $t->phone,
                'status' => $t->status,
                'trackingStatus' => $trackingStatus,
                'statusFreshness' => $freshness,
                'secondsAgo' => $secondsAgo,
                'latitude' => $hasCoords ? $lat : null,
                'longitude' => $hasCoords ? $lng : null,
                'accuracy' => (float) ($cached['accuracy'] ?? ($t->current_accuracy ?? 10)),
                'heading' => (float) ($cached['heading'] ?? ($t->current_heading ?? 0)),
                'speed' => (float) ($cached['speed'] ?? ($t->current_speed ?? 0)),
                'serviceId' => $service ? (string) $service->id : null,
                'serviceNumber' => $service ? $service->service_number : null,
                'jobCardId' => $jobCard ? (string) $jobCard->id : null,
                'jobCardNumber' => $jobCard ? $jobCard->job_card_number : null,
                'customerName' => $customerName,
                'customerAddress' => $destAddress,
                'customerLatitude' => $destLat,
                'customerLongitude' => $destLng,
                'scheduledTime' => $service ? $service->scheduled_time_slot : null,
                'distanceKm' => $distKm,
                'etaMinutes' => $etaMins,
                'distanceText' => $distKm !== null ? "{$distKm} km" : null,
                'etaText' => $etaMins !== null ? "{$etaMins} mins" : null,
                'routeStatus' => ($hasCoords && $destLat && $destLng) ? 'SUCCESS' : 'UNAVAILABLE',
                'travelledPath' => $travelledPath,
                'lastUpdated' => $lastPing ? date('c', (int) ($lastPing / 1000)) : now()->toIso8601String(),
            ];
        }

        return $result;
    }

    protected function addToActiveSet(string $technicianId): void
    {
        $set = Cache::get(self::KEY_ACTIVE_SET, []);
        if (!in_array($technicianId, $set)) {
            $set[] = $technicianId;
            Cache::put(self::KEY_ACTIVE_SET, $set, self::STATE_TTL_SECONDS);
        }
    }

    protected function removeFromActiveSet(string $technicianId): void
    {
        $set = Cache::get(self::KEY_ACTIVE_SET, []);
        $filtered = array_values(array_filter($set, fn ($id) => $id !== $technicianId));
        Cache::put(self::KEY_ACTIVE_SET, $filtered, self::STATE_TTL_SECONDS);
    }
}
