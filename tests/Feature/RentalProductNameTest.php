<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalProductNameTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->adminUser = User::where('username', 'admin')->first() ?? User::first();
        $this->customer = Customer::first();
    }

    /**
     * Rental index page renders with product_name text field and placeholders, without dropdown.
     */
    public function test_rental_index_page_renders_with_product_name_input_and_no_dropdown(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('rentals.index'));

        $response->assertStatus(200);

        // Assert Product Name input is present
        $response->assertSee('Product Name');
        $response->assertSee('name="product_name"', false);
        $response->assertSee('placeholder="Enter product name', false);

        // Assert asset_id select dropdown is NOT present in form
        $response->assertDontSee('<select name="asset_id"', false);
        $response->assertDontSee('Select Asset');

        // Assert prefilled values are removed and placeholders are present
        $response->assertDontSee('value="500"', false);
        $response->assertDontSee('value="1500"', false);
        $response->assertSee('placeholder="500"', false);
        $response->assertSee('placeholder="1500"', false);
        $response->assertSee('placeholder="1"', false);
    }

    /**
     * Admin can create a rental agreement with a product name.
     */
    public function test_admin_can_create_rental_agreement_with_product_name(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'customer_id' => $this->customer->id,
            'product_name' => 'Aquaguard Commercial RO 50 LPH',
            'monthly_fee' => 750.00,
            'deposit_amount' => 2000.00,
            'start_date' => now()->toDateString(),
            'billing_day' => 5,
            'notes' => 'Commercial rental agreement with filter replacement included',
        ];

        $response = $this->post(route('rentals.store'), $payload);

        $response->assertStatus(302);

        $rental = Rental::where('product_name', 'Aquaguard Commercial RO 50 LPH')->first();
        $this->assertNotNull($rental);
        $this->assertEquals($this->customer->id, $rental->customer_id);
        $this->assertEquals(750.00, (float) $rental->monthly_fee);
        $this->assertEquals(2000.00, (float) $rental->deposit_amount);
        $this->assertEquals(5, $rental->billing_day);
        $this->assertNull($rental->asset_id);

        // Verify index page displays the product name
        $indexResponse = $this->get(route('rentals.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Aquaguard Commercial RO 50 LPH');
    }

    /**
     * Rental search includes product_name.
     */
    public function test_rental_search_includes_product_name(): void
    {
        $this->actingAs($this->adminUser);

        Rental::create([
            'rental_number' => 'RNT-2026-9901',
            'customer_id' => $this->customer->id,
            'product_name' => 'ZeroB Alkaline RO Purifier',
            'monthly_fee' => 1200.00,
            'start_date' => now(),
            'billing_day' => 1,
            'status' => 'ACTIVE',
        ]);

        $searchResponse = $this->get(route('rentals.index', ['search' => 'ZeroB Alkaline']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('ZeroB Alkaline RO Purifier');
        $searchResponse->assertSee('RNT-2026-9901');
    }
}
