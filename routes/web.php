<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;

// Public routes (guests only)
Route::middleware('guest')->group(function () {
    // Both / and /login show the login form
    Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
    Route::get('/login', [LoginController::class, 'showLoginForm']);

    // Process login
    Route::post('/dologin', [LoginController::class, 'login'])->name('login.post');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Impersonation routes
    Route::impersonate();

    // Roles, Permissions, and Users Management (Admin and Super Admin only)
    Route::middleware('role:Admin|Super Admin')->group(function () {
        // Roles
        Route::resource('roles', RoleController::class);

        // Permissions
        Route::resource('permissions', PermissionController::class)->except(['show']);
        Route::get('permissions/{permission}/usage', [PermissionController::class, 'usageExamples'])
            ->name('permissions.usage');

        // Users
        Route::resource('users', UserController::class);
    });

    // Add other authenticated routes here...
});
