<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('product_type'); // RO_MACHINE, SPARE_PART
            $table->string('brand');
            $table->string('model')->nullable();
            $table->text('description')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('tax_rate_percent', 5, 2)->default(18.00);
            $table->integer('default_warranty_months')->default(12);
            $table->integer('default_service_interval_months')->default(6);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();

            $table->index('sku');
            $table->index('product_type');
            $table->index('brand');
            $table->index('is_active');
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('category'); // Filter, Membrane, Pump, SMPS, Fitting, Tubing, Accessories, Other
            $table->string('brand')->nullable();
            $table->string('part_number')->nullable();
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('selling_price', 12, 2);
            $table->integer('current_stock')->default(0);
            $table->integer('min_stock_level')->default(0);
            $table->string('status')->default('ACTIVE'); // ACTIVE, INACTIVE, DISCONTINUED
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();

            $table->index('name');
            $table->index('category');
            $table->index('part_number');
            $table->index('status');
        });

        Schema::create('inventory_purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('purchase_number')->unique();
            $table->uuid('item_id');
            $table->string('supplier_name')->nullable();
            $table->timestamp('purchase_date');
            $table->integer('quantity');
            $table->integer('remaining_quantity'); // Available for FIFO
            $table->decimal('purchase_price_per_unit', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('item_id');
            $table->index('purchase_date');
            $table->foreign('item_id')->references('id')->on('inventory_items')->onDelete('restrict');
        });

        Schema::create('inventory_sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sale_number')->unique();
            $table->uuid('item_id');
            $table->uuid('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->timestamp('sale_date');
            $table->integer('quantity');
            $table->decimal('selling_price_per_unit', 12, 2);
            $table->decimal('purchase_cost_per_unit', 12, 2); // FIFO unit cost
            $table->decimal('total_sale_amount', 12, 2);
            $table->decimal('total_cost_amount', 12, 2);
            $table->decimal('profit', 12, 2);
            $table->string('payment_status')->default('COMPLETED'); // COMPLETED, PENDING
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('item_id');
            $table->index('customer_id');
            $table->index('sale_date');
            $table->foreign('item_id')->references('id')->on('inventory_items')->onDelete('restrict');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_sales');
        Schema::dropIfExists('inventory_purchases');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('products');
    }
};
