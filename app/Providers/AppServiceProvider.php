<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Product;
use App\Models\User;
use App\Observers\ProductObserver;
use App\Policies\BranchPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\ProductPolicy;
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

        // Observers
        Product::observe(ProductObserver::class);
    }
}
