<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemModule;
use App\Models\Role;
use App\Models\RoleModulePermission;

class SystemModuleSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Crear módulo (seguro)
        |--------------------------------------------------------------------------
        */
        $module = SystemModule::updateOrCreate(
            ['key' => 'mediciones'],
            [
                'name' => 'Mediciones',
                'description' => 'Módulo de mediciones técnicas por activo',
                'status' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 2. Permisos por rol
        |--------------------------------------------------------------------------
        */
        $permissionsByRole = [
            'superadmin' => [
                'can_view' => true,
                'can_create' => true,
                'can_manage' => true,
            ],
            'admin_global' => [
                'can_view' => true,
                'can_create' => true,
                'can_manage' => true,
            ],
            'admin' => [
                'can_view' => true,
                'can_create' => true,
                'can_manage' => false,
            ],
            'admin_cliente' => [
                'can_view' => true,
                'can_create' => true,
                'can_manage' => false,
            ],
            'observador' => [
                'can_view' => false,
                'can_create' => false,
                'can_manage' => false,
            ],
            'observador_cliente' => [
                'can_view' => false,
                'can_create' => false,
                'can_manage' => false,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | 3. Insertar permisos sin duplicar
        |--------------------------------------------------------------------------
        */
        foreach ($permissionsByRole as $roleKey => $permissions) {

            $role = Role::query()->where('key', $roleKey)->first();

            if (!$role) {
                continue;
            }

            RoleModulePermission::updateOrCreate(
                [
                    'role_id' => $role->id,
                    'system_module_id' => $module->id,
                ],
                [
                    'can_view' => $permissions['can_view'],
                    'can_create' => $permissions['can_create'],
                    'can_manage' => $permissions['can_manage'],
                ]
            );
        }
    }
}