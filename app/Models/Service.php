<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'service_number',
        'customer_id',
        'asset_id',
        'warranty_id',
        'technician_id',
        'service_type',
        'service_location',
        'service_classification',
        'scheduled_date',
        'scheduled_time_slot',
        'status',
        'priority',
        'customer_notes',
        'internal_notes',
        'completed_at',
        'created_by',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function asset()
    {
        return $this->belongsTo(CustomerAsset::class, 'asset_id');
    }

    public function warranty()
    {
        return $this->belongsTo(Warranty::class, 'warranty_id');
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class, 'technician_id');
    }

    public function jobCard()
    {
        return $this->hasOne(JobCard::class, 'service_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'service_id');
    }
}
