<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'invoice_number',
        'customer_id',
        'sale_id',
        'job_card_id',
        'service_id',
        'invoice_date',
        'due_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'status',
        'po_number',
        'notes',
        'terms_and_conditions',
        'pdf_file_id',
        'created_by',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'invoice_date' => 'datetime',
        'due_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public function getCustomerNameAttribute()
    {
        return $this->customer->full_name ?? ($this->attributes['customer_name'] ?? 'Direct Customer');
    }

    public function getCustomerPhoneAttribute()
    {
        return $this->customer->phone ?? ($this->attributes['customer_phone'] ?? '');
    }

    public function getOutstandingAmountAttribute()
    {
        return $this->balance_amount ?? max(0, $this->total_amount - $this->paid_amount);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'PAID' && $this->status !== 'CANCELLED' && $this->due_date->isPast();
    }
}
