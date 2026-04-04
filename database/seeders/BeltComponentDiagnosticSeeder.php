<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Component;
use App\Models\Diagnostic;
use App\Models\ElementType;
use Illuminate\Database\Seeder;
use RuntimeException;

class BeltComponentDiagnosticSeeder extends Seeder
{
    public function run(): void
    {
        $client = Client::query()
            ->where('name', 'CORONA')
            ->firstOrFail();

        $elementType = ElementType::query()
            ->where('client_id', $client->id)
            ->where('name', 'Banda transportadora')
            ->firstOrFail();

        $map = [
            'Banda' => [
                'Estado',
                'Empalme',
                'Alineacion',
                'Temperatura',
            ],
            'Chute de descarga' => [
                'Estado',
                'Pantalla de sacrificio',
                'Lamina de sacrificio',
            ],
            'Encausadores' => [
                'Estado',
            ],
            'Guardilla' => [
                'Estado',
            ],
            'Cama de impacto' => [
                'Estado',
            ],
            'Rodillos de carga' => [
                'Estado',
            ],
            'Rodillos de retorno' => [
                'Estado',
            ],
            'Rodillos laterales' => [
                'Estado',
            ],
            'Tambor de cola' => [
                'Recubrimiento',
                'Rodamientos',
            ],
            'Tambor de inflexion' => [
                'Recubrimiento',
                'Rodamientos',
            ],
            'Tambor de contra pesa' => [
                'Recubrimiento',
                'Rodamientos',
            ],
            'Tambor snub' => [
                'Recubrimiento',
                'Rodamientos',
            ],
            'Tambor motriz' => [
                'Recubrimiento',
                'Rodamientos',
            ],
            'Motorreductor' => [
                'Estado',
                'Temperatura',
            ],
            'Material acumulado' => [
                'Aseo',
            ],
            'Condicion de seguridad' => [
                'Estado',
            ],
            'Cubiertas' => [
                'Estado',
            ],
            'Otros' => [
                'Estado',
            ],
            'Limpiador primario' => [
                'Estado',
            ],
            'Limpiador secundario' => [
                'Estado',
            ],
            'Limpiador tipo arado' => [
                'Estado',
            ],
            'Limpiador transversal' => [
                'Estado',
            ],
        ];

        foreach ($map as $componentName => $diagnosticNames) {
            $component = Component::query()
                ->where('client_id', $client->id)
                ->where('element_type_id', $elementType->id)
                ->where('name', trim($componentName))
                ->firstOrFail();

            $normalizedDiagnosticNames = collect($diagnosticNames)
                ->map(fn ($name) => trim($name))
                ->values()
                ->all();

            $diagnosticIds = Diagnostic::query()
                ->where('client_id', $client->id)
                ->whereIn('name', $normalizedDiagnosticNames)
                ->pluck('id')
                ->all();

            if (count($diagnosticIds) !== count($normalizedDiagnosticNames)) {
                throw new RuntimeException(
                    "No se encontraron todos los diagnósticos para el componente [{$componentName}]."
                );
            }

            $component->diagnostics()->syncWithoutDetaching($diagnosticIds);
        }
    }
}
