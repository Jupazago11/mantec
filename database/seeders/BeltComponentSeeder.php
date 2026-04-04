<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Component;
use App\Models\ElementType;
use Illuminate\Database\Seeder;

class BeltComponentSeeder extends Seeder
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

        $components = [
            'Banda',
            'Chute de descarga',
            'Encausadores',
            'Guardilla',
            'Cama de impacto',
            'Rodillos de carga',
            'Rodillos de retorno',
            'Rodillos laterales',
            'Tambor de cola',
            'Tambor de inflexion',
            'Tambor de contra pesa',
            'Tambor snub',
            'Tambor motriz',
            'Motorreductor',
            'Material acumulado',
            'Condicion de seguridad',
            'Cubiertas',
            'Otros',
            'Limpiador primario',
            'Limpiador secundario',
            'Limpiador tipo arado',
            'Limpiador transversal',
        ];

        foreach ($components as $name) {
            Component::updateOrCreate(
                [
                    'client_id' => $client->id,
                    'element_type_id' => $elementType->id,
                    'name' => trim($name),
                ],
                [
                    'code' => null,
                    'is_required' => false,
                    'is_default' => false,
                    'status' => true,
                ]
            );
        }
    }
}
