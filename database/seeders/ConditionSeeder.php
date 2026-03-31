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
            ['name' => 'ALTA',      'code' => 'PF1',        'severity' => 1,    'color' => '#ff0000'],
            ['name' => 'MEDIA',     'code' => 'PF2',        'severity' => 2,    'color' => '#fffb00'],
            ['name' => 'BAJA',      'code' => 'PF3',        'severity' => 3,    'color' => '#00a2ff'],
            ['name' => 'ALTA',      'code' => 'SEG C',      'severity' => 1,    'color' => '#ff0000'],
            ['name' => 'MEDIA',     'code' => 'SEG D',      'severity' => 2,    'color' => '#fffb00'],
            ['name' => 'ASEO',      'code' => 'ASEO',       'severity' => 0,    'color' => '#ffae00'],
            ['name' => 'OBSERVACIÓN', 'code' => 'OBSERV',   'severity' => 0,    'color' => '#6e34cc'],
            ['name' => 'OK',        'code' => 'OK',         'severity' => 0,    'color' => '#11a152'],
        ];

        foreach ($conditions as $condition) {
            Condition::create([
                'client_id' => $corona->id,
                'name' => $condition['name'],
                'code' => $condition['code'],
                'description' => null,
                'severity' => $condition['severity'],
                'color' => $condition['color'],
                'status' => true,
            ]);
        }
    }
}