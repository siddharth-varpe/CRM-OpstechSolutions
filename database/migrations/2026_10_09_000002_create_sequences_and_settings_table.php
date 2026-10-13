<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_sequences', function (Blueprint $table) {
            $table->string('name')->primary(); // 'CUSTOMER', 'INVOICE', 'SALE', 'SERVICE', 'JOB_CARD', 'PAYMENT', 'WARRANTY', 'ASSET', 'INQUIRY', 'RENTAL'
            $table->string('prefix');
            $table->bigInteger('current_val')->default(0);
            $table->integer('padding')->default(4);
            $table->boolean('year_reset')->default(true);
            $table->integer('current_year')->default(date('Y'));
            $table->timestamps();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('business_sequences');
    }
};
