<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Gate;
use App\Models\User;

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
        // Super Admin bypass & dynamic permission check from database
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }

            if ($user->hasPermission($ability)) {
                return true;
            }

            return null;
        });

        // User & Settings Management
        Gate::define('manage-users', fn (User $user) => $user->hasRole('superadmin'));
        Gate::define('manage-settings', fn (User $user) => $user->hasRole('superadmin'));

        // Finance
        Gate::define('view-finance', fn (User $user) => $user->hasAnyRole(['superadmin', 'admin', 'finance']));
        Gate::define('manage-finance', fn (User $user) => $user->hasAnyRole(['superadmin', 'finance']));

        // Clients & Projects
        Gate::define('manage-clients', fn (User $user) => $user->hasAnyRole(['superadmin', 'admin', 'pm']));
        Gate::define('manage-projects', fn (User $user) => $user->hasAnyRole(['superadmin', 'admin', 'pm']));

        // QA / QC Module
        Gate::define('access-qc', fn (User $user) => $user->hasAnyRole(['superadmin', 'admin', 'pm', 'qc', 'staff']));
        Gate::define('manage-qc', fn (User $user) => $user->hasAnyRole(['superadmin', 'admin', 'pm', 'qc']));

        // AI Pricing Estimator
        Gate::define('use-ai-pricing', fn (User $user) => $user->hasAnyRole(['superadmin', 'admin', 'pm', 'staff']));
    }
}
