<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Rental;
use App\Models\RentalPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalPaymentsTabTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Customer $customer;
    protected Rental $rental;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->adminUser = User::where('username', 'admin')->first() ?? User::first();
        $this->customer = Customer::first() ?? Customer::create([
            'name' => 'John Doe',
            'phone' => '9876543210',
            'email' => 'john@example.com',
            'status' => 'ACTIVE',
        ]);

        $this->rental = Rental::create([
            'rental_number' => 'RNT-2026-0001',
            'customer_id' => $this->customer->id,
            'product_name' => 'Commercial RO Plant 250 LPH',
            'start_date' => now()->startOfMonth(),
            'monthly_fee' => 3500.00,
            'deposit_amount' => 10000.00,
            'billing_day' => 5,
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Payments page renders both Invoice Payments and Rental Payments tabs.
     */
    public function test_payments_page_renders_tabs_and_record_buttons(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('payments.index'));

        $response->assertStatus(200);
        $response->assertSee('Invoice Payments');
        $response->assertSee('Rental Payments');
        $response->assertSee('Record Invoice Payment');
        $response->assertSee('Record Rental Payment');
        $response->assertSee('modalRecordRentalPayment', false);
    }

    /**
     * Rental payments tab displays rental payments table and active rentals KPI.
     */
    public function test_rental_payments_tab_displays_rental_table(): void
    {
        $this->actingAs($this->adminUser);

        $rentalPayment = RentalPayment::create([
            'payment_number' => 'RPAY-2026-0001',
            'rental_id' => $this->rental->id,
            'billing_month' => '2026-10',
            'amount' => 3500.00,
            'due_date' => now(),
            'paid_date' => now(),
            'payment_method' => 'UPI',
            'reference_number' => 'UPI123456789',
            'status' => 'PAID',
            'notes' => 'Monthly rental collected via GPay',
        ]);

        $response = $this->get(route('payments.index', ['tab' => 'rentals']));

        $response->assertStatus(200);
        $response->assertSee('Active Rentals');
        $response->assertSee('RPAY-2026-0001');
        $response->assertSee('Commercial RO Plant 250 LPH');
        $response->assertSee('2026-10');
        $response->assertSee('3,500.00');
        $response->assertSee('UPI123456789');
    }

    /**
     * Admin can successfully record a new rental payment.
     */
    public function test_admin_can_record_rental_payment(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'rental_id' => $this->rental->id,
            'billing_month' => '2026-10',
            'amount' => 3500.00,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'UPI',
            'transaction_reference' => 'UPI9988776655',
            'notes' => 'October rent settlement',
        ];

        $response = $this->post(route('payments.rentals.store'), $payload);

        $response->assertRedirect(route('payments.index', ['tab' => 'rentals']));

        $this->assertDatabaseHas('rental_payments', [
            'rental_id' => $this->rental->id,
            'billing_month' => '2026-10',
            'amount' => 3500.00,
            'payment_method' => 'UPI',
            'reference_number' => 'UPI9988776655',
            'status' => 'PAID',
        ]);
    }

    /**
     * Recording rental payment via AJAX returns JSON with payment data.
     */
    public function test_admin_can_record_rental_payment_via_ajax(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'rental_id' => $this->rental->id,
            'billing_month' => '2026-11',
            'amount' => 3500.00,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'BANK_TRANSFER',
            'transaction_reference' => 'NEFT000112233',
            'notes' => 'November rent advanced',
        ];

        $response = $this->postJson(route('payments.rentals.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'payment_number',
                'amount',
                'billing_month',
            ],
        ]);
    }

    /**
     * Rental payment receipt can be retrieved via show endpoint.
     */
    public function test_rental_payment_receipt_can_be_retrieved(): void
    {
        $this->actingAs($this->adminUser);

        $rentalPayment = RentalPayment::create([
            'payment_number' => 'RPAY-2026-0005',
            'rental_id' => $this->rental->id,
            'billing_month' => '2026-10',
            'amount' => 3500.00,
            'due_date' => now(),
            'paid_date' => now(),
            'payment_method' => 'CASH',
            'status' => 'PAID',
        ]);

        $response = $this->getJson(route('payments.show', $rentalPayment->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $rentalPayment->id,
                'payment_number' => 'RPAY-2026-0005',
                'amount' => '3500.00',
                'payment_method' => 'CASH',
            ],
        ]);
    }

    /**
     * Rental payments tab filters by search query and payment method.
     */
    public function test_rental_payments_filters_work(): void
    {
        $this->actingAs($this->adminUser);

        RentalPayment::create([
            'payment_number' => 'RPAY-MATCH-111',
            'rental_id' => $this->rental->id,
            'billing_month' => '2026-09',
            'amount' => 3500.00,
            'due_date' => now(),
            'paid_date' => now(),
            'payment_method' => 'UPI',
            'reference_number' => 'SPECIAL_REF_999',
            'status' => 'PAID',
        ]);

        RentalPayment::create([
            'payment_number' => 'RPAY-OTHER-222',
            'rental_id' => $this->rental->id,
            'billing_month' => '2026-08',
            'amount' => 3500.00,
            'due_date' => now(),
            'paid_date' => now(),
            'payment_method' => 'CASH',
            'reference_number' => 'OTHER_REF_888',
            'status' => 'PAID',
        ]);

        // Search match
        $response = $this->get(route('payments.index', [
            'tab' => 'rentals',
            'search' => 'SPECIAL_REF_999',
        ]));
        $response->assertStatus(200);
        $response->assertSee('RPAY-MATCH-111');
        $response->assertDontSee('RPAY-OTHER-222');

        // Method filter match
        $responseMethod = $this->get(route('payments.index', [
            'tab' => 'rentals',
            'method' => 'UPI',
        ]));
        $responseMethod->assertStatus(200);
        $responseMethod->assertSee('RPAY-MATCH-111');
        $responseMethod->assertDontSee('RPAY-OTHER-222');
    }
}
