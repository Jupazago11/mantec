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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('nickname', 50);
            $table->string('abreviatura', 10)->nullable();
            // 'Campo' | 'Administrativos' — ver seccion 4.3 del documento
            // NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md.
            $table->string('categoria', 20);
            // 'supervisor' | 'administrativo' | null (Campo nunca tiene rol
            // ni login, validado en el controller). Columna string simple
            // a proposito: los nombres formales de rol siguen pendientes de
            // definir con el cliente (seccion 11 del documento) — una tabla
            // de permisos completa seria adelantarse a un requisito que aun
            // no esta cerrado.
            $table->string('role', 20)->nullable();
            $table->boolean('activo')->default(true);
            // "Tiene usuario de acceso" (seccion 4 del documento).
            $table->boolean('has_login')->default(false);
            $table->string('username', 50)->nullable()->unique();
            $table->string('password', 255)->nullable();
            // "Hace parte de la Bitacora" (seccion 4 del documento).
            $table->boolean('in_bitacora')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
