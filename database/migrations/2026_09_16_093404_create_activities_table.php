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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('company_id')->constrained('companies');
            $table->unsignedSmallInteger('group_number')->nullable();
            // Catalogo "ilustrativo", sin captura real que lo respalde
            // todavia (seccion 6 del documento) — texto libre, no FK.
            $table->string('area', 100)->nullable();
            // "Equipo" — confirmado texto libre, no catalogo (seccion 5).
            $table->string('team', 150)->nullable();
            // "Actividad", version programada — texto libre.
            $table->string('description', 255);
            // 'P' | 'S' — propiedad de la actividad completa, no por
            // persona (correccion seccion 6.1).
            $table->string('activity_type', 1);
            $table->decimal('estimated_hours', 5, 2)->nullable();
            $table->string('shift', 20);
            $table->foreignId('responsible_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('created_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();

            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
