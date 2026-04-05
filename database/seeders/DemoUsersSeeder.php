<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\Client;
use App\Models\ElementType;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::pluck('id', 'key');

        $corona = Client::where('name', 'CORONA')->firstOrFail();

        $bandaTransportadora = ElementType::where('client_id', $corona->id)
            ->where('name', 'Banda transportadora')
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | ADMIN EMPRESA
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
        $adminEmpresa->allowedElementTypes()->detach();

        /*
        |--------------------------------------------------------------------------
        | INSPECTOR CORONA
        |--------------------------------------------------------------------------
        */
        $inspectorCorona1 = User::updateOrCreate(
            ['username' => 'ins.corona'],
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

        $inspectorCorona1->allowedElementTypes()->sync([
            $bandaTransportadora->id => [
                'client_id' => $corona->id,
            ],
        ]);
    }
}
