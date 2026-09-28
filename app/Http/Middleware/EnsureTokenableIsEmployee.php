<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Seccion 14.24: Sanctum es polimorfico puro en este proyecto — el mismo
// middleware auth:sanctum autentica igual de valido un token de User
// (Inspector) que uno de Employee (Supervisor), segun el tokenable_type
// guardado en cada fila de personal_access_tokens. Sin este chequeo
// explicito, un token de Inspector podria llamar por error a rutas de
// Employee (y viceversa) y $request->user() devolveria el tipo
// equivocado.
//
// 2026-09-28 (revision de seguridad): ademas, el empleado dueño del token
// debe seguir activo y con usuario de acceso — antes un empleado
// inactivado seguia usando la app indefinidamente (los tokens no
// expiran). Si ya no puede entrar, se borra el token usado y se responde
// 401 para que la app lo mande al login.
class EnsureTokenableIsEmployee
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof Employee, 403, 'Token no válido para este recurso.');

        if (! $user->puedeIniciarSesion()) {
            $token = $user->currentAccessToken();
            if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
                $token->delete();
            }

            return response()->json([
                'success' => false,
                'message' => 'Tu acceso fue deshabilitado. Contacta al administrador.',
            ], 401);
        }

        return $next($request);
    }
}
