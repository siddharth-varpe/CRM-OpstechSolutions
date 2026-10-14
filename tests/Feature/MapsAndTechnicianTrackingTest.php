<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Technician;
use App\Models\TechnicianLocation;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Service;
use App\Services\MapsTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MapsAndTechnicianTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Technician $technician;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->user = User::where('username', 'admin')->first();
        $this->technician = Technician::first();
        $this->service = Service::first();

        if ($this->service && $this->technician) {
            $this->service->update(['technician_id' => $this->technician->id]);
        }
    }

    public function test_authenticated_user_can_access_technicians_roster_and_live_map(): void
    {
        $response = $this->actingAs($this->user)->get(route('technicians.index'));
        $response->assertStatus(200);
        $response->assertSee('Technicians &amp; Field Workforce', false);
        $response->assertSee($this->technician->full_name);

        $mapResponse = $this->actingAs($this->user)->get(route('technicians.map'));
        $mapResponse->assertStatus(200);
        $mapResponse->assertSee('Live Field Map');
        $mapResponse->assertSee('adminLiveMapContainer');
    }

    public function test_admin_live_technicians_api_returns_structured_telemetry(): void
    {
        // Update technician with active location and destination
        $this->technician->update([
            'current_latitude' => 18.5089,
            'current_longitude' => 73.8550,
            'current_accuracy' => 12.0,
            'tracking_status' => 'ON_THE_WAY',
            'last_ping_at' => now(),
            'destination_latitude' => 18.5167,
            'destination_longitude' => 73.8415,
            'destination_address' => 'Sahyadri Hospital, Swargate, Pune',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('maps.admin.live'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'technicianId',
                    'technicianName',
                    'technicianPhone',
                    'trackingStatus',
                    'statusFreshness',
                    'latitude',
                    'longitude',
                    'customerLatitude',
                    'customerLongitude',
                    'distanceKm',
                    'etaMinutes',
                    'travelledPath',
                ],
            ],
        ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals('ON_THE_WAY', $data[0]['trackingStatus']);
        $this->assertEquals(18.5089, $data[0]['latitude']);
        $this->assertEquals(73.8550, $data[0]['longitude']);
    }

    public function test_admin_recalculate_route_endpoint(): void
    {
        $this->technician->update([
            'current_latitude' => 18.5089,
            'current_longitude' => 73.8550,
            'destination_latitude' => 18.5167,
            'destination_longitude' => 73.8415,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('maps.admin.recalculate'), [
            'technicianId' => $this->technician->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'technicianId' => $this->technician->id,
                'routeStatus' => 'SUCCESS',
            ],
        ]);
        $this->assertNotNull($response->json('data.distanceKm'));
        $this->assertNotNull($response->json('data.etaMinutes'));
    }

    public function test_technician_gps_ping_updates_coordinates_and_records_location(): void
    {
        $initialLocationsCount = TechnicianLocation::where('technician_id', $this->technician->id)->count();

        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.gps'), [
                'technician_id' => $this->technician->id,
                'latitude' => 18.5120,
                'longitude' => 73.8530,
                'accuracy' => 9.5,
                'speed' => 24.0,
                'heading' => 80.0,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'technicianId' => $this->technician->id,
            'statusFreshness' => 'LIVE',
        ]);

        $this->technician->refresh();
        $this->assertEquals(18.5120, $this->technician->current_latitude);
        $this->assertEquals(73.8530, $this->technician->current_longitude);
        $this->assertEquals(9.5, $this->technician->current_accuracy);

        $newLocationsCount = TechnicianLocation::where('technician_id', $this->technician->id)->count();
        $this->assertEquals($initialLocationsCount + 1, $newLocationsCount);
    }

    public function test_unauthenticated_user_cannot_access_technician_navigation(): void
    {
        $response = $this->get(route('tech_portal.navigation', $this->service->id));
        $response->assertStatus(302);
        $response->assertRedirect(route('tech_portal.login'));
    }

    public function test_technician_navigation_redirects_to_google_maps_with_customer_location(): void
    {
        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->get(route('tech_portal.navigation', $this->service->id));

        $response->assertStatus(302);
        $this->assertStringContainsString('https://www.google.com/maps/dir/?api=1&destination=', $response->headers->get('Location'));
    }

    public function test_technician_cannot_publish_location_for_another_technician_returns_403(): void
    {
        $otherTech = Technician::create([
            'full_name' => 'Other Field Engineer',
            'phone' => '9888877777',
            'status' => 'ACTIVE',
        ]);

        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.gps'), [
                'technician_id' => $otherTech->id,
                'latitude' => 18.5120,
                'longitude' => 73.8530,
            ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED_SPOOFING_REJECTED',
            ],
        ]);
    }

    public function test_invalid_coordinates_are_rejected_with_422(): void
    {
        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.gps'), [
                'latitude' => 999.0, // Invalid latitude (> 90)
                'longitude' => 73.8530,
            ]);

        $response->assertStatus(422);
    }

    public function test_technician_cannot_navigate_unassigned_service_returns_403(): void
    {
        $otherTech = Technician::create([
            'full_name' => 'Second Tech',
            'phone' => '9777766666',
            'status' => 'ACTIVE',
        ]);

        $unassignedService = Service::create([
            'service_number' => 'SRV-UNASSIGNED-01',
            'technician_id' => $otherTech->id,
            'customer_id' => $this->service->customer_id,
            'asset_id' => $this->service->asset_id,
            'service_type' => 'ROUTINE_SERVICE',
            'status' => 'SCHEDULED',
            'priority' => 'NORMAL',
            'scheduled_date' => now()->toDateString(),
        ]);

        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.navigate.start', $unassignedService->id));

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'FORBIDDEN',
            ],
        ]);
    }

    public function test_navigate_without_customer_coordinates_returns_400(): void
    {
        $customerWithoutCoords = Customer::create([
            'customer_number' => 'CUST-NO-GPS',
            'full_name' => 'No GPS Customer',
            'phone' => '9998881111',
            'status' => 'ACTIVE',
        ]);
        // Customer has no address record and no coordinates

        $serviceNoGps = Service::create([
            'service_number' => 'SRV-NO-GPS-01',
            'technician_id' => $this->technician->id,
            'customer_id' => $customerWithoutCoords->id,
            'asset_id' => $this->service->asset_id,
            'service_type' => 'ROUTINE_SERVICE',
            'status' => 'SCHEDULED',
            'priority' => 'NORMAL',
            'scheduled_date' => now()->toDateString(),
        ]);

        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.navigate.start', $serviceNoGps->id));

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'CUSTOMER_COORDINATES_MISSING',
            ],
        ]);
    }

    public function test_technician_can_navigate_registered_customer_with_address_string_only(): void
    {
        $customerWithAddress = Customer::create([
            'customer_number' => 'CUST-ADDR-01',
            'full_name' => 'Siddharth Address Test',
            'phone' => '9998882222',
            'status' => 'ACTIVE',
        ]);
        CustomerAddress::create([
            'customer_id' => $customerWithAddress->id,
            'address_line1' => 'Akash-Kiran Apartment, Phase-2',
            'landmark' => 'Ramkrishna Mangalkaryalay',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'postal_code' => '411061',
            'latitude' => null,
            'longitude' => null,
        ]);

        $serviceWithAddress = Service::create([
            'service_number' => 'SRV-ADDR-01',
            'technician_id' => $this->technician->id,
            'customer_id' => $customerWithAddress->id,
            'asset_id' => $this->service->asset_id,
            'service_type' => 'ROUTINE_SERVICE',
            'status' => 'SCHEDULED',
            'priority' => 'NORMAL',
            'scheduled_date' => now()->toDateString(),
        ]);

        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.navigate.start', $serviceWithAddress->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'trackingStatus' => 'ON_THE_WAY',
        ]);
        $this->assertStringContainsString('Akash-Kiran Apartment', $response->json('destination.address'));
        $this->assertStringContainsString('https://www.google.com/maps/dir/?api=1&destination=', $response->json('googleMapsUrl'));

        // Check navigation endpoint redirects directly to Google Maps with the address filled by admin
        $screenRes = $this->withSession(['technician_id' => $this->technician->id])
            ->get(route('tech_portal.navigation', $serviceWithAddress->id));
        $screenRes->assertStatus(302);
        $this->assertStringContainsString('https://www.google.com/maps/dir/?api=1&destination=', $screenRes->headers->get('Location'));
        $this->assertStringContainsString('Akash-Kiran', urldecode($screenRes->headers->get('Location')));
    }

    public function test_technician_dashboard_job_cards_have_google_maps_navigate_links_and_no_in_app_map(): void
    {
        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->get(route('tech_portal.dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('tabBtn-navigation');
        $response->assertDontSee('mapPortalNav');
        $response->assertDontSee('leaflet.js');
        $response->assertSee(route('tech_portal.navigation', $this->service->id));
        $response->assertSee('Navigate');
    }

    public function test_save_destination_coordinates_persists_lat_lng_to_customer_address(): void
    {
        $customer = Customer::create([
            'customer_number' => 'CUST-RESOLVE-01',
            'full_name' => 'Resolve Coords Customer',
            'phone' => '9998883333',
            'status' => 'ACTIVE',
        ]);
        $address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'address_line1' => 'Pimple Gurav Main Road',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'postal_code' => '411061',
            'latitude' => null,
            'longitude' => null,
        ]);

        $service = Service::create([
            'service_number' => 'SRV-RESOLVE-01',
            'technician_id' => $this->technician->id,
            'customer_id' => $customer->id,
            'asset_id' => $this->service->asset_id,
            'service_type' => 'ROUTINE_SERVICE',
            'status' => 'SCHEDULED',
            'priority' => 'NORMAL',
            'scheduled_date' => now()->toDateString(),
        ]);

        // Start navigation
        $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.navigate.start', $service->id));

        // Post resolved coordinates from client
        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.destination_coordinates', $service->id), [
                'latitude' => 18.5830,
                'longitude' => 73.8180,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'latitude' => 18.5830,
            'longitude' => 73.8180,
        ]);

        $address->refresh();
        $this->assertEquals(18.5830, $address->latitude);
        $this->assertEquals(73.8180, $address->longitude);
    }

    public function test_concurrency_conflict_when_already_navigating_another_service_returns_409(): void
    {
        // First navigation succeeds
        $firstService = $this->service;
        $custAddr = $firstService->customer->addresses->first();
        if (!$custAddr || !$custAddr->latitude) {
            CustomerAddress::create([
                'customer_id' => $firstService->customer_id,
                'address_line1' => 'Test Street',
                'city' => 'Pune',
                'latitude' => 18.5204,
                'longitude' => 73.8567,
            ]);
        }

        $res1 = $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.navigate.start', $firstService->id));
        $res1->assertStatus(200);

        // Create second assigned service with coordinates
        $customer2 = Customer::create([
            'customer_number' => 'CUST-TEST-002',
            'full_name' => 'Second Destination Customer',
            'phone' => '9991112222',
            'status' => 'ACTIVE',
        ]);
        CustomerAddress::create([
            'customer_id' => $customer2->id,
            'address_line1' => 'Kothrud',
            'city' => 'Pune',
            'latitude' => 18.5300,
            'longitude' => 73.8600,
        ]);
        $secondService = Service::create([
            'service_number' => 'SRV-TEST-003',
            'technician_id' => $this->technician->id,
            'customer_id' => $customer2->id,
            'asset_id' => $this->service->asset_id,
            'service_type' => 'ROUTINE_SERVICE',
            'status' => 'SCHEDULED',
            'priority' => 'NORMAL',
            'scheduled_date' => now()->toDateString(),
        ]);

        // Attempting to navigate second service without confirmSwitch triggers 409
        $res2 = $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.navigate.start', $secondService->id));

        $res2->assertStatus(409);
        $res2->assertJson([
            'success' => false,
            'error' => [
                'code' => 'ACTIVE_NAVIGATION_EXISTS',
            ],
        ]);

        // With confirmSwitch: true, it successfully switches
        $res3 = $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.navigate.start', $secondService->id), [
                'confirmSwitch' => true,
            ]);

        $res3->assertStatus(200);
        $this->assertEquals((string) $secondService->id, $res3->json('data.destination.serviceId'));
    }

    public function test_stop_navigation_clears_active_tracking_state(): void
    {
        $mapsService = app(MapsTrackingService::class);
        $mapsService->setActiveDestination((string) $this->technician->id, [
            'serviceId' => (string) $this->service->id,
            'customerName' => 'Test Customer',
            'latitude' => 18.5204,
            'longitude' => 73.8567,
        ]);

        $this->assertNotNull($mapsService->getActiveDestination((string) $this->technician->id));

        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.navigate.stop'), [
                'status' => 'ARRIVED',
            ]);

        $response->assertStatus(200);
        $this->assertNull($mapsService->getActiveDestination((string) $this->technician->id));
        $this->technician->refresh();
        $this->assertEquals('ARRIVED', $this->technician->tracking_status);
    }

    public function test_api_v1_maps_technician_active_endpoint(): void
    {
        $mapsService = app(MapsTrackingService::class);
        $mapsService->updateLocation((string) $this->technician->id, $this->technician->full_name, $this->technician->phone, [
            'latitude' => 18.5200,
            'longitude' => 73.8500,
            'accuracy' => 10.0,
            'timestamp' => now()->timestamp * 1000,
        ]);

        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->getJson(route('api.maps.technician.active'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertEquals(18.5200, $response->json('data.latitude'));
    }

    public function test_technician_360_profile_page_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('technicians.show', $this->technician->id));
        $response->assertStatus(200);
        $response->assertSee('Technician 360° Profile');
        $response->assertSee($this->technician->full_name);
        $response->assertSee('Assigned Services');
        $response->assertSee('Job Cards');
        $response->assertSee('Billing &amp; Revenue', false);
    }

    public function test_reset_technician_portal_pin(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('technicians.reset_pin', $this->technician->id), [
            'pin' => '4321',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Technician Portal PIN updated successfully.',
        ]);
    }

    public function test_api_360_profile_returns_json(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('technicians.api.360', $this->technician->id));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'technician' => ['id', 'fullName', 'phone'],
                'workSummary' => ['totalAssigned', 'completedCount', 'inProgressCount'],
                'financialSummary' => ['totalLaborBilled', 'totalPartsBilled', 'totalRevenue'],
            ],
        ]);
    }

    public function test_navigate_button_click_activates_route_and_shows_on_admin_map(): void
    {
        // 1. Ensure customer address has coordinates
        $customerAddress = $this->service->customer->addresses->first();
        if ($customerAddress) {
            $customerAddress->update([
                'latitude' => 18.5300,
                'longitude' => 73.8400,
            ]);
        }

        // 2. Technician clicks "Navigate" button (with device GPS coordinates)
        $navResponse = $this->withSession(['technician_id' => $this->technician->id])
            ->get(route('tech_portal.navigation', [
                'id' => $this->service->id,
                'lat' => 18.5100,
                'lng' => 73.8500,
            ]));

        // Technician is redirected to Google Maps with customer destination
        $navResponse->assertStatus(302);
        $locationHeader = $navResponse->headers->get('Location');
        $this->assertStringContainsString('https://www.google.com/maps/dir/?api=1&destination=', $locationHeader);

        // 3. Technician state is now ON_THE_WAY with active service and destination
        $this->technician->refresh();
        $this->assertEquals('ON_THE_WAY', $this->technician->tracking_status);
        $this->assertEquals($this->service->id, $this->technician->active_service_id);
        $this->assertEquals(18.5100, $this->technician->current_latitude);
        $this->assertEquals(73.8500, $this->technician->current_longitude);
        $this->assertEquals(18.5300, $this->technician->destination_latitude);
        $this->assertEquals(73.8400, $this->technician->destination_longitude);

        // 4. Admin Live Map endpoint returns the technician with live route information
        $adminMapResponse = $this->actingAs($this->user)->getJson(route('maps.admin.live'));
        $adminMapResponse->assertStatus(200);

        $techData = collect($adminMapResponse->json('data'))->firstWhere('technicianId', (string) $this->technician->id);
        $this->assertNotNull($techData);
        $this->assertEquals('ON_THE_WAY', $techData['trackingStatus']);
        $this->assertEquals(18.5100, $techData['latitude']);
        $this->assertEquals(73.8500, $techData['longitude']);
        $this->assertEquals(18.5300, $techData['customerLatitude']);
        $this->assertEquals(73.8400, $techData['customerLongitude']);
        $this->assertEquals('SUCCESS', $techData['routeStatus']);
        $this->assertNotNull($techData['distanceKm']);
        $this->assertNotNull($techData['etaMinutes']);

        // 5. Admin Map Blade view contains route telemetry HUD, turn-by-turn directions panel, and map legend
        $mapViewResponse = $this->actingAs($this->user)->get(route('technicians.map'));
        $mapViewResponse->assertStatus(200);
        $mapViewResponse->assertSee('activeRouteBanner');
        $mapViewResponse->assertSee('routeTechName');
        $mapViewResponse->assertSee('routeDistance');
        $mapViewResponse->assertSee('turnByTurnContainer');
        $mapViewResponse->assertSee('btnToggleTurnByTurn');
        $mapViewResponse->assertSee('turnByTurnStepsList');
        $mapViewResponse->assertSee('Planned Route');
        $mapViewResponse->assertSee('Observed GPS Trail');
    }

    public function test_admin_map_renders_turn_by_turn_panel_and_observed_gps_trail_elements(): void
    {
        $response = $this->actingAs($this->user)->get(route('technicians.map'));
        $response->assertStatus(200);
        $response->assertSee('turnByTurnContainer');
        $response->assertSee('btnToggleTurnByTurn');
        $response->assertSee('turnByTurnStepsList');
        $response->assertSee('routeNextManeuverBox');
        $response->assertSee('Planned Route');
        $response->assertSee('Observed GPS Trail');
    }

    public function test_observed_gps_trail_telemetry_records_genuine_coordinates(): void
    {
        // Setup customer address coordinates
        $address = $this->service->customer->addresses->first();
        if ($address) {
            $address->update(['latitude' => 18.5300, 'longitude' => 73.8400]);
        }

        // Start navigation
        $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.navigate.start', $this->service->id), ['confirmSwitch' => true]);

        // Send two successive valid GPS pings
        $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.gps'), [
                'latitude' => 18.5100,
                'longitude' => 73.8500,
                'accuracy' => 8.5,
            ]);

        $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.gps'), [
                'latitude' => 18.5150,
                'longitude' => 73.8480,
                'accuracy' => 6.0,
            ]);

        // Fetch live telemetry for Admin Map
        $adminRes = $this->actingAs($this->user)->getJson(route('maps.admin.live'));
        $adminRes->assertStatus(200);

        $tech = collect($adminRes->json('data'))->firstWhere('technicianId', (string) $this->technician->id);
        $this->assertNotNull($tech);
        $this->assertArrayHasKey('travelledPath', $tech);
        $this->assertIsArray($tech['travelledPath']);
        $this->assertGreaterThanOrEqual(1, count($tech['travelledPath']));

        // Verify latest coordinate matches genuine transmitted point
        $this->assertEquals(18.5150, $tech['latitude']);
        $this->assertEquals(73.8480, $tech['longitude']);
    }

    public function test_technician_dashboard_contains_active_navigation_hud(): void
    {
        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->get(route('tech_portal.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('activeNavHud');
        $response->assertSee('activeNavServiceNum');
        $response->assertSee('stopActiveNavigation');
        $response->assertSee('I Have Arrived');
    }

    public function test_admin_route_geometry_endpoint_returns_road_coordinates(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('maps.admin.route_geometry', [
            'originLat' => 18.5204,
            'originLng' => 73.8567,
            'destLat' => 18.5833,
            'destLng' => 73.8200,
        ]));

        $response->assertStatus(200);
        $this->assertEquals('Ok', $response->json('code'));
        $this->assertNotEmpty($response->json('routes'));
        $this->assertNotEmpty($response->json('routes.0.geometry.coordinates'));
    }
}
