<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Condition;

class ConditionSeeder extends Seeder
{
    public function run(): void
    {
        $corona = Client::where('name', 'CORONA')->firstOrFail();

        $conditions = [
            ['name' => 'Probabilidad de falla alta', 'code' => 'PF1', 'severity' => 1],
            ['name' => 'Probabilidad de falla media', 'code' => 'PF2', 'severity' => 2],
            ['name' => 'Probabilidad de falla baja', 'code' => 'PF3', 'severity' => 3],
            ['name' => 'Seguridad por condición', 'code' => 'SEG C', 'severity' => 1],
            ['name' => 'Seguridad por diseño', 'code' => 'SEG D', 'severity' => 2],
            ['name' => 'Material acumulado', 'code' => 'ASEO', 'severity' => 0],
            ['name' => 'Observación', 'code' => 'OBSERV', 'severity' => 0],
            ['name' => 'OK', 'code' => 'OK', 'severity' => 0],
        ];

        foreach ($conditions as $condition) {
            Condition::create([
                'client_id' => $corona->id,
                'name' => $condition['name'],
                'code' => $condition['code'],
                'description' => null,
                'severity' => $condition['severity'],
                'status' => true,
            ]);
        }
    }
}