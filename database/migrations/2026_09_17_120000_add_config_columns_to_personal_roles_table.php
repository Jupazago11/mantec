<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dos columnas nuevas en personal_roles, pedidas por el usuario el
 * 2026-09-17:
 *
 * - activo: el modulo "Roles y permisos" no tenia forma de archivar un
 *   subrol, solo eliminarlo (hard delete) si no tenia empleados. Se
 *   reemplaza el delete por un archivado reversible (mismo patron que
 *   Company::archived) — sigue exigiendose 0 empleados asignados para
 *   archivar.
 * - disponible_en_programacion: hoy el selector de "Personas de la
 *   actividad" en Programacion muestra TODOS los empleados de categoria
 *   Administrativos, sin distinguir subrol. El usuario pidio que los
 *   subroles "solo administrativos" (sin trabajo de campo real) se puedan
 *   excluir de ese selector, mediante configuracion en el modulo de Roles
 *   (no hardcodeado en el backend). Empleados de categoria Campo nunca se
 *   filtran por esto — ver ActivityController::index().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_roles', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('name');
            $table->boolean('disponible_en_programacion')->default(true)->after('activo');
        });

        // Backfill de los 2 roles sembrados en la migracion anterior: el
        // rol "Administrativo" es oficina/administrativo puro, no deberia
        // aparecer como persona seleccionable en Programacion. "Supervisor"
        // si trabaja en campo, se mantiene visible (default true ya cubre
        // cualquier subrol nuevo que se cree despues, ej. SISO).
        DB::table('personal_roles')
            ->where('name', 'Administrativo')
            ->update(['disponible_en_programacion' => false]);
    }

    public function down(): void
    {
        Schema::table('personal_roles', function (Blueprint $table) {
            $table->dropColumn(['activo', 'disponible_en_programacion']);
        });
    }
};
