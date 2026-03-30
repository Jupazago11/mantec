<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            [
                'name' => 'CORONA',
                'obs' => 'Cliente base de pruebas',
                'status' => true,
            ],
            [
                'name' => 'ARGOS',
                'obs' => 'Cliente base de pruebas',
                'status' => true,
            ],
            [
                'name' => 'CALINA',
                'obs' => 'Cliente base de pruebas',
                'status' => true,
            ],
        ];

        foreach ($clients as $client) {
            Client::create($client);
        }
    }
}