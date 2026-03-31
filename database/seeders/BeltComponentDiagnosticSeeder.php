<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Component;
use App\Models\Diagnostic;
use Illuminate\Database\Seeder;

class BeltComponentDiagnosticSeeder extends Seeder
{
    public function run(): void
    {
        $clientName = 'CORONA';

        $client = Client::where('name', $clientName)->firstOrFail();

        $map = [
            'Banda' => ['Mal estado'],
            'Cama de impacto' => ['Mal estado'],
            'Chumaceras y rodamientos' => ['Mal estado'],
            'Sistema de descarga' => ['Mal estado'],
            'Motorreductor' => ['Fuga de aceite'],
            'Guardilla' => ['Mal estado'],
            'Limpiador primario' => ['Mal estado'],
            'Limpiador secundario' => ['Mal estado'],
            'Limpiador tipo arado' => ['Mal estado'],
            'Limpiador transversal' => ['Mal estado'],
            'Rodillos de carga' => ['Mal estado'],
            'Rodillos de retorno' => ['Mal estado'],
            'Rodillos laterales' => ['Mal estado'],
            'Tambor cola' => ['Mal estado'],
            'Tambor contra pesa' => ['Mal estado'],
            'Tambor de inflexión' => ['Mal estado'],
            'Tambor motriz' => ['Mal estado'],
            'Tambor snub' => ['Mal estado'],
            'General' => ['Condición de seguridad', 'Material acumulado'],
        ];

        foreach ($map as $componentName => $diagnosticNames) {
            $component = Component::where('client_id', $client->id)
                ->where('name', $componentName)
                ->firstOrFail();

            $diagnosticIds = Diagnostic::where('client_id', $client->id)
                ->whereIn('name', $diagnosticNames)
                ->pluck('id')
                ->toArray();

            $component->diagnostics()->syncWithoutDetaching($diagnosticIds);
        }
    }
}