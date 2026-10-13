<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Technician extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'full_name',
        'phone',
        'email',
        'status',
        'skills',
        'address',
        'emergency_contact',
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
    ];

    protected $casts = [
        'skills' => 'array',
        'current_latitude' => 'float',
        'current_longitude' => 'float',
        'current_accuracy' => 'float',
        'current_heading' => 'float',
        'current_speed' => 'float',
        'destination_latitude' => 'float',
        'destination_longitude' => 'float',
        'distance_km' => 'float',
        'eta_minutes' => 'integer',
        'last_ping_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'technician_id');
    }

    public function activeService()
    {
        return $this->belongsTo(Service::class, 'active_service_id');
    }

    public function jobCards()
    {
        return $this->hasMany(JobCard::class, 'technician_id');
    }

    public function portalAccess()
    {
        return $this->hasOne(TechnicianPortalAccess::class, 'technician_id');
    }

    public function locations()
    {
        return $this->hasMany(TechnicianLocation::class, 'technician_id')->latest('recorded_at');
    }

    /**
     * Compute GPS signal freshness
     */
    public function getStatusFreshnessAttribute(): string
    {
        if (!$this->last_ping_at || $this->tracking_status === 'UNTRACKED') {
            return 'OFFLINE';
        }

        $seconds = abs((int) now()->diffInSeconds($this->last_ping_at));
        if ($seconds <= 60) {
            return 'LIVE';
        }
        if ($seconds <= 300) {
            return 'WARNING';
        }
        if ($seconds <= 900) {
            return 'STALE';
        }
        return 'OFFLINE';
    }

    /**
     * Seconds elapsed since last GPS ping
     */
    public function getSecondsAgoAttribute(): int
    {
        return $this->last_ping_at ? abs((int) now()->diffInSeconds($this->last_ping_at)) : 999999;
    }
}
