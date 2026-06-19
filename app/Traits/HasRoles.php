<?php

namespace App\Traits;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function hasRole(string $name): bool
    {
        return $this->roles()->where('name', $name)->exists();
    }

    public function hasPermission(string $action, string $module): bool
    {
        $column = 'can_' . $action;

        return $this->roles()
            ->with(['permissions' => fn($q) => $q->whereHas('module', fn($q) => $q->where('name', $module))])
            ->get()
            ->flatMap->permissions
            ->where($column, true)
            ->isNotEmpty();
    }

    public function assignRole(Role $role): void
    {
        $this->roles()->syncWithoutDetaching([$role->id]);
    }

    public function removeRole(Role $role): void
    {
        $this->roles()->detach($role->id);
    }
}
