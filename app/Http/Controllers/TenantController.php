<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $query = Tenant::withCount('users');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|string|email|max:255|unique:tenants',
            'domain' => 'nullable|string|max:255|unique:tenants',
            'status' => 'sometimes|in:active,suspended,cancelled',
        ]);

        $tenant = Tenant::create($validated);

        return response()->json($tenant, 201);
    }

    public function show(Tenant $tenant)
    {
        return response()->json($tenant->load(['users', 'roles']));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name'   => 'sometimes|string|max:255',
            'email'  => ['sometimes', 'string', 'email', 'max:255', Rule::unique('tenants')->ignore($tenant->id)],
            'domain' => ['nullable', 'string', 'max:255', Rule::unique('tenants')->ignore($tenant->id)],
            'status' => 'sometimes|in:active,suspended,cancelled',
        ]);

        $tenant->update($validated);

        return response()->json($tenant->fresh());
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->users()->delete();
        $tenant->delete();

        return response()->json(['message' => 'Tenant deleted successfully']);
    }
}