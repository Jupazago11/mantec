<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de comentarios sobre las horas reportadas de un trabajador
 * (pedido 2026-09-22) — reemplaza el campo unico `bitacora_entries.comment`
 * (que se sobrescribia cada vez) por un historial que mezcla dos origenes:
 *
 * - `activity_id` no nulo: comentario del RESPONSABLE de esa actividad
 *   puntual, puesto desde la app Android al marcar que un trabajador no
 *   cumplio las horas programadas. Como solo el responsable asignado puede
 *   actuar sobre su actividad (ver Api\Personal\ActivityController::store,
 *   abort_unless responsible_employee_id === empleado del token), solo
 *   puede existir UNA fila por (activity_id, employee_id) — se actualiza
 *   por upsert, nunca se acumulan varias del mismo responsable.
 * - `activity_id` nulo: comentario del ADMINISTRATIVO/superadmin, puesto
 *   desde Bitacora web (nivel dia, no una actividad puntual) — siempre se
 *   inserta una fila nueva (create(), nunca upsert), asi se acumula
 *   historial completo.
 *
 * `date` queda denormalizado a proposito (en vez de resolverse siempre via
 * JOIN a `activities`) para que Bitacora pueda listar el historial de una
 * celda empleado+dia con una sola consulta plana, sin importar el origen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_employee_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('activity_id')->nullable()->constrained()->cascadeOnDelete();
            // Mismo patron que bitacora_entries.corrected_by_employee_id:
            // null cuando quien comento fue un superadmin sin fila propia
            // en `employees` (ver App\Support\PersonalGuard::employee()).
            $table->foreignId('author_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('author_name');
            $table->text('comment');
            $table->timestamps();

            $table->index(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_employee_comments');
    }
};
