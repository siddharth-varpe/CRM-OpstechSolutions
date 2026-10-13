<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'sale_id',
        'product_id',
        'product_name_snapshot',
        'sku_snapshot',
        'quantity',
        'unit_price_snapshot',
        'discount_amount',
        'tax_rate_percent',
        'tax_amount',
        'line_total',
        'warranty_months',
        'service_interval_months',
        'serial_number',
        'next_service_date',
        'created_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_snapshot' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'warranty_months' => 'integer',
        'service_interval_months' => 'integer',
        'next_service_date' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
