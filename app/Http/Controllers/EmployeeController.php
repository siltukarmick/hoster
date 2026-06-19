<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::query();

        // Tenant users only see their own employees
        if ($request->user()->isTenantUser()) {
            $query->where('tenant_user_id', $request->user()->id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:employees',
            'password' => 'required|string|min:8',
            'phone'    => 'nullable|string|max:20',
        ]);

        $validated['tenant_user_id'] = $request->user()->id;
        $validated['password'] = Hash::make($validated['password']);

        $employee = Employee::create($validated);

        return response()->json($employee, 201);
    }

    public function show(Request $request, Employee $employee)
    {
        if ($request->user()->isTenantUser() && $employee->tenant_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($employee);
    }

    public function update(Request $request, Employee $employee)
    {
        if ($request->user()->isTenantUser() && $employee->tenant_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => ['sometimes', 'string', 'email', 'max:255', Rule::unique('employees')->ignore($employee->id)],
            'password' => 'sometimes|string|min:8',
            'phone'    => 'nullable|string|max:20',
            'status'   => 'sometimes|in:active,inactive',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $employee->update($validated);

        return response()->json($employee->fresh());
    }

    public function destroy(Request $request, Employee $employee)
    {
        if ($request->user()->isTenantUser() && $employee->tenant_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $employee->delete();

        return response()->json(['message' => 'Employee deleted successfully']);
    }
}