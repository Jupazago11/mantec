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
        Schema::create('personal_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();

            // Lista fija de 6 permisos (confirmada con el usuario) — no un
            // catalogo de modulos dinamico, por eso columnas simples y no
            // una tabla pivot.
            $table->boolean('ver_empleados')->default(false);
            $table->boolean('ver_programacion')->default(false);
            $table->boolean('editar_programacion_sin_limite')->default(false);
            $table->boolean('ver_diario_campo')->default(false);
            $table->boolean('cerrar_diario_campo')->default(false);
            $table->boolean('ver_bitacora')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_roles');
    }
};
