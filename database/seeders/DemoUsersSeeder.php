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
        // Roles
        $adminRole = Role::where('key', 'admin')->firstOrFail();
        $adminClienteRole = Role::where('key', 'admin_cliente')->firstOrFail();
        $inspectorRole = Role::where('key', 'inspector')->firstOrFail();

        // Clientes
        $corona = Client::where('name', 'CORONA')->firstOrFail();
        $argos = Client::where('name', 'ARGOS')->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | ADMINS
        |--------------------------------------------------------------------------
        */

        $admin1 = User::create([
            'name' => 'Admin Corona',
            'document' => '1001',
            'username' => 'admin.corona',
            'email' => 'admin.corona@mantec.local',
            'password' => '123456',
            'role_id' => $adminRole->id,
            'status' => true,
        ]);

        $admin1->clients()->sync([$corona->id]);

        $admin2 = User::create([
            'name' => 'Admin Argos',
            'document' => '1002',
            'username' => 'admin.argos',
            'email' => 'admin.argos@mantec.local',
            'password' => '123456',
            'role_id' => $adminRole->id,
            'status' => true,
        ]);

        $admin2->clients()->sync([$argos->id]);

        /*
        |--------------------------------------------------------------------------
        | ADMIN CLIENTE
        |--------------------------------------------------------------------------
        */

        $adminCliente1 = User::create([
            'name' => 'Admin Cliente Corona',
            'document' => '2001',
            'username' => 'adminc.corona',
            'email' => 'adminc.corona@mantec.local',
            'password' => '123456',
            'role_id' => $adminClienteRole->id,
            'status' => true,
        ]);

        $adminCliente1->clients()->sync([$corona->id]);

        $adminCliente2 = User::create([
            'name' => 'Admin Cliente Argos',
            'document' => '2002',
            'username' => 'adminc.argos',
            'email' => 'adminc.argos@mantec.local',
            'password' => '123456',
            'role_id' => $adminClienteRole->id,
            'status' => true,
        ]);

        $adminCliente2->clients()->sync([$argos->id]);

        /*
        |--------------------------------------------------------------------------
        | INSPECTORES
        |--------------------------------------------------------------------------
        */

        $inspector1 = User::create([
            'name' => 'Inspector Corona',
            'document' => '3001',
            'username' => 'insp.corona',
            'email' => 'insp.corona@mantec.local',
            'password' => '123456',
            'role_id' => $inspectorRole->id,
            'status' => true,
        ]);

        $inspector1->clients()->sync([$corona->id]);

        $inspector2 = User::create([
            'name' => 'Inspector Argos',
            'document' => '3002',
            'username' => 'insp.argos',
            'email' => 'insp.argos@mantec.local',
            'password' => '123456',
            'role_id' => $inspectorRole->id,
            'status' => true,
        ]);

        $inspector2->clients()->sync([$argos->id]);
    }
}