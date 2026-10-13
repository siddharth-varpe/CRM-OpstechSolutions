<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technicians', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->unique();
            $table->string('full_name');
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->string('status')->default('ACTIVE'); // ACTIVE, ON_LEAVE, INACTIVE
            $table->json('skills')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->timestamps();

            $table->index('phone');
            $table->index('status');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('service_number')->unique(); // SRV-2026-0001
            $table->uuid('customer_id');
            $table->uuid('asset_id');
            $table->uuid('warranty_id')->nullable();
            $table->uuid('technician_id')->nullable();
            $table->string('service_type'); // INSTALLATION, REPAIR, PERIODIC_MAINTENANCE, EMERGENCY, SPARE_REPLACEMENT
            $table->string('service_location')->default('DOORSTEP'); // DOORSTEP, IN_SHOP
            $table->string('service_classification')->default('GENERAL'); // GENERAL, WARRANTY
            $table->timestamp('scheduled_date');
            $table->string('scheduled_time_slot')->nullable();
            $table->string('status')->default('SCHEDULED'); // SCHEDULED, ASSIGNED, IN_PROGRESS, ON_HOLD, COMPLETED, CANCELLED, OVERDUE
            $table->string('priority')->default('NORMAL'); // LOW, NORMAL, HIGH, URGENT
            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->index('service_number');
            $table->index('customer_id');
            $table->index('asset_id');
            $table->index('technician_id');
            $table->index('scheduled_date');
            $table->index('status');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('asset_id')->references('id')->on('customer_assets')->onDelete('restrict');
            $table->foreign('warranty_id')->references('id')->on('warranties')->onDelete('set null');
            $table->foreign('technician_id')->references('id')->on('technicians')->onDelete('set null');
        });

        Schema::create('service_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->uuid('asset_id');
            $table->uuid('warranty_id')->nullable();
            $table->integer('schedule_index');
            $table->integer('total_schedules');
            $table->timestamp('planned_date');
            $table->string('target_month'); // e.g. "2026-10"
            $table->string('status')->default('PENDING'); // PENDING, SERVICE_CREATED, COMPLETED, SKIPPED
            $table->uuid('generated_service_id')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('asset_id');
            $table->index('planned_date');
            $table->index('status');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('asset_id')->references('id')->on('customer_assets')->onDelete('restrict');
            $table->foreign('warranty_id')->references('id')->on('warranties')->onDelete('cascade');
            $table->foreign('generated_service_id')->references('id')->on('services')->onDelete('set null');
        });

        Schema::create('job_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('job_card_number')->unique(); // JC-2026-0001
            $table->uuid('service_id')->unique();
            $table->uuid('customer_id');
            $table->uuid('asset_id');
            $table->uuid('technician_id')->nullable();
            $table->text('problem_reported')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('work_performed')->nullable();
            $table->json('parts_replaced')->nullable();
            $table->text('technician_notes')->nullable();
            $table->text('customer_remarks')->nullable();
            $table->uuid('customer_signature_file_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('labor_charges', 12, 2)->default(0.00);
            $table->decimal('parts_charges', 12, 2)->default(0.00);
            $table->decimal('total_charges', 12, 2)->default(0.00);
            $table->integer('next_service_recommendation_months')->nullable();
            $table->text('next_service_notes')->nullable();
            $table->string('status')->default('SCHEDULED'); // SCHEDULED, ASSIGNED, STARTED, DIAGNOSIS, IN_PROGRESS, ON_HOLD, CANCELLED, COMPLETED, CUSTOMER_CONFIRMED, CLOSED
            $table->timestamps();

            $table->index('job_card_number');
            $table->index('service_id');
            $table->index('customer_id');
            $table->index('technician_id');
            $table->index('status');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('restrict');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('asset_id')->references('id')->on('customer_assets')->onDelete('restrict');
            $table->foreign('technician_id')->references('id')->on('technicians')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_cards');
        Schema::dropIfExists('service_schedules');
        Schema::dropIfExists('services');
        Schema::dropIfExists('technicians');
    }
};
