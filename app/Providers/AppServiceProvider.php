<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\CashRegisterMovement;
use App\Models\Department;
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
use App\Policies\InventoryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\SalePolicy;
use App\Policies\SaleTypePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
        Gate::policy(CashRegister::class, CashRegisterPolicy::class);
        Gate::policy(CashRegisterMovement::class, CashRegisterMovementPolicy::class);

        // Observers
        Product::observe(ProductObserver::class);
    }
}
