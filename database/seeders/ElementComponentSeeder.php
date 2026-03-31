<?php

namespace Database\Seeders;

use App\Models\Component;
use App\Models\Element;
use Illuminate\Database\Seeder;

class ElementComponentSeeder extends Seeder
{
    public function run(): void
    {
        // Traer todos los elementos activos
        $elements = Element::where('status', true)->get();

        if ($elements->isEmpty()) {
            $this->command->warn('No hay elementos para relacionar.');
            return;
        }

        // Traer todos los componentes activos
        $components = Component::where('status', true)->get();

        if ($components->isEmpty()) {
            $this->command->warn('No hay componentes para relacionar.');
            return;
        }

        // Obtener el componente GENERAL (obligatorio)
        $generalComponent = $components->firstWhere('name', 'General');

        if (!$generalComponent) {
            $this->command->error('No existe el componente "General".');
            return;
        }

        foreach ($elements as $element) {

            // 🔹 Filtrar componentes del mismo cliente
            $clientComponents = $components
                ->where('client_id', $element->area->client_id)
                ->values();

            if ($clientComponents->isEmpty()) {
                continue;
            }

            // 🔹 Excluir "General" del random
            $randomPool = $clientComponents
                ->reject(fn($c) => $c->name === 'General')
                ->values();

            // 🔹 Cantidad aleatoria de componentes (ej: entre 4 y 10)
            $take = rand(4, min(10, $randomPool->count()));

            $randomComponents = $randomPool->shuffle()->take($take);

            // 🔹 Siempre incluir "General"
            $finalComponents = $randomComponents->push($generalComponent);

            // 🔹 Guardar sin borrar relaciones existentes
            $element->components()->syncWithoutDetaching(
                $finalComponents->pluck('id')->toArray()
            );
        }

        $this->command->info('Relación elementos-componentes generada correctamente.');
    }
}