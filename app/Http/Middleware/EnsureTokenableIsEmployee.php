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
class EnsureTokenableIsEmployee
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() instanceof Employee, 403, 'Token no válido para este recurso.');

        return $next($request);
    }
}
