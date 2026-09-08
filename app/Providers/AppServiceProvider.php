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

            static $allPermissions = null;
            if ($allPermissions === null) {
                try {
                    $allPermissions = \App\Models\Permission::pluck('name')->flip()->toArray();
                } catch (\Throwable $e) {
                    $allPermissions = [];
                }
            }

            // If ability corresponds to a system permission, strictly evaluate user permission
            if (isset($allPermissions[$ability])) {
                return $user->hasPermission($ability);
            }

            if ($user->hasPermission($ability)) {
                return true;
            }

            return null;
        });

        // User & Settings Management mapped to granular permissions
        Gate::define('manage-users', fn (User $user) => $user->hasAnyPermission(['users.view', 'users.create', 'users.edit', 'roles.manage']));
        Gate::define('manage-settings', fn (User $user) => $user->hasPermission('settings.manage'));

        // Finance mapped to granular permissions
        Gate::define('view-finance', fn (User $user) => $user->hasAnyPermission(['finance.view', 'projects.view_financial']));
        Gate::define('manage-finance', fn (User $user) => $user->hasAnyPermission(['finance.transactions', 'finance.bank_accounts', 'invoices.manage', 'expenses.manage', 'payments.manage']));

        // Clients & Projects mapped to granular permissions
        Gate::define('manage-clients', fn (User $user) => $user->hasAnyPermission(['clients.view', 'clients.create', 'clients.edit']));
        Gate::define('manage-projects', fn (User $user) => $user->hasPermission('projects.manage'));

        // QA / QC Module mapped to granular permissions
        Gate::define('access-qc', fn (User $user) => $user->hasAnyPermission(['qc.view', 'projects.qc']));
        Gate::define('manage-qc', fn (User $user) => $user->hasAnyPermission(['qc.manage_tasks', 'qc.manage_test_cases', 'qc.manage_bugs']));

        // AI Pricing Estimator mapped to granular permissions
        Gate::define('use-ai-pricing', fn (User $user) => $user->hasPermission('ai_pricing.use'));
    }
}
