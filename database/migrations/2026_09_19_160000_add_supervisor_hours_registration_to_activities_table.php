<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 3 (seccion 7 de NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md,
 * seccion 14.18): primer paso de "la logica del API que usara la app" —
 * el registro que hace el supervisor sobre una actividad ya ejecutada
 * (comentarios + si todos trabajaron las horas programadas). `comments` y
 * `reported_hours` ya existian (agregados en la migracion de Diario de
 * Campo, con el comentario explicito "reported_hours queda lista para
 * cuando exista la API de la app") — no se duplican aqui, solo se
 * completan con lo que faltaba: la bandera de "todos trabajaron igual" y
 * la trazabilidad de quien registro y cuando.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->boolean('all_worked_scheduled_hours')->nullable()->after('reported_hours');
            $table->foreignId('hours_registered_by_employee_id')->nullable()
                ->after('all_worked_scheduled_hours')->constrained('employees')->nullOnDelete();
            $table->timestamp('hours_registered_at')->nullable()->after('hours_registered_by_employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hours_registered_by_employee_id');
            $table->dropColumn(['all_worked_scheduled_hours', 'hours_registered_at']);
        });
    }
};
