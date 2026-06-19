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
        $query = User::with('tenant', 'roles');

        // System users can see all; tenant users see only their own tenant's users
        if ($request->user()->isTenantUser()) {
            $query->where('tenant_id', $request->user()->tenant_id);
        }

        // Filter by user_type
        if ($request->has('user_type')) {
            $query->where('user_type', $request->user_type);
        }

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users',
            'password'  => 'required|string|min:8',
            'user_type' => 'required|in:system,tenant',
        ]);

        // Tenant users can only create users within their tenant
        if ($request->user()->isTenantUser()) {
            $validated['tenant_id'] = $request->user()->tenant_id;
            $validated['user_type'] = 'tenant';
        }

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json($user->load('tenant', 'roles'), 201);
    }

    public function show(Request $request, User $user)
    {
        // Check authorization
        if ($request->user()->isTenantUser() && $user->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($user->load('tenant', 'roles', 'employees'));
    }

    public function update(Request $request, User $user)
    {
        // Check authorization
        if ($request->user()->isTenantUser() && $user->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:8',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json($user->fresh()->load('tenant', 'roles'));
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