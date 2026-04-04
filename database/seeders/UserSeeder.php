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
                    'document' => '10000001',
                    'email' => 'superadmin@mantec.local',
                    'password' => Hash::make('123456'),
                    'role_id' => $superadminRole->id,
                    'status' => true,
                ]
            );
        }

        if ($adminRole) {
            User::updateOrCreate(
                ['username' => 'admin_global'],
                [
                    'name' => 'Administrador Global',
                    'document' => '1037977046',
                    'email' => 'jupazago11@gmail.com',
                    'password' => Hash::make('159875321'),
                    'role_id' => $adminRole->id,
                    'status' => true,
                ]
            );
        }
    }
}
