<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Employee extends Authenticatable
{
    protected $fillable = [
        'tenant_user_id',
        'name',
        'email',
        'password',
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
            'password' => 'hashed',
        ];
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_user_id');
    }
}