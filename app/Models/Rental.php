<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Rental extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'rental_number',
        'customer_id',
        'asset_id',
        'product_name',
        'start_date',
        'end_date',
        'monthly_fee',
        'deposit_amount',
        'billing_day',
        'status',
        'billing_frequency',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'monthly_fee' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'billing_day' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function asset()
    {
        return $this->belongsTo(CustomerAsset::class, 'asset_id');
    }

    public function payments()
    {
        return $this->hasMany(RentalPayment::class, 'rental_id');
    }
}
