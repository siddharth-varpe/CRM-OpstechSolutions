<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'payment_number',
        'customer_id',
        'invoice_id',
        'amount',
        'payment_date',
        'payment_method',
        'status',
        'reference_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    public function getReceiptNumberAttribute()
    {
        return $this->payment_number;
    }

    public function getPaymentModeAttribute()
    {
        return $this->payment_method;
    }

    public function getTransactionReferenceAttribute()
    {
        return $this->reference_number;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
