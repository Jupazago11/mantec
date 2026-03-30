<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;
use App\Models\Client;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $corona = Client::where('name', 'CORONA')->firstOrFail();

        $areas = [
            'TRITURACION',
            'PREHOMO',
            'APILADO ADITIVOS',
            'RECLAMADOR ADITIVOS',
            'APILADO CARBON',
            'MOLINO CARBON',
            'MOLINO CRUDO',
            'MOLINO CEMENTO',
            'ALTERNOS',
        ];

        foreach ($areas as $area) {
            Area::create([
                'name' => $area,
                'code' => null,
                'client_id' => $corona->id,
                'status' => true,
            ]);
        }
    }
}