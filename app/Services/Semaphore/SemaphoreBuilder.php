<?php

namespace App\Services\Semaphore;

use App\Models\SemaphoreBeltChange;
use App\Models\SemaphoreTemplate;
use Illuminate\Support\Collection;

class SemaphoreBuilder
{
    public function __construct(
        private readonly LegacySemaphoreDefinition $legacyDefinition,
        private readonly SemaphoreColumnOrderer $columnOrderer,
    ) {
    }

    public function legacyColumns(): array
    {
        return $this->legacyDefinition->columns();
    }

    public function serializeTemplateColumns(SemaphoreTemplate $template): array
    {
        return $this->columnOrderer
            ->orderCollection($template->columns)
            ->where('status', true)
            ->values()
            ->map(fn ($column) => [
                'key' => $column->key,
                'label' => $column->label,
                'type' => $column->column_type,
                'source_column_key' => $column->source_column_key,
            ])
            ->all();
    }

    public function buildLegacyRowCells(Collection $details, ?SemaphoreBeltChange $override = null): array
    {
        $beltStatus = $this->buildBeltStatus($details);

        return [
            'change_belt' => $this->buildChangeBelt($details, $override, $beltStatus),
            'belt_status' => $beltStatus,
            'safety_condition' => $this->buildSafetyCondition($details),
            'discharge' => $this->buildComponentColumn($details, $this->legacyDefinition->dischargeComponentNames()),
            'cleaner' => $this->buildComponentColumn($details, $this->legacyDefinition->cleanerComponentNames()),
        ];
    }

    public function buildTemplateRowCells(Collection $details, ?SemaphoreBeltChange $override, SemaphoreTemplate $template): array
    {
        $columns = $this->columnOrderer
            ->orderCollection($template->columns)
            ->where('status', true)
            ->values();

        $columnsByKey = $columns->keyBy('key');
        $resolved = [];
        $building = [];

        $resolveColumn = function (string $key) use (&$resolveColumn, &$resolved, &$building, $columnsByKey, $details, $override) {
            if (array_key_exists($key, $resolved)) {
                return $resolved[$key];
            }

            if (isset($building[$key])) {
                return [
                    'label' => 'N/A',
                    'level' => 'neutral',
                    'detail' => 'La columna depende de otra columna en un ciclo no permitido.',
                    'severity' => null,
                    'order' => 900,
                ];
            }

            $column = $columnsByKey->get($key);

            if (!$column) {
                return [
                    'label' => 'N/A',
                    'level' => 'neutral',
                    'detail' => 'La columna configurada ya no existe en la plantilla activa.',
                    'severity' => null,
                    'order' => 900,
                ];
            }

            $building[$key] = true;

            if ($column->column_type === 'belt_change_manual') {
                $sourceCell = $column->source_column_key
                    ? $resolveColumn($column->source_column_key)
                    : $this->buildBeltStatus($details);

                $resolved[$key] = $this->buildChangeBelt($details, $override, $sourceCell);
            } else {
                $resolved[$key] = $this->buildConfiguredAggregateColumn($details, $column);
            }

            unset($building[$key]);

            return $resolved[$key];
        };

        foreach ($columns as $column) {
            $resolved[$column->key] = $resolveColumn($column->key);
        }

        return $resolved;
    }

    private function buildConfiguredAggregateColumn(Collection $details, $column): array
    {
        $breakdown = $column->rules
            ->map(function ($rule) use ($details) {
                $componentLabel = trim((string) ($rule->component?->name ?? 'Componente sin configurar'));
                $diagnosticLabel = trim((string) ($rule->diagnostic?->name ?? 'Diagnostico sin configurar'));
                $ruleLabel = $diagnosticLabel !== ''
                    ? "{$componentLabel} · {$diagnosticLabel}"
                    : $componentLabel;

                $detail = $details
                    ->filter(function ($detail) use ($rule) {
                        return (int) ($detail->component_id ?? 0) === (int) ($rule->component_id ?? 0)
                            && (int) ($detail->diagnostic_id ?? 0) === (int) ($rule->diagnostic_id ?? 0);
                    })
                    ->sortByDesc(fn ($detail) => optional($detail->updated_at ?? $detail->created_at)->timestamp ?? 0)
                    ->first();

                if (!$detail) {
                    return [
                        'component' => $ruleLabel,
                        'evaluated' => false,
                        'condition_name' => null,
                        'condition_description' => null,
                        'severity' => null,
                        'detail' => 'No evaluado en la semana seleccionada.',
                    ];
                }

                $condition = $detail->condition;
                $severity = $condition && is_numeric($condition->severity)
                    ? (int) $condition->severity
                    : 0;

                return [
                    'component' => $ruleLabel,
                    'evaluated' => true,
                    'condition_name' => $condition ? $this->conditionDisplayLabel($condition) : 'Sin condicion',
                    'condition_description' => $condition?->description ?: ($condition?->name ?: 'Evaluado sin descripcion registrada.'),
                    'color' => $this->resolveConditionColor($condition, $severity),
                    'severity' => $severity,
                    'detail' => $condition
                        ? ($condition->description ?: $condition->name)
                        : 'Evaluado sin condicion asociada.',
                ];
            })
            ->values();

        $evaluated = $breakdown->where('evaluated', true)->values();

        if ($evaluated->isEmpty()) {
            return [
                'label' => 'N/A',
                'level' => 'neutral',
                'detail' => 'Sin evaluacion registrada para las reglas configuradas.',
                'breakdown' => $breakdown->all(),
                'missing_components' => $breakdown->where('evaluated', false)->pluck('component')->values()->all(),
                'severity' => null,
                'order' => 900,
            ];
        }

        $critical = $evaluated
            ->filter(fn ($item) => (int) ($item['severity'] ?? 0) > 0)
            ->values();

        if ($critical->isEmpty()) {
            $selected = $evaluated->first();

            return [
                'label' => $selected['condition_name'] ?: 'N/A',
                'level' => 'neutral',
                'detail' => $selected['condition_description']
                    ?: ($selected['detail'] ?: 'Reglas evaluadas sin criticidad relevante.'),
                'breakdown' => $breakdown->all(),
                'missing_components' => $breakdown->where('evaluated', false)->pluck('component')->values()->all(),
                'color' => $selected['color'] ?? $this->indicatorColorFromSeverity(0),
                'severity' => 0,
                'order' => 40,
            ];
        }

        $selected = $column->severity_direction === 'desc'
            ? $critical->sortByDesc('severity')->first()
            : $critical->sortBy('severity')->first();

        return [
            'label' => $selected['condition_name'] ?: 'N/A',
            'level' => $this->levelFromConfiguredSeverity($selected['severity'], $column->severity_direction),
            'detail' => $selected['condition_description'] ?: $selected['detail'],
            'breakdown' => $breakdown->all(),
            'missing_components' => $breakdown->where('evaluated', false)->pluck('component')->values()->all(),
            'color' => $selected['color'] ?? $this->resolveConditionColor(null, $selected['severity']),
            'severity' => $selected['severity'],
            'order' => $this->orderFromConfiguredSeverity($selected['severity'], $column->severity_direction),
        ];
    }

    private function buildChangeBelt(Collection $details, ?SemaphoreBeltChange $override = null, ?array $beltStatus = null): array
    {
        $beltEstadoDetails = $details
            ->filter(function ($detail) {
                return $this->normalizeText($detail->component?->name) === 'banda'
                    && $this->normalizeText($detail->diagnostic?->name) === 'estado';
            })
            ->values();

        $latestInspectorBeltChange = $beltEstadoDetails
            ->filter(fn ($detail) => $detail->is_belt_change !== null)
            ->sortByDesc(fn ($detail) => optional($detail->updated_at ?? $detail->created_at)->timestamp ?? 0)
            ->first();

        $hasFreshInspectorReport = $override !== null
            && $latestInspectorBeltChange !== null
            && optional($latestInspectorBeltChange->updated_at ?? $latestInspectorBeltChange->created_at)->gt($override->updated_at);

        $hasOverride = $override !== null && !$hasFreshInspectorReport;

        if ($hasOverride) {
            $hasChange = (bool) $override->is_belt_change;
            $visual = $this->resolveBeltChangeVisual($hasChange, $beltStatus);

            return [
                'label' => $hasChange ? 'SI' : 'NO',
                'level' => $visual['level'],
                'detail' => 'Valor ajustado manualmente para el componente Banda con diagnóstico Estado.',
                'value' => $hasChange,
                'has_override' => true,
                'color' => $visual['color'],
                'order' => $visual['order'],
            ];
        }

        if ($latestInspectorBeltChange === null) {
            return [
                'label' => 'N/A',
                'level' => 'neutral',
                'detail' => 'Sin registro del componente Banda con diagnóstico Estado.',
                'value' => null,
                'has_override' => false,
                'color' => '#94a3b8',
                'order' => 30,
            ];
        }

        $hasChange = (bool) $latestInspectorBeltChange->is_belt_change;
        $visual = $this->resolveBeltChangeVisual($hasChange, $beltStatus);

        return [
            'label' => $hasChange ? 'SI' : 'NO',
            'level' => $visual['level'],
            'detail' => 'Valor tomado del componente Banda con diagnóstico Estado.',
            'value' => $hasChange,
            'has_override' => false,
            'color' => $visual['color'],
            'order' => $visual['order'],
        ];
    }

