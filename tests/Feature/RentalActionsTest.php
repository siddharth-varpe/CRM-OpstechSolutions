<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalActionsTest extends TestCase
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
            'name' => 'Acme Corporation',
            'phone' => '9876543210',
            'email' => 'acme@example.com',
            'status' => 'ACTIVE',
        ]);

        $this->rental = Rental::create([
            'rental_number' => 'RNT-2026-0099',
            'customer_id' => $this->customer->id,
            'product_name' => 'Industrial RO Water Plant 500 LPH',
            'start_date' => now()->subMonths(2),
            'monthly_fee' => 4500.00,
            'deposit_amount' => 15000.00,
            'billing_day' => 10,
            'status' => 'ACTIVE',
            'notes' => 'Contract with maintenance included',
        ]);
    }

    /**
     * Rentals index page displays action buttons for every rental: WhatsApp, Edit, Return Machine.
     */
    public function test_rentals_index_displays_action_buttons_for_every_rental(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('rentals.index'));

        $response->assertStatus(200);

        // Header column
        $response->assertSee('Actions');

        // Rental details
        $response->assertSee('RNT-2026-0099');
        $response->assertSee('Industrial RO Water Plant 500 LPH');

        // Actions: WhatsApp, Edit, Return Machine
        $response->assertSee('sendRentalPaymentWhatsApp', false);
        $response->assertSee('WhatsApp');
        $response->assertSee('openEditRentalModal', false);
        $response->assertSee('Edit');
        $response->assertSee('openReturnRentalModal', false);
        $response->assertSee('Return Machine');

        // Modals present
        $response->assertSee('modalEditRental', false);
        $response->assertSee('modalReturnRental', false);

        // Customer search bars present in Create and Edit modals
        $response->assertSee('id="rentalCustomerSearch"', false);
        $response->assertSee('id="btnClearRentalCustomerSearch"', false);
        $response->assertSee('id="rentalCustomerSelect"', false);
        $response->assertSee('id="rentalCustomerSearchHelp"', false);
        $response->assertSee('id="editRentalCustomerSearch"', false);
        $response->assertSee('id="btnClearEditRentalCustomerSearch"', false);
        $response->assertSee('id="editCustomerId"', false);
        $response->assertSee('id="editRentalCustomerSearchHelp"', false);
        $response->assertSee('data-search=', false);
    }

    /**
     * Show endpoint returns JSON data for populating edit modal.
     */
    public function test_rental_show_endpoint_returns_json(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->getJson(route('rentals.show', $this->rental->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $this->rental->id,
                'rental_number' => 'RNT-2026-0099',
                'customer_id' => $this->customer->id,
                'product_name' => 'Industrial RO Water Plant 500 LPH',
                'monthly_fee' => 4500.00,
                'deposit_amount' => 15000.00,
                'billing_day' => 10,
                'status' => 'ACTIVE',
            ]
        ]);
    }

    /**
     * Admin can edit/update a rental agreement.
     */
    public function test_admin_can_update_rental_agreement(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'customer_id' => $this->customer->id,
            'product_name' => 'Commercial RO Plant 1000 LPH Upgraded',
            'monthly_fee' => 6000.00,
            'deposit_amount' => 18000.00,
            'billing_day' => 15,
            'start_date' => '2026-08-01',
            'end_date' => '2027-08-01',
            'status' => 'ACTIVE',
            'notes' => 'Upgraded machine capacity upon client request',
        ];

        $response = $this->putJson(route('rentals.update', $this->rental->id), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->rental->refresh();
        $this->assertEquals('Commercial RO Plant 1000 LPH Upgraded', $this->rental->product_name);
        $this->assertEquals(6000.00, (float) $this->rental->monthly_fee);
        $this->assertEquals(18000.00, (float) $this->rental->deposit_amount);
        $this->assertEquals(15, $this->rental->billing_day);
        $this->assertEquals('Upgraded machine capacity upon client request', $this->rental->notes);

        // Audit log exists
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'RENTAL',
            'entity_id' => $this->rental->id,
            'action' => 'UPDATE',
        ]);
    }

    /**
     * Admin can return a machine when rental is over.
     */
    public function test_admin_can_return_machine_and_end_rental(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'return_date' => '2026-10-18',
            'refund_amount' => 15000.00,
            'notes' => 'Machine returned in excellent working condition, full security deposit returned via UPI',
        ];

        $response = $this->postJson(route('rentals.return', $this->rental->id), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->rental->refresh();
        $this->assertEquals('RETURNED', $this->rental->status);
        $this->assertNotNull($this->rental->end_date);
        $this->assertStringContainsString('Machine returned on 18 Oct 2026', $this->rental->notes);
        $this->assertStringContainsString('Deposit refund: ₹15,000.00', $this->rental->notes);

        // Audit log exists
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'RENTAL',
            'entity_id' => $this->rental->id,
            'action' => 'RETURN_MACHINE',
        ]);

        // When rendered, status is RETURNED and Return Machine button shows Returned badge
        $indexResponse = $this->get(route('rentals.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('RETURNED');
        $indexResponse->assertSee('Returned');
    }

    /**
     * Rentals index page displays Record Payment button in header and per-row, with modal.
     */
    public function test_rentals_index_displays_record_payment_buttons_and_modal(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('rentals.index'));

        $response->assertStatus(200);

        // Header button
        $response->assertSee('openRecordRentalPaymentModal', false);
        $response->assertSee('Record Payment');

        // Row button
        $response->assertSee('openRecordRentalPaymentModalForRental', false);

        // Modal
        $response->assertSee('id="modalRecordRentalPayment"', false);
        $response->assertSee('id="rentalSelect"', false);
        $response->assertSee('id="rentalAmountInput"', false);
        $response->assertSee('id="rentalBillingMonth"', false);
        $response->assertSee(route('payments.rentals.store'), false);
    }

    /**
     * Recording a payment for rental equipment writes to rental_payments,
     * appears on rental payments tab, and is recorded on reports.
     */
    public function test_recording_rental_payment_records_to_payment_page_and_reports(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'rental_id' => $this->rental->id,
            'amount' => 4500.00,
            'billing_month' => '2026-10',
            'payment_date' => '2026-10-18',
            'payment_method' => 'UPI',
            'transaction_reference' => 'UPI99887766',
            'notes' => 'October rental payment recorded from equipment rentals page',
        ];

        $postResponse = $this->post(route('payments.rentals.store'), $payload);

        // Redirects to rental payments page with success
        $postResponse->assertRedirect(route('payments.index', ['tab' => 'rentals']));
        $postResponse->assertSessionHas('success');

        // Recorded in database
        $this->assertDatabaseHas('rental_payments', [
            'rental_id' => $this->rental->id,
            'amount' => 4500.00,
            'billing_month' => '2026-10',
            'payment_method' => 'UPI',
            'reference_number' => 'UPI99887766',
            'status' => 'PAID',
        ]);

        // Recorded on the rental payment page
        $paymentPageResponse = $this->get(route('payments.index', ['tab' => 'rentals']));
        $paymentPageResponse->assertStatus(200);
        $paymentPageResponse->assertSee('4,500.00');
        $paymentPageResponse->assertSee('UPI99887766');
        $paymentPageResponse->assertSee($this->customer->name);
        $paymentPageResponse->assertSee('Industrial RO Water Plant 500 LPH');

        // Recorded on reports
        $reportResponse = $this->get(route('reports.index'));
        $reportResponse->assertStatus(200);
        $reportResponse->assertSee('Rental Collections');
        $reportResponse->assertSee('4,500.00');
        $reportResponse->assertSee('Equipment Rentals &amp; Subscriptions', false);
        $reportResponse->assertSee('Active Machine Contracts');

        $viewTotalRentalCollected = $reportResponse->viewData('totalRentalCollected');
        $this->assertGreaterThanOrEqual(4500.00, $viewTotalRentalCollected);
    }
}
