<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'SuperAdmin',
                'key' => 'superadmin',
                'description' => 'Acceso total al sistema',
                'status' => true,
            ],
            [
                'name' => 'Administrador Global',
                'key' => 'admin_global',
                'description' => 'Administración general',
                'status' => true,
            ],
            [
                'name' => 'Administrador',
                'key' => 'admin',
                'description' => 'Administración general',
                'status' => true,
            ],
            [
                'name' => 'Administrador Cliente',
                'key' => 'admin_cliente',
                'description' => 'Administra información del cliente',
                'status' => true,
            ],
            [
                'name' => 'Inspector',
                'key' => 'inspector',
                'description' => 'Registro y consulta de inspecciones',
                'status' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}