<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nickname y usuario de empleado unicos SIN distinguir mayusculas
 * (pedido 2026-09-29). La validacion vive en EmployeeController (nickname)
 * y App\Rules\UsuarioUnicoEnPlataforma (usuario, que ademas cruza con
 * users); estos indices la respaldan en la BD ante dos guardados
 * simultaneos. El unique case-sensitive existente de employees.username se
 * conserva. Verificado antes de crear la migracion: 0 duplicados en local
 * y en produccion.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE UNIQUE INDEX employees_nickname_lower_unique ON employees (lower(trim(nickname)))');
        DB::statement('CREATE UNIQUE INDEX employees_username_lower_unique ON employees (lower(username)) WHERE username IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS employees_nickname_lower_unique');
        DB::statement('DROP INDEX IF EXISTS employees_username_lower_unique');
    }
};
