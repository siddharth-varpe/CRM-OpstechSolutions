<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\BusinessSequence;
use App\Models\SystemSetting;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\InventoryItem;
use App\Models\CustomerAsset;
use App\Models\Warranty;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Technician;
use App\Models\TechnicianPortalAccess;
use App\Models\Service;
use App\Models\JobCard;
use App\Models\Rental;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin'], ['description' => 'System Owner with Unrestricted Privileges', 'is_system' => true]);
        $adminRole = Role::firstOrCreate(['name' => 'Admin'], ['description' => 'Business Administrator with Management Access', 'is_system' => true]);
        $staffRole = Role::firstOrCreate(['name' => 'Staff'], ['description' => 'Operations & Sales Staff', 'is_system' => true]);
        $techRole = Role::firstOrCreate(['name' => 'Technician'], ['description' => 'Field Service Technician', 'is_system' => true]);

        // 2. Permissions
        $permissionDefs = [
            ['key' => 'customers.view', 'name' => 'View Customers', 'module' => 'Customers'],
            ['key' => 'customers.create', 'name' => 'Create Customers', 'module' => 'Customers'],
            ['key' => 'customers.update', 'name' => 'Update Customers', 'module' => 'Customers'],
            ['key' => 'customers.archive', 'name' => 'Archive Customers', 'module' => 'Customers'],

            ['key' => 'products.view', 'name' => 'View Products', 'module' => 'Products'],
            ['key' => 'products.create', 'name' => 'Create Products', 'module' => 'Products'],
            ['key' => 'products.update', 'name' => 'Update Products', 'module' => 'Products'],
            ['key' => 'products.archive', 'name' => 'Archive Products', 'module' => 'Products'],

            ['key' => 'assets.view', 'name' => 'View Assets', 'module' => 'Assets'],
            ['key' => 'assets.create', 'name' => 'Create Assets', 'module' => 'Assets'],
            ['key' => 'assets.update', 'name' => 'Update Assets', 'module' => 'Assets'],

            ['key' => 'sales.view', 'name' => 'View Sales', 'module' => 'Sales'],
            ['key' => 'sales.create', 'name' => 'Create Sales', 'module' => 'Sales'],
            ['key' => 'sales.update', 'name' => 'Update Sales', 'module' => 'Sales'],
            ['key' => 'sales.confirm', 'name' => 'Confirm Sales', 'module' => 'Sales'],
            ['key' => 'sales.cancel', 'name' => 'Cancel Sales', 'module' => 'Sales'],

            ['key' => 'invoices.view', 'name' => 'View Invoices', 'module' => 'Invoices'],
            ['key' => 'invoices.create', 'name' => 'Create Invoices', 'module' => 'Invoices'],
            ['key' => 'invoices.update', 'name' => 'Update Invoices', 'module' => 'Invoices'],
            ['key' => 'invoices.cancel', 'name' => 'Cancel Invoices', 'module' => 'Invoices'],

            ['key' => 'payments.view', 'name' => 'View Payments', 'module' => 'Payments'],
            ['key' => 'payments.create', 'name' => 'Record Payments', 'module' => 'Payments'],
            ['key' => 'payments.modify', 'name' => 'Modify Payments', 'module' => 'Payments'],

            ['key' => 'warranties.view', 'name' => 'View Warranties', 'module' => 'Warranties'],
            ['key' => 'warranties.create', 'name' => 'Create Warranties', 'module' => 'Warranties'],
            ['key' => 'warranties.extend', 'name' => 'Extend Warranties', 'module' => 'Warranties'],

            ['key' => 'services.view', 'name' => 'View Services', 'module' => 'Services'],
            ['key' => 'services.create', 'name' => 'Schedule Services', 'module' => 'Services'],
            ['key' => 'services.assign', 'name' => 'Assign Services', 'module' => 'Services'],
            ['key' => 'services.complete', 'name' => 'Complete Services', 'module' => 'Services'],
            ['key' => 'services.cancel', 'name' => 'Cancel Services', 'module' => 'Services'],

            ['key' => 'job_cards.view', 'name' => 'View Job Cards', 'module' => 'Job Cards'],
            ['key' => 'job_cards.create', 'name' => 'Create Job Cards', 'module' => 'Job Cards'],
            ['key' => 'job_cards.update', 'name' => 'Update Job Cards', 'module' => 'Job Cards'],
            ['key' => 'job_cards.close', 'name' => 'Close Job Cards', 'module' => 'Job Cards'],

            ['key' => 'technicians.view', 'name' => 'View Technicians', 'module' => 'Technicians'],
            ['key' => 'technicians.manage', 'name' => 'Manage Technicians', 'module' => 'Technicians'],

            ['key' => 'inquiries.view', 'name' => 'View Inquiries', 'module' => 'Inquiries'],
            ['key' => 'inquiries.create', 'name' => 'Create Inquiries', 'module' => 'Inquiries'],

            ['key' => 'rentals.view', 'name' => 'View Rentals', 'module' => 'Rentals'],
            ['key' => 'rentals.create', 'name' => 'Create Rentals', 'module' => 'Rentals'],

            ['key' => 'reports.view', 'name' => 'View Reports', 'module' => 'Analytics'],
            ['key' => 'reports.export', 'name' => 'Export Reports', 'module' => 'Analytics'],

            ['key' => 'users.view', 'name' => 'View Users', 'module' => 'Administration'],
            ['key' => 'users.manage', 'name' => 'Manage Users', 'module' => 'Administration'],
            ['key' => 'settings.manage', 'name' => 'Manage Settings', 'module' => 'Administration'],
        ];

        $permIds = [];
        foreach ($permissionDefs as $def) {
            $p = Permission::firstOrCreate(['key' => $def['key']], $def);
            $permIds[$def['key']] = $p->id;
        }

        // Attach all permissions to Super Admin & Admin
        $superAdminRole->permissions()->sync(array_values($permIds));
        $adminRole->permissions()->sync(array_values($permIds));

        // Staff permissions
        $staffKeys = array_filter(array_keys($permIds), fn($k) => !str_starts_with($k, 'users.') && !str_starts_with($k, 'settings.'));
        $staffRole->permissions()->sync(array_intersect_key($permIds, array_flip($staffKeys)));

        // Tech permissions
        $techKeys = ['services.view', 'services.complete', 'job_cards.view', 'job_cards.update', 'job_cards.close', 'customers.view', 'assets.view'];
        $techRole->permissions()->sync(array_intersect_key($permIds, array_flip($techKeys)));

        // 3. System Users
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'display_name' => 'System Administrator',
                'email' => 'admin@enterprisescrm.com',
                'password' => Hash::make('admin'),
                'role' => 'Super Admin',
                'status' => 'ACTIVE',
            ]
        );

        $staff = User::firstOrCreate(
            ['username' => 'staff'],
            [
                'display_name' => 'Pooja Sharma',
                'email' => 'pooja@enterprisescrm.com',
                'password' => Hash::make('staff'),
                'role' => 'Staff',
                'status' => 'ACTIVE',
            ]
        );

        $techUser = User::firstOrCreate(
            ['username' => 'tech'],
            [
                'display_name' => 'Suresh Patil',
                'email' => 'suresh@enterprisescrm.com',
                'password' => Hash::make('tech'),
                'role' => 'Technician',
                'status' => 'ACTIVE',
            ]
        );

        // 4. Technician Profile
        $technician = Technician::firstOrCreate(
            ['phone' => '9822012345'],
            [
                'user_id' => $techUser->id,
                'full_name' => 'Suresh Patil',
                'email' => 'suresh@enterprisescrm.com',
                'status' => 'ACTIVE',
                'skills' => ['Equipment Installation', 'Component Replacement', 'Preventive Maintenance', 'Commercial Diagnostics'],
                'address' => 'Plot 45, MIDC Industrial Area, Pune',
                'emergency_contact' => '9822099999',
            ]
        );

        // Assign unique portal PIN for technician
        TechnicianPortalAccess::firstOrCreate(
            ['technician_id' => $technician->id],
            [
                'portal_enabled' => true,
                'pin_hash' => Hash::make('5821'),
            ]
        );

        // 5. Business Sequences
        $sequences = [
            'CUSTOMER' => ['prefix' => 'CUST', 'padding' => 4],
            'INVOICE' => ['prefix' => 'INV', 'padding' => 4],
            'SALE' => ['prefix' => 'SALE', 'padding' => 4],
            'SERVICE' => ['prefix' => 'SRV', 'padding' => 4],
            'JOB_CARD' => ['prefix' => 'JC', 'padding' => 4],
            'PAYMENT' => ['prefix' => 'PAY', 'padding' => 4],
            'WARRANTY' => ['prefix' => 'WAR', 'padding' => 4],
            'ASSET' => ['prefix' => 'ASSET', 'padding' => 4],
            'INQUIRY' => ['prefix' => 'INQ', 'padding' => 6],
            'RENTAL' => ['prefix' => 'RNT', 'padding' => 4],
        ];

        foreach ($sequences as $name => $cfg) {
            BusinessSequence::firstOrCreate(
                ['name' => $name],
                [
                    'prefix' => $cfg['prefix'],
                    'current_val' => 0,
                    'padding' => $cfg['padding'],
                    'year_reset' => true,
                    'current_year' => (int) date('Y'),
                ]
            );
        }

        // 6. Products Catalog
        $prod1 = Product::firstOrCreate(
            ['sku' => 'RO-COMM-50LPH'],
            [
                'name' => 'Commercial System 50 LPH',
                'product_type' => 'RO_MACHINE',
                'brand' => 'Enterprises CRM',
                'model' => 'C50-PRO',
                'description' => 'Industrial stainless steel skid with dual membrane and automatic flush',
                'unit_price' => 38500.00,
                'tax_rate_percent' => 18.00,
                'default_warranty_months' => 12,
                'default_service_interval_months' => 3,
                'is_active' => true,
            ]
        );

        $prod2 = Product::firstOrCreate(
            ['sku' => 'RO-DOM-15LPH'],
            [
                'name' => 'Standard Domestic Unit 15 LPH',
                'product_type' => 'RO_MACHINE',
                'brand' => 'Enterprises CRM',
                'model' => 'D15-ALK',
                'description' => 'Multi-stage RO + UV + UF with copper-alkaline mineral cartidge',
                'unit_price' => 14500.00,
                'tax_rate_percent' => 18.00,
                'default_warranty_months' => 12,
                'default_service_interval_months' => 6,
                'is_active' => true,
            ]
        );

        // 7. Inventory Items (Spare Parts)
        InventoryItem::firstOrCreate(
            ['part_number' => 'SED-10-INCH'],
            [
                'name' => 'Spun Polypropylene Sediment Filter 10"',
                'category' => 'Filter',
                'brand' => 'SR OEM',
                'description' => '5 micron depth filtration cartridge',
                'purchase_price' => 85.00,
                'selling_price' => 250.00,
                'current_stock' => 150,
                'min_stock_level' => 30,
                'status' => 'ACTIVE',
            ]
        );

        InventoryItem::firstOrCreate(
            ['part_number' => 'MEM-75GPD-FT'],
            [
                'name' => 'High-Flow Filter 75 GPD Dow',
                'category' => 'Membrane',
                'brand' => 'Dow Filmtec',
                'description' => 'High rejection polyamide thin-film composite membrane',
                'purchase_price' => 1100.00,
                'selling_price' => 2200.00,
                'current_stock' => 45,
                'min_stock_level' => 10,
                'status' => 'ACTIVE',
            ]
        );

        InventoryItem::firstOrCreate(
            ['part_number' => 'PUMP-100GPD-KMF'],
            [
                'name' => 'Kemflo Diaphragm Booster Pump 100 GPD',
                'category' => 'Pump',
                'brand' => 'Kemflo',
                'description' => '24V DC heavy-duty RO booster pump',
                'purchase_price' => 1350.00,
                'selling_price' => 2800.00,
                'current_stock' => 28,
                'min_stock_level' => 5,
                'status' => 'ACTIVE',
            ]
        );

        // 8. Sample Customer & Asset
        $custNum = BusinessSequence::nextNumber('CUSTOMER');
        $customer = Customer::firstOrCreate(
            ['phone' => '9850123456'],
            [
                'customer_number' => $custNum,
                'full_name' => 'Sahyadri Hospital (Swargate)',
                'email' => 'facility@sahyadrihospital.org',
                'customer_type' => 'COMMERCIAL',
                'company_name' => 'Sahyadri Speciality Hospitals Ltd',
                'gst_number' => '27AAACS1234F1Z8',
                'status' => 'ACTIVE',
                'customer_label' => 'GOOD',
                'notes' => 'Commercial equipment installation at 3rd Floor Cafeteria',
                'created_by' => $admin->id,
            ]
        );

        $address = CustomerAddress::firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'address_type' => 'SERVICE',
                'address_line1' => 'Plot 33, Karve Road, Near Deccan Gymkhana',
                'address_line2' => 'Opposite Goodluck Cafe',
                'landmark' => 'Deccan Corner',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'postal_code' => '411004',
                'latitude' => 18.5167,
                'longitude' => 73.8415,
                'is_default' => true,
            ]
        );

        $assetNum = BusinessSequence::nextNumber('ASSET');
        $asset = CustomerAsset::firstOrCreate(
            ['serial_number' => 'SR-2026-C50-0089'],
            [
                'asset_number' => $assetNum,
                'customer_id' => $customer->id,
                'product_id' => $prod1->id,
                'asset_type' => 'RO_MACHINE',
                'custom_name' => 'Cafeteria 50 LPH Commercial Unit',
                'installation_address_id' => $address->id,
                'purchase_date' => now()->subMonths(3),
                'initial_warranty_months' => 12,
                'service_interval_months' => 3,
                'status' => 'ACTIVE',
                'notes' => 'Installed on 3rd floor cafeteria line with pre-filter pressure gauge',
            ]
        );

        // Warranty
        $warNum = BusinessSequence::nextNumber('WARRANTY');
        $warranty = Warranty::firstOrCreate(
            ['asset_id' => $asset->id],
            [
                'warranty_number' => $warNum,
                'customer_id' => $customer->id,
                'warranty_type' => 'STANDARD_MACHINE',
                'start_date' => now()->subMonths(3),
                'end_date' => now()->addMonths(9),
                'duration_months' => 12,
                'status' => 'ACTIVE',
                'terms' => 'Covers booster pump, SMPS, and electronic components. Filters and membranes excluded.',
            ]
        );

        // 9. Initial Invoice & Payment
        $invNum = BusinessSequence::nextNumber('INVOICE');
        $invoice = Invoice::firstOrCreate(
            ['invoice_number' => $invNum],
            [
                'customer_id' => $customer->id,
                'invoice_date' => now()->subMonths(3),
                'due_date' => now()->subMonths(2),
                'subtotal' => 38500.00,
                'discount_amount' => 0.00,
                'tax_amount' => 6930.00,
                'cgst_amount' => 3465.00,
                'sgst_amount' => 3465.00,
                'igst_amount' => 0.00,
                'total_amount' => 45430.00,
                'paid_amount' => 45430.00,
                'balance_amount' => 0.00,
                'status' => 'PAID',
                'created_by' => $admin->id,
            ]
        );

        InvoiceItem::firstOrCreate(
            ['invoice_id' => $invoice->id, 'name_snapshot' => $prod1->name],
            [
                'product_id' => $prod1->id,
                'item_type' => 'PRODUCT',
                'quantity' => 1,
                'unit_price_snapshot' => 38500.00,
                'discount_amount' => 0.00,
                'tax_rate_percent' => 18.00,
                'tax_amount' => 6930.00,
                'line_total' => 45430.00,
            ]
        );

        Payment::firstOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'payment_number' => 'PAY-2026-0000',
                'customer_id' => $customer->id,
                'amount' => 45430.00,
                'payment_date' => now()->subMonths(3),
                'payment_method' => 'BANK_TRANSFER',
                'status' => 'COMPLETED',
                'reference_number' => 'NEFT-HDFC-991284',
                'notes' => 'Settled in full upon installation',
                'created_by' => $admin->id,
            ]
        );

        // 10. Scheduled Service for Technician
        $srvNum = BusinessSequence::nextNumber('SERVICE');
        $service = Service::firstOrCreate(
            ['service_number' => $srvNum],
            [
                'customer_id' => $customer->id,
                'asset_id' => $asset->id,
                'warranty_id' => $warranty->id,
                'technician_id' => $technician->id,
                'service_type' => 'PERIODIC_MAINTENANCE',
                'service_location' => 'DOORSTEP',
                'service_classification' => 'WARRANTY',
                'scheduled_date' => now()->addDays(2),
                'scheduled_time_slot' => '10:00 AM - 12:00 PM',
                'status' => 'ASSIGNED',
                'priority' => 'HIGH',
                'customer_notes' => 'TDS output check and sediment cartridge cleaning required.',
                'internal_notes' => 'Take replacement spun filter and TDS meter calibration solution.',
                'created_by' => $admin->id,
            ]
        );

        $jcNum = BusinessSequence::nextNumber('JOB_CARD');
        JobCard::firstOrCreate(
            ['job_card_number' => $jcNum],
            [
                'service_id' => $service->id,
                'customer_id' => $customer->id,
                'asset_id' => $asset->id,
                'technician_id' => $technician->id,
                'problem_reported' => 'TDS output check and sediment cartridge cleaning required.',
                'status' => 'ASSIGNED',
                'labor_charges' => 350.00,
                'parts_charges' => 0.00,
                'total_charges' => 350.00,
            ]
        );

        // 11. System Settings
        SystemSetting::set('company_name', 'Enterprises CRM', 'Official registered company name');
        SystemSetting::set('gst_number', '27AAACS1234F1Z8', 'Default company GSTIN');
        SystemSetting::set('default_gst_rate', '0', 'Default GST rate percentage for all invoices and sales');
    }
}
