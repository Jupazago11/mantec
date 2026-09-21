<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // Antes esta linea siempre forzaba https, sin importar el entorno
        // — funcionaba en produccion (Railway, HTTPS real) pero rompia
        // local (`php artisan serve` solo habla HTTP), y viceversa si se
        // quitaba la 's' a mano para probar en local. APP_ENV ya distingue
        // los dos casos sin tocar nada (local: "local" en .env local,
        // produccion: "production" en Railway) — con esto ya no hace
        // falta editar esta linea a mano segun donde se este corriendo.
        if (! $this->app->environment('local')) {
            URL::forceScheme('https');
        }
    }
}
