<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Diagnostic;
use Illuminate\Database\Seeder;

class BeltDiagnosticSeeder extends Seeder
{
    public function run(): void
    {
        $client = Client::where('name', 'CORONA')->firstOrFail();

        $diagnostics = [
            [
                'name' => 'Mal estado',
                'description' => 'Componente en condición deficiente o deteriorada.',
            ],
            [
                'name' => 'Fuga de aceite',
                'description' => 'Presencia de fuga de aceite en el componente.',
            ],
            [
                'name' => 'Condición de seguridad',
                'description' => 'Hallazgo relacionado con condición de seguridad.',
            ],
            [
                'name' => 'Material acumulado',
                'description' => 'Presencia de acumulación de material.',
            ],
        ];

        foreach ($diagnostics as $item) {
            $exists = Diagnostic::where('client_id', $client->id)
                ->where('name', $item['name'])
                ->exists();

            if (!$exists) {
                Diagnostic::create([
                    'client_id' => $client->id,
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'status' => true,
                ]);
            }
        }
    }
}
