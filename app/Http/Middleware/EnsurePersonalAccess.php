<?php

namespace App\Http\Middleware;

use App\Support\PersonalGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege las rutas /personal/* — ver App\Support\PersonalGuard::check()
 * para la regla (guard `personal`, o superadmin via el guard `web`
 * existente).
 */
class EnsurePersonalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! PersonalGuard::check()) {
            return redirect()->guest(route('personal.login'));
        }

        return $next($request);
    }
}
