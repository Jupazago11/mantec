<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('personal_role_id')->nullable()->after('role')->constrained('personal_roles')->nullOnDelete();
        });

        // Migracion de datos (no solo de esquema): siembra los 2 roles que
        // ya existian como string ('administrativo'/'supervisor'), con los
        // mismos 6 permisos que ese rol ya tenia en la practica hoy (ej.
        // Empleados/Empresas no tenian ninguna restriccion de rol todavia,
        // por eso ambos arrancan con ver_empleados=true) — asi el deploy
        // de este cambio no le quita acceso a nadie. El usuario ajusta
        // desde la UI nueva de ahi en adelante.
        $now = now();

        $adminId = DB::table('personal_roles')->insertGetId([
            'name' => 'Administrativo',
            'ver_empleados' => true,
            'ver_programacion' => true,
            'editar_programacion_sin_limite' => true,
            'ver_diario_campo' => true,
            'cerrar_diario_campo' => true,
            'ver_bitacora' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $supervisorId = DB::table('personal_roles')->insertGetId([
            'name' => 'Supervisor',
            'ver_empleados' => true,
            'ver_programacion' => true,
            'editar_programacion_sin_limite' => false,
            'ver_diario_campo' => true,
            'cerrar_diario_campo' => false,
            'ver_bitacora' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('employees')->where('role', 'administrativo')->update(['personal_role_id' => $adminId]);
        DB::table('employees')->where('role', 'supervisor')->update(['personal_role_id' => $supervisorId]);

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('role')->nullable()->after('categoria');
        });

        $adminId = DB::table('personal_roles')->where('name', 'Administrativo')->value('id');
        $supervisorId = DB::table('personal_roles')->where('name', 'Supervisor')->value('id');

        if ($adminId) {
            DB::table('employees')->where('personal_role_id', $adminId)->update(['role' => 'administrativo']);
        }
        if ($supervisorId) {
            DB::table('employees')->where('personal_role_id', $supervisorId)->update(['role' => 'supervisor']);
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('personal_role_id');
        });

        DB::table('personal_roles')->whereIn('name', ['Administrativo', 'Supervisor'])->delete();
    }
};
