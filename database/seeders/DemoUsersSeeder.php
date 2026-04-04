<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\Client;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::pluck('id', 'key');

        $corona = Client::where('name', 'CORONA')->firstOrFail();
        //$argos = Client::where('name', 'ARGOS')->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | ADMIN EMPRESA (ADMIN2)
        |--------------------------------------------------------------------------
        */
        $adminEmpresa = User::updateOrCreate(
            ['username' => 'luis.montoya'],
            [
                'name' => 'Luis Montoya',
                'document' => '1037977052',
                'email' => 'lui@gmail.com',
                'password' => Hash::make('123456'),
                'role_id' => $roles['admin'],
                'status' => true,
            ]
        );
        $adminEmpresa->clients()->sync([$corona->id]);

        /*
        |--------------------------------------------------------------------------
        | ADMIN CLIENTE CORONA (2)
        |--------------------------------------------------------------------------
        */
        /*
        $adminClienteCorona1 = User::updateOrCreate(
            ['username' => 'admin.corona1'],
            [
                'name' => 'Admin Cliente Corona 1',
                'document' => '2001',
                'email' => 'adminc1.corona@mantec.local',
                'password' => Hash::make('123456'),
                'role_id' => $roles['admin_cliente'],
                'status' => true,
            ]
        );

        $adminClienteCorona1->clients()->sync([$corona->id]);


        /*
        |--------------------------------------------------------------------------
        | INSPECTORES CORONA (1)
        |--------------------------------------------------------------------------
        */
        /*
        $inspectorCorona1 = User::updateOrCreate(
            ['username' => 'ins.corona1'],
            [
                'name' => 'Inspector Corona 1',
                'document' => '3001',
                'email' => 'insp1.corona@mantec.local',
                'password' => Hash::make('123456'),
                'role_id' => $roles['inspector'],
                'status' => true,
            ]
        );

        $inspectorCorona1->clients()->sync([$corona->id]);
        */
    }
}
