<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleOrderFreeTextProductTest extends TestCase
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
     * Sales index view renders cleanly with HTTP 200 and loads catalog products.
     */
    public function test_sales_index_page_renders_successfully(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('sales.index'));

        $response->assertStatus(200);
        $response->assertSee('saleProductName');
        $response->assertSee('saleProductList');
        $response->assertSee('saleApplyGst');
        $response->assertSee('saleGstRate');
    }

    /**
     * A sale order can be created without GST (0% tax).
     */
    public function test_sale_order_can_be_created_without_gst(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'customer_id' => $this->customer->id,
            'product_name' => 'Aquaguard Compact RO System (Zero GST)',
            'quantity' => 1,
            'unit_price' => 12000.00,
            'discount_amount' => 500.00,
            'apply_gst' => 0,
            'notes' => 'Non-GST customer billing',
        ];

        $response = $this->postJson(route('sales.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $sale = Sale::latest()->first();
        $this->assertNotNull($sale);

        // Subtotal = 12000 - 500 = 11500
        // GST = 0.00
        // Total = 11500
        $this->assertEquals(11500.00, (float) $sale->subtotal);
        $this->assertEquals(0.00, (float) $sale->tax_amount);
        $this->assertEquals(11500.00, (float) $sale->total_amount);

        $saleItem = SaleItem::where('sale_id', $sale->id)->first();
        $this->assertEquals(0.00, (float) $saleItem->tax_rate_percent);
        $this->assertEquals(0.00, (float) $saleItem->tax_amount);
        $this->assertEquals(11500.00, (float) $saleItem->line_total);

        $invoice = Invoice::where('sale_id', $sale->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(0.00, (float) $invoice->tax_amount);
        $this->assertEquals(0.00, (float) $invoice->cgst_amount);
        $this->assertEquals(0.00, (float) $invoice->sgst_amount);
        $this->assertEquals(11500.00, (float) $invoice->total_amount);

        // Also verify the invoice detail page renders cleanly with 0% GST
        $invoicePage = $this->get(route('invoices.show', $invoice->id));
        $invoicePage->assertStatus(200);
        $invoicePage->assertSee('Exempt / Nil Rated');
    }

    /**
     * A sale order can be created with a custom or non-standard GST rate (e.g. 12%).
     */
    public function test_sale_order_can_be_created_with_custom_gst_rate(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'customer_id' => $this->customer->id,
            'product_name' => 'Industrial RO Membrane Vessel (12% GST)',
            'quantity' => 1,
            'unit_price' => 10000.00,
            'discount_amount' => 0.00,
            'apply_gst' => 1,
            'tax_rate' => 12.00,
        ];

        $response = $this->postJson(route('sales.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $sale = Sale::latest()->first();
        $this->assertNotNull($sale);

        // Subtotal = 10000, Tax 12% = 1200, Total = 11200
        $this->assertEquals(10000.00, (float) $sale->subtotal);
        $this->assertEquals(1200.00, (float) $sale->tax_amount);
        $this->assertEquals(11200.00, (float) $sale->total_amount);

        $saleItem = SaleItem::where('sale_id', $sale->id)->first();
        $this->assertEquals(12.00, (float) $saleItem->tax_rate_percent);
        $this->assertEquals(1200.00, (float) $saleItem->tax_amount);
    }

    /**
     * A sale order can be created with any custom free-text product name.
     */
    public function test_sale_order_can_be_created_with_custom_free_text_product_name(): void
    {
        $this->actingAs($this->adminUser);

        $customProductName = 'Kent Grand Plus RO Alkaline Water Purifier 2026 Edition';

        // Precondition: Product does not exist
        $this->assertDatabaseMissing('products', ['name' => $customProductName]);

        $payload = [
            'customer_id' => $this->customer->id,
            'product_name' => $customProductName,
            'quantity' => 2,
            'unit_price' => 18500.00,
            'discount_amount' => 1000.00,
            'serial_number' => 'SN-KENT-2026-XYZ',
            'notes' => 'Custom installation with 1-year AMC included',
        ];

        $response = $this->postJson(route('sales.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // Verify product was auto-created with the custom product name
        $this->assertDatabaseHas('products', [
            'name' => $customProductName,
            'unit_price' => 18500.00,
        ]);

        // Verify sale order was created
        $sale = Sale::latest()->first();
        $this->assertNotNull($sale);
        $this->assertEquals($this->customer->id, $sale->customer_id);

        // Subtotal = (2 * 18500) - 1000 = 36000
        // GST 18% = 6480
        // Total = 42480
        $this->assertEquals(36000.00, (float) $sale->subtotal);
        $this->assertEquals(6480.00, (float) $sale->tax_amount);
        $this->assertEquals(42480.00, (float) $sale->total_amount);

        // Verify sale item preserves the custom product name snapshot
        $saleItem = SaleItem::where('sale_id', $sale->id)->first();
        $this->assertNotNull($saleItem);
        $this->assertEquals($customProductName, $saleItem->product_name_snapshot);
        $this->assertEquals('SN-KENT-2026-XYZ', $saleItem->serial_number);

        // Verify invoice and invoice item were generated with the custom product name
        $invoice = Invoice::where('sale_id', $sale->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(42480.00, (float) $invoice->total_amount);

        $invoiceItem = InvoiceItem::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($invoiceItem);
        $this->assertEquals($customProductName, $invoiceItem->name_snapshot);
    }

    /**
     * Selecting or typing an existing catalogue product reuses that product.
     */
    public function test_sale_order_reuses_existing_catalogue_product(): void
    {
        $this->actingAs($this->adminUser);

        $existingProduct = Product::where('sku', 'RO-COMM-50LPH')->first();
        $this->assertNotNull($existingProduct);

        $initialProductCount = Product::count();

        $payload = [
            'customer_id' => $this->customer->id,
            'product_name' => $existingProduct->name,
            'product_id' => $existingProduct->id,
            'quantity' => 1,
            'unit_price' => 38500.00,
            'discount_amount' => 0,
        ];

        $response = $this->postJson(route('sales.store'), $payload);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        // No new duplicate product created
        $this->assertEquals($initialProductCount, Product::count());

        $sale = Sale::latest()->first();
        $saleItem = SaleItem::where('sale_id', $sale->id)->first();
        $this->assertEquals($existingProduct->id, $saleItem->product_id);
        $this->assertEquals($existingProduct->name, $saleItem->product_name_snapshot);
    }

    /**
     * Missing both product_name and product_id is rejected with 422.
     */
    public function test_sale_order_requires_product_name_or_product_id(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'customer_id' => $this->customer->id,
            'product_name' => '',
            'product_id' => '',
            'quantity' => 1,
            'unit_price' => 5000,
        ];

        $response = $this->postJson(route('sales.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['product_name']);
    }
}
