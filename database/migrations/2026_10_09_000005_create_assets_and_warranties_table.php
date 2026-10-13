<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('asset_number')->unique(); // ASSET-2026-0001
            $table->uuid('customer_id');
            $table->uuid('product_id');
            $table->string('asset_type'); // RO_MACHINE, SPARE_PART
            $table->string('serial_number')->nullable();
            $table->string('custom_name')->nullable();
            $table->uuid('installation_address_id')->nullable();
            $table->timestamp('purchase_date');
            $table->integer('initial_warranty_months')->default(12);
            $table->integer('service_interval_months')->default(6);
            $table->string('status')->default('ACTIVE'); // ACTIVE, IN_SERVICE, REPLACED, DECOMMISSIONED
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('product_id');
            $table->index('serial_number');
            $table->index('status');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('installation_address_id')->references('id')->on('customer_addresses')->onDelete('set null');
        });

        Schema::create('warranties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('warranty_number')->unique(); // WAR-2026-0001
            $table->uuid('customer_id');
            $table->uuid('asset_id');
            $table->uuid('sale_id')->nullable();
            $table->string('warranty_type'); // STANDARD_MACHINE, EXTENDED_MACHINE, SPARE_PART
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->integer('duration_months');
            $table->string('status')->default('ACTIVE'); // ACTIVE, EXPIRING_SOON, EXPIRED, VOID
            $table->text('terms')->nullable();
            $table->timestamps();

            $table->index('warranty_number');
            $table->index('customer_id');
            $table->index('asset_id');
            $table->index('end_date');
            $table->index('status');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('asset_id')->references('id')->on('customer_assets')->onDelete('restrict');
        });

        Schema::create('warranty_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warranty_id');
            $table->uuid('customer_id');
            $table->uuid('asset_id');
            $table->string('event_type'); // ACTIVATED, EXTENDED, CLAIM_FILED, REPLACEMENT_APPROVED, REPLACEMENT_COMPLETED, VOIDED, EXPIRED
            $table->timestamp('event_date');
            $table->uuid('actor_id')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('replacement_asset_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('warranty_id');
            $table->index('asset_id');
            $table->foreign('warranty_id')->references('id')->on('warranties')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_events');
        Schema::dropIfExists('warranties');
        Schema::dropIfExists('customer_assets');
    }
};
