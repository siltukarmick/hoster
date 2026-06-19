<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('tenant', 'roles', 'parent', 'children');

        // System users see all; tenant users see only their own tenant's users
        if ($request->user()->isTenantUser()) {
            $query->where('tenant_id', $request->user()->tenant_id);
        } elseif ($request->user()->isEmployee()) {
            // Employees only see themselves
            $query->where('id', $request->user()->id);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users',
            'password'  => 'required|string|min:8',
            'type'      => 'required|in:system,tenant,employee',
            'phone'     => 'nullable|string|max:20',
            'status'    => 'sometimes|in:active,inactive',
        ]);

        $user = $request->user();

        // Authorization rules
        if ($user->isSystemUser()) {
            // System users can create system or tenant users
            if ($validated['type'] === 'tenant') {
                $validated['tenant_id'] = $validated['tenant_id'] ?? null;
            }
        } elseif ($user->isTenantUser()) {
            // Tenant users can only create employees under themselves
            if ($validated['type'] !== 'employee') {
                return response()->json(['message' => 'Tenant users can only create employees'], 403);
            }
            $validated['tenant_id'] = $user->tenant_id;
            $validated['parent_id'] = $user->id;
            $validated['type'] = 'employee';
        } else {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json($user->load('parent', 'tenant', 'roles'), 201);
    }

    public function show(Request $request, User $user)
    {
        // Authorization
        if ($request->user()->isTenantUser() && $user->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($request->user()->isEmployee() && $user->id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($user->load('tenant', 'roles', 'parent', 'children'));
    }

    public function update(Request $request, User $user)
    {
        // Authorization
        if ($request->user()->isTenantUser() && $user->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($request->user()->isEmployee() && $user->id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:8',
            'phone'    => 'nullable|string|max:20',
            'status'   => 'sometimes|in:active,inactive',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json($user->fresh()->load('parent', 'tenant', 'roles', 'children'));
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->isTenantUser() && $user->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}