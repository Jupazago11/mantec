<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Element;
use App\Models\ElementType;
use Illuminate\Database\Seeder;

class ElementSeeder extends Seeder
{
    public function run(): void
    {
        $type = ElementType::query()
            ->where('name', 'Banda transportadora')
            ->firstOrFail();

        $data = [
            'TRITURACION' => [
                '211BT01',
                '291BT01',
                '291SM01',
                '291BT03',
            ],

            'PREHOMO' => [
                '291BT04',
                '311BT01',
                '311BT02',
                '291BT02',
            ],

            'APILADO ADITIVOS' => [
                'K11BT01',
                'K11BT02',
                'K11BT03',
                'K21BT01',
                'K21BT02',
            ],

            'RECLAMADOR ADITIVOS' => [
                'K91BT01',
                'K91BT02',
                'K91BT03',
            ],

            'APILADO CARBON' => [
                'L11BT01',
                'L11BT02',
                'L11BT03',
                'L11BT04',
                'L11BT05',
                'L11BT06',
            ],

            'MOLINO CARBON' => [
                'L21BT01',
                'L21BT02',
                'L21BT03',
                'L21SM01',
                'L61BP01',
            ],

            'MOLINO CRUDO' => [
                'K91BT05',
                '321BP01',
                '321BP02',
                '321BP03',
                '331BT01',
                '361BT01',
                '361BT02',
                '361BT03',
                'K91BT04',
            ],

            'MOLINO CEMENTO' => [
                'K91BT04',
                '521BT01',
                '521BT02',
                '521BP01',
                '521BP02',
                '521BP03',
                '531BT01',
                '511BT01',
                '511BP01',
                '561BT01',
                '561SM01',
                '561BT02',
                '561BT03',
                '561BT04',
                '491BT01',
            ],

            'ALTERNOS' => [
                'L71BP01',
                'L71TN01',
            ],
        ];

        foreach ($data as $areaName => $elements) {
            $area = Area::query()
                ->where('name', trim($areaName))
                ->firstOrFail();

            foreach ($elements as $code) {
                $code = trim($code);

                $exists = Element::query()
                    ->where('area_id', $area->id)
                    ->where('name', $code)
                    ->exists();

                if ($exists) {
                    continue;
                }

                Element::create([
                    'name' => $code,
                    'code' => $code,
                    'warehouse_code' => null,
                    'area_id' => $area->id,
                    'element_type_id' => $type->id,
                    'status' => true,
                ]);
            }
        }
    }
}