    private function resolveBeltChangeVisual(bool $hasChange, ?array $beltStatus = null): array
    {
        if (!$hasChange) {
            return [
                'level' => 'neutral',
                'color' => '#e2e8f0',
                'order' => 20,
            ];
        }

        $beltLevel = $beltStatus['level'] ?? null;

        if ($beltLevel === 'high') {
            return [
                'level' => 'high',
                'color' => '#fca5a5',
                'order' => 10,
            ];
        }

        if ($beltLevel === 'medium') {
            return [
                'level' => 'medium',
                'color' => '#fde68a',
                'order' => 15,
            ];
        }

        return [
            'level' => 'warning',
            'color' => '#fdba74',
            'order' => 18,
        ];
    }

    private function buildBeltStatus(Collection $details): array
    {
        return $this->buildAggregatedStateColumn(
            $details,
            $this->legacyDefinition->beltStatusComponentNames(),
            [
                'missing_detail' => 'Sin evaluación del componente Banda con diagnóstico Estado.',
                'normal_label' => 'N/A',
                'normal_detail' => 'Banda evaluada sin criticidad.',
            ]
        );
    }

    private function buildSafetyCondition(Collection $details): array
    {
        return $this->buildAggregatedStateColumn(
            $details,
            $this->legacyDefinition->safetyComponentNames(),
            [
                'missing_detail' => 'Sin evaluación de componentes de seguridad con diagnóstico Estado.',
                'normal_label' => 'N/A',
                'normal_detail' => 'Componentes de seguridad evaluados sin criticidad.',
            ]
        );
    }

    private function buildComponentColumn(Collection $details, array $componentNames): array
    {
        return $this->buildAggregatedStateColumn(
            $details,
            $componentNames,
            [
                'missing_detail' => 'Sin evaluación de componentes asociados con diagnóstico Estado.',
                'normal_label' => 'N/A',
                'normal_detail' => 'Componentes evaluados sin criticidad.',
            ]
        );
    }

