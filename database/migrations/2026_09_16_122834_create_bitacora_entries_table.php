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
        Schema::create('bitacora_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');

            // Texto libre a proposito (seccion 8, confirmado 2026-09-09/10):
            // admite numeros y codigos de letra (ej. "L" = licencia).
            $table->string('corrected_value', 20)->nullable();
            $table->text('comment')->nullable();

            $table->foreignId('corrected_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacora_entries');
    }
};
