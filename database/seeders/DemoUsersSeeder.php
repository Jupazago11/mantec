<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Client;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::pluck('id', 'key');

        $corona = Client::where('name', 'CORONA')->firstOrFail();
        $argos = Client::where('name', 'ARGOS')->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | ADMIN EMPRESA (ADMIN2)
        |--------------------------------------------------------------------------
        */
        $adminEmpresa = User::create([
            'name' => 'Admin Empresa',
            'document' => '1000',
            'username' => 'admin.empresa',
            'email' => 'admin.empresa@mantec.local',
            'password' => '123456',
            'role_id' => $roles['admin'],
            'status' => true,
        ]);

        $adminEmpresa->clients()->sync([$corona->id, $argos->id]);

        /*
        |--------------------------------------------------------------------------
        | ADMIN CLIENTE CORONA (2)
        |--------------------------------------------------------------------------
        */
        $adminClienteCorona1 = User::create([
            'name' => 'Admin Cliente Corona 1',
            'document' => '2001',
            'username' => 'adminc.corona1',
            'email' => 'adminc1.corona@mantec.local',
            'password' => '123456',
            'role_id' => $roles['admin_cliente'],
            'status' => true,
        ]);

        $adminClienteCorona2 = User::create([
            'name' => 'Admin Cliente Corona 2',
            'document' => '2002',
            'username' => 'adminc.corona2',
            'email' => 'adminc2.corona@mantec.local',
            'password' => '123456',
            'role_id' => $roles['admin_cliente'],
            'status' => true,
        ]);

        $adminClienteCorona1->clients()->sync([$corona->id]);
        $adminClienteCorona2->clients()->sync([$corona->id]);

        /*
        |--------------------------------------------------------------------------
        | ADMIN CLIENTE ARGOS (2)
        |--------------------------------------------------------------------------
        */
        $adminClienteArgos1 = User::create([
            'name' => 'Admin Cliente Argos 1',
            'document' => '2003',
            'username' => 'adminc.argos1',
            'email' => 'adminc1.argos@mantec.local',
            'password' => '123456',
            'role_id' => $roles['admin_cliente'],
            'status' => true,
        ]);

        $adminClienteArgos2 = User::create([
            'name' => 'Admin Cliente Argos 2',
            'document' => '2004',
            'username' => 'adminc.argos2',
            'email' => 'adminc2.argos@mantec.local',
            'password' => '123456',
            'role_id' => $roles['admin_cliente'],
            'status' => true,
        ]);

        $adminClienteArgos1->clients()->sync([$argos->id]);
        $adminClienteArgos2->clients()->sync([$argos->id]);

        /*
        |--------------------------------------------------------------------------
        | INSPECTORES CORONA (2)
        |--------------------------------------------------------------------------
        */
        $inspectorCorona1 = User::create([
            'name' => 'Inspector Corona 1',
            'document' => '3001',
            'username' => 'insp.corona1',
            'email' => 'insp1.corona@mantec.local',
            'password' => '123456',
            'role_id' => $roles['inspector'],
            'status' => true,
        ]);

        $inspectorCorona2 = User::create([
            'name' => 'Inspector Corona 2',
            'document' => '3002',
            'username' => 'insp.corona2',
            'email' => 'insp2.corona@mantec.local',
            'password' => '123456',
            'role_id' => $roles['inspector'],
            'status' => true,
        ]);

        $inspectorCorona1->clients()->sync([$corona->id]);
        $inspectorCorona2->clients()->sync([$corona->id]);

        /*
        |--------------------------------------------------------------------------
        | INSPECTORES ARGOS (2)
        |--------------------------------------------------------------------------
        */
        $inspectorArgos1 = User::create([
            'name' => 'Inspector Argos 1',
            'document' => '3003',
            'username' => 'insp.argos1',
            'email' => 'insp1.argos@mantec.local',
            'password' => '123456',
            'role_id' => $roles['inspector'],
            'status' => true,
        ]);

        $inspectorArgos2 = User::create([
            'name' => 'Inspector Argos 2',
            'document' => '3004',
            'username' => 'insp.argos2',
            'email' => 'insp2.argos@mantec.local',
            'password' => '123456',
            'role_id' => $roles['inspector'],
            'status' => true,
        ]);

        $inspectorArgos1->clients()->sync([$argos->id]);
        $inspectorArgos2->clients()->sync([$argos->id]);
    }
}
