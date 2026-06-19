<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'type'                  => 'required|in:system,tenant',
            'tenant_name'           => 'required_if:type,tenant|string|max:255',
            'tenant_email'          => 'required_if:type,tenant|string|email|max:255',
            'tenant_domain'         => 'nullable|string|max:255|unique:tenants,domain',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'type'     => $validated['type'],
        ]);

        if ($validated['type'] === 'tenant') {
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

    // Show admin login form
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Show tenant login form
    public function showTenantLoginForm()
    {
        return view('auth.tenant-login');
    }

    // Admin login (users table)
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

        Auth::guard('web')->login($user, $request->filled('remember'));

        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    // Tenant login (tenants table)
    public function tenantLogin(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        $tenant = Tenant::where('email', $validated['email'])->first();

        if (! $tenant || ! Hash::check($validated['password'], $tenant->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        Auth::guard('tenant')->login($tenant, $request->filled('remember'));

        $request->session()->regenerate();

        return redirect()->intended('/tenant/dashboard');
    }

    public function logout(Request $request)
    {
        // Determine which guard is currently authenticated
        if (Auth::guard('tenant')->check()) {
            Auth::guard('tenant')->logout();
        } else {
            Auth::guard('web')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('tenant', 'roles', 'children'));
    }
}