<?php

namespace App\Services\Semaphore;

class LegacySemaphoreDefinition
{
    public function columns(): array
    {
        return [
            ['key' => 'change_belt', 'label' => 'Cambio banda', 'type' => 'belt_change_manual', 'source_column_key' => 'belt_status'],
            ['key' => 'belt_status', 'label' => 'Estado banda', 'type' => 'condition_aggregate', 'source_column_key' => null],
            ['key' => 'safety_condition', 'label' => 'Seguridad', 'type' => 'condition_aggregate', 'source_column_key' => null],
            ['key' => 'discharge', 'label' => 'Descarga', 'type' => 'condition_aggregate', 'source_column_key' => null],
            ['key' => 'cleaner', 'label' => 'Limpiador', 'type' => 'condition_aggregate', 'source_column_key' => null],
        ];
    }

    public function dischargeComponentNames(): array
    {
        return ['Tolva de alimentación'];
    }

    public function cleanerComponentNames(): array
    {
        return [
            'Limpiador primario',
            'Limpiador secundario',
            'Limpiador transversal',
            'Limpiador en V',
        ];
    }

    public function safetyComponentNames(): array
    {
        return ['Guardas de seguridad', 'Cubiertas', 'Plataforma y estructura'];
    }

    public function beltStatusComponentNames(): array
    {
        return ['Banda'];
    }
}
