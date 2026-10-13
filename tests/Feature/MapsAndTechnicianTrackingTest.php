<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Technician;
use App\Models\TechnicianLocation;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Service;
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
    }

    public function test_authenticated_user_can_access_technicians_roster_and_live_map(): void
    {
        $response = $this->actingAs($this->user)->get(route('technicians.index'));
        $response->assertStatus(200);
        $response->assertSee('Technicians &amp; Field Workforce', false);
        $response->assertSee($this->technician->full_name);

        $mapResponse = $this->actingAs($this->user)->get(route('technicians.map'));
        $mapResponse->assertStatus(200);
        $mapResponse->assertSee('Live Field Workforce Map');
        $mapResponse->assertSee('GPS STREAM LIVE');
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

        $response = $this->postJson(route('tech_portal.gps'), [
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

    public function test_technician_navigation_screen_loads(): void
    {
        if (!$this->service) {
            $this->markTestSkipped('No service ticket found for testing.');
        }

        $response = $this->get(route('tech_portal.navigation', $this->service->id));
        $response->assertStatus(200);
        $response->assertSee('Active Field Navigation');
        $response->assertSee($this->service->service_number);
        $response->assertSee('GPS Accuracy');
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
}
