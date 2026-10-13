<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CustomerCustomLabel extends Model
{
    use HasUuids;

    protected $fillable = ['id', 'name', 'color', 'description'];

    public function customers()
    {
        return $this->hasMany(Customer::class, 'custom_label_id');
    }
}
