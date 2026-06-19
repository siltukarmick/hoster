<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Module;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::withCount('users', 'permissions');

        // System users see system-scoped roles; tenant users see their own tenant's roles
        if ($request->user()->isSystemUser()) {
            $query->where('scope', 'system');
        } else {
            $query->where('scope', 'tenant')
                  ->where('tenant_id', $request->user()->tenant_id);
        }

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        if ($request->user()->isSystemUser()) {
            $validated['scope'] = 'system';
            $validated['tenant_id'] = null;
        } else {
            $validated['scope'] = 'tenant';
            $validated['tenant_id'] = $request->user()->tenant_id;
        }

        $role = Role::create($validated);

        return response()->json($role, 201);
    }

    public function show(Request $request, Role $role)
    {
        if ($request->user()->isTenantUser() && $role->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($role->load(['permissions.module', 'users']));
    }

    public function update(Request $request, Role $role)
    {
        if ($request->user()->isTenantUser() && $role->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
        ]);

        $role->update($validated);

        return response()->json($role->fresh());
    }

    public function destroy(Request $request, Role $role)
    {
        if ($request->user()->isTenantUser() && $role->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $role->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }

    /**
     * Assign users to a role
     */
    public function assignUsers(Request $request, Role $role)
    {
        if ($request->user()->isTenantUser() && $role->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'user_ids'   => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $role->users()->syncWithoutDetaching($validated['user_ids']);

        return response()->json(['message' => 'Users assigned to role successfully']);
    }

    /**
     * Remove users from a role
     */
    public function removeUsers(Request $request, Role $role)
    {
        if ($request->user()->isTenantUser() && $role->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'user_ids'   => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $role->users()->detach($validated['user_ids']);

        return response()->json(['message' => 'Users removed from role successfully']);
    }

    /**
     * Set permissions for a role
     */
    public function setPermissions(Request $request, Role $role)
    {
        if ($request->user()->isTenantUser() && $role->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'permissions'             => 'required|array',
            'permissions.*.module_id' => 'required|exists:modules,id',
            'permissions.*.can_view'   => 'boolean',
            'permissions.*.can_create' => 'boolean',
            'permissions.*.can_edit'   => 'boolean',
            'permissions.*.can_delete' => 'boolean',
        ]);

        foreach ($validated['permissions'] as $perm) {
            Permission::updateOrCreate(
                [
                    'role_id'   => $role->id,
                    'module_id' => $perm['module_id'],
                ],
                [
                    'can_view'   => $perm['can_view'] ?? false,
                    'can_create' => $perm['can_create'] ?? false,
                    'can_edit'   => $perm['can_edit'] ?? false,
                    'can_delete' => $perm['can_delete'] ?? false,
                ]
            );
        }

        return response()->json([
            'message'     => 'Permissions updated successfully',
            'permissions' => $role->fresh()->load('permissions.module'),
        ]);
    }
}