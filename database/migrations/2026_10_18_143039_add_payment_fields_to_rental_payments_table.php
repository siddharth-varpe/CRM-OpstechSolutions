<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rental_payments', function (Blueprint $table) {
            $table->string('payment_number')->nullable()->after('id');
            $table->string('payment_method')->nullable()->after('paid_date');
            $table->string('reference_number')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_payments', function (Blueprint $table) {
            $table->dropColumn(['payment_number', 'payment_method', 'reference_number']);
        });
    }
};
