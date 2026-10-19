<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Technician;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class ServiceScheduleCustomerSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->admin = User::where('role', 'SUPER_ADMIN')->first() ?? User::first();
    }

    public function test_service_schedule_modal_contains_customer_search_bar(): void
    {
        $response = $this->actingAs($this->admin)->get(route('services.index'));

        $response->assertStatus(200);

        // Assert customer search bar elements exist in the view
        $response->assertSee('id="serviceCustomerSearch"', false);
        $response->assertSee('id="btnClearServiceCustomerSearch"', false);
        $response->assertSee('id="serviceCustomerSearchHelp"', false);
        $response->assertSee('placeholder="Search customer by name, phone, or customer #..."', false);
        $response->assertSee('id="serviceCustomerSelect"', false);
        $response->assertSee('data-search=', false);
    }

    public function test_service_schedule_submission_with_registered_customer_succeeds(): void
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'customer_number' => 'CUST-SEARCH-01',
            'full_name' => 'Searchable VIP Client',
            'phone' => '9988776655',
            'status' => 'ACTIVE',
            'customer_type' => 'COMMERCIAL',
        ]);

        $technician = Technician::first();

        $postData = [
            'customer_id' => $customer->id,
            'custom_asset_name' => 'Custom RO 100 LPH Plant',
            'service_type' => 'PERIODIC_MAINTENANCE',
            'priority' => 'HIGH',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time_slot' => '10:00 AM - 01:00 PM',
            'technician_id' => $technician ? $technician->id : null,
            'service_location' => 'DOORSTEP',
        ];

        $response = $this->actingAs($this->admin)->post(route('services.store'), $postData);

        $response->assertStatus(302);
        $this->assertDatabaseHas('services', [
            'customer_id' => $customer->id,
            'service_type' => 'PERIODIC_MAINTENANCE',
        ]);
    }
}
