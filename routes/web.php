<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BranchContextController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductBranchPriceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleTypeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

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

    // Branch context switcher (Admin/Manager)
    Route::post('/branch-context/switch', [BranchContextController::class, 'switch'])->name('branch-context.switch');

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

        // Ventas (listado y cancelación)
        Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');

        // Users
        Route::resource('users', UserController::class);

        // Branches
        Route::resource('branches', BranchController::class)->except(['show']);
        Route::get('branches/{id}/restore', [BranchController::class, 'restore'])
            ->name('branches.restore');

        // Departments
        Route::resource('departments', DepartmentController::class)->except(['show']);

        // Products
        Route::get('products/import', [ProductImportController::class, 'create'])->name('products.import.create');
        Route::post('products/import', [ProductImportController::class, 'store'])->name('products.import.store');
        Route::get('products/import/template', [ProductImportController::class, 'template'])->name('products.import.template');
        Route::resource('products', ProductController::class)->except(['show']);
        Route::put('products/{product}/branch-prices', [ProductBranchPriceController::class, 'sync'])
            ->name('products.branch-prices.sync');

        // Configuracion del sitio (nombre, color, logo, datos del ticket)
        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    });

    // Inventory / Inventory Movements: write access (Admin/Manager, scoped to branch context)
    Route::middleware('role:Admin|Manager')->group(function () {
        Route::post('inventory-movements', [InventoryMovementController::class, 'store'])->name('inventory-movements.store');
        Route::get('inventory-movements/products/search', [InventoryMovementController::class, 'searchProducts'])->name('inventory-movements.products.search');

        // Cash Register History
        Route::get('/cash-registers', [CashRegisterController::class, 'history'])->name('cash-registers.history');

        // Reportes
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });

    // Inventory / Inventory Movements: read access (Admin/Manager/Vendedor, scoped to branch context)
    Route::middleware('role:Admin|Manager|Vendedor')->group(function () {
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('inventory-movements', [InventoryMovementController::class, 'index'])->name('inventory-movements.index');
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
