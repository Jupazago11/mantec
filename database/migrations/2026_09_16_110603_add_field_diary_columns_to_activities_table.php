<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // Diario de Campo — mismo objeto Activity enriquecido (seccion
            // 5 del documento), no una tabla aparte.
            $table->string('process', 150)->nullable(); // "Proceso"
            $table->text('executed_description')->nullable(); // "Actividad" version ejecutada

            // Trazabilidad de horas (seccion 5/8): programada ya vive en
            // estimated_hours. reported_hours queda lista para cuando
            // exista la API de la app (seccion 7, repo aparte) — no editable
            // desde esta pantalla todavia.
            $table->decimal('corrected_hours', 5, 2)->nullable();
            $table->decimal('reported_hours', 5, 2)->nullable();

            $table->text('comments')->nullable();

            // Codigos de integracion con el cliente (ej. SAP de Argos) —
            // texto libre a proposito, significado real aun no confirmado
            // (seccion 5, "pendiente explicito").
            $table->string('zcom', 50)->nullable();
            $table->string('line_code', 50)->nullable(); // "Linea" ("line" a secas evitado por ambiguedad SQL
            $table->string('ot_sap', 50)->nullable();
            $table->string('acta_entrega', 50)->nullable();
            $table->string('we_code', 50)->nullable();

            $table->foreignId('closed_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closed_by_employee_id');
            $table->dropColumn([
                'process', 'executed_description', 'corrected_hours', 'reported_hours',
                'comments', 'zcom', 'line_code', 'ot_sap', 'acta_entrega', 'we_code', 'closed_at',
            ]);
        });
    }
};
