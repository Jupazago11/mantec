<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superadminRole = Role::where('key', 'superadmin')->first();
        $adminRole = Role::where('key', 'admin_global')->first();

        if ($superadminRole) {
            User::create([
                'name' => 'Super Administrador',
                'document' => '10000001',
                'username' => 'superadmin',
                'email' => 'superadmin@mantec.local',
                'password' => '123456',
                'role_id' => $superadminRole->id,
                'status' => true,
            ]);
        }

        if ($adminRole) {
            User::create([
                'name' => 'Administrador Global',
                'document' => '10000002',
                'username' => 'admin_global',
                'email' => 'admin@mantec.local',
                'password' => '123456',
                'role_id' => $adminRole->id,
                'status' => true,
            ]);
        }
    }
}