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
        'type',
        'tenant_id',
        'parent_id',
        'phone',
        'status',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function isSystemUser(): bool
    {
        return $this->type === 'system';
    }

    public function isTenantUser(): bool
    {
        return $this->type === 'tenant';
    }

    public function isEmployee(): bool
    {
        return $this->type === 'employee';
    }

    public function scopeSystem($query)
    {
        return $query->where('type', 'system');
    }

    public function scopeTenant($query)
    {
        return $query->where('type', 'tenant');
    }

    public function scopeEmployee($query)
    {
        return $query->where('type', 'employee');
    }
}