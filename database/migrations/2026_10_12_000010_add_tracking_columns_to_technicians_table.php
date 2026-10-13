<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->double('current_latitude')->nullable()->after('address');
            $table->double('current_longitude')->nullable()->after('current_latitude');
            $table->double('current_accuracy')->nullable()->after('current_longitude');
            $table->double('current_heading')->nullable()->after('current_accuracy');
            $table->double('current_speed')->nullable()->after('current_heading');
            $table->string('tracking_status')->default('IDLE')->after('current_speed'); // IDLE, ON_THE_WAY, ARRIVED, UNTRACKED
            $table->uuid('active_service_id')->nullable()->after('tracking_status');
            $table->double('destination_latitude')->nullable()->after('active_service_id');
            $table->double('destination_longitude')->nullable()->after('destination_latitude');
            $table->text('destination_address')->nullable()->after('destination_longitude');
            $table->double('distance_km')->nullable()->after('destination_address');
            $table->integer('eta_minutes')->nullable()->after('distance_km');
            $table->timestamp('last_ping_at')->nullable()->after('eta_minutes');

            $table->index('tracking_status');
            $table->index('last_ping_at');
        });
    }

    public function down(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->dropIndex(['tracking_status']);
            $table->dropIndex(['last_ping_at']);
            $table->dropColumn([
                'current_latitude',
                'current_longitude',
                'current_accuracy',
                'current_heading',
                'current_speed',
                'tracking_status',
                'active_service_id',
                'destination_latitude',
                'destination_longitude',
                'destination_address',
                'distance_km',
                'eta_minutes',
                'last_ping_at',
            ]);
        });
    }
};
