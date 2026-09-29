<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Nombre de usuario unico en TODA la plataforma (pedido 2026-09-29): no
 * puede repetirse entre empleados del modulo Personal (employees) ni con
 * los usuarios del sistema de reportes de activos (users), en ninguna de
 * las dos direcciones, y sin distinguir mayusculas ("Lfdo" = "lfdo").
 *
 * Motivo tecnico ademas del de negocio: /personal/login busca primero un
 * empleado y despues un usuario superadmin con el mismo username — un
 * empleado con el usuario de un superadmin (el navegador llego a
 * autocompletar "superadmin" en el formulario de empleado) volvia ambiguo
 * el inicio de sesion.
 *
 * Al editar se ignora el propio registro: ignorarEmpleado (Empleados) o
 * ignorarUsuario (usuarios del sistema).
 */
class UsuarioUnicoEnPlataforma implements ValidationRule
{
    public function __construct(
        private ?int $ignorarEmpleado = null,
        private ?int $ignorarUsuario = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $usuario = mb_strtolower(trim($value));

        $enEmpleados = DB::table('employees')
            ->whereRaw('lower(username) = ?', [$usuario])
            ->when($this->ignorarEmpleado, fn ($q) => $q->where('id', '!=', $this->ignorarEmpleado))
            ->exists();

        $enUsuarios = DB::table('users')
            ->whereRaw('lower(username) = ?', [$usuario])
            ->when($this->ignorarUsuario, fn ($q) => $q->where('id', '!=', $this->ignorarUsuario))
            ->exists();

        if ($enEmpleados || $enUsuarios) {
            $fail('Ese nombre de usuario ya está en uso (en Empleados o en los usuarios del sistema).');
        }
    }
}
