<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartialPaymentNextDueDateTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->adminUser = User::where('username', 'admin')->first();
        $this->customer = Customer::first();
    }

    /**
     * Default GST rate across the entire CRM is 0% by default.
     */
    public function test_default_gst_rate_is_zero_percent_across_crm(): void
    {
        // 1. System setting default is 0
        $this->assertEquals('0', (string) SystemSetting::get('default_gst_rate', 0));

        // 2. InvoiceItem tax rate defaults to 0%
        $item = new InvoiceItem();
        $this->assertEquals(0, (float) $item->tax_rate);

        // 3. New Invoice created without tax_rate uses 0% tax
        $this->actingAs($this->adminUser);

        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => date('Y-m-d'),
            'items' => [
                [
                    'item_description' => 'Annual Maintenance Service Package',
                    'quantity' => 1,
                    'unit_price' => 5000.00,
                    'discount' => 0,
                    // tax_rate omitted to test system-wide default
                ],
            ],
        ];

        $response = $this->post(route('invoices.store'), $payload);
        $response->assertSessionHasNoErrors();

        $invoice = Invoice::orderByDesc('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(5000.00, (float) $invoice->subtotal);
        $this->assertEquals(0.00, (float) $invoice->tax_amount);
        $this->assertEquals(5000.00, (float) $invoice->total_amount);

        $invoiceItem = $invoice->items()->first();
        $this->assertNotNull($invoiceItem);
        $this->assertEquals(0.00, (float) $invoiceItem->tax_rate_percent);
        $this->assertEquals(0.00, (float) $invoiceItem->tax_amount);
    }

    /**
     * When customer makes a partial payment, setting next_due_date updates the invoice due_date.
     */
    public function test_partial_payment_with_next_due_date_updates_invoice_due_date(): void
    {
        $this->actingAs($this->adminUser);

        $initialDueDate = Carbon::now()->addDays(5)->format('Y-m-d');
        $nextFollowUpDueDate = Carbon::now()->addDays(20)->format('Y-m-d');

        // Create an invoice with total ₹10,000
        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-001',
            'customer_id' => $this->customer->id,
            'invoice_date' => now(),
            'due_date' => $initialDueDate,
            'subtotal' => 10000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'total_amount' => 10000.00,
            'paid_amount' => 0.00,
            'balance_amount' => 10000.00,
            'status' => 'ISSUED',
            'created_by' => $this->adminUser->id,
        ]);

        // Customer pays partial amount ₹4,000, promising remaining ₹6,000 by $nextFollowUpDueDate
        $paymentPayload = [
            'invoice_id' => $invoice->id,
            'amount' => 4000.00,
            'payment_date' => date('Y-m-d'),
            'payment_mode' => 'UPI',
            'transaction_reference' => 'UPI-PARTIAL-12345',
            'next_due_date' => $nextFollowUpDueDate,
            'notes' => 'Customer promised remaining amount on 20th',
        ];

        $response = $this->postJson(route('payments.store'), $paymentPayload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Reload invoice and verify state
        $invoice->refresh();

        $this->assertEquals(4000.00, (float) $invoice->paid_amount);
        $this->assertEquals(6000.00, (float) $invoice->balance_amount);
        $this->assertEquals('PARTIALLY_PAID', $invoice->status);

        // Verify that the invoice due_date was updated to the next_due_date
        $this->assertEquals(
            Carbon::parse($nextFollowUpDueDate)->format('Y-m-d'),
            $invoice->due_date->format('Y-m-d')
        );

        // Verify payment record
        $payment = Payment::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(4000.00, (float) $payment->amount);
        $this->assertStringContainsString('Next due date for remaining balance', $payment->notes);

        // Verify Payment Dues ledger reflects the updated due date
        $duesResponse = $this->get(route('dues.index', ['tab' => 'payments']));
        $duesResponse->assertStatus(200);
        $duesResponse->assertSee($invoice->invoice_number);
    }

    /**
     * A full payment marks invoice as PAID without altering remaining due date.
     */
    public function test_full_payment_marks_invoice_as_paid(): void
    {
        $this->actingAs($this->adminUser);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-002',
            'customer_id' => $this->customer->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(7),
            'subtotal' => 8000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'total_amount' => 8000.00,
            'paid_amount' => 0.00,
            'balance_amount' => 8000.00,
            'status' => 'ISSUED',
            'created_by' => $this->adminUser->id,
        ]);

        $paymentPayload = [
            'invoice_id' => $invoice->id,
            'amount' => 8000.00,
            'payment_date' => date('Y-m-d'),
            'payment_mode' => 'CASH',
        ];

        $response = $this->postJson(route('payments.store'), $paymentPayload);

        $response->assertStatus(200);
        $invoice->refresh();

        $this->assertEquals(8000.00, (float) $invoice->paid_amount);
        $this->assertEquals(0.00, (float) $invoice->balance_amount);
        $this->assertEquals('PAID', $invoice->status);
    }

    /**
     * Payment view modals render the next due date input elements.
     */
    public function test_payment_modals_render_next_due_date_elements(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Payments index page
        $paymentsPage = $this->get(route('payments.index'));
        $paymentsPage->assertStatus(200);
        $paymentsPage->assertSee('partialDueDateContainer');
        $paymentsPage->assertSee('paymentNextDueDate');

        // 2. Invoices show page
        $invoice = Invoice::first();
        if ($invoice) {
            $invoicePage = $this->get(route('invoices.show', $invoice->id));
            $invoicePage->assertStatus(200);
            $invoicePage->assertSee('partialDueDateContainerInvoice');
            $invoicePage->assertSee('paymentNextDueDateInvoice');
        }
    }
}
