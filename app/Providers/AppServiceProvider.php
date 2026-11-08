<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Relation::enforceMorphMap([
            'purchase' => \App\Models\Purchase::class,
            'sale' => \App\Models\Sale::class,
        ]);

        Gate::define('view-reports', fn (User $user) => $user->canManageFinance());
        Gate::define('manage-transactions', fn (User $user) => $user->canManageFinance());
        Gate::define('manage-master-data', fn (User $user) => $user->canManageFinance());
        Gate::define('manage-settings', fn (User $user) => $user->isOwner());
        Gate::define('manage-users', fn (User $user) => $user->isOwner());
        Gate::define('view-activity', fn (User $user) => $user->isOwner());

        View::composer('*', function ($view) {
            $view->with('settings', app('kamsya.settings'));
        });

        $this->app->scoped('kamsya.settings', fn () => Setting::current());

        View::composer('layouts.app', function ($view) {
            $view->with('navAvailableCount', \App\Models\Goat::query()->available()->count());
        });
    }
}
