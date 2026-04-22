<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('band_events', function (Blueprint $table) {
            $table->id();

            // Relación con activo
            $table->foreignId('element_id')
                ->constrained('elements')
                ->cascadeOnDelete();

            // Tipo de evento
            $table->string('type'); // band | vulcanization | section_change

            // Relación padre (solo hijos)
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('band_events')
                ->nullOnDelete();

            // Fecha del negocio (CRÍTICA)
            $table->date('report_date');

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
            // CAMPOS CAMBIO DE TRAMO
            // =========================
            $table->decimal('section_length', 10, 2)->nullable();
            $table->decimal('section_width', 10, 2)->nullable();

            // =========================
            // COMUNES
            // =========================
            $table->text('observation')->nullable();

            // Control lógico
            $table->boolean('status')->default(true);

            // Auditoría
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('published_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Índices IMPORTANTES
            $table->index(['element_id', 'report_date']);
            $table->index(['type']);
            $table->index(['parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('band_events');
    }
};