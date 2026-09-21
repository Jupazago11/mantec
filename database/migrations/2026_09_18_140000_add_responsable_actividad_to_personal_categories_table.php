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
        Schema::table('personal_categories', function (Blueprint $table) {
            // Reemplaza el chequeo hardcodeado por nombre de subrol
            // ("Supervisor") en ActivityController — ahora "quien puede ser
            // Responsable de una actividad" se decide por Rol (categoria),
            // no por el nombre de un subrol especifico. Default false a
            // proposito: el deploy no reactiva automaticamente a nadie como
            // Responsable, queda a criterio del superadmin activarlo desde
            // "Roles y permisos" para el Rol que corresponda.
            $table->boolean('responsable_actividad')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_categories', function (Blueprint $table) {
            $table->dropColumn('responsable_actividad');
        });
    }
};
