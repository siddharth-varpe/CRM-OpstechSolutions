<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_custom_labels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('color');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_number')->unique();
            $table->string('full_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('customer_type')->default('INDIVIDUAL'); // INDIVIDUAL, COMMERCIAL
            $table->string('company_name')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('status')->default('ACTIVE'); // ACTIVE, INACTIVE, ARCHIVED
            $table->string('customer_label')->nullable(); // GOOD, BAD
            $table->uuid('custom_label_id')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();

            $table->index('customer_number');
            $table->index('phone');
            $table->index('email');
            $table->index('status');
            $table->index('created_at');
            $table->foreign('custom_label_id')->references('id')->on('customer_custom_labels')->onDelete('set null');
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->string('address_type')->default('SERVICE'); // BILLING, SERVICE, BOTH
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('landmark')->nullable();
            $table->string('city')->default('');
            $table->string('state')->default('');
            $table->string('postal_code')->default('');
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('customer_custom_labels');
    }
};
