<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAsset;
use App\Models\Product;
use App\Models\Service;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCustomerAssetTest extends TestCase
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
     * Test services index renders the customer asset select and custom asset name field.
     */
    public function test_services_index_renders_customer_asset_elements(): void
    {
        $response = $this->actingAs($this->user)->get(route('services.index'));

        $response->assertStatus(200);
        $response->assertSee('id="serviceCustomerSelect"', false);
        $response->assertSee('id="serviceAssetSelect"', false);
        $response->assertSee('id="customAssetNameInput"', false);
        $response->assertSee('name="custom_asset_name"', false);
    }

    /**
     * Test fetching assets for a customer via AJAX endpoint.
     */
    public function test_customer_assets_api_endpoint_lists_customer_assets(): void
    {
        $customer = Customer::first();
        $this->assertNotNull($customer);

        $response = $this->actingAs($this->user)->getJson(route('customers.assets.list', [
            'customerId' => $customer->id,
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'count',
            'assets',
        ]);
    }

    /**
     * Test scheduling a service with directly typed asset name creates asset and service.
     */
    public function test_schedule_service_with_directly_typed_custom_asset_name(): void
    {
        $customer = Customer::first();
        $technician = Technician::first();

        $postData = [
            'customer_id' => $customer->id,
            'custom_asset_name' => 'Kent Grand Plus RO (Typed By Admin)',
            'custom_asset_serial' => 'KENT-CUSTOM-1234',
            'service_type' => 'PERIODIC_MAINTENANCE',
            'scheduled_date' => now()->addDays(2)->format('Y-m-d'),
            'scheduled_time_slot' => '10:00 AM - 01:00 PM',
            'priority' => 'HIGH',
            'technician_id' => $technician ? $technician->id : null,
            'customer_notes' => 'Customer reported lower flow',
        ];

        $response = $this->actingAs($this->user)->postJson(route('services.store'), $postData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // Assert asset was automatically created for this customer
        $this->assertDatabaseHas('customer_assets', [
            'customer_id' => $customer->id,
            'custom_name' => 'Kent Grand Plus RO (Typed By Admin)',
            'serial_number' => 'KENT-CUSTOM-1234',
        ]);

        // Assert service was created referencing the new asset
        $asset = CustomerAsset::where('custom_name', 'Kent Grand Plus RO (Typed By Admin)')->first();
        $this->assertNotNull($asset);

        $this->assertDatabaseHas('services', [
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'priority' => 'HIGH',
        ]);

        $this->assertDatabaseHas('job_cards', [
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
        ]);
    }

    /**
     * Test scheduling a service with existing asset_id still works as expected.
     */
    public function test_schedule_service_with_existing_asset_id(): void
    {
        $customer = Customer::first();
        $asset = CustomerAsset::first();

        // Ensure asset belongs to customer for this test
        if ($asset && $customer) {
            $asset->customer_id = $customer->id;
            $asset->save();
        }

        $postData = [
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'service_type' => 'REPAIR',
            'scheduled_date' => now()->addDays(1)->format('Y-m-d'),
            'scheduled_time_slot' => '02:00 PM - 05:00 PM',
            'priority' => 'NORMAL',
            'customer_notes' => 'General checkup',
        ];

        $response = $this->actingAs($this->user)->postJson(route('services.store'), $postData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('services', [
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'service_type' => 'REPAIR',
        ]);
    }
}
