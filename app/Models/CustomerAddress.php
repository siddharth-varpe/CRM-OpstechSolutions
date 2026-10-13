<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'customer_id',
        'address_type',
        'address_line1',
        'address_line2',
        'landmark',
        'city',
        'state',
        'postal_code',
        'latitude',
        'longitude',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->landmark ? "Near {$this->landmark}" : null,
            $this->city,
            $this->state,
            $this->postal_code,
        ]);
        return implode(', ', $parts);
    }
}
