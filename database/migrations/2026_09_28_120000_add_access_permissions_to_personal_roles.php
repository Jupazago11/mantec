<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dos permisos nuevos del modulo Personal (pedido 2026-09-28, revision de
 * seguridad):
 *
 * - personal_roles.editar_programacion: crear/editar/eliminar actividades
 *   en Programacion, separado de "ver_programacion" (a futuro un rol
 *   "Coordinador" programa y el Supervisor solo ve). Backfill = el valor de
 *   ver_programacion, para que el deploy no le quite a nadie lo que hoy ya
 *   puede hacer (hoy quien ve Programacion tambien crea).
 *
 * - administrar_roles en personal_categories (Rol) Y personal_roles
 *   (Subrol): mismo esquema que responsable_actividad — solo cuenta si el
 *   Rol y el Subrol lo tienen. Da acceso a "Roles y permisos" y a los
 *   campos de acceso de Empleados (subrol, usuario, contrasena, activar/
 *   inactivar cuentas). Arranca en false para todos: hasta que el
 *   superadmin lo marque, nadie mas que el puede administrar accesos (igual
 *   que antes de esta migracion).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_roles', function (Blueprint $table) {
            $table->boolean('editar_programacion')->default(false)->after('ver_programacion');
            $table->boolean('administrar_roles')->default(false)->after('responsable_actividad');
        });

        DB::table('personal_roles')->update(['editar_programacion' => DB::raw('ver_programacion')]);

        Schema::table('personal_categories', function (Blueprint $table) {
            $table->boolean('administrar_roles')->default(false)->after('responsable_actividad');
        });
    }

    public function down(): void
    {
        Schema::table('personal_roles', function (Blueprint $table) {
            $table->dropColumn(['editar_programacion', 'administrar_roles']);
        });

        Schema::table('personal_categories', function (Blueprint $table) {
            $table->dropColumn('administrar_roles');
        });
    }
};
