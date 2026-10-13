<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ServiceSchedule extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'customer_id',
        'asset_id',
        'warranty_id',
        'schedule_index',
        'total_schedules',
        'planned_date',
        'target_month',
        'status',
        'generated_service_id',
    ];

    protected $casts = [
        'planned_date' => 'datetime',
        'schedule_index' => 'integer',
        'total_schedules' => 'integer',
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

    public function service()
    {
        return $this->belongsTo(Service::class, 'generated_service_id');
    }
}
