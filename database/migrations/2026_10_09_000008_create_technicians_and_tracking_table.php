<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_portal_access', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('technician_id')->unique();
            $table->boolean('portal_enabled')->default(true);
            $table->string('pin_hash')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->foreign('technician_id')->references('id')->on('technicians')->onDelete('cascade');
        });

        Schema::create('technician_portal_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('technician_id');
            $table->string('type'); // NEW_ASSIGNMENT, SCHEDULE_CHANGE, CANCELLATION, REASSIGNMENT, JOB_UPDATE
            $table->string('title');
            $table->text('message');
            $table->string('reference_type')->nullable(); // SERVICE, JOB_CARD, PAYMENT, GENERAL
            $table->string('reference_id')->nullable();
            $table->string('dedup_key')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('technician_id');
            $table->index(['technician_id', 'is_read']);
            $table->foreign('technician_id')->references('id')->on('technicians')->onDelete('cascade');
        });

        Schema::create('technician_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('technician_id');
            $table->double('latitude');
            $table->double('longitude');
            $table->double('accuracy')->nullable();
            $table->double('speed')->nullable();
            $table->timestamp('recorded_at')->useCurrent();

            $table->index('technician_id');
            $table->index('recorded_at');
            $table->foreign('technician_id')->references('id')->on('technicians')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_locations');
        Schema::dropIfExists('technician_portal_notifications');
        Schema::dropIfExists('technician_portal_access');
    }
};
