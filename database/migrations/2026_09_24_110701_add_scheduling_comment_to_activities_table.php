<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comentario opcional de contexto para la actividad, capturado al
 * programarla y editable despues (pedido 2026-09-24). Distinto a
 * `comments` (ya existente): ese es "la actividad ejecutada" segun
 * FieldDiaryController (Diario de Campo, seccion 14.23); este es
 * contexto adicional puesto por quien programa, antes de ejecutarse.
 * Se lee tambien desde Bitacora (BitacoraController::index), fusionado
 * en el historial de comentarios que ya existe por empleado/dia — no se
 * duplica en activity_employee_comments para no interferir con el
 * upsert exclusivo del responsable via app Android sobre esa tabla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->text('scheduling_comment')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('scheduling_comment');
        });
    }
};
