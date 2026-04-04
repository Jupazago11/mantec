<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Client;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $client = Client::query()
            ->where('name', 'CORONA')
            ->firstOrFail();

        $areas = [
            ['code' => 'TRI', 'name' => 'TRITURACION'],
            ['code' => 'PRH', 'name' => 'PREHOMO'],
            ['code' => 'APA', 'name' => 'APILADO ADITIVOS'],
            ['code' => 'REA', 'name' => 'RECLAMADOR ADITIVOS'],
            ['code' => 'APC', 'name' => 'APILADO CARBON'],
            ['code' => 'MCA', 'name' => 'MOLINO CARBON'],
            ['code' => 'MCR', 'name' => 'MOLINO CRUDO'],
            ['code' => 'MCE', 'name' => 'MOLINO CEMENTO'],
            ['code' => 'ALT', 'name' => 'ALTERNOS'],
        ];

        foreach ($areas as $areaData) {
            Area::updateOrCreate(
                [
                    'client_id' => $client->id,
                    'name' => $areaData['name'],
                ],
                [
                    'code' => $areaData['code'],
                    'status' => true,
                ]
            );
        }
    }
}
