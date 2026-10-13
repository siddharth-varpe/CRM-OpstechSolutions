<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WarrantyEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'warranty_id',
        'customer_id',
        'asset_id',
        'event_type',
        'event_date',
        'actor_id',
        'reason',
        'notes',
        'replacement_asset_id',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'created_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function warranty()
    {
        return $this->belongsTo(Warranty::class, 'warranty_id');
    }
}
