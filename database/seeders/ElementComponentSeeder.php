<?php

namespace Database\Seeders;

use App\Models\Component;
use App\Models\Element;
use Illuminate\Database\Seeder;

class ElementComponentSeeder extends Seeder
{
    public function run(): void
    {
        $elements = Element::query()
            ->with('area:id,client_id')
            ->where('status', true)
            ->get();

        if ($elements->isEmpty()) {
            $this->command?->warn('No hay elementos para relacionar.');
            return;
        }

        $relatedCount = 0;

        foreach ($elements as $element) {
            $clientId = $element->area?->client_id;
            $elementTypeId = $element->element_type_id;

            if (!$clientId || !$elementTypeId) {
                $this->command?->warn("Activo {$element->id} omitido: no tiene cliente o tipo de activo válido.");
                continue;
            }

            $componentIds = Component::query()
                ->where('status', true)
                ->where('client_id', $clientId)
                ->where('element_type_id', $elementTypeId)
                ->pluck('id')
                ->values()
                ->all();

            $element->components()->sync($componentIds);
            $relatedCount++;
        }

        $this->command?->info("Relación elementos-componentes generada correctamente para {$relatedCount} activos.");
    }
}
