<?php

namespace App\Models;

use App\Traits\HasRoles;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'tenant_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'tenant_user_id');
    }

    public function isSystemUser(): bool
    {
        return $this->user_type === 'system';
    }

    public function isTenantUser(): bool
    {
        return $this->user_type === 'tenant';
    }

    public function scopeSystem($query)
    {
        return $query->where('user_type', 'system');
    }

    public function scopeTenant($query)
    {
        return $query->where('user_type', 'tenant');
    }
}
