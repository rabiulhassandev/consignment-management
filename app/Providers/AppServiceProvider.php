<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewContract;

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
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasRole(RolesAndPermissionsSeeder::SUPER_ADMIN_ROLE) ? true : null;
        });

        View::composer('layouts.*', function (ViewContract $view): void {
            $view->with('siteName', Setting::get('site_name', 'BNoor Group'));
        });
    }
}
