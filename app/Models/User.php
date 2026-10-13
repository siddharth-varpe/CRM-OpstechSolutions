<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'username',
        'display_name',
        'email',
        'password',
        'role',
        'status',
        'login_attempts',
        'locked_until',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function getNameAttribute()
    {
        return $this->display_name;
    }

    public function technician()
    {
        return $this->hasOne(Technician::class, 'user_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'Super Admin';
    }

    public function hasRole(string|array $roles): bool
    {
        if ($this->isSuperAdmin()) return true;
        $roles = is_array($roles) ? $roles : func_get_args();
        return in_array($this->role, $roles);
    }

    public function hasPermission(string $permissionKey): bool
    {
        if ($this->isSuperAdmin()) return true;

        $role = Role::where('name', $this->role)->with('permissions')->first();
        if (!$role) return false;

        return $role->permissions->contains('key', $permissionKey);
    }
}
