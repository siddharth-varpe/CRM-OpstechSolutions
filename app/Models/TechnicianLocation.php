<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TechnicianLocation extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'technician_id',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'speed' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function technician()
    {
        return $this->belongsTo(Technician::class, 'technician_id');
    }
}
