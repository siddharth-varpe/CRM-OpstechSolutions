<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'sku',
        'name',
        'product_type',
        'brand',
        'model',
        'description',
        'unit_price',
        'tax_rate_percent',
        'default_warranty_months',
        'default_service_interval_months',
        'is_active',
        'archived_at',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'tax_rate_percent' => 'decimal:2',
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function assets()
    {
        return $this->hasMany(CustomerAsset::class, 'product_id');
    }
}
