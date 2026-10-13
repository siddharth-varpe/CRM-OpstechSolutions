<?php

namespace App\Services;

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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DataCleanupService
{
    /**
     * Compute actual record counts for all eligible operational categories
     * and list protected system entities.
     */
    public function getImpactPreview(): array
    {
        $categories = [
            'customers' => [
                'label' => 'Customers & Addresses',
                'description' => 'Customer profiles, contact records, commercial entities and service addresses',
                'count' => Customer::count() + CustomerAddress::count(),
            ],
            'customer_assets' => [
                'label' => 'Customer Assets & Equipment',
                'description' => 'Installed RO water machines, unit serial numbers and customer machine histories',
                'count' => CustomerAsset::count(),
            ],
            'sales' => [
                'label' => 'Sales & Line Items',
                'description' => 'Sales orders, line item snapshots, discounts and order status records',
                'count' => Sale::count() + SaleItem::count(),
            ],
            'invoices' => [
                'label' => 'Invoices & Billing',
                'description' => 'Official GST tax invoices, invoice item lines, and tax calculations',
                'count' => Invoice::count() + InvoiceItem::count(),
            ],
            'payments' => [
                'label' => 'Payment Receipts & Dues',
                'description' => 'Customer payment transactions, settlement logs and ledger receipts',
                'count' => Payment::count(),
            ],
            'services' => [
                'label' => 'Service Operations & Schedules',
                'description' => 'Service dispatch tickets, periodic maintenance schedules and appointment history',
                'count' => Service::count() + ServiceSchedule::count(),
            ],
            'job_cards' => [
                'label' => 'Technician Job Cards',
                'description' => 'Field repair job cards, diagnoses, parts replaced and technician work logs',
                'count' => JobCard::count(),
            ],
            'warranties' => [
                'label' => 'Warranties & AMC Events',
                'description' => 'Equipment warranty policies, claim events, warranty extensions and replacement logs',
                'count' => Warranty::count() + WarrantyEvent::count(),
            ],
            'rentals' => [
                'label' => 'Rentals & Payment Schedules',
                'description' => 'Commercial equipment rental contracts and monthly billing dues',
                'count' => Rental::count() + RentalPayment::count(),
            ],
            'inventory_transactions' => [
                'label' => 'Inventory Purchases & Sales',
                'description' => 'Supplier purchase entries and retail counter sales (item catalogue preserved with stock reset to 0)',
                'count' => InventoryPurchase::count() + InventorySale::count(),
            ],
            'technician_activity' => [
                'label' => 'Field Workforce Dispatch Activity',
                'description' => 'GPS location breadcrumbs, live tracking status and mobile portal notifications',
                'count' => TechnicianLocation::count() + DB::table('technician_portal_notifications')->count(),
            ],
            'inquiries_leads' => [
                'label' => 'Enquiries & Customer Communications',
                'description' => 'Inbound sales enquiries, leads, and customer WhatsApp messaging records',
                'count' => Inquiry::count() + DB::table('whatsapp_messages')->count(),
            ],
            'operational_notifications' => [
                'label' => 'Operational Reminders & Alerts',
                'description' => 'Business alerts, overdue notices, payment reminders and operational notifications',
                'count' => Notification::count(),
            ],
            'documents' => [
                'label' => 'Attached Files & Documents',
                'description' => 'Uploaded files and attachments linked to customers, invoices, services and job cards',
                'count' => Document::count(),
            ],
        ];

        $totalRecords = array_sum(array_column($categories, 'count'));

        $preservedEntities = [
            'Super Administrator Account' => 'Your authenticated account and administrative credentials will remain 100% active and intact.',
            'Staff & Technician Users' => 'User login accounts (' . User::count() . ' users) and field staff directory are preserved.',
            'Roles & Permissions' => 'Access control rules (' . Role::count() . ' roles, ' . DB::table('permissions')->count() . ' permissions) remain fully intact.',
            'Product & Spare Parts Catalogue' => 'Master product definitions (' . Product::count() . ' products, ' . InventoryItem::count() . ' catalog items) are retained.',
            'Company & GST Settings' => 'Official trade name, GSTIN, bank settlement details and invoice terms are preserved.',
            'Database Schema & Migrations' => 'All tables, columns, indexes, constraints, and migration history remain unchanged.',
            'Audit Trail Log' => 'Historical security records and the new cleanup audit log will be permanently preserved.',
        ];

        return [
            'total_records' => $totalRecords,
            'categories' => $categories,
            'preserved_entities' => $preservedEntities,
            'database_connection' => config('database.default'),
            'backup_advice' => 'The system is configured on ' . config('database.default') . ' storage. Please confirm you have created and independently verified an offline backup before executing this permanent deletion.',
        ];
    }

    /**
     * Execute dependency-aware deletion within a database transaction.
     *
     * @param User $actingUser The authenticated Super Admin initiating the cleanup
     * @return array Summary of deleted records
     * @throws \Throwable
     */
    public function executeCleanup(User $actingUser): array
    {
        $deletedCounts = [];

        DB::transaction(function () use ($actingUser, &$deletedCounts) {
            // 1. Technician Operational Activity & Locations
            $deletedCounts['technician_locations'] = TechnicianLocation::query()->delete();
            $deletedCounts['technician_portal_notifications'] = DB::table('technician_portal_notifications')->delete();

            // Reset technician dispatch state to IDLE without deleting the technician master profiles
            Technician::query()->update([
                'active_service_id' => null,
                'tracking_status' => 'IDLE',
                'destination_latitude' => null,
                'destination_longitude' => null,
                'destination_address' => null,
                'distance_km' => null,
                'eta_minutes' => null,
                'last_ping_at' => null,
            ]);

            // 2. Job Cards
            $deletedCounts['job_cards'] = JobCard::query()->delete();

            // 3. Services & Service Schedules
            $deletedCounts['service_schedules'] = ServiceSchedule::query()->delete();
            $deletedCounts['services'] = Service::query()->delete();

            // 4. Warranties & Warranty Events
            $deletedCounts['warranty_events'] = WarrantyEvent::query()->delete();
            $deletedCounts['warranties'] = Warranty::query()->delete();

            // 5. Rentals & Customer Assets
            $deletedCounts['rental_payments'] = RentalPayment::query()->delete();
            $deletedCounts['rentals'] = Rental::query()->delete();
            $deletedCounts['customer_assets'] = CustomerAsset::query()->delete();

            // 6. Payments, Invoices & Sales
            $deletedCounts['payments'] = Payment::query()->delete();
            $deletedCounts['invoice_items'] = InvoiceItem::query()->delete();
            $deletedCounts['invoices'] = Invoice::query()->delete();
            $deletedCounts['sale_items'] = SaleItem::query()->delete();
            $deletedCounts['sales'] = Sale::query()->delete();

            // 7. Inventory Transactions & Reset Stock
            $deletedCounts['inventory_sales'] = InventorySale::query()->delete();
            $deletedCounts['inventory_purchases'] = InventoryPurchase::query()->delete();
            InventoryItem::query()->update(['current_stock' => 0]);

            // 8. Customers & Addresses
            $deletedCounts['customer_addresses'] = CustomerAddress::query()->delete();
            $deletedCounts['customers'] = Customer::query()->delete();

            // 9. Communications, Inquiries, Notifications & Documents
            $deletedCounts['whatsapp_messages'] = DB::table('whatsapp_messages')->delete();
            $deletedCounts['inquiries'] = Inquiry::query()->delete();
            $deletedCounts['notifications'] = Notification::query()->delete();

            // Safely cleanup physical document files referenced by records
            $deletedFilesCount = $this->cleanupDocumentFiles();
            $deletedCounts['documents'] = Document::query()->delete();
            $deletedCounts['physical_files_removed'] = $deletedFilesCount;

            // 10. Reset Business Sequences to 0 for fresh numbering
            BusinessSequence::query()->update(['current_val' => 0]);

            // 11. Write Audit Trail Entry
            AuditLog::create([
                'id' => (string) Str::uuid(),
                'user_id' => $actingUser->id,
                'action' => 'DATA_CLEANUP',
                'entity_type' => 'CRM_BUSINESS_DATA',
                'entity_id' => 'full_reset',
                'old_values' => null,
                'new_values' => [
                    'actor_username' => $actingUser->username,
                    'actor_role' => $actingUser->role,
                    'deleted_records' => $deletedCounts,
                    'total_records_deleted' => array_sum(array_filter($deletedCounts, fn($k) => $k !== 'physical_files_removed', ARRAY_FILTER_USE_KEY)),
                    'executed_at' => now()->toIso8601String(),
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);
        });

        return [
            'success' => true,
            'message' => 'CRM business data has been cleaned successfully. Protected administrator access, essential application configuration, and database structure have been preserved.',
            'deleted_counts' => $deletedCounts,
            'total_deleted' => array_sum(array_filter($deletedCounts, fn($k) => $k !== 'physical_files_removed', ARRAY_FILTER_USE_KEY)),
        ];
    }

    /**
     * Safely delete physical files stored locally that belonged to cleaned records.
     */
    protected function cleanupDocumentFiles(): int
    {
        $count = 0;
        $documents = Document::where('storage_provider', 'LOCAL')->get();

        foreach ($documents as $doc) {
            $path = $doc->file_path;
            if (!$path) continue;

            // Strict path traversal defense: prevent traversal outside storage
            if (str_contains($path, '..') || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
                continue;
            }

            try {
                if (Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                    $count++;
                } elseif (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                    $count++;
                }
            } catch (\Throwable $e) {
                // Silently skip if file already unlinked or unavailable
            }
        }

        return $count;
    }
}
