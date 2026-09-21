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
        Schema::table('personal_roles', function (Blueprint $table) {
            // Complementa personal_categories.responsable_actividad (ver
            // migracion 2026_09_18_140000): el flag de Rol marca "todo
            // subrol de este Rol es Responsable"; este flag de Subrol
            // permite marcar solo uno o varios subroles especificos dentro
            // de un Rol que no se quiere marcar completo (ej. "Supervisor"
            // si, "Administrativo"/"SISO" no, los 3 dentro del mismo Rol
            // "Administrativo"). Ambos se combinan con OR en
            // ActivityController. Default false: no reactiva a nadie solo.
            $table->boolean('responsable_actividad')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_roles', function (Blueprint $table) {
            $table->dropColumn('responsable_actividad');
        });
    }
};
