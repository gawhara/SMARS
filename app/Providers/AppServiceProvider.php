<?php

namespace App\Providers;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Pagination\Paginator;
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
        $this->registerAuthorization();

        Paginator::defaultView('vendor.pagination.smars');
        Paginator::defaultSimpleView('vendor.pagination.smars');
    }

    /**
     * RBAC wiring. super_admin bypasses every check and inactive users are locked
     * out entirely; every catalogue permission is registered as a named ability
     * so Gate::has()/allows(), the `can:` middleware, @can, and the sidebar all
     * resolve consistently. The catalogue is static, so this never touches the
     * database at boot.
     */
    private function registerAuthorization(): void
    {
        Gate::before(function (User $user, string $ability) {
            if (! $user->is_active) {
                return false;
            }

            return $user->isSuperAdmin() ? true : null;
        });

        foreach (PermissionCatalog::names() as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermissionTo($permission));
        }
    }
}
