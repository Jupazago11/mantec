<?php

namespace App\Http\Middleware;

use App\Support\PersonalGuard;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege las rutas /personal/* — ver App\Support\PersonalGuard::check()
 * para la regla (guard `personal`, o superadmin via el guard `web`
 * existente).
 *
 * Desde 2026-09-28 (revision de seguridad) tambien corta la sesion de un
 * empleado que ya no puede entrar: inactivado, sin usuario de acceso, o
 * con la contrasena cambiada por un administrador despues de iniciar
 * sesion (se compara contra el hash guardado en la sesion al hacer login,
 * mismo principio que AuthenticateSession de Laravel). Antes solo se
 * revisaba al iniciar sesion.
 */
class EnsurePersonalAccess
{
    public const SESSION_PASSWORD_KEY = 'personal_password_hash';

    public function handle(Request $request, Closure $next): Response
    {
        $employee = PersonalGuard::employee();

        if ($employee && ! $this->sesionVigente($request, $employee)) {
            Auth::guard('personal')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $mensaje = 'Tu acceso fue deshabilitado o cambió. Vuelve a iniciar sesión.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $mensaje], 401);
            }

            return redirect()->route('personal.login')->withErrors(['login' => $mensaje]);
        }

        if (! PersonalGuard::check()) {
            return redirect()->guest(route('personal.login'));
        }

        return $next($request);
    }

    private function sesionVigente(Request $request, $employee): bool
    {
        if (! $employee->puedeIniciarSesion()) {
            return false;
        }

        $guardado = $request->session()->get(self::SESSION_PASSWORD_KEY);

        // Sesiones abiertas antes de este cambio (o de otro empleado) no
        // tienen el hash de ESTE empleado: se toma el actual en vez de sacar
        // a todo el mundo en el deploy.
        if (! is_array($guardado) || ($guardado['id'] ?? null) !== $employee->id) {
            self::recordarClave($request, $employee);

            return true;
        }

        return hash_equals((string) ($guardado['hash'] ?? ''), (string) $employee->getAuthPassword());
    }

    // Lo llama tambien PersonalAuthController::login().
    public static function recordarClave(Request $request, $employee): void
    {
        $request->session()->put(self::SESSION_PASSWORD_KEY, [
            'id' => $employee->id,
            'hash' => $employee->getAuthPassword(),
        ]);
    }
}
