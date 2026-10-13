<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sale_number')->unique(); // SALE-2026-0001
            $table->uuid('customer_id');
            $table->timestamp('sale_date');
            $table->string('status')->default('COMPLETED'); // DRAFT, COMPLETED, CANCELLED
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2);
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->index('sale_number');
            $table->index('customer_id');
            $table->index('sale_date');
            $table->index('status');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sale_id');
            $table->uuid('product_id');
            $table->string('product_name_snapshot');
            $table->string('sku_snapshot');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price_snapshot', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('tax_rate_percent', 5, 2)->default(18.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('line_total', 12, 2);
            $table->integer('warranty_months')->default(12);
            $table->integer('service_interval_months')->default(6);
            $table->string('serial_number')->nullable();
            $table->timestamp('next_service_date')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('sale_id');
            $table->index('product_id');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number')->unique(); // INV-2026-0001
            $table->uuid('customer_id');
            $table->uuid('sale_id')->nullable();
            $table->uuid('job_card_id')->nullable();
            $table->uuid('service_id')->nullable();
            $table->timestamp('invoice_date');
            $table->timestamp('due_date');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('cgst_amount', 12, 2)->default(0.00);
            $table->decimal('sgst_amount', 12, 2)->default(0.00);
            $table->decimal('igst_amount', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->decimal('balance_amount', 12, 2)->default(0.00);
            $table->string('status')->default('ISSUED'); // DRAFT, ISSUED, PARTIALLY_PAID, PAID, OVERDUE, CANCELLED
            $table->string('po_number')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->uuid('pdf_file_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->index('invoice_number');
            $table->index('customer_id');
            $table->index('sale_id');
            $table->index('due_date');
            $table->index('status');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->uuid('product_id')->nullable();
            $table->string('item_type')->default('PRODUCT'); // PRODUCT, SERVICE, SPARE_PART, CUSTOM
            $table->string('name_snapshot');
            $table->text('description_snapshot')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price_snapshot', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('tax_rate_percent', 5, 2)->default(18.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('line_total', 12, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->index('invoice_id');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('payment_number')->unique(); // PAY-2026-0001
            $table->uuid('customer_id');
            $table->uuid('invoice_id');
            $table->decimal('amount', 12, 2);
            $table->timestamp('payment_date');
            $table->string('payment_method')->default('CASH'); // CASH, UPI, CARD, BANK_TRANSFER, CHEQUE, OTHER
            $table->string('status')->default('COMPLETED'); // COMPLETED, PENDING, FAILED, CANCELLED, REFUNDED
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->index('payment_number');
            $table->index('customer_id');
            $table->index('invoice_id');
            $table->index('payment_date');
            $table->index('status');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
