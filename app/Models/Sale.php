<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'sale_number',
        'customer_id',
        'sale_date',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'notes',
        'created_by',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'sale_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
