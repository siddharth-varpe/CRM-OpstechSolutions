<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'name',
        'category',
        'brand',
        'part_number',
        'description',
        'purchase_price',
        'selling_price',
        'current_stock',
        'min_stock_level',
        'status',
        'archived_at',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'current_stock' => 'integer',
        'min_stock_level' => 'integer',
        'archived_at' => 'datetime',
    ];

    public function purchases()
    {
        return $this->hasMany(InventoryPurchase::class, 'item_id');
    }

    public function sales()
    {
        return $this->hasMany(InventorySale::class, 'item_id');
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->min_stock_level;
    }
}
