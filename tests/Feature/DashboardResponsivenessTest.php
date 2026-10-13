<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Invoice;
use App\Models\Warranty;
use App\Models\Technician;
use App\Models\CustomerAsset;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardResponsivenessTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->admin = User::where('role', 'SUPER_ADMIN')->first() ?? User::first();
    }

    /**
     * Test dashboard renders accurately and reflects initial CRM data.
     */
    public function test_dashboard_renders_with_real_crm_data(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Operations Command Center');
        $response->assertSee('Services Due');
        $response->assertSee('Warranty Expiries');
        $response->assertSee('Payments Due');
        $response->assertSee('Field Technicians');
        $response->assertSee('Enterprise Summary');
    }

    /**
     * Test overview API endpoint returns live JSON metrics.
     */
    public function test_dashboard_overview_endpoint_returns_json_metrics(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard.overview'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'cards' => [
                    'servicesDueToday',
                    'servicesUrgent',
                    'newInquiries',
                    'inquiriesUnread',
                    'warrantiesExpiring',
                    'paymentsDue',
                    'paymentsOverdue',
                    'totalOverdueAmount',
                    'techniciansOnDuty',
                    'techniciansAvailable',
                ],
                'counts' => [
                    'totalCustomers',
                    'activeServices',
                    'totalInvoices',
                    'activeWarranties',
                ],
                'schedule',
                'paymentReminders',
                'totalOverdueAmountFormatted',
            ]
        ]);
    }

    /**
     * Test each dashboard field updates responsively when original CRM data changes.
     */
    public function test_dashboard_fields_are_responsive_to_crm_data_changes(): void
    {
        $customer = Customer::first();
        $this->assertNotNull($customer);

        $product = Product::first();
        $asset = CustomerAsset::first();

        // 1. Create a service due today with URGENT priority
        $service = Service::create([
            'service_number' => 'SRV-TEST-0099',
            'customer_id' => $customer->id,
            'asset_id' => $asset ? $asset->id : $customer->id,
            'service_type' => 'REPAIR',
            'scheduled_date' => now(),
            'scheduled_time_slot' => '10:00 AM - 12:00 PM',
            'status' => 'SCHEDULED',
            'priority' => 'URGENT',
        ]);

        // 2. Create an overdue unpaid invoice
        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-0099',
            'customer_id' => $customer->id,
            'invoice_date' => now()->subDays(10),
            'due_date' => now()->subDays(3),
            'subtotal' => 5000.00,
            'total_amount' => 5000.00,
            'balance_amount' => 5000.00,
            'paid_amount' => 0.00,
            'status' => 'ISSUED',
        ]);

        // Query the overview endpoint
        $response = $this->actingAs($this->admin)->get(route('dashboard.overview'));
        $response->assertStatus(200);
        $data = $response->json('data');

        // Verify that the dashboard responds to the new service
        $this->assertGreaterThanOrEqual(1, $data['cards']['servicesDueToday']);
        $this->assertGreaterThanOrEqual(1, $data['cards']['servicesUrgent']);

        // Verify that the dashboard responds to the unpaid invoice
        $this->assertGreaterThanOrEqual(1, $data['cards']['paymentsDue']);
        $this->assertGreaterThanOrEqual(1, $data['cards']['paymentsOverdue']);
        $this->assertGreaterThanOrEqual(5000.00, $data['cards']['totalOverdueAmount']);

        // Verify schedule and payment reminders include the new items
        $scheduleNumbers = array_column($data['schedule'], 'service_number');
        $this->assertContains('SRV-TEST-0099', $scheduleNumbers);

        $reminderNumbers = array_column($data['paymentReminders'], 'invoice_number');
        $this->assertContains('INV-TEST-0099', $reminderNumbers);
    }
}
