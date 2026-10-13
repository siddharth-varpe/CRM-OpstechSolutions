<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'target_role',
        'notification_type',
        'title',
        'message',
        'severity',
        'priority',
        'is_read',
        'read_at',
        'entity_type',
        'entity_id',
        'action_url',
        'event_key',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
