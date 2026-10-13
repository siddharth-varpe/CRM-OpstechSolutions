<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CustomerAsset extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'asset_number',
        'customer_id',
        'product_id',
        'asset_type',
        'serial_number',
        'custom_name',
        'installation_address_id',
        'purchase_date',
        'initial_warranty_months',
        'service_interval_months',
        'status',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'datetime',
        'initial_warranty_months' => 'integer',
        'service_interval_months' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function installationAddress()
    {
        return $this->belongsTo(CustomerAddress::class, 'installation_address_id');
    }

    public function warranties()
    {
        return $this->hasMany(Warranty::class, 'asset_id');
    }

    public function activeWarranty()
    {
        return $this->hasOne(Warranty::class, 'asset_id')->where('status', 'ACTIVE');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'asset_id');
    }

    public function jobCards()
    {
        return $this->hasMany(JobCard::class, 'asset_id');
    }
}
