<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('rental_number')->unique(); // RNT-2026-0001
            $table->uuid('customer_id');
            $table->uuid('asset_id')->nullable();
            $table->timestamp('start_date');
            $table->timestamp('end_date')->nullable();
            $table->decimal('monthly_fee', 12, 2);
            $table->decimal('deposit_amount', 12, 2)->default(0.00);
            $table->integer('billing_day')->default(1);
            $table->string('status')->default('ACTIVE'); // ACTIVE, PAYMENT_DUE, OVERDUE, SUSPENDED, RETURNED, COMPLETED, CANCELLED, TERMINATED
            $table->string('billing_frequency')->default('MONTHLY'); // MONTHLY, QUARTERLY, HALF_YEARLY, YEARLY, CUSTOM
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('rental_number');
            $table->index('customer_id');
            $table->index('status');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('asset_id')->references('id')->on('customer_assets')->onDelete('set null');
        });

        Schema::create('rental_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('rental_id');
            $table->string('billing_month'); // e.g. "2026-10"
            $table->decimal('amount', 12, 2);
            $table->timestamp('due_date');
            $table->timestamp('paid_date')->nullable();
            $table->string('status')->default('DUE'); // PAID, PARTIALLY_PAID, NOT_PAID, DUE, OVERDUE
            $table->uuid('invoice_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('rental_id');
            $table->index('billing_month');
            $table->index('status');
            $table->foreign('rental_id')->references('id')->on('rentals')->onDelete('cascade');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('target_role')->nullable();
            $table->string('notification_type'); // NEW_INQUIRY, PAYMENT_RECEIVED, INVOICE_OVERDUE, REMINDER_DUE, SERVICE_DUE, etc.
            $table->string('title');
            $table->text('message');
            $table->string('severity')->default('INFO'); // INFO, SUCCESS, WARNING, CRITICAL
            $table->string('priority')->default('NORMAL'); // LOW, NORMAL, HIGH, URGENT
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('entity_type')->nullable(); // CUSTOMER, SALE, INVOICE, PAYMENT, SERVICE, JOB_CARD, WARRANTY
            $table->string('entity_id')->nullable();
            $table->string('action_url')->nullable();
            $table->string('event_key')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('is_read');
            $table->index('notification_type');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('inquiries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('inquiry_number')->unique(); // INQ-2026-000001
            $table->string('source')->default('DIRECT'); // WEBSITE, DIRECT, PHONE, WHATSAPP, REFERRAL, SOCIAL, WALK_IN, OTHER
            $table->string('customer_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('inquiry_type')->default('NEW_PURCHASE'); // NEW_PURCHASE, SERVICE, REPAIR, WARRANTY, INSTALLATION, GENERAL
            $table->string('status')->default('NEW'); // NEW, CONTACTED, FOLLOW_UP, IN_PROGRESS, QUALIFIED, CONVERTED, CLOSED, SPAM
            $table->string('priority')->default('NORMAL'); // LOW, NORMAL, HIGH, URGENT
            $table->text('notes')->nullable();
            $table->uuid('converted_customer_id')->nullable();
            $table->timestamps();

            $table->index('inquiry_number');
            $table->index('phone');
            $table->index('status');
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_type'); // CUSTOMER, SALE, INVOICE, SERVICE, JOB_CARD, PRODUCT, WARRANTY
            $table->uuid('entity_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->integer('file_size_bytes')->nullable();
            $table->string('storage_provider')->default('LOCAL'); // LOCAL, S3
            $table->uuid('uploaded_by')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->nullable();
            $table->string('phone');
            $table->string('direction')->default('OUTBOUND'); // INBOUND, OUTBOUND
            $table->string('message_type')->default('TEXT'); // TEXT, TEMPLATE, IMAGE, DOCUMENT
            $table->text('content');
            $table->string('status')->default('SENT'); // QUEUED, SENT, DELIVERED, READ, FAILED
            $table->text('error_message')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();

            $table->index('phone');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('rental_payments');
        Schema::dropIfExists('rentals');
    }
};
