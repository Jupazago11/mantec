<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PersonalRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Arranque local del modulo "Personal y Programacion" (Fase 1) — mismo
 * mecanismo que UserSeeder ya usa para dar acceso inicial al sistema
 * actual (superadmin/159875321): sin esto no hay forma de entrar a
 * /personal/login la primera vez, porque el guard `personal` no comparte
 * tabla de usuarios con el sistema actual. Los roles "Administrativo" y
 * "Supervisor" ya quedan sembrados por la migracion
 * add_personal_role_id_to_employees_table (ahi tambien se backfillean
 * los empleados existentes) — aqui solo se referencian por nombre.
 */
class PersonalModuleSeeder extends Seeder
{
    public function run(): void
    {
        Company::updateOrCreate(
            ['name' => 'ARGOS'],
            ['is_default' => true, 'archived' => false]
        );

        $rolAdministrativo = PersonalRole::where('name', 'Administrativo')->first();

        Employee::updateOrCreate(
            ['username' => 'personal.admin'],
            [
                'nombre' => 'Administrativo Demo',
                'nickname' => 'Admin',
                'abreviatura' => 'AD',
                'categoria' => 'Administrativos',
                'personal_role_id' => $rolAdministrativo?->id,
                'activo' => true,
                'has_login' => true,
                'password' => Hash::make('123456'),
                'in_bitacora' => true,
            ]
        );
    }
}
