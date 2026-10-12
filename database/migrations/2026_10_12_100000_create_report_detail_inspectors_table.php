<?php

use App\Services\Reports\ReportDetailInspectorBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Inspectores que complementaron un ReportDetail por fusión (mismo activo +
 * componente + diagnóstico dentro de 24h, ver ReportDetailMerger). El creador
 * sigue siendo report_details.user_id; aquí solo quedan los adicionales.
 *
 * El backfill reconstruye los adicionales de reportes ya fusionados a partir de
 * los encabezados "Nombre — dd/mm/aaaa HH:mm:" que la fusión escribe en el hallazgo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_detail_inspectors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('report_detail_id')
                ->constrained('report_details')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['report_detail_id', 'user_id']);
            $table->index('user_id');
        });

        app(ReportDetailInspectorBackfill::class)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('report_detail_inspectors');
    }
};
