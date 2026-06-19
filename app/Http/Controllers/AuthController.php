<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|string|email|max:255|unique:users',
            'password'              => 'required|string|min:8|confirmed',
            'user_type'             => 'required|in:system,tenant',
            'tenant_name'           => 'required_if:user_type,tenant|string|max:255',
            'tenant_email'          => 'required_if:user_type,tenant|string|email|max:255',
            'tenant_domain'         => 'nullable|string|max:255|unique:tenants,domain',
        ]);

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'user_type' => $validated['user_type'],
        ]);

        // If registering a tenant user, also create the tenant
        if ($validated['user_type'] === 'tenant') {
            $tenant = Tenant::create([
                'name'   => $validated['tenant_name'],
                'email'  => $validated['tenant_email'],
                'domain' => $validated['tenant_domain'] ?? null,
                'status' => 'active',
            ]);

            $user->tenant_id = $tenant->id;
            $user->save();
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user'  => $user->load('tenant'),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user'  => $user->load('tenant'),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('tenant', 'roles'));
    }
}