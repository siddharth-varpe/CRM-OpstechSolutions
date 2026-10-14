<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Role;
use App\Models\Service;
use App\Models\Technician;
use App\Models\TechnicianPortalAccess;
use App\Models\User;
use App\Services\MapsTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerPrecisionLocationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Technician $technician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->adminUser = User::where('username', 'admin')->first() ?? User::first();

        $this->technician = Technician::first() ?? Technician::create([
            'phone' => '9888877771',
            'full_name' => 'Precision Field Tech',
            'email' => 'tech_loc_test@enterprises.test',
            'status' => 'ACTIVE',
            'current_latitude' => 18.5204,
            'current_longitude' => 73.8567,
        ]);

        TechnicianPortalAccess::firstOrCreate(
            ['technician_id' => $this->technician->id],
            [
                'portal_enabled' => true,
                'pin_hash' => Hash::make('1234'),
            ]
        );
    }

    /**
     * Test A: Customer Location Selection & Storage with Double Precision
     */
    public function test_customer_creation_and_update_preserves_exact_coordinates(): void
    {
        $testLat = 18.5912345;
        $testLng = 73.7912345;

        // 1. Create customer with exact coordinates
        $response = $this->actingAs($this->adminUser)->postJson(route('customers.store'), [
            'full_name' => 'Precision Customer Test',
            'phone' => '9899001122',
            'email' => 'precision_cust@example.test',
            'customer_type' => 'INDIVIDUAL',
            'status' => 'ACTIVE',
            'address_line1' => 'Plot 42, Sunrise IT Park',
            'address_line2' => 'Hinjawadi Phase 1',
            'landmark' => 'Opposite Tech Mahindra',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'postal_code' => '411057',
            'latitude' => $testLat,
            'longitude' => $testLng,
        ]);

        $response->assertStatus(200);
        $customer = Customer::where('phone', '9899001122')->firstOrFail();
        $address = $customer->primaryAddress;

        $this->assertNotNull($address);
        $this->assertEqualsWithDelta($testLat, (float) $address->latitude, 0.0000001);
        $this->assertEqualsWithDelta($testLng, (float) $address->longitude, 0.0000001);

        // 2. Update customer coordinates to another exact point
        $updatedLat = 18.6098765;
        $updatedLng = 73.8154321;

        $updateResponse = $this->actingAs($this->adminUser)->putJson(route('customers.update', $customer->id), [
            'full_name' => 'Precision Customer Test Updated',
            'phone' => '9899001122',
            'customer_type' => 'INDIVIDUAL',
            'status' => 'ACTIVE',
            'address_line1' => 'Unit 101, Blue Ridge Complex',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'postal_code' => '411057',
            'latitude' => $updatedLat,
            'longitude' => $updatedLng,
        ]);

        $updateResponse->assertStatus(200);
        $address->refresh();

        $this->assertEqualsWithDelta($updatedLat, (float) $address->latitude, 0.0000001);
        $this->assertEqualsWithDelta($updatedLng, (float) $address->longitude, 0.0000001);
    }

    /**
     * Test B: Technician Navigation uses authoritative saved coordinates
     */
    public function test_navigate_generates_exact_coordinates_google_maps_url(): void
    {
        $testLat = 18.5743210;
        $testLng = 73.7812340;

        $customer = Customer::create([
            'customer_number' => 'CUST-TEST-' . Str::random(5),
            'full_name' => 'Navigate Target Customer',
            'phone' => '9811223344',
            'customer_type' => 'INDIVIDUAL',
            'status' => 'ACTIVE',
        ]);

        CustomerAddress::create([
            'customer_id' => $customer->id,
            'address_type' => 'SERVICE',
            'address_line1' => 'Survey 55, Wakad Chowk',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'postal_code' => '411057',
            'latitude' => $testLat,
            'longitude' => $testLng,
            'is_default' => true,
        ]);

        $asset = \App\Models\CustomerAsset::first();

        $service = Service::create([
            'service_number' => 'SRV-TEST-' . Str::random(5),
            'customer_id' => $customer->id,
            'asset_id' => $asset ? $asset->id : (string) Str::uuid(),
            'technician_id' => $this->technician->id,
            'service_type' => 'PREVENTIVE_MAINTENANCE',
            'service_location' => 'ON_SITE',
            'service_classification' => 'MEMBRANE_CHECK',
            'scheduled_date' => now(),
            'scheduled_time_slot' => '10:00 AM - 12:00 PM',
            'status' => 'ASSIGNED',
            'priority' => 'HIGH',
        ]);

        // Start Navigation as the assigned technician
        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.navigate.start', $service->id), [
                'confirmSwitch' => 1,
            ]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertTrue($json['success']);
        $this->assertStringContainsString('https://www.google.com/maps/dir/?api=1', $json['googleMapsUrl']);
        $this->assertStringContainsString("destination={$testLat},{$testLng}", $json['googleMapsUrl']);
        $this->assertStringContainsString('travelmode=driving', $json['googleMapsUrl']);

        $destination = $json['destination'];
        $this->assertEqualsWithDelta($testLat, (float) $destination['latitude'], 0.0000001);
        $this->assertEqualsWithDelta($testLng, (float) $destination['longitude'], 0.0000001);
    }

    /**
     * Test C: Admin Live Map receives exact authoritative customer coordinates
     */
    public function test_admin_live_map_destination_marker_matches_customer_coordinates(): void
    {
        $testLat = 18.5432100;
        $testLng = 73.8234500;

        $customer = Customer::create([
            'customer_number' => 'CUST-ADMIN-' . Str::random(5),
            'full_name' => 'Admin Live Map Target',
            'phone' => '9822334455',
            'customer_type' => 'INDIVIDUAL',
            'status' => 'ACTIVE',
        ]);

        CustomerAddress::create([
            'customer_id' => $customer->id,
            'address_type' => 'SERVICE',
            'address_line1' => 'Plot 88, Kothrud Depot',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'postal_code' => '411038',
            'latitude' => $testLat,
            'longitude' => $testLng,
            'is_default' => true,
        ]);

        $asset = \App\Models\CustomerAsset::first();

        $service = Service::create([
            'service_number' => 'SRV-ADMIN-' . Str::random(5),
            'customer_id' => $customer->id,
            'asset_id' => $asset ? $asset->id : (string) Str::uuid(),
            'technician_id' => $this->technician->id,
            'service_type' => 'INSTALLATION',
            'service_location' => 'ON_SITE',
            'service_classification' => 'COMMERCIAL_RO',
            'scheduled_date' => now(),
            'status' => 'ASSIGNED',
            'priority' => 'HIGH',
        ]);

        $this->technician->update([
            'active_service_id' => $service->id,
            'tracking_status' => 'ON_THE_WAY',
            'destination_latitude' => $testLat,
            'destination_longitude' => $testLng,
            'current_latitude' => 18.5204,
            'current_longitude' => 73.8567,
        ]);

        $mapsService = app(MapsTrackingService::class);
        $activeLocations = $mapsService->getAllActiveLocations();

        $techData = collect($activeLocations)->firstWhere('technicianId', (string) $this->technician->id);

        $this->assertNotNull($techData);
        $this->assertEqualsWithDelta($testLat, (float) $techData['customerLatitude'], 0.0000001);
        $this->assertEqualsWithDelta($testLng, (float) $techData['customerLongitude'], 0.0000001);
    }

    /**
     * Test D: Customer Coordinate Update immediately synchronizes active navigation destination
     */
    public function test_customer_coordinate_update_syncs_active_navigation_destination(): void
    {
        $initialLat = 18.5100000;
        $initialLng = 73.8400000;

        $customer = Customer::create([
            'customer_number' => 'CUST-SYNC-' . Str::random(5),
            'full_name' => 'Sync Destination Customer',
            'phone' => '9833445566',
            'customer_type' => 'INDIVIDUAL',
            'status' => 'ACTIVE',
        ]);

        $address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'address_type' => 'SERVICE',
            'address_line1' => 'Shivajinagar Station Road',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'postal_code' => '411005',
            'latitude' => $initialLat,
            'longitude' => $initialLng,
            'is_default' => true,
        ]);

        $asset = \App\Models\CustomerAsset::first();

        $service = Service::create([
            'service_number' => 'SRV-SYNC-' . Str::random(5),
            'customer_id' => $customer->id,
            'asset_id' => $asset ? $asset->id : (string) Str::uuid(),
            'technician_id' => $this->technician->id,
            'service_type' => 'EMERGENCY_REPAIR',
            'service_location' => 'ON_SITE',
            'service_classification' => 'REPAIR',
            'scheduled_date' => now(),
            'status' => 'IN_PROGRESS',
            'priority' => 'HIGH',
        ]);

        $mapsService = app(MapsTrackingService::class);
        $mapsService->setActiveDestination((string) $this->technician->id, [
            'serviceId' => (string) $service->id,
            'serviceNumber' => $service->service_number,
            'customerId' => (string) $customer->id,
            'customerName' => $customer->full_name,
            'address' => $address->full_address,
            'latitude' => $initialLat,
            'longitude' => $initialLng,
        ]);

        // Customer pin is updated by admin
        $newLat = 18.5350000;
        $newLng = 73.8650000;

        $this->actingAs($this->adminUser)->putJson(route('customers.update', $customer->id), [
            'full_name' => $customer->full_name,
            'phone' => $customer->phone,
            'customer_type' => 'INDIVIDUAL',
            'status' => 'ACTIVE',
            'address_line1' => 'JM Road, Deccan Gymkhana',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'postal_code' => '411004',
            'latitude' => $newLat,
            'longitude' => $newLng,
        ])->assertStatus(200);

        // Verify active destination in cache and Admin Map reflects the new coordinates
        $updatedDest = $mapsService->getActiveDestination((string) $this->technician->id);
        $this->assertNotNull($updatedDest);
        $this->assertEqualsWithDelta($newLat, (float) $updatedDest['latitude'], 0.0000001);
        $this->assertEqualsWithDelta($newLng, (float) $updatedDest['longitude'], 0.0000001);

        $activeLocations = $mapsService->getAllActiveLocations();
        $techData = collect($activeLocations)->firstWhere('technicianId', (string) $this->technician->id);
        $this->assertEqualsWithDelta($newLat, (float) $techData['customerLatitude'], 0.0000001);
        $this->assertEqualsWithDelta($newLng, (float) $techData['customerLongitude'], 0.0000001);
    }

    /**
     * Test E: Error Conditions - Missing coordinates & address fails safely
     */
    public function test_missing_destination_coordinates_and_address_fails_safely(): void
    {
        $customer = Customer::create([
            'customer_number' => 'CUST-EMPTY-' . Str::random(5),
            'full_name' => 'Empty Destination Customer',
            'phone' => '9844556677',
            'customer_type' => 'INDIVIDUAL',
            'status' => 'ACTIVE',
        ]);

        // Customer with blank address and null coordinates
        CustomerAddress::create([
            'customer_id' => $customer->id,
            'address_type' => 'SERVICE',
            'address_line1' => '',
            'city' => '',
            'state' => '',
            'postal_code' => '',
            'latitude' => null,
            'longitude' => null,
            'is_default' => true,
        ]);

        $asset = \App\Models\CustomerAsset::first();

        $service = Service::create([
            'service_number' => 'SRV-EMPTY-' . Str::random(5),
            'customer_id' => $customer->id,
            'asset_id' => $asset ? $asset->id : (string) Str::uuid(),
            'technician_id' => $this->technician->id,
            'service_type' => 'FILTER_REPLACEMENT',
            'service_location' => 'ON_SITE',
            'service_classification' => 'FILTERS',
            'scheduled_date' => now(),
            'status' => 'ASSIGNED',
            'priority' => 'MEDIUM',
        ]);

        $response = $this->withSession(['technician_id' => $this->technician->id])
            ->postJson(route('tech_portal.navigate.start', $service->id), [
                'confirmSwitch' => 1,
            ]);

        $response->assertStatus(400);
        $json = $response->json();
        $this->assertFalse($json['success']);
        $this->assertEquals('CUSTOMER_COORDINATES_MISSING', $json['error']['code']);
    }

    /**
     * Test F: Validation rejects out-of-range coordinates
     */
    public function test_validation_rejects_out_of_range_coordinates(): void
    {
        $response = $this->actingAs($this->adminUser)->postJson(route('customers.store'), [
            'full_name' => 'Invalid Range Customer',
            'phone' => '9855667788',
            'customer_type' => 'INDIVIDUAL',
            'status' => 'ACTIVE',
            'address_line1' => 'Outer Space Road',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'postal_code' => '411001',
            'latitude' => 195.0, // Invalid: exceeds 90
            'longitude' => 200.0, // Invalid: exceeds 180
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['latitude', 'longitude']);
    }
}
