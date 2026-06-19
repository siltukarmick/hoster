<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Users
    Route::apiResource('users', UserController::class);

    // Tenants
    Route::apiResource('tenants', TenantController::class);

    // Employees
    Route::apiResource('employees', EmployeeController::class);

    // Roles
    Route::get('roles', [RoleController::class, 'index']);
    Route::post('roles', [RoleController::class, 'store']);
    Route::get('roles/{role}', [RoleController::class, 'show']);
    Route::put('roles/{role}', [RoleController::class, 'update']);
    Route::delete('roles/{role}', [RoleController::class, 'destroy']);
    Route::post('roles/{role}/assign-users', [RoleController::class, 'assignUsers']);
    Route::post('roles/{role}/remove-users', [RoleController::class, 'removeUsers']);
    Route::post('roles/{role}/permissions', [RoleController::class, 'setPermissions']);

    // Modules
    Route::apiResource('modules', ModuleController::class);

    // Current user
    Route::get('/user', function (Request $request) {
        return $request->user()->load('tenant', 'roles');
    });
});