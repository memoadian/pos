<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\CashRegisterMovement;
use App\Models\Department;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleType;
use App\Models\User;
use App\Observers\ProductObserver;
use App\Policies\BranchPolicy;
use App\Policies\CashRegisterMovementPolicy;
use App\Policies\CashRegisterPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\InventoryPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\ProductPolicy;
use App\Policies\RolePolicy;
use App\Policies\SalePolicy;
use App\Policies\SaleTypePolicy;
use App\Policies\UserPolicy;
use App\Services\BranchContextService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BranchContextService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Branch::class, BranchPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(SaleType::class, SaleTypePolicy::class);
        Gate::policy(Inventory::class, InventoryPolicy::class);
        Gate::policy(Sale::class, SalePolicy::class);
        Gate::policy(Expense::class, ExpensePolicy::class);
        Gate::policy(CashRegister::class, CashRegisterPolicy::class);
        Gate::policy(CashRegisterMovement::class, CashRegisterMovementPolicy::class);
        Gate::policy(SpatieRole::class, RolePolicy::class);
        Gate::policy(SpatiePermission::class, PermissionPolicy::class);

        // El rol "Admin" tiene acceso completo: cualquier permiso "plano" del
        // catálogo (los que llevan espacios, p. ej. "ver reportes", "crear
        // productos") se le concede sin necesidad de asignárselo. Las
        // habilidades de policy (viewAny, create, update, delete, reopen…) NO
        // se cortocircuitan aquí: pasan por su policy para respetar sus guardas
        // de negocio (no borrar caja con ventas, no reabrir caja abierta, etc.).
        Gate::before(function ($user, string $ability) {
            if (str_contains($ability, ' ') && $user->hasRole('Admin')) {
                return true;
            }

            return null;
        });

        // Observers
        Product::observe(ProductObserver::class);
    }
}
