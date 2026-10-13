<?php

namespace Tests\Feature;

use App\Models\BusinessSequence;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JobCard;
use App\Models\Service;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->adminUser = User::where('username', 'admin')->first();
    }

    public function test_settings_pages_render_successfully(): void
    {
        $this->actingAs($this->adminUser);

        $responseBusiness = $this->get(route('settings.index', ['tab' => 'business']));
        $responseBusiness->assertStatus(200);
        $responseBusiness->assertSee('Company Profile');

        $responseNumbering = $this->get(route('settings.index', ['tab' => 'numbering']));
        $responseNumbering->assertStatus(200);
        $responseNumbering->assertSee('Document Sequences');

        $responseTax = $this->get(route('settings.index', ['tab' => 'tax']));
        $responseTax->assertStatus(200);
        $responseTax->assertSee('GST, Invoicing');

        $responseAudit = $this->get(route('settings.index', ['tab' => 'audit']));
        $responseAudit->assertStatus(200);
        $responseAudit->assertSee('System Audit Activity Trail');
    }

    public function test_saving_business_profile_updates_system_settings_and_audit_trail(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'tab' => 'business',
            'company_name' => 'Acme Water Systems Ltd',
            'company_gstin' => '27ABCDE1234F1Z5',
            'company_phone' => '+91 99887 76655',
            'company_email' => 'finance@acmewater.com',
            'company_address' => 'Unit 10, Industrial Park, Mumbai - 400001, MH',
            'bank_name' => 'HDFC Bank',
            'bank_account_number' => '5010022334455',
            'bank_ifsc' => 'HDFC0001234',
            'bank_branch' => 'Bandra Kurla Complex, Mumbai',
            'upi_id' => 'acmewater@hdfcbank',
        ];

        $response = $this->post(route('settings.update'), $payload);
        $response->assertSessionHas('success');

        $this->assertEquals('Acme Water Systems Ltd', SystemSetting::get('company_name'));
        $this->assertEquals('27ABCDE1234F1Z5', SystemSetting::get('company_gstin'));
        $this->assertEquals('27ABCDE1234F1Z5', SystemSetting::get('gst_number'));
        $this->assertEquals('+91 99887 76655', SystemSetting::get('company_phone'));
        $this->assertEquals('finance@acmewater.com', SystemSetting::get('company_email'));
        $this->assertEquals('HDFC Bank', SystemSetting::get('bank_name'));
        $this->assertEquals('5010022334455', SystemSetting::get('bank_account_number'));
        $this->assertEquals('HDFC0001234', SystemSetting::get('bank_ifsc'));
        $this->assertEquals('acmewater@hdfcbank', SystemSetting::get('upi_id'));

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'SYSTEM_SETTINGS',
            'action' => 'UPDATE',
        ]);
    }

    public function test_saving_tax_and_operational_settings(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'tab' => 'tax',
            'default_gst_rate' => 12,
            'default_hsn' => '842199',
            'payment_terms_days' => 15,
            'invoice_terms' => "1. Goods non-refundable.\n2. Strictly 15 days payment credit.",
            'default_warranty_months' => 24,
            'default_service_interval_months' => 4,
            'sla_hours' => 12,
            'low_stock_threshold' => 10,
        ];

        $response = $this->post(route('settings.update'), $payload);
        $response->assertSessionHas('success');

        $this->assertEquals('12', (string) SystemSetting::get('default_gst_rate'));
        $this->assertEquals('842199', SystemSetting::get('default_hsn'));
        $this->assertEquals('15', (string) SystemSetting::get('payment_terms_days'));
        $this->assertEquals('24', (string) SystemSetting::get('default_warranty_months'));
        $this->assertEquals('4', (string) SystemSetting::get('default_service_interval_months'));
        $this->assertEquals('12', (string) SystemSetting::get('sla_hours'));
        $this->assertEquals('10', (string) SystemSetting::get('low_stock_threshold'));
    }

    public function test_updating_sequences_and_next_number_generation(): void
    {
        $this->actingAs($this->adminUser);

        BusinessSequence::updateOrCreate(
            ['name' => 'INVOICE'],
            [
                'prefix' => 'INV',
                'current_val' => 5,
                'padding' => 4,
                'year_reset' => true,
                'current_year' => (int) date('Y'),
            ]
        );

        $payload = [
            'sequences' => [
                'INVOICE' => [
                    'prefix' => 'ACME-INV',
                    'padding' => 5,
                ],
            ],
        ];

        $response = $this->post(route('settings.sequences.update'), $payload);
        $response->assertSessionHas('success');

        $seq = BusinessSequence::find('INVOICE');
        $this->assertEquals('ACME-INV', $seq->prefix);
        $this->assertEquals(5, $seq->padding);

        $nextInvoiceNumber = BusinessSequence::nextNumber('INVOICE');
        $currentYear = date('Y');
        $this->assertEquals("ACME-INV-{$currentYear}-00006", $nextInvoiceNumber);
    }

    public function test_saved_settings_are_implemented_on_invoice_view(): void
    {
        $this->actingAs($this->adminUser);

        SystemSetting::set('company_name', 'Blue Water Tech');
        SystemSetting::set('company_gstin', '27ZZZZZ9999Z1Z1');
        SystemSetting::set('company_phone', '+91 88888 77777');
        SystemSetting::set('company_email', 'contact@bluewatertech.com');
        SystemSetting::set('company_address', 'Industrial Estate, Pune 411001');
        SystemSetting::set('bank_name', 'Kotak Mahindra Bank');
        SystemSetting::set('bank_account_number', '1234567890');
        SystemSetting::set('bank_ifsc', 'KKBK0001234');
        SystemSetting::set('bank_branch', 'Shivaji Nagar, Pune');
        SystemSetting::set('upi_id', 'bluewater@kotak');
        SystemSetting::set('invoice_terms', "Warranty for 2 years.\nPayment on delivery.");

        $customer = Customer::first() ?? Customer::create([
            'customer_number' => 'CUST-2026-0001',
            'name' => 'Rahul Sharma',
            'phone' => '9822001122',
            'customer_type' => 'RESIDENTIAL',
            'status' => 'ACTIVE',
        ]);

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'invoice_number' => 'INV-TEST-9999',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal' => 1000,
            'tax_amount' => 180,
            'total_amount' => 1180,
            'paid_amount' => 0,
            'outstanding_amount' => 1180,
            'status' => 'ISSUED',
        ]);

        $response = $this->get(route('invoices.show', $invoice->id));
        $response->assertStatus(200);

        // Header reflection
        $response->assertSee('Blue Water Tech');
        $response->assertSee('27ZZZZZ9999Z1Z1');
        $response->assertSee('+91 88888 77777');
        $response->assertSee('contact@bluewatertech.com');
        $response->assertSee('Industrial Estate, Pune 411001');

        // Lower bank details & UPI reflection
        $response->assertSee('Kotak Mahindra Bank');
        $response->assertSee('1234567890');
        $response->assertSee('KKBK0001234');
        $response->assertSee('Shivaji Nagar, Pune');
        $response->assertSee('bluewater@kotak');

        // Terms reflection
        $response->assertSee('Warranty for 2 years.');
        $response->assertSee('Payment on delivery.');
    }
}
