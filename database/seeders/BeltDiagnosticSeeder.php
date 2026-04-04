<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Diagnostic;
use Illuminate\Database\Seeder;

class BeltDiagnosticSeeder extends Seeder
{
    public function run(): void
    {
        $client = Client::query()
            ->where('name', 'CORONA')
            ->firstOrFail();

        $diagnostics = [
            [
                'name' => 'Estado',
                'description' => 'Inspección general del estado del componente.',
            ],
            [
                'name' => 'Empalme',
                'description' => 'Revisión de la condición del empalme de la banda.',
            ],
            [
                'name' => 'Alineacion',
                'description' => 'Verificación de alineación del componente o sistema.',
            ],
            [
                'name' => 'Temperatura',
                'description' => 'Verificación de temperatura de operación.',
            ],
            [
                'name' => 'Pantalla de sacrificio',
                'description' => 'Inspección de desgaste o condición de la pantalla de sacrificio.',
            ],
            [
                'name' => 'Lamina de sacrificio',
                'description' => 'Inspección de desgaste o condición de la lámina de sacrificio.',
            ],
            [
                'name' => 'Recubrimiento',
                'description' => 'Inspección del recubrimiento del componente.',
            ],
            [
                'name' => 'Rodamientos',
                'description' => 'Inspección del estado y funcionamiento de los rodamientos.',
            ],
            [
                'name' => 'Aseo',
                'description' => 'Verificación de limpieza y orden del componente o zona.',
            ],
        ];

        foreach ($diagnostics as $item) {
            Diagnostic::updateOrCreate(
                [
                    'client_id' => $client->id,
                    'name' => trim($item['name']),
                ],
                [
                    'description' => $item['description'],
                    'status' => true,
                ]
            );
        }
    }
}
