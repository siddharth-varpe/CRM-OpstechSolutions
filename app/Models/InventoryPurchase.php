<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InventoryPurchase extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'purchase_number',
        'item_id',
        'supplier_name',
        'purchase_date',
        'quantity',
        'remaining_quantity',
        'purchase_price_per_unit',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'datetime',
        'quantity' => 'integer',
        'remaining_quantity' => 'integer',
        'purchase_price_per_unit' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