    private function buildAggregatedStateColumn(Collection $details, array $componentNames, array $options = []): array
    {
        $normalizedTargets = collect($componentNames)
            ->map(fn ($name) => $this->normalizeText($name))
            ->filter()
            ->values();

        $breakdown = $normalizedTargets->map(function ($normalizedName, $index) use ($details, $componentNames) {
            $componentLabel = $componentNames[$index] ?? $normalizedName;

            $detail = $details
                ->filter(function ($detail) use ($normalizedName) {
                    return $this->normalizeText($detail->component?->name) === $normalizedName
                        && $this->normalizeText($detail->diagnostic?->name) === 'estado';
                })
                ->sortByDesc(fn ($detail) => optional($detail->updated_at ?? $detail->created_at)->timestamp ?? 0)
                ->first();

            if (!$detail) {
                return [
                    'component' => $componentLabel,
                    'evaluated' => false,
                    'condition_name' => null,
                    'condition_description' => null,
                    'severity' => null,
                    'detail' => 'No evaluado en la semana seleccionada.',
                ];
            }

            $condition = $detail->condition;
            $severity = $condition && is_numeric($condition->severity)
                ? (int) $condition->severity
                : 0;

            return [
                'component' => $componentLabel,
                'evaluated' => true,
                'condition_name' => $condition ? $this->conditionDisplayLabel($condition) : 'Sin condición',
                'condition_description' => $condition?->description ?: ($condition?->name ?: 'Evaluado sin descripción registrada.'),
                'color' => $this->resolveConditionColor($condition, $severity),
                'severity' => $severity,
                'detail' => $condition
                    ? ($condition->description ?: $condition->name)
                    : 'Evaluado sin condición asociada.',
            ];
        })->values();

        $evaluated = $breakdown->where('evaluated', true)->values();

        if ($evaluated->isEmpty()) {
            return [
                'label' => 'N/A',
                'level' => 'neutral',
                'detail' => $options['missing_detail'] ?? 'Sin evaluación registrada.',
                'breakdown' => $breakdown->all(),
                'missing_components' => $breakdown->where('evaluated', false)->pluck('component')->values()->all(),
                'severity' => null,
                'order' => 30,
            ];
        }

        $critical = $evaluated
            ->filter(fn ($item) => (int) ($item['severity'] ?? 0) > 0)
            ->sortBy('severity')
            ->values();

        if ($critical->isEmpty()) {
            $selected = $evaluated->first();

            return [
                'label' => $selected['condition_name'] ?: ($options['normal_label'] ?? 'N/A'),
                'level' => 'neutral',
                'detail' => $selected['condition_description']
                    ?: ($selected['detail'] ?: ($options['normal_detail'] ?? 'Evaluado sin criticidad.')),
                'breakdown' => $breakdown->all(),
                'missing_components' => $breakdown->where('evaluated', false)->pluck('component')->values()->all(),
                'color' => $selected['color'] ?? $this->indicatorColorFromSeverity(0),
                'severity' => 0,
                'order' => 40,
            ];
        }

        $selected = $critical->first();

        return [
            'label' => $selected['condition_name'] ?: 'N/A',
            'level' => $this->levelFromSeverity($selected['severity']),
            'detail' => $selected['condition_description'] ?: $selected['detail'],
            'breakdown' => $breakdown->all(),
            'missing_components' => $breakdown->where('evaluated', false)->pluck('component')->values()->all(),
            'color' => $selected['color'] ?? $this->resolveConditionColor(null, $selected['severity']),
            'severity' => $selected['severity'],
            'order' => $this->orderFromSeverity($selected['severity']),
        ];
    }

    private function resolveConditionColor($condition, $severity): string
    {
        $color = strtoupper(trim((string) ($condition?->color ?? '')));

        if (preg_match('/^#[0-9A-F]{6}$/', $color) === 1) {
            return $color;
        }

        return $this->indicatorColorFromSeverity($severity);
    }

    private function conditionDisplayLabel($condition): string
    {
        if (!$condition) {
            return 'N/A';
        }

        $code = trim((string) ($condition->code ?? ''));
        $name = trim((string) ($condition->name ?? ''));

        if ($code !== '' && $name !== '') {
            return "{$code} - {$name}";
        }

        if ($code !== '') {
            return $code;
        }

        return $name !== '' ? $name : 'N/A';
    }

    private function levelFromSeverity($severity): string
    {
        if ($severity === null) {
            return 'neutral';
        }

        return match ((int) $severity) {
            0 => 'ok',
            1 => 'high',
            2 => 'medium',
            3 => 'low',
            default => 'neutral',
        };
    }

    private function levelFromConfiguredSeverity($severity, ?string $direction): string
    {
        if ($direction === 'desc') {
            if ($severity === null) {
                return 'neutral';
            }

            return match ((int) $severity) {
                0 => 'ok',
                3 => 'high',
                2 => 'medium',
                1 => 'low',
                default => 'neutral',
            };
        }

        return $this->levelFromSeverity($severity);
    }

    private function orderFromSeverity($severity): int
    {
        if ($severity === null) {
            return 900;
        }

        return match ((int) $severity) {
            1 => 10,
            2 => 20,
            3 => 30,
            0 => 40,
            default => 100 + (int) $severity,
        };
    }

    private function orderFromConfiguredSeverity($severity, ?string $direction): int
    {
        if ($direction === 'desc') {
            if ($severity === null) {
                return 900;
            }

            return match ((int) $severity) {
                3 => 10,
                2 => 20,
                1 => 30,
                0 => 40,
                default => 100 + max(0, 99 - (int) $severity),
            };
        }

        return $this->orderFromSeverity($severity);
    }

    private function indicatorColorFromSeverity($severity): string
    {
        if ($severity === null || $severity === 'sin_criticidad') {
            return '#8b5cf6';
        }

        return match ((int) $severity) {
            0 => '#34d399',
            1 => '#f87171',
            2 => '#fbbf24',
            3 => '#60a5fa',
            default => '#8b5cf6',
        };
    }

    private function normalizeText($value): string
    {
        $value = mb_strtolower((string) $value);
        $value = trim($value);

        return strtr($value, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ]);
    }
}
