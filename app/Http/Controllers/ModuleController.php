<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ModuleController extends Controller
{
    public function index()
    {
        return response()->json(Module::withCount('permissions')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255|unique:modules',
            'label' => 'required|string|max:255',
        ]);

        $module = Module::create($validated);

        return response()->json($module, 201);
    }

    public function show(Module $module)
    {
        return response()->json($module->load('permissions.role'));
    }

    public function update(Request $request, Module $module)
    {
        $validated = $request->validate([
            'name'  => ['sometimes', 'string', 'max:255', Rule::unique('modules')->ignore($module->id)],
            'label' => 'sometimes|string|max:255',
        ]);

        $module->update($validated);

        return response()->json($module->fresh());
    }

    public function destroy(Module $module)
    {
        $module->delete();

        return response()->json(['message' => 'Module deleted successfully']);
    }
}