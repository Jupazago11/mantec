<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * activities.reported_hours guarda la SUMA de horas de todas las personas
 * cuando el responsable registra detalle por persona (RegistroSupervisor).
 * Con decimal(5,2) el maximo era 999.99: una cuadrilla grande (ej. 45
 * personas x 24 h) desbordaba la columna con un error 500 aunque cada
 * valor individual fuera valido (revision 2026-09-28). decimal(8,2) da
 * margen de sobra; el resto de columnas de horas son por persona/dia y
 * quedan en (5,2), con validacion max:24.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->decimal('reported_hours', 8, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->decimal('reported_hours', 5, 2)->nullable()->change();
        });
    }
};
