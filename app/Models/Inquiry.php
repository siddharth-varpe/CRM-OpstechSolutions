<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'inquiry_number',
        'source',
        'customer_name',
        'phone',
        'email',
        'inquiry_type',
        'status',
        'priority',
        'notes',
        'converted_customer_id',
    ];

    public function convertedCustomer()
    {
        return $this->belongsTo(Customer::class, 'converted_customer_id');
    }
}
