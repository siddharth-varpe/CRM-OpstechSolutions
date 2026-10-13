<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RentalPayment extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'rental_id',
        'billing_month',
        'amount',
        'due_date',
        'paid_date',
        'status',
        'invoice_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'datetime',
        'paid_date' => 'datetime',
    ];

    public function rental()
    {
        return $this->belongsTo(Rental::class, 'rental_id');
    }
}
