<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InventorySale extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'sale_number',
        'item_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'sale_date',
        'quantity',
        'selling_price_per_unit',
        'purchase_cost_per_unit',
        'total_sale_amount',
        'total_cost_amount',
        'profit',
        'payment_status',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'quantity' => 'integer',
        'selling_price_per_unit' => 'decimal:2',
        'purchase_cost_per_unit' => 'decimal:2',
        'total_sale_amount' => 'decimal:2',
        'total_cost_amount' => 'decimal:2',
        'profit' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
