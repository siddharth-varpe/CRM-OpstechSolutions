<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->user = User::first() ?? User::factory()->create();
    }

    /**
     * Test customers index page renders the import button and modal dialog.
     */
    public function test_customer_page_renders_import_button_and_modal(): void
    {
        $response = $this->actingAs($this->user)->get(route('customers.index'));

        $response->assertStatus(200);
        $response->assertSee('Import Customers');
        $response->assertSee('id="import-customer-modal"', false);
        $response->assertSee('name', false);
        $response->assertSee('number', false);
        $response->assertSee('address', false);
        $response->assertSee('Analyze File');
        $response->assertSee('Download Sample CSV');
    }

    /**
     * Test sample CSV template download endpoint.
     */
    public function test_download_sample_import_template(): void
    {
        $response = $this->actingAs($this->user)->get(route('customers.import.sample'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('name,number,address', $content);
        $this->assertStringContainsString('Rohan Deshmukh', $content);
        $this->assertStringContainsString('9822011223', $content);
    }

    /**
     * Test analyzing a CSV file with complete and missing column values.
     */
    public function test_analyze_csv_with_complete_and_missing_values(): void
    {
        $csvContent = implode("\n", [
            "name,number,address",
            "Anand Verma,9822001122,\"Flat 202, Model Colony, Pune\"",
            "Pooja Patil,,\"Baner, Pune\"",
            ",9822998877,\"Hinjawadi Phase 1\"",
            "Kiran Rao,,",
        ]);

        $file = UploadedFile::fake()->createWithContent('customers.csv', $csvContent);

        $response = $this->actingAs($this->user)->postJson(route('customers.import.analyze'), [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'report' => [
                'total_rows' => 4,
                'complete_count' => 1,
                'partial_count' => 3,
                'blank_count' => 0,
            ],
        ]);

        $rows = $response->json('report.rows');
        $this->assertCount(4, $rows);

        // Row 1: Complete
        $this->assertEquals('Anand Verma', $rows[0]['name']);
        $this->assertEquals('9822001122', $rows[0]['number']);
        $this->assertEquals('COMPLETE', $rows[0]['status']);

        // Row 2: Missing number
        $this->assertEquals('Pooja Patil', $rows[1]['name']);
        $this->assertEquals('', $rows[1]['number']);
        $this->assertEquals('PARTIAL', $rows[1]['status']);
        $this->assertContains('Number', $rows[1]['missing']);

        // Row 3: Missing name
        $this->assertEquals('', $rows[2]['name']);
        $this->assertEquals('9822998877', $rows[2]['number']);
        $this->assertEquals('PARTIAL', $rows[2]['status']);
        $this->assertContains('Name', $rows[2]['missing']);

        // Row 4: Missing number and address
        $this->assertEquals('Kiran Rao', $rows[3]['name']);
        $this->assertEquals('', $rows[3]['number']);
        $this->assertEquals('', $rows[3]['address']);
        $this->assertEquals('PARTIAL', $rows[3]['status']);
        $this->assertContains('Number', $rows[3]['missing']);
        $this->assertContains('Address', $rows[3]['missing']);
    }

    /**
     * Test final import commit stores customers and keeps missing fields empty.
     */
    public function test_commit_import_stores_customers_and_keeps_missing_fields_empty(): void
    {
        $rows = [
            [
                'name' => 'Rajesh Sharma',
                'number' => '9822114455',
                'address' => 'Shop 5, Market Yard, Pune',
            ],
            [
                'name' => 'Snehal More',
                'number' => '', // Missing number kept empty
                'address' => 'Flat 12, Kothrud, Pune',
            ],
            [
                'name' => '', // Missing name kept empty
                'number' => '9822336699',
                'address' => 'Bhosari MIDC',
            ],
            [
                'name' => 'Vasant Joshi',
                'number' => '9822445566',
                'address' => '', // Missing address kept empty
            ],
        ];

        $initialCustomerCount = Customer::count();

        $response = $this->actingAs($this->user)->postJson(route('customers.import.commit'), [
            'rows' => $rows,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'imported_count' => 4,
        ]);

        $this->assertEquals($initialCustomerCount + 4, Customer::count());

        // Check Row 1: Rajesh Sharma
        $c1 = Customer::where('phone', '9822114455')->first();
        $this->assertNotNull($c1);
        $this->assertEquals('Rajesh Sharma', $c1->full_name);
        $this->assertNotNull($c1->customer_number);
        $this->assertStringStartsWith('CUST-', $c1->customer_number);
        $this->assertEquals('Shop 5, Market Yard, Pune', $c1->primaryAddress->address_line1);

        // Check Row 2: Snehal More with empty phone
        $c2 = Customer::where('full_name', 'Snehal More')->first();
        $this->assertNotNull($c2);
        $this->assertEquals('', $c2->phone);
        $this->assertEquals('Flat 12, Kothrud, Pune', $c2->primaryAddress->address_line1);

        // Check Row 3: Empty name with phone 9822336699
        $c3 = Customer::where('phone', '9822336699')->first();
        $this->assertNotNull($c3);
        $this->assertEquals('', $c3->full_name);
        $this->assertEquals('Bhosari MIDC', $c3->primaryAddress->address_line1);

        // Check Row 4: Vasant Joshi with empty address
        $c4 = Customer::where('full_name', 'Vasant Joshi')->first();
        $this->assertNotNull($c4);
        $this->assertEquals('9822445566', $c4->phone);
        $this->assertEquals('', $c4->primaryAddress->address_line1);
    }

    /**
     * Test analyzing an Excel (.xlsx) file.
     */
    public function test_analyze_excel_file(): void
    {
        $xlsxPath = '/home/siddharth/Downloads/SR CST DATA.xlsx';
        if (file_exists($xlsxPath)) {
            $file = new UploadedFile($xlsxPath, 'SR CST DATA.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

            $response = $this->actingAs($this->user)->postJson(route('customers.import.analyze'), [
                'file' => $file,
            ]);

            $response->assertStatus(200);
            $response->assertJson([
                'success' => true,
            ]);
            $this->assertGreaterThanOrEqual(100, $response->json('report.total_rows'));
            $this->assertArrayHasKey('headers', $response->json('report'));
            $this->assertArrayHasKey('detected_cols', $response->json('report'));
        }
    }

    /**
     * Test column mapping overrides during analyze.
     */
    public function test_column_mapping_override_during_analyze(): void
    {
        $csvContent = implode("\n", [
            "ID,Full Address,Client Contact,Customer Name",
            "1,\"Pune, Baner\",9822001122,Ramesh Kale",
            "2,\"Aundh, Pune\",9822998877,Seema Joshi",
        ]);

        $file = UploadedFile::fake()->createWithContent('custom_columns.csv', $csvContent);

        $response = $this->actingAs($this->user)->postJson(route('customers.import.analyze'), [
            'file' => $file,
            'name_col' => 3,    // Customer Name is col 3
            'number_col' => 2,  // Client Contact is col 2
            'address_col' => 1, // Full Address is col 1
        ]);

        $response->assertStatus(200);
        $rows = $response->json('report.rows');
        $this->assertEquals('Ramesh Kale', $rows[0]['name']);
        $this->assertEquals('9822001122', $rows[0]['number']);
        $this->assertEquals('Pune, Baner', $rows[0]['address']);
        $this->assertEquals('COMPLETE', $rows[0]['status']);
    }

    /**
     * Test large volume customer commit performance.
     */
    public function test_large_volume_customer_commit_performance(): void
    {
        $rows = [];
        for ($i = 1; $i <= 500; $i++) {
            $rows[] = [
                'name' => "Batch Customer {$i}",
                'number' => '9800000' . str_pad((string)$i, 3, '0', STR_PAD_LEFT),
                'address' => "Plot {$i}, Hinjawadi, Pune",
            ];
        }

        $start = microtime(true);
        $response = $this->actingAs($this->user)->postJson(route('customers.import.commit'), [
            'rows' => $rows,
        ]);
        $duration = microtime(true) - $start;

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'imported_count' => 500,
        ]);

        // 500 rows should commit in less than 3 seconds
        $this->assertLessThan(3.0, $duration, "500 rows took {$duration}s which is too slow");
    }
}
