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
            ->where('status', true)
            ->get();

        if ($elements->isEmpty()) {
            $this->command?->warn('No hay elementos para relacionar.');
            return;
        }

        $componentIds = Component::query()
            ->where('status', true)
            ->pluck('id')
            ->values()
            ->all();

        if (empty($componentIds)) {
            $this->command?->warn('No hay componentes para relacionar.');
            return;
        }

        $relatedCount = 0;

        foreach ($elements as $element) {
            $element->components()->sync($componentIds);
            $relatedCount++;
        }

        $this->command?->info("Relación elementos-componentes generada correctamente para {$relatedCount} activos.");
    }
}
