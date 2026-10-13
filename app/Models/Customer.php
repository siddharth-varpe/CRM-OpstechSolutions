<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'customer_number',
        'full_name',
        'phone',
        'email',
        'customer_type',
        'company_name',
        'gst_number',
        'status',
        'customer_label',
        'custom_label_id',
        'notes',
        'created_by',
        'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function getNameAttribute()
    {
        return $this->full_name;
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
    }

    public function primaryAddress()
    {
        return $this->hasOne(CustomerAddress::class, 'customer_id')->where('is_default', true);
    }

    public function customLabel()
    {
        return $this->belongsTo(CustomerCustomLabel::class, 'custom_label_id');
    }

    public function assets()
    {
        return $this->hasMany(CustomerAsset::class, 'customer_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'customer_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'customer_id');
    }

    public function warranties()
    {
        return $this->hasMany(Warranty::class, 'customer_id');
    }

    public function rentals()
    {
        return $this->hasMany(Rental::class, 'customer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
