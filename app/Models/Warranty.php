<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Warranty extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'warranty_number',
        'customer_id',
        'asset_id',
        'sale_id',
        'warranty_type',
        'start_date',
        'end_date',
        'duration_months',
        'status',
        'terms',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'duration_months' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function asset()
    {
        return $this->belongsTo(CustomerAsset::class, 'asset_id');
    }

    public function events()
    {
        return $this->hasMany(WarrantyEvent::class, 'warranty_id');
    }

    public function schedules()
    {
        return $this->hasMany(ServiceSchedule::class, 'warranty_id');
    }
}
