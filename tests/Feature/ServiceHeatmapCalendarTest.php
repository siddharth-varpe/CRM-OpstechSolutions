<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Service;
use App\Models\Technician;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceHeatmapCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->user = User::first() ?? User::factory()->create();
    }

    /**
     * Test services page renders the heatmap and month calendar elements.
     */
    public function test_services_page_renders_heatmap_and_calendar(): void
    {
        $response = $this->actingAs($this->user)->get(route('services.index'));

        $response->assertStatus(200);
        $response->assertSee('Daily Service Schedule Heatmap');
        $response->assertSee('id="serviceHeatmapContainer"', false);
        $response->assertSee('id="calendarDaysGrid"', false);
        $response->assertSee('id="selectedDateServicesSection"', false);
        $response->assertSee('id="btnCalPrevMonth"', false);
        $response->assertSee('id="btnCalNextMonth"', false);
        $response->assertSee('id="calMonthLabel"', false);
    }

    /**
     * Test calendar-heatmap JSON API returns month structure and daily service counts.
     */
    public function test_calendar_heatmap_api_returns_monthly_service_data(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('services.calendar_heatmap', [
            'year' => 2026,
            'month' => 10,
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'year' => 2026,
                'month' => 10,
                'days_in_month' => 31,
            ],
        ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('counts', $data);
        $this->assertArrayHasKey('services', $data);
        $this->assertEquals('October 2026', $data['month_name']);
    }

    /**
     * Test scheduling a service reflects in the heatmap date count and service list.
     */
    public function test_scheduled_service_shows_in_heatmap_for_specific_date(): void
    {
        $customer = Customer::first();
        $technician = Technician::first();
        $asset = \App\Models\CustomerAsset::first();
        $scheduledDate = Carbon::create(2026, 10, 24, 11, 0, 0);

        $service = Service::create([
            'service_number' => 'SRV-TEST-CAL-1',
            'customer_id' => $customer ? $customer->id : null,
            'asset_id' => $asset ? $asset->id : null,
            'technician_id' => $technician ? $technician->id : null,
            'service_type' => 'PERIODIC_MAINTENANCE',
            'status' => 'SCHEDULED',
            'priority' => 'HIGH',
            'scheduled_date' => $scheduledDate,
            'scheduled_time_slot' => '10:00 AM - 01:00 PM',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('services.calendar_heatmap', [
            'year' => 2026,
            'month' => 10,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');

        $dateKey = '2026-10-24';
        $this->assertArrayHasKey($dateKey, $data['counts']);
        $this->assertGreaterThanOrEqual(1, $data['counts'][$dateKey]);

        $servicesOnDate = $data['services'][$dateKey];
        $this->assertNotEmpty($servicesOnDate);

        $matching = collect($servicesOnDate)->firstWhere('service_number', 'SRV-TEST-CAL-1');
        $this->assertNotNull($matching);
        $this->assertEquals('HIGH', $matching['priority']);
        $this->assertEquals('10:00 AM - 01:00 PM', $matching['scheduled_time_slot']);
    }
}
