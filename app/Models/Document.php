<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'entity_type',
        'entity_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size_bytes',
        'storage_provider',
        'uploaded_by',
    ];
}
