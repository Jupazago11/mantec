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
        $clientName = 'CORONA';

        $client = Client::where('name', $clientName)->firstOrFail();

        $elementType = ElementType::where('client_id', $client->id)
            ->where('name', 'Banda transportadora')
            ->firstOrFail();

        $components = [
            'Banda',
            'Cama de impacto',
            'Chumaceras y rodamientos',
            'Sistema de descarga',
            'Motorreductor',
            'Guardilla',
            'Limpiador primario',
            'Limpiador secundario',
            'Limpiador tipo arado',
            'Limpiador transversal',
            'Rodillos de carga',
            'Rodillos de retorno',
            'Rodillos laterales',
            'Tambor cola',
            'Tambor contra pesa',
            'Tambor de inflexión',
            'Tambor motriz',
            'Tambor snub',
            'General',
        ];

        foreach ($components as $name) {
            $exists = Component::where('client_id', $client->id)
                ->where('element_type_id', $elementType->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->exists();

            if (!$exists) {
                Component::create([
                    'client_id' => $client->id,
                    'element_type_id' => $elementType->id,
                    'name' => $name,
                    'code' => null,
                    'is_required' => false,
                    'is_default' => false,
                    'status' => true,
                ]);
            }
        }
    }
}