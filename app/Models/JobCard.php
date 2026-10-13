<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class JobCard extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'job_card_number',
        'service_id',
        'customer_id',
        'asset_id',
        'technician_id',
        'problem_reported',
        'diagnosis',
        'work_performed',
        'parts_replaced',
        'technician_notes',
        'customer_remarks',
        'customer_signature_file_id',
        'started_at',
        'completed_at',
        'labor_charges',
        'parts_charges',
        'total_charges',
        'next_service_recommendation_months',
        'next_service_notes',
        'status',
    ];

    protected $casts = [
        'parts_replaced' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'labor_charges' => 'decimal:2',
        'parts_charges' => 'decimal:2',
        'total_charges' => 'decimal:2',
        'next_service_recommendation_months' => 'integer',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function asset()
    {
        return $this->belongsTo(CustomerAsset::class, 'asset_id');
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class, 'technician_id');
    }
}
