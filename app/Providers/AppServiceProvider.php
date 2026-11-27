<?php

namespace App\Providers;

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
        if (config('database.connections.mysql.database') === 'foodbridge_dusk' || $this->app->environment('testing')) {
            \Illuminate\Support\Facades\Vite::useHotFile(storage_path('framework/dusk-nonexistent-hot'));
        }
    }
}
