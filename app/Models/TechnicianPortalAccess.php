<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TechnicianPortalAccess extends Model
{
    use HasUuids;

    protected $table = 'technician_portal_access';

    protected $fillable = [
        'id',
        'technician_id',
        'portal_enabled',
        'pin_hash',
        'last_login_at',
    ];

    protected $casts = [
        'portal_enabled' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function technician()
    {
        return $this->belongsTo(Technician::class, 'technician_id');
    }
}
