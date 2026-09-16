<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Punto unico para saber "quien esta actuando" en el modulo "Personal y
 * Programacion" — puede ser un Employee logueado por el guard `personal`,
 * o superadmin actuando via el guard `web` existente (ver seccion 3 de
 * NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md: superadmin es el unico
 * rol del sistema actual que mantiene acceso aqui, sin fila propia en
 * `employees` ni login nuevo).
 */
class PersonalGuard
{
    public static function employee(): ?Employee
    {
        return Auth::guard('personal')->user();
    }

    public static function webUser(): ?User
    {
        return Auth::guard('web')->user();
    }

    public static function isSuperadmin(): bool
    {
        return self::webUser()?->role?->key === 'superadmin';
    }

    public static function check(): bool
    {
        return Auth::guard('personal')->check() || self::isSuperadmin();
    }

    // Punto unico de autorizacion por permiso (seccion "Roles y permisos
    // dinamicos"): superadmin siempre puede todo; un Employee depende de
    // los 6 booleanos de su PersonalRole. $permission es el nombre exacto
    // de la columna en personal_roles (ej. 'ver_bitacora').
    public static function can(string $permission): bool
    {
        if (self::isSuperadmin()) {
            return true;
        }

        return (bool) (self::employee()?->personalRole?->{$permission} ?? false);
    }

    public static function displayName(): string
    {
        if ($employee = self::employee()) {
            return $employee->nombre;
        }

        if ($user = self::webUser()) {
            return "{$user->name} (superadmin)";
        }

        return 'Invitado';
    }

    public static function roleLabel(): string
    {
        if (self::isSuperadmin()) {
            return 'Superadmin';
        }

        return self::employee()?->personalRole?->name ?? '';
    }

    // Primer modulo al que el actor tiene acceso, en el mismo orden que
    // el sidebar — usado para saber a donde mandarlo tras el login en vez
    // de un destino fijo que podria no estarle permitido (ej. un rol sin
    // "ver_empleados"). null si no tiene ningun permiso.
    public static function firstAccessibleRoute(): ?string
    {
        $mapa = [
            'ver_empleados' => 'personal.empleados.index',
            'ver_programacion' => 'personal.programacion.index',
            'ver_diario_campo' => 'personal.diario-campo.index',
            'ver_bitacora' => 'personal.bitacora.index',
        ];

        foreach ($mapa as $permiso => $ruta) {
            if (self::can($permiso)) {
                return $ruta;
            }
        }

        return null;
    }
}
