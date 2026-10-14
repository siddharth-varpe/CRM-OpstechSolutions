<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BusinessSequence;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerAsset;
use App\Models\Document;
use App\Models\Inquiry;
use App\Models\InventoryItem;
use App\Models\InventoryPurchase;
use App\Models\InventorySale;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JobCard;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Rental;
use App\Models\RentalPayment;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Service;
use App\Models\ServiceSchedule;
use App\Models\SystemSetting;
use App\Models\Technician;
use App\Models\TechnicianLocation;
use App\Models\User;
use App\Models\Warranty;
use App\Models\WarrantyEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrmDataCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed initial database state
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->superAdmin = User::where('username', 'admin')->first();
        $this->staffUser = User::where('username', 'staff')->first();
    }

    /**
     * Unauthenticated guest users must be blocked from preview and execution endpoints.
     */
    public function test_guest_cannot_access_preview_or_execute(): void
    {
        $previewResponse = $this->getJson(route('settings.clean_data.preview'));
        $previewResponse->assertStatus(401);

        $executeResponse = $this->postJson(route('settings.clean_data.execute'), [
            'confirmation_phrase' => 'DELETE CRM DATA',
            'acknowledge_risks' => 1,
            'password' => 'admin',
        ]);
        $executeResponse->assertStatus(401);
    }

    /**
     * Non-Super Admin users (e.g. Staff) must receive 403 Forbidden.
     */
    public function test_non_super_admin_cannot_access_preview_or_execute(): void
    {
        $this->actingAs($this->staffUser);

        $previewResponse = $this->getJson(route('settings.clean_data.preview'));
        $previewResponse->assertStatus(403);

        $executeResponse = $this->postJson(route('settings.clean_data.execute'), [
            'confirmation_phrase' => 'DELETE CRM DATA',
            'acknowledge_risks' => 1,
            'password' => 'staff',
        ]);
        $executeResponse->assertStatus(403);
    }

    /**
     * Super Admin can view server-calculated impact preview with honest real-time counts.
     */
    public function test_super_admin_can_view_impact_preview(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->getJson(route('settings.clean_data.preview'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'preview' => [
                'total_records',
                'categories' => [
                    'customers',
                    'customer_assets',
                    'sales',
                    'invoices',
                    'payments',
                    'services',
                    'job_cards',
                    'warranties',
                    'rentals',
                    'inventory_transactions',
                    'technicians',
                    'technician_activity',
                    'inquiries_leads',
                    'operational_notifications',
                    'documents',
                ],
                'preserved_entities',
                'database_connection',
                'backup_advice',
            ],
        ]);

        $json = $response->json();
        $this->assertTrue($json['success']);
        $this->assertGreaterThan(0, $json['preview']['total_records']);
    }

    /**
     * Execution must reject missing confirmation phrase with 422.
     */
    public function test_execution_rejects_missing_confirmation_phrase(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->postJson(route('settings.clean_data.execute'), [
            'confirmation_phrase' => '',
            'acknowledge_risks' => 1,
            'password' => 'admin',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['confirmation_phrase']);
    }

    /**
     * Execution must reject incorrect confirmation phrase with 422.
     */
    public function test_execution_rejects_incorrect_confirmation_phrase(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->postJson(route('settings.clean_data.execute'), [
            'confirmation_phrase' => 'delete crm data', // lower case
            'acknowledge_risks' => 1,
            'password' => 'admin',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['confirmation_phrase']);
    }

    /**
     * Execution must reject missing risk acknowledgment with 422.
     */
    public function test_execution_rejects_missing_acknowledgment(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->postJson(route('settings.clean_data.execute'), [
            'confirmation_phrase' => 'DELETE CRM DATA',
            'acknowledge_risks' => 0,
            'password' => 'admin',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['acknowledge_risks']);
    }

    /**
     * Execution must reject incorrect Super Admin password with 422.
     */
    public function test_execution_rejects_incorrect_password(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->postJson(route('settings.clean_data.execute'), [
            'confirmation_phrase' => 'DELETE CRM DATA',
            'acknowledge_risks' => 1,
            'password' => 'wrongpassword123',
        ]);

        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));
        $this->assertStringContainsString('Invalid Super Administrator password', $response->json('message'));
    }

    /**
     * Full execution removes all operational records while preserving protected entities.
     */
    public function test_successful_cleanup_deletes_business_data_and_preserves_system_integrity(): void
    {
        $this->actingAs($this->superAdmin);

        // Pre-condition: Verify seeded demo/operational records exist
        $this->assertGreaterThan(0, Customer::count());
        $this->assertGreaterThan(0, CustomerAddress::count());
        $this->assertGreaterThan(0, Product::count());
        $this->assertGreaterThan(0, InventoryItem::count());

        // Create a few additional operational records to test all categories
        $customer = Customer::first();
        $product = Product::first();
        $inventoryItem = InventoryItem::first();

        // 1. Inventory purchase & sale
        InventoryPurchase::create([
            'purchase_number' => 'PURCH-TEST-001',
            'item_id' => $inventoryItem->id,
            'purchase_date' => now(),
            'quantity' => 10,
            'remaining_quantity' => 10,
            'purchase_price_per_unit' => 50,
            'total_amount' => 500,
        ]);
        InventorySale::create([
            'sale_number' => 'ISALE-TEST-001',
            'item_id' => $inventoryItem->id,
            'customer_id' => $customer->id,
            'sale_date' => now(),
            'quantity' => 2,
            'selling_price_per_unit' => 100,
            'purchase_cost_per_unit' => 50,
            'total_sale_amount' => 200,
            'total_cost_amount' => 100,
            'profit' => 100,
        ]);

        // 2. Customer asset, warranty & event
        $asset = CustomerAsset::create([
            'asset_number' => 'ASSET-TEST-001',
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'asset_type' => 'RO_MACHINE',
            'purchase_date' => now(),
        ]);
        $warranty = Warranty::create([
            'warranty_number' => 'WAR-TEST-001',
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'warranty_type' => 'STANDARD_MACHINE',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'duration_months' => 12,
        ]);
        WarrantyEvent::create([
            'warranty_id' => $warranty->id,
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'event_type' => 'ACTIVATED',
            'event_date' => now(),
        ]);

        // 3. Rental and rental payment
        $rental = Rental::create([
            'rental_number' => 'RNT-TEST-001',
            'customer_id' => $customer->id,
            'start_date' => now(),
            'monthly_fee' => 1500,
        ]);
        RentalPayment::create([
            'rental_id' => $rental->id,
            'billing_month' => '2026-10',
            'amount' => 1500,
            'due_date' => now(),
        ]);

        // 4. Sales order and sale item
        $sale = Sale::create([
            'sale_number' => 'SALE-TEST-001',
            'customer_id' => $customer->id,
            'sale_date' => now(),
            'subtotal' => 1000,
            'total_amount' => 1000,
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'quantity' => 1,
            'unit_price_snapshot' => 1000,
            'line_total' => 1000,
        ]);

        // 5. Invoice, invoice item, payment
        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-001',
            'customer_id' => $customer->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(15),
            'subtotal' => 1000,
            'total_amount' => 1000,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'name_snapshot' => 'Service Visit',
            'quantity' => 1,
            'unit_price_snapshot' => 1000,
            'line_total' => 1000,
        ]);
        Payment::create([
            'payment_number' => 'PAY-TEST-001',
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'amount' => 1000,
            'payment_date' => now(),
        ]);

        // 6. Service & Job card
        $service = Service::create([
            'service_number' => 'SRV-TEST-001',
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'service_type' => 'REPAIR',
            'scheduled_date' => now(),
        ]);
        ServiceSchedule::create([
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'schedule_index' => 1,
            'total_schedules' => 4,
            'planned_date' => now()->addMonths(3),
            'target_month' => '2027-01',
        ]);
        JobCard::create([
            'job_card_number' => 'JC-TEST-001',
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
        ]);

        // 7. Technician tracking & notifications
        $tech = Technician::first();
        if ($tech) {
            TechnicianLocation::create([
                'technician_id' => $tech->id,
                'latitude' => 18.5204,
                'longitude' => 73.8567,
            ]);
            DB::table('technician_portal_notifications')->insert([
                'id' => (string) Str::uuid(),
                'technician_id' => $tech->id,
                'type' => 'NEW_ASSIGNMENT',
                'title' => 'New Service Assigned',
                'message' => 'Please visit the customer',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $tech->update(['tracking_status' => 'ON_THE_WAY', 'active_service_id' => $service->id]);
        }

        // 8. Lead/Inquiry, notification, whatsapp message
        Inquiry::create([
            'inquiry_number' => 'INQ-TEST-001',
            'customer_name' => 'Ramesh Sharma',
            'phone' => '9998887776',
        ]);
        Notification::create([
            'notification_type' => 'PAYMENT_RECEIVED',
            'title' => 'Payment received',
            'message' => 'Payment of Rs. 1000 received',
        ]);
        DB::table('whatsapp_messages')->insert([
            'id' => (string) Str::uuid(),
            'phone' => '9998887776',
            'content' => 'Your service visit is scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Advance sequence
        BusinessSequence::where('name', 'CUSTOMER')->update(['current_val' => 25]);
        BusinessSequence::where('name', 'INVOICE')->update(['current_val' => 40]);

        // Execute cleanup
        $response = $this->postJson(route('settings.clean_data.execute'), [
            'confirmation_phrase' => 'DELETE CRM DATA',
            'acknowledge_risks' => 1,
            'password' => 'admin',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertGreaterThan(0, $response->json('total_deleted'));

        // ==========================================
        // VERIFY ZERO OPERATIONAL RECORDS REMAIN
        // ==========================================
        $this->assertEquals(0, Customer::count(), 'Customers table must be empty');
        $this->assertEquals(0, CustomerAddress::count(), 'Customer addresses table must be empty');
        $this->assertEquals(0, CustomerAsset::count(), 'Customer assets table must be empty');
        $this->assertEquals(0, Sale::count(), 'Sales table must be empty');
        $this->assertEquals(0, SaleItem::count(), 'Sale items table must be empty');
        $this->assertEquals(0, Invoice::count(), 'Invoices table must be empty');
        $this->assertEquals(0, InvoiceItem::count(), 'Invoice items table must be empty');
        $this->assertEquals(0, Payment::count(), 'Payments table must be empty');
        $this->assertEquals(0, Service::count(), 'Services table must be empty');
        $this->assertEquals(0, ServiceSchedule::count(), 'Service schedules table must be empty');
        $this->assertEquals(0, JobCard::count(), 'Job cards table must be empty');
        $this->assertEquals(0, Warranty::count(), 'Warranties table must be empty');
        $this->assertEquals(0, WarrantyEvent::count(), 'Warranty events table must be empty');
        $this->assertEquals(0, Rental::count(), 'Rentals table must be empty');
        $this->assertEquals(0, RentalPayment::count(), 'Rental payments table must be empty');
        $this->assertEquals(0, InventoryPurchase::count(), 'Inventory purchases table must be empty');
        $this->assertEquals(0, InventorySale::count(), 'Inventory sales table must be empty');
        $this->assertEquals(0, Inquiry::count(), 'Inquiries table must be empty');
        $this->assertEquals(0, Notification::count(), 'Notifications table must be empty');
        $this->assertEquals(0, DB::table('whatsapp_messages')->count(), 'WhatsApp messages table must be empty');
        $this->assertEquals(0, TechnicianLocation::count(), 'Technician locations table must be empty');
        $this->assertEquals(0, DB::table('technician_portal_notifications')->count(), 'Technician portal notifications table must be empty');
        $this->assertEquals(0, Document::count(), 'Documents table must be empty');

        // ==========================================
        // VERIFY PROTECTED RESOURCES ARE INTACT
        // ==========================================
        // 1. Super Admin Account is preserved and authenticatable
        $adminInDb = User::where('username', 'admin')->first();
        $this->assertNotNull($adminInDb);
        $this->assertEquals('Super Admin', $adminInDb->role);
        $this->assertTrue(Hash::check('admin', $adminInDb->password));

        // 2. Staff user accounts are preserved
        $this->assertGreaterThan(0, User::count());

        // 3. Roles and Permissions are preserved
        $this->assertGreaterThanOrEqual(4, Role::count());
        $this->assertGreaterThan(0, DB::table('permissions')->count());
        $this->assertGreaterThan(0, DB::table('role_permissions')->count());

        // 4. Products master catalogue is preserved
        $this->assertGreaterThan(0, Product::count());

        // 5. Inventory item definitions are preserved with stock reset to 0
        $this->assertGreaterThan(0, InventoryItem::count());
        $this->assertEquals(0, InventoryItem::sum('current_stock'));

        // 6. Technicians roster, portal access and technician accounts are strictly removed
        $this->assertEquals(0, Technician::count(), 'Technicians table must be empty');
        $this->assertEquals(0, DB::table('technician_portal_access')->count(), 'Technician portal access table must be empty');
        $this->assertEquals(0, User::where('role', 'Technician')->count(), 'Technician user accounts must be removed');

        // 7. Sequences reset to 0
        $custSeq = BusinessSequence::where('name', 'CUSTOMER')->first();
        $this->assertEquals(0, $custSeq->current_val);
        $invSeq = BusinessSequence::where('name', 'INVOICE')->first();
        $this->assertEquals(0, $invSeq->current_val);

        // 8. Company profile settings preserved
        $this->assertGreaterThan(0, SystemSetting::count());

        // 9. Audit trail contains DATA_CLEANUP record
        $cleanupLog = AuditLog::where('action', 'DATA_CLEANUP')->latest('created_at')->first();
        $this->assertNotNull($cleanupLog);
        $this->assertEquals($this->superAdmin->id, $cleanupLog->user_id);
        $this->assertEquals('CRM_BUSINESS_DATA', $cleanupLog->entity_type);
        $this->assertIsArray($cleanupLog->new_values);
        $this->assertArrayHasKey('deleted_records', $cleanupLog->new_values);

        // 10. Post-cleanup regression: Settings page still renders for Super Admin
        $settingsResponse = $this->get(route('settings.index', ['tab' => 'data']));
        $settingsResponse->assertStatus(200);
        $settingsResponse->assertSee('Data Management');
        $settingsResponse->assertSee('Clean CRM Data');
    }
}
