<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BranchContextController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ExpenseController;
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

    // Cada módulo se protege con "role_or_permission": el rol Admin siempre
    // entra; cualquier otro rol necesita tener asignado el permiso de Spatie
    // indicado (Sistema → Roles). El permiso listado es el mínimo para ENTRAR
    // al módulo; el detalle (crear/editar/eliminar) lo resuelve cada Policy.

    // Roles
    Route::middleware('role_or_permission:Admin|ver roles')->group(function () {
        Route::resource('roles', RoleController::class);
    });

    // Permisos
    Route::middleware('role_or_permission:Admin|ver permisos')->group(function () {
        Route::resource('permissions', PermissionController::class)->except(['show']);
        Route::get('permissions/{permission}/usage', [PermissionController::class, 'usageExamples'])
            ->name('permissions.usage');
    });

    // Tipos de Venta
    Route::middleware('role_or_permission:Admin|ver tipos de venta')->group(function () {
        Route::resource('sale-types', SaleTypeController::class)->except(['show']);
    });

    // Ventas (listado y cancelación)
    Route::middleware('role_or_permission:Admin|ver ventas')->group(function () {
        Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
    });

    // Users
    Route::middleware('role_or_permission:Admin|ver usuarios')->group(function () {
        Route::resource('users', UserController::class);
    });

    // Branches
    Route::middleware('role_or_permission:Admin|ver sucursales')->group(function () {
        Route::resource('branches', BranchController::class)->except(['show']);
        Route::get('branches/{id}/restore', [BranchController::class, 'restore'])
            ->name('branches.restore');
    });

    // Departments
    Route::middleware('role_or_permission:Admin|ver departamentos')->group(function () {
        Route::resource('departments', DepartmentController::class)->except(['show']);
    });

    // Products
    Route::middleware('role_or_permission:Admin|ver productos')->group(function () {
        Route::get('products/import', [ProductImportController::class, 'create'])->name('products.import.create');
        Route::post('products/import', [ProductImportController::class, 'store'])->name('products.import.store');
        Route::get('products/import/template', [ProductImportController::class, 'template'])->name('products.import.template');
        Route::resource('products', ProductController::class)->except(['show']);
        Route::put('products/{product}/branch-prices', [ProductBranchPriceController::class, 'sync'])
            ->name('products.branch-prices.sync');
    });

    // Configuracion del sitio (nombre, color, logo, datos del ticket)
    Route::middleware('role_or_permission:Admin|ver configuracion')->group(function () {
        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    });

    // Inventario: alta de movimientos (scoped a la sucursal activa)
    Route::middleware('role_or_permission:Admin|Manager|registrar movimientos inventario')->group(function () {
        Route::post('inventory-movements', [InventoryMovementController::class, 'store'])->name('inventory-movements.store');
        Route::get('inventory-movements/products/search', [InventoryMovementController::class, 'searchProducts'])->name('inventory-movements.products.search');
    });

    // Historial de cajas
    Route::middleware('role_or_permission:Admin|Manager|ver historial cajas')->group(function () {
        Route::get('/cash-registers', [CashRegisterController::class, 'history'])->name('cash-registers.history');
    });

    // Reportes
    Route::middleware('role_or_permission:Admin|Manager|ver reportes')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });

    // Gastos (consulta)
    Route::middleware('role_or_permission:Admin|Manager|ver gastos')->group(function () {
        Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    });

    // Gastos: registrar contra la caja abierta / borrar (corrección)
    Route::middleware(['role_or_permission:Admin|Manager|Vendedor|registrar gastos', 'pos.cash-register'])
        ->post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    // Inventario / Movimientos: consulta (scoped a la sucursal activa)
    Route::middleware('role_or_permission:Admin|Manager|Vendedor|ver inventario')->group(function () {
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    });
    Route::middleware('role_or_permission:Admin|Manager|Vendedor|ver movimientos inventario')->group(function () {
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

        // Acciones de gestión (aprobar/rechazar movimientos, reabrir, eliminar).
        // La autorización fina la aplica cada método vía CashRegisterPolicy /
        // CashRegisterMovementPolicy (permisos: "aprobar movimientos caja",
        // "reabrir cajas", "eliminar cajas").
        Route::middleware('role_or_permission:Admin|aprobar movimientos caja|reabrir cajas|eliminar cajas')->group(function () {
            Route::post('/movement/{movement}/approve', [CashRegisterController::class, 'approveMovement'])->name('movement.approve');
            Route::post('/movement/{movement}/reject', [CashRegisterController::class, 'rejectMovement'])->name('movement.reject');
            Route::post('/{cashRegister}/reopen', [CashRegisterController::class, 'reopen'])->name('reopen');
            Route::delete('/{cashRegister}', [CashRegisterController::class, 'destroy'])->name('destroy');
        });
    });

    // POS - Punto de Venta (requiere permiso y caja abierta)
    Route::middleware(['role_or_permission:Admin|Manager|Vendedor|usar punto de venta', 'pos.cash-register'])
        ->prefix('pos')->name('pos.')->group(function () {
            Route::get('/', [PosController::class, 'index'])->name('index');
            Route::get('/products/search', [PosController::class, 'searchProducts'])->name('products.search');
            Route::post('/validate-stock', [PosController::class, 'validateStock'])->name('validate-stock');
            Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
        });
});
