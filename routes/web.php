<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\SaleTypeController;
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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Impersonation routes
    Route::impersonate();

    // Roles, Permissions, and Users Management (Admin only)
    Route::middleware('role:Admin')->group(function () {
        // Roles
        Route::resource('roles', RoleController::class);

        // Permissions
        Route::resource('permissions', PermissionController::class)->except(['show']);
        Route::get('permissions/{permission}/usage', [PermissionController::class, 'usageExamples'])
            ->name('permissions.usage');

        // Sale Types
        Route::resource('sale-types', SaleTypeController::class)->except(['show']);

        // Users
        Route::resource('users', UserController::class);

        // Branches
        Route::resource('branches', BranchController::class)->except(['show']);
        Route::get('branches/{id}/restore', [BranchController::class, 'restore'])
            ->name('branches.restore');

        // Departments
        Route::resource('departments', DepartmentController::class)->except(['show']);

        // Products
        Route::resource('products', ProductController::class)->except(['show']);

        // Inventory
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');

        // Inventory Movements
        Route::prefix('inventory-movements')->name('inventory-movements.')->group(function () {
            Route::get('/', [InventoryMovementController::class, 'index'])->name('index');
            Route::get('/create', [InventoryMovementController::class, 'create'])->name('create');
            Route::post('/', [InventoryMovementController::class, 'store'])->name('store');
            Route::get('/products/search', [InventoryMovementController::class, 'searchProducts'])->name('products.search');
        });

        // Cash Register History (Admin only)
        Route::get('/cash-registers', [CashRegisterController::class, 'history'])->name('cash-registers.history');
    });

    // Cash Register - Gestión de Caja (cualquier usuario autenticado con sucursal)
    Route::prefix('cash-register')->name('cash-register.')->group(function () {
        Route::get('/', [CashRegisterController::class, 'index'])->name('index');
        Route::get('/open', [CashRegisterController::class, 'open'])->name('open');
        Route::post('/open', [CashRegisterController::class, 'storeOpen'])->name('store-open');
        Route::get('/close', [CashRegisterController::class, 'close'])->name('close');
        Route::post('/close', [CashRegisterController::class, 'storeClose'])->name('store-close');
        Route::get('/{cashRegister}', [CashRegisterController::class, 'show'])->name('show');
        Route::post('/movement/add', [CashRegisterController::class, 'addMovement'])->name('movement.add');

        // Admin actions (approval/rejection)
        Route::middleware('role:Admin')->group(function () {
            Route::post('/movement/{movement}/approve', [CashRegisterController::class, 'approveMovement'])->name('movement.approve');
            Route::post('/movement/{movement}/reject', [CashRegisterController::class, 'rejectMovement'])->name('movement.reject');
        });
    });

    // POS - Punto de Venta (requiere caja abierta)
    Route::middleware(['pos.cash-register'])->prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::get('/products/search', [PosController::class, 'searchProducts'])->name('products.search');
        Route::post('/validate-stock', [PosController::class, 'validateStock'])->name('validate-stock');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
    });
});
