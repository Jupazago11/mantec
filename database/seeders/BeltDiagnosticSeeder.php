<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Diagnostic;
use Illuminate\Database\Seeder;

class BeltDiagnosticSeeder extends Seeder
{
    public function run(): void
    {
        $clientName = 'CORONA';

        $client = Client::where('name', $clientName)->firstOrFail();

        $diagnostics = [
            [
                'code' => 'MAL-ESTADO',
                'name' => 'Mal estado',
                'description' => 'Componente en condición deficiente o deteriorada.',
            ],
            [
                'code' => 'FUGA-ACEITE',
                'name' => 'Fuga de aceite',
                'description' => 'Presencia de fuga de aceite en el componente.',
            ],
            [
                'code' => 'COND-SEG',
                'name' => 'Condición de seguridad',
                'description' => 'Hallazgo relacionado con condición de seguridad.',
            ],
            [
                'code' => 'MAT-ACUM',
                'name' => 'Material acumulado',
                'description' => 'Presencia de acumulación de material.',
            ],
        ];

        foreach ($diagnostics as $item) {
            $exists = Diagnostic::where('client_id', $client->id)
                ->where('code', $item['code'])
                ->exists();

            if (!$exists) {
                Diagnostic::create([
                    'client_id' => $client->id,
                    'code' => $item['code'],
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'status' => true,
                ]);
            }
        }
    }
}