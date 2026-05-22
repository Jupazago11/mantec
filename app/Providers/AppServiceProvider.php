<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
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
        if (! $this->app->isLocal()) {
            $hotFile = public_path('hot');

            // If a local Vite hot file leaks into a deployed environment,
            // force Laravel to ignore it and use compiled assets instead.
            if (is_file($hotFile) && is_writable($hotFile)) {
                @unlink($hotFile);
            }

            Vite::useHotFile(storage_path('framework/vite.hot.disabled'));
        }
    }
}
