<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAsset;
use App\Models\JobCard;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceSchedule;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServiceDeleteTest extends TestCase
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
     * Helper to create a scheduled service with a job card.
     */
    protected function createScheduledService(): Service
    {
        $customer = Customer::first() ?? Customer::create([
            'id' => (string) Str::uuid(),
            'customer_number' => 'CUST-TEST-01',
            'full_name' => 'Test Customer',
            'phone' => '9876543210',
            'status' => 'ACTIVE',
        ]);

        $product = Product::first() ?? Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'RO Machine',
            'sku' => 'RO-001',
            'base_price' => 1000,
            'is_active' => true,
        ]);

        $asset = CustomerAsset::first() ?? CustomerAsset::create([
            'id' => (string) Str::uuid(),
            'asset_number' => 'AST-001',
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'serial_number' => 'SN-TEST-123',
            'status' => 'ACTIVE',
        ]);

        $technician = Technician::first();

        $service = Service::create([
            'id' => (string) Str::uuid(),
            'service_number' => 'SRV-TEST-' . rand(1000, 9999),
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'technician_id' => $technician ? $technician->id : null,
            'service_type' => 'PERIODIC_MAINTENANCE',
            'service_location' => 'DOORSTEP',
            'service_classification' => 'GENERAL',
            'scheduled_date' => now()->addDays(2),
            'scheduled_time_slot' => '10:00 AM - 01:00 PM',
            'status' => 'SCHEDULED',
            'priority' => 'NORMAL',
            'created_by' => $this->user->id,
        ]);

        JobCard::create([
            'id' => (string) Str::uuid(),
            'job_card_number' => 'JC-TEST-' . rand(1000, 9999),
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'technician_id' => $technician ? $technician->id : null,
            'status' => 'SCHEDULED',
            'labor_charges' => 0.00,
            'parts_charges' => 0.00,
            'total_charges' => 0.00,
        ]);

        return $service;
    }

    /**
     * Test services.index page renders the delete button for scheduled services.
     */
    public function test_services_index_renders_delete_button_for_scheduled_services(): void
    {
        $service = $this->createScheduledService();

        $response = $this->actingAs($this->user)->get(route('services.index'));

        $response->assertStatus(200);
        $response->assertSee('confirmDeleteService', false);
        $response->assertSee($service->service_number, false);
        $response->assertSee('Delete', false);
    }

    /**
     * Test services.show page renders the delete button for scheduled services.
     */
    public function test_services_show_renders_delete_button(): void
    {
        $service = $this->createScheduledService();

        $response = $this->actingAs($this->user)->get(route('services.show', $service->id));

        $response->assertStatus(200);
        $response->assertSee('Delete Service', false);
        $response->assertSee('confirmDeleteService', false);
    }

    /**
     * Test DELETE request successfully deletes scheduled service and associated job card.
     */
    public function test_delete_scheduled_service_via_delete_route(): void
    {
        $service = $this->createScheduledService();
        $serviceId = $service->id;
        $serviceNumber = $service->service_number;

        $this->assertDatabaseHas('services', ['id' => $serviceId]);
        $this->assertDatabaseHas('job_cards', ['service_id' => $serviceId]);

        $response = $this->actingAs($this->user)->deleteJson(route('services.destroy', $serviceId));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseMissing('services', ['id' => $serviceId]);
        $this->assertDatabaseMissing('job_cards', ['service_id' => $serviceId]);
    }

    /**
     * Test POST delete route also deletes scheduled service.
     */
    public function test_delete_scheduled_service_via_post_delete_route(): void
    {
        $service = $this->createScheduledService();
        $serviceId = $service->id;

        $response = $this->actingAs($this->user)->postJson(route('services.delete', $serviceId));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseMissing('services', ['id' => $serviceId]);
        $this->assertDatabaseMissing('job_cards', ['service_id' => $serviceId]);
    }

    /**
     * Test deleting a scheduled service cleans up linked technician and service schedules.
     */
    public function test_delete_service_cleans_up_linked_records(): void
    {
        $service = $this->createScheduledService();
        $technician = Technician::first();

        if ($technician) {
            $technician->update([
                'active_service_id' => $service->id,
                'tracking_status' => 'IN_TRANSIT',
            ]);
        }

        $serviceSchedule = ServiceSchedule::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $service->customer_id,
            'asset_id' => $service->asset_id,
            'schedule_index' => 1,
            'total_schedules' => 4,
            'planned_date' => now(),
            'target_month' => now()->format('Y-m'),
            'status' => 'SERVICE_CREATED',
            'generated_service_id' => $service->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson(route('services.destroy', $service->id));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('services', ['id' => $service->id]);

        if ($technician) {
            $this->assertDatabaseHas('technicians', [
                'id' => $technician->id,
                'active_service_id' => null,
            ]);
        }

        $this->assertDatabaseHas('service_schedules', [
            'id' => $serviceSchedule->id,
            'generated_service_id' => null,
            'status' => 'PENDING',
        ]);
    }
}
