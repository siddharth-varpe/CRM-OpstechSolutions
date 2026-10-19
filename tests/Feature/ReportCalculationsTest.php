<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class ReportCalculationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->admin = User::where('role', 'SUPER_ADMIN')->first() ?? User::first();
    }

    public function test_report_page_accurately_reflects_realized_collections()
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'customer_number' => 'CUST-TEST-001',
            'full_name' => 'Acme Corporation',
            'phone' => '9876543210',
            'status' => 'ACTIVE',
            'customer_type' => 'COMMERCIAL',
        ]);

        // Invoice 1: Fully Paid (e.g., 50,000 billed, 50,000 collected)
        $invoice1 = Invoice::create([
            'id' => (string) Str::uuid(),
            'invoice_number' => 'INV-TEST-0001',
            'customer_id' => $customer->id,
            'invoice_date' => now()->subMonths(2),
            'due_date' => now()->subMonths(1),
            'subtotal' => 45000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 5000.00,
            'total_amount' => 50000.00,
            'paid_amount' => 50000.00,
            'balance_amount' => 0.00,
            'status' => 'PAID',
        ]);

        Payment::create([
            'id' => (string) Str::uuid(),
            'payment_number' => 'PAY-TEST-0001',
            'customer_id' => $customer->id,
            'invoice_id' => $invoice1->id,
            'amount' => 50000.00,
            'payment_date' => now()->subMonths(2),
            'payment_method' => 'BANK_TRANSFER',
            'status' => 'COMPLETED',
        ]);

        // Invoice 2: Partially Paid (e.g., 20,000 billed, 5,000 collected, 15,000 balance)
        $invoice2 = Invoice::create([
            'id' => (string) Str::uuid(),
            'invoice_number' => 'INV-TEST-0002',
            'customer_id' => $customer->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(15),
            'subtotal' => 18000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 2000.00,
            'total_amount' => 20000.00,
            'paid_amount' => 5000.00,
            'balance_amount' => 15000.00,
            'status' => 'PARTIALLY_PAID',
        ]);

        Payment::create([
            'id' => (string) Str::uuid(),
            'payment_number' => 'PAY-TEST-0002',
            'customer_id' => $customer->id,
            'invoice_id' => $invoice2->id,
            'amount' => 5000.00,
            'payment_date' => now(),
            'payment_method' => 'UPI',
            'status' => 'COMPLETED',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.index'));

        $response->assertStatus(200);

        // Verify that total collected is non-zero and greater than initial seed
        $totalCollected = $response->viewData('totalCollected');
        $this->assertGreaterThan(50000.00, $totalCollected);

        // Verify HTML output displays the realized collection
        $response->assertSee('Realized Collections');
        $response->assertSee('₹' . number_format($totalCollected, 2));
    }

    public function test_report_reflects_paid_amount_even_if_payment_receipt_missing()
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'customer_number' => 'CUST-TEST-002',
            'full_name' => 'Direct Client',
            'phone' => '9123456789',
            'status' => 'ACTIVE',
            'customer_type' => 'INDIVIDUAL',
        ]);

        // Invoice directly recorded as settled upon creation without standalone receipt
        Invoice::create([
            'id' => (string) Str::uuid(),
            'invoice_number' => 'INV-TEST-0099',
            'customer_id' => $customer->id,
            'invoice_date' => now()->subMonths(1),
            'due_date' => now(),
            'subtotal' => 10000.00,
            'tax_amount' => 1800.00,
            'total_amount' => 11800.00,
            'paid_amount' => 11800.00,
            'balance_amount' => 0.00,
            'status' => 'PAID',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.index'));

        $response->assertStatus(200);
        $totalCollected = $response->viewData('totalCollected');
        $totalInvoiced = $response->viewData('totalInvoiced');

        $this->assertGreaterThanOrEqual(11800.00, $totalCollected);
        $this->assertGreaterThanOrEqual(11800.00, $totalInvoiced);
    }

    public function test_reports_accurately_record_rental_payments_and_equipment_subscriptions()
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'customer_number' => 'CUST-RENTAL-001',
            'full_name' => 'Apex Healthcare Labs',
            'phone' => '9988776655',
            'status' => 'ACTIVE',
            'customer_type' => 'COMMERCIAL',
        ]);

        $rental = Rental::create([
            'id' => (string) Str::uuid(),
            'rental_number' => 'RNT-2026-REPORT-1',
            'customer_id' => $customer->id,
            'product_name' => 'Dialysis RO Machine 500 LPH',
            'start_date' => now()->startOfMonth(),
            'monthly_fee' => 7500.00,
            'deposit_amount' => 20000.00,
            'billing_day' => 1,
            'status' => 'ACTIVE',
            'billing_frequency' => 'MONTHLY',
        ]);

        $rentalPayment = RentalPayment::create([
            'id' => (string) Str::uuid(),
            'payment_number' => 'RPAY-2026-REPORT-1',
            'rental_id' => $rental->id,
            'billing_month' => now()->format('Y-m'),
            'amount' => 7500.00,
            'due_date' => now(),
            'paid_date' => now(),
            'payment_method' => 'BANK_TRANSFER',
            'reference_number' => 'NEFT987654321',
            'status' => 'PAID',
            'notes' => 'Monthly rental payment recorded',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.index'));

        $response->assertStatus(200);

        // Verify view data
        $totalRentalCollected = $response->viewData('totalRentalCollected');
        $this->assertGreaterThanOrEqual(7500.00, $totalRentalCollected);

        $activeRentalsCount = $response->viewData('activeRentalsCount');
        $this->assertGreaterThanOrEqual(1, $activeRentalsCount);

        $monthlyRentalRevenue = $response->viewData('monthlyRentalRevenue');
        $this->assertGreaterThanOrEqual(7500.00, $monthlyRentalRevenue);

        // Verify HTML output
        $response->assertSee('Rental Collections');
        $response->assertSee('Equipment Rentals &amp; Subscriptions', false);
        $response->assertSee('Active Machine Contracts');
        $response->assertSee('7,500.00');
    }
}
