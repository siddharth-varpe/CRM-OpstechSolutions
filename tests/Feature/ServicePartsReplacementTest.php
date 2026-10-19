<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAsset;
use App\Models\InventoryItem;
use App\Models\JobCard;
use App\Models\Product;
use App\Models\Service;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServicePartsReplacementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Technician $technician;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->user = User::first() ?? User::factory()->create();
        $this->technician = Technician::first() ?? Technician::create([
            'id' => (string) Str::uuid(),
            'technician_number' => 'TECH-001',
            'full_name' => 'John Service Tech',
            'phone' => '9988776655',
            'email' => 'tech@srenterprises.com',
            'pin' => bcrypt('1234'),
            'status' => 'ACTIVE',
        ]);
    }

    protected function createAssignedService(): Service
    {
        $customer = Customer::first() ?? Customer::create([
            'id' => (string) Str::uuid(),
            'customer_number' => 'CUST-PARTS-01',
            'full_name' => 'Amit Sharma',
            'phone' => '9876543210',
            'status' => 'ACTIVE',
        ]);

        $product = Product::first() ?? Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'SR Water Purifier Pro',
            'sku' => 'SR-PRO-01',
            'base_price' => 12500,
            'is_active' => true,
        ]);

        $asset = CustomerAsset::first() ?? CustomerAsset::create([
            'id' => (string) Str::uuid(),
            'asset_number' => 'AST-P01',
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'serial_number' => 'SN-PART-001',
            'status' => 'ACTIVE',
        ]);

        $service = Service::create([
            'id' => (string) Str::uuid(),
            'service_number' => 'SRV-TEST-' . rand(1000, 9999),
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'technician_id' => $this->technician->id,
            'service_type' => 'REPAIR',
            'service_location' => 'DOORSTEP',
            'service_classification' => 'GENERAL',
            'scheduled_date' => now(),
            'scheduled_time_slot' => '10:00 AM - 01:00 PM',
            'status' => 'ASSIGNED',
            'priority' => 'NORMAL',
            'created_by' => $this->user->id,
        ]);

        JobCard::create([
            'id' => (string) Str::uuid(),
            'job_card_number' => 'JC-TEST-' . rand(1000, 9999),
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'technician_id' => $this->technician->id,
            'status' => 'ASSIGNED',
            'labor_charges' => 350.00,
            'parts_charges' => 0.00,
            'total_charges' => 350.00,
        ]);

        return $service->fresh(['jobCard', 'customer', 'asset']);
    }

    /**
     * Test technician completes a service with catalogue and new custom parts with custom quantity & price.
     */
    public function test_technician_can_submit_catalogue_and_custom_parts_with_quantities_and_prices(): void
    {
        $service = $this->createAssignedService();

        // Simulate technician authenticated session
        session(['technician_id' => $this->technician->id]);

        $partsPayload = [
            0 => [
                'name' => 'High-Flow Filter 75 GPD Dow',
                'quantity' => 2,
                'price' => 2200.00,
                'total' => 4400.00,
            ],
            1 => [
                'name' => 'Custom Mineral Alkaline Filter 12-inch',
                'quantity' => 1,
                'price' => 850.00,
                'total' => 850.00,
            ],
        ];

        $response = $this->post(route('tech_portal.complete', $service->id), [
            'diagnosis' => 'Water pressure drop and foul taste',
            'work_performed' => 'Replaced membrane filters, sanitised housing, calibrated booster pump',
            'inlet_tds' => 450,
            'outlet_tds' => 35,
            'parts_replaced' => $partsPayload,
            'labor_charges' => 350.00,
            'parts_charges' => 5250.00,
            'customer_remarks' => 'Good output flow, water tastes sweet',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $service->refresh();
        $this->assertEquals('COMPLETED', $service->status);

        $jobCard = $service->jobCard->fresh();
        $this->assertEquals('COMPLETED', $jobCard->status);
        $this->assertEquals(5250.00, (float)$jobCard->parts_charges);
        $this->assertEquals(5600.00, (float)$jobCard->total_charges); // 350 labor + 5250 parts

        // Verify structured parts array stored in JobCard
        $formattedParts = $jobCard->formatted_parts;
        $this->assertCount(2, $formattedParts);
        $this->assertEquals('High-Flow Filter 75 GPD Dow', $formattedParts[0]['name']);
        $this->assertEquals(2, $formattedParts[0]['quantity']);
        $this->assertEquals(2200.00, $formattedParts[0]['price']);
        $this->assertEquals(4400.00, $formattedParts[0]['total']);

        $this->assertEquals('Custom Mineral Alkaline Filter 12-inch', $formattedParts[1]['name']);
        $this->assertEquals(1, $formattedParts[1]['quantity']);
        $this->assertEquals(850.00, $formattedParts[1]['price']);
        $this->assertEquals(850.00, $formattedParts[1]['total']);
    }

    /**
     * Test admin service view displays the itemized parts breakdown table with quantity and price.
     */
    public function test_admin_service_show_renders_parts_breakdown_with_quantities_and_prices(): void
    {
        $service = $this->createAssignedService();
        $jobCard = $service->jobCard;

        $jobCard->update([
            'parts_replaced' => [
                ['name' => 'Kemflo Booster Pump 100 GPD', 'quantity' => 1, 'price' => 2800.00, 'total' => 2800.00],
                ['name' => 'Spun Sediment Filter 10-inch', 'quantity' => 3, 'price' => 250.00, 'total' => 750.00],
            ],
            'labor_charges' => 350.00,
            'parts_charges' => 3550.00,
            'total_charges' => 3900.00,
            'status' => 'COMPLETED',
        ]);

        $response = $this->actingAs($this->user)->get(route('services.show', $service->id));
        $response->assertStatus(200);

        // Check that parts table elements exist
        $response->assertSee('Parts Replaced On-Site');
        $response->assertSee('Kemflo Booster Pump 100 GPD');
        $response->assertSee('Spun Sediment Filter 10-inch');
        $response->assertSee('2,800.00');
        $response->assertSee('250.00');
        $response->assertSee('3,550.00');
        $response->assertSee('Manage / Update Parts');
    }

    /**
     * Test admin can update parts for a service directly from the admin section.
     */
    public function test_admin_can_update_parts_for_service(): void
    {
        $service = $this->createAssignedService();
        $jobCard = $service->jobCard;

        $jobCard->update([
            'labor_charges' => 350.00,
            'parts_charges' => 0.00,
            'total_charges' => 350.00,
        ]);

        $updatedParts = [
            0 => [
                'name' => 'SMPS Power Adapter 24V 2.5A',
                'quantity' => 2,
                'price' => 650.00,
            ],
            1 => [
                'name' => 'TDS Adjuster Valve Brass',
                'quantity' => 1,
                'price' => 180.00,
            ],
        ];

        // 1. Test via AJAX/JSON
        $response = $this->actingAs($this->user)->postJson(route('services.parts.update', $service->id), [
            'parts_replaced' => $updatedParts,
            'parts_charges' => 1480.00, // 2*650 + 1*180 = 1480
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'parts_charges' => 1480.00,
            'total_charges' => 1830.00, // 350 + 1480
        ]);

        $jobCard->refresh();
        $this->assertEquals(1480.00, (float)$jobCard->parts_charges);
        $this->assertEquals(1830.00, (float)$jobCard->total_charges);

        $parts = $jobCard->formatted_parts;
        $this->assertCount(2, $parts);
        $this->assertEquals('SMPS Power Adapter 24V 2.5A', $parts[0]['name']);
        $this->assertEquals(2, $parts[0]['quantity']);
        $this->assertEquals(650.00, $parts[0]['price']);
        $this->assertEquals(1300.00, $parts[0]['total']);

        $this->assertEquals('TDS Adjuster Valve Brass', $parts[1]['name']);
        $this->assertEquals(1, $parts[1]['quantity']);
        $this->assertEquals(180.00, $parts[1]['price']);
        $this->assertEquals(180.00, $parts[1]['total']);

        // 2. Test standard form POST redirect
        $redirectResponse = $this->actingAs($this->user)->post(route('services.parts.update', $service->id), [
            'parts_replaced' => $updatedParts,
        ]);
        $redirectResponse->assertRedirect(route('services.show', $service->id));
    }

    /**
     * Test backward compatibility with legacy plain string parts_replaced.
     */
    public function test_legacy_string_parts_replaced_formats_safely(): void
    {
        $service = $this->createAssignedService();
        $jobCard = $service->jobCard;

        // Simulate legacy string storage
        $jobCard->update([
            'parts_replaced' => '1x Sediment Filter, 1x Carbon Block',
        ]);

        $jobCard->refresh();
        $formatted = $jobCard->formatted_parts;

        $this->assertIsArray($formatted);
        $this->assertCount(2, $formatted);
        $this->assertEquals('1x Sediment Filter', $formatted[0]['name']);
        $this->assertEquals(1, $formatted[0]['quantity']);
        $this->assertEquals('1x Carbon Block', $formatted[1]['name']);
        $this->assertEquals(1, $formatted[1]['quantity']);
    }
}
