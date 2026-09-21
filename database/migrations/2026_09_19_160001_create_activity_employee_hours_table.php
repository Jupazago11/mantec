<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Detalle por persona de horas realmente trabajadas (seccion 7, seccion
 * 14.18) — solo se llena cuando el supervisor indica que NO todos
 * trabajaron las horas programadas de la actividad
 * (activities.all_worked_scheduled_hours = false). Mecanica confirmada en
 * el documento: si la persona no trabajo, sus horas quedan en 0
 * (worked=false, sin start/end); si si trabajo, se captura hora de inicio
 * y hora final (no un numero directo) y el sistema calcula la duracion
 * (worked_hours).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_employee_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->boolean('worked')->default(true);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            // Calculada desde start_time/end_time (cruzando medianoche si
            // end < start, ej. turno nocturno) — se guarda para no
            // recalcularla cada vez que se consulte.
            $table->decimal('worked_hours', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['activity_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_employee_hours');
    }
};
