<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superadminRole = Role::where('key', 'superadmin')->first();
        $adminRole = Role::where('key', 'admin_global')->first();

        if ($superadminRole) {
            User::updateOrCreate(
                ['username' => 'superadmin'],
                [
                    'name' => 'Desarrollador',
                    'document' => '0000000000',
                    'email' => 'ejemplo@gmail.com',
                    'password' => Hash::make('159875321'),
                    'role_id' => $superadminRole->id,
                    'status' => true,
                ]
            );
        }

        if ($adminRole) {
            User::updateOrCreate(
                ['username' => 'luis.montoya'],
                [
                    'name' => 'Administrador Global',
                    'document' => '1037977052',
                    'email' => 'ejemplo2@gmail.com',
                    'password' => Hash::make('123456'),
                    'role_id' => $adminRole->id,
                    'status' => true,
                ]
            );
        }
    }
}
