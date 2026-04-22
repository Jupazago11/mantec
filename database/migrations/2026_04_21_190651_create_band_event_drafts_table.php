<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('band_event_drafts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('element_id')
                ->constrained('elements')
                ->cascadeOnDelete();

            $table->string('type'); // band | vulcanization | section_change

            // Relación con banda padre (si es hijo)
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('band_events')
                ->nullOnDelete();

            $table->date('report_date')->nullable();

            // =========================
            // CAMPOS CAMBIO DE BANDA
            // =========================
            $table->string('brand')->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->integer('roll_count')->nullable();

            // =========================
            // CAMPOS VULCANIZADO
            // =========================
            $table->string('vulcanization_type')->nullable();
            $table->decimal('temperature', 10, 2)->nullable();
            $table->decimal('pressure', 10, 2)->nullable();
            $table->decimal('time', 10, 2)->nullable();

            // =========================
            // CAMBIO DE TRAMO
            // =========================
            $table->decimal('section_length', 10, 2)->nullable();
            $table->decimal('section_width', 10, 2)->nullable();

            // =========================
            // COMUNES
            // =========================
            $table->text('observation')->nullable();

            // Auditoría
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // 🔥 CLAVE: 1 borrador por activo por tipo
            $table->unique(['element_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('band_event_drafts');
    }
};