<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'invoice_id',
        'product_id',
        'item_type',
        'name_snapshot',
        'description_snapshot',
        'quantity',
        'unit_price_snapshot',
        'discount_amount',
        'tax_rate_percent',
        'tax_amount',
        'line_total',
        'created_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_snapshot' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function getItemDescriptionAttribute()
    {
        return $this->name_snapshot ?? ($this->attributes['item_description'] ?? '');
    }

    public function getUnitPriceAttribute()
    {
        return $this->unit_price_snapshot ?? 0;
    }

    public function getTaxRateAttribute()
    {
        return $this->tax_rate_percent ?? (float) SystemSetting::get('default_gst_rate', 0);
    }

    public function getTotalAmountAttribute()
    {
        return $this->line_total ?? 0;
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
