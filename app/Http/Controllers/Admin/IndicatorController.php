<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Element;
use App\Models\Group;
use App\Models\ReportDetail;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class IndicatorController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless(
            $this->canAccessIndicators($roleKey),
            403,
            'Rol no autorizado para consultar indicadores.'
        );

        $clients = $this->getScopedClients($user);
        $groups = $this->getScopedGroups($user, $clients->pluck('id')->all());
        $elementTypeOptions = $this->buildElementTypeOptions($groups);

        return view('admin.indicators.index', [
            'clients' => $clients,
            'groups' => $groups,
            'elementTypeOptions' => $elementTypeOptions,
            'roleKey' => $roleKey,
            'isReadOnly' => in_array($roleKey, ['observador', 'observador_cliente'], true),
            'defaultDateFrom' => now()->startOfYear()->toDateString(),
            'defaultDateTo' => now()->toDateString(),
            'dataRoute' => route('admin.indicators.data'),
            'semaphoreDataRoute' => route('admin.indicators.semaphore.data'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $user = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless(
            $this->canAccessIndicators($roleKey),
            403,
            'Rol no autorizado para consultar indicadores.'
        );

        $validated = $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'element_type_id' => ['nullable', 'integer', 'exists:element_types,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $dateFrom = Carbon::parse($validated['date_from'] ?? now()->startOfYear()->toDateString())->startOfDay();
        $dateTo = Carbon::parse($validated['date_to'] ?? now()->toDateString())->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha inicial no puede ser mayor que la fecha final.',
            ], 422);
        }

        $clients = $this->getScopedClients($user);
        $clientIds = $clients->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (!empty($validated['client_id'])) {
            $requestedClientId = (int) $validated['client_id'];

            if (!in_array($requestedClientId, $clientIds, true)) {
                abort(403, 'No tienes acceso a este cliente.');
            }

            $clientIds = [$requestedClientId];
        }

        $groups = $this->getScopedGroups($user, $clientIds);

        if (!empty($validated['group_id'])) {
            $requestedGroupId = (int) $validated['group_id'];

            if (!$groups->pluck('id')->map(fn ($id) => (int) $id)->contains($requestedGroupId)) {
                abort(403, 'No tienes acceso a esta agrupación.');
            }

            $groups = $groups->where('id', $requestedGroupId)->values();
        }

        $groupIds = $groups->pluck('id')->map(fn ($id) => (int) $id)->all();

        $elementsQuery = Element::query()
            ->with([
                'area:id,name',
                'elementType:id,name',
                'group:id,name,client_id',
            ])
            ->where('status', true)
            ->whereIn('group_id', $groupIds);

        if (!empty($validated['element_type_id'])) {
            $elementsQuery->where('element_type_id', (int) $validated['element_type_id']);
        }

        $elements = $elementsQuery->get([
            'id',
            'area_id',
            'element_type_id',
            'group_id',
            'name',
            'code',
            'status',
        ]);

        $elementIds = $elements->pluck('id')->map(fn ($id) => (int) $id)->all();
        $weekPairs = $this->buildWeekPairs($dateFrom, $dateTo);

        $details = collect();

        if (!empty($elementIds) && !empty($weekPairs)) {
            $details = ReportDetail::query()
                ->with([
                    'element:id,name,group_id,element_type_id,area_id',
                    'element.elementType:id,name',
                    'component:id,name',
                    'diagnostic:id,name',
                    'condition:id,code,name,description,severity',
                    'executionStatus:id,name',
                ])
                ->where('status', true)
                ->whereIn('element_id', $elementIds)
                ->where(function ($query) use ($weekPairs) {
                    foreach ($weekPairs as $pair) {
                        $query->orWhere(function ($subQuery) use ($pair) {
                            $subQuery
                                ->where('year', $pair['year'])
                                ->where('week', $pair['week']);
                        });
                    }
                })
                ->get();
        }

        $inspectedElementIds = $details->pluck('element_id')->unique()->values();

        $totalElements = $elements->count();
        $inspectedElements = $inspectedElementIds->count();
        $notInspectedElements = max($totalElements - $inspectedElements, 0);

        $coverage = $totalElements > 0
            ? round(($inspectedElements / $totalElements) * 100, 1)
            : 0;

        $preventiveReports = $this->countPreventiveReports($details);

        $uniqueElementTypeIds = $elements
            ->pluck('element_type_id')
            ->filter()
            ->unique()
            ->values();

        $selectedElementTypeId = !empty($validated['element_type_id'])
            ? (int) $validated['element_type_id']
            : null;

        $singleTypeMode = $selectedElementTypeId !== null || $uniqueElementTypeIds->count() === 1;

        $severityDistribution = $this->buildSeverityDistribution($details);
        $conditionDistribution = $this->buildConditionDistribution($details, $singleTypeMode);

        $reportsByWeek = $details
            ->groupBy(fn ($detail) => "{$detail->year}-" . str_pad((string) $detail->week, 2, '0', STR_PAD_LEFT))
            ->map(fn ($items, $key) => [
                'label' => 'S' . substr($key, -2) . ' / ' . substr($key, 0, 4),
                'total' => $this->countPreventiveReports($items),
            ])
            ->sortBy('label')
            ->values();

        $summaryByElementType = $this->buildSummaryByElementType($elements, $details);

        $topElements = $details
            ->groupBy('element_id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'name' => $first?->element?->name ?: 'Sin activo',
                    'type' => $first?->element?->elementType?->name ?: 'Sin tipo',
                    'total' => $items->count(),
                    'attention' => $this->countAttentionLike($items),
                ];
            })
            ->sortByDesc('total')
            ->take(10)
            ->values();

        $topComponents = $details
            ->groupBy('component_id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'name' => $first?->component?->name ?: 'Sin componente',
                    'total' => $items->count(),
                    'attention' => $this->countAttentionLike($items),
                ];
            })
            ->sortByDesc('total')
            ->take(10)
            ->values();

        $topDiagnostics = $details
            ->groupBy('diagnostic_id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'name' => $first?->diagnostic?->name ?: 'Sin diagnóstico',
                    'total' => $items->count(),
                    'attention' => $this->countAttentionLike($items),
                ];
            })
            ->sortByDesc('total')
            ->take(10)
            ->values();

        $topConditions = $details
            ->groupBy(function ($detail) {
                return ($detail->element?->element_type_id ?: 'none') . '-' . ($detail->condition_id ?: 'none');
            })
            ->map(function ($items) {
                $first = $items->first();
                $condition = $first?->condition;

                return [
                    'type' => $first?->element?->elementType?->name ?: 'Sin tipo',
                    'code' => $condition?->code ?: '—',
                    'name' => $condition?->name ?: 'Sin condición',
                    'severity' => $condition?->severity,
                    'severity_label' => $this->severityLabel($condition?->severity),
                    'total' => $items->count(),
                ];
            })
            ->sortByDesc('total')
            ->take(10)
            ->values();

        $pendingExecution = $details
            ->filter(function ($detail) {
                $statusName = mb_strtolower((string) ($detail->executionStatus?->name ?? ''));

                if ($detail->execution_date) {
                    return false;
                }

                if ($statusName === '') {
                    return true;
                }

                return !str_contains($statusName, 'ejecut');
            })
            ->count();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_elements' => $totalElements,
                'inspected_elements' => $inspectedElements,
                'not_inspected_elements' => $notInspectedElements,
                'coverage' => $coverage,
                'preventive_reports' => $preventiveReports,
                'evaluated_components' => $details->count(),
                'diagnostics' => $details->whereNotNull('diagnostic_id')->count(),
                'attention_findings' => $this->countAttentionLike($details),
                'pending_execution' => $pendingExecution,
            ],
            'charts' => [
                'mode' => $singleTypeMode ? 'condition' : 'severity',
                'severity_distribution' => $severityDistribution,
                'condition_distribution' => $conditionDistribution,
                'reports_by_week' => $reportsByWeek,
            ],
            'tables' => [
                'summary_by_element_type' => $summaryByElementType,
                'top_elements' => $topElements,
                'top_components' => $topComponents,
                'top_diagnostics' => $topDiagnostics,
                'top_conditions' => $topConditions,
            ],
            'meta' => [
                'client_ids' => $clientIds,
                'group_ids' => $groupIds,
                'element_type_id' => $selectedElementTypeId,
                'chart_mode' => $singleTypeMode ? 'condition' : 'severity',
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
        ]);
    }

    public function semaphoreData(Request $request): JsonResponse
{
    $user = auth()->user();
    $roleKey = $user->role?->key;

    abort_unless(
        $this->canAccessIndicators($roleKey),
        403,
        'Rol no autorizado para consultar el semáforo.'
    );

    $validated = $request->validate([
        'client_id' => ['nullable', 'integer', 'exists:clients,id'],
        'group_id' => ['nullable', 'integer', 'exists:groups,id'],
        'element_type_id' => ['required', 'integer', 'exists:element_types,id'],
        'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        'week' => ['required', 'integer', 'min:1', 'max:53'],
    ]);

    $clients = $this->getScopedClients($user);
    $clientIds = $clients->pluck('id')->map(fn ($id) => (int) $id)->all();

    if (!empty($validated['client_id'])) {
        $requestedClientId = (int) $validated['client_id'];

        if (!in_array($requestedClientId, $clientIds, true)) {
            abort(403, 'No tienes acceso a este cliente.');
        }

        $clientIds = [$requestedClientId];
    }

    $groups = $this->getScopedGroups($user, $clientIds);

    if (!empty($validated['group_id'])) {
        $requestedGroupId = (int) $validated['group_id'];

        if (!$groups->pluck('id')->map(fn ($id) => (int) $id)->contains($requestedGroupId)) {
            abort(403, 'No tienes acceso a esta agrupación.');
        }

        $groups = $groups->where('id', $requestedGroupId)->values();
    }

    $groupIds = $groups->pluck('id')->map(fn ($id) => (int) $id)->all();

    $elementTypeId = (int) $validated['element_type_id'];

    $elementTypeHasSemaphore = Element::query()
        ->whereIn('group_id', $groupIds)
        ->where('element_type_id', $elementTypeId)
        ->whereHas('elementType', fn ($query) => $query
            ->where('status', true)
            ->where('has_semaphore', true)
        )
        ->exists();

    if (!$elementTypeHasSemaphore) {
        return response()->json([
            'success' => false,
            'message' => 'El tipo de activo seleccionado no tiene semáforo habilitado.',
        ], 422);
    }

    $elements = Element::query()
        ->with([
            'area:id,name,client_id,status',
            'elementType:id,name,has_semaphore,status',
            'group:id,name,client_id',
        ])
        ->where('status', true)
        ->whereIn('group_id', $groupIds)
        ->where('element_type_id', $elementTypeId)
        ->whereHas('area', fn ($query) => $query->where('status', true))
        ->orderBy('name')
        ->get([
            'id',
            'area_id',
            'element_type_id',
            'group_id',
            'name',
            'code',
            'status',
        ]);

    $elementIds = $elements->pluck('id')->map(fn ($id) => (int) $id)->all();

    $details = collect();

    if (!empty($elementIds)) {
        $details = ReportDetail::query()
            ->with([
                'element:id,name,area_id,element_type_id,group_id',
                'component:id,name,code',
                'diagnostic:id,name',
                'condition:id,code,name,severity,color',
            ])
            ->where('status', true)
            ->whereIn('element_id', $elementIds)
            ->where('year', (int) $validated['year'])
            ->where('week', (int) $validated['week'])
            ->get();
    }

    $detailsByElement = $details->groupBy('element_id');

    $areas = $elements
        ->filter(fn ($element) => $element->area)
        ->groupBy(fn ($element) => $element->area->id)
        ->map(function ($areaElements) use ($detailsByElement) {
            $area = $areaElements->first()->area;

            return [
                'id' => $area->id,
                'name' => $area->name,
                'elements_count' => $areaElements->count(),
                'rows' => $areaElements
                    ->sortBy('name')
                    ->map(function (Element $element) use ($detailsByElement) {
                        $elementDetails = $detailsByElement->get($element->id, collect());

                        return [
                            'element_id' => $element->id,
                            'element_name' => $element->name,
                            'element_code' => $element->code,
                            'change_belt' => $this->buildSemaphoreChangeBelt($elementDetails),
                            'belt_status' => $this->buildSemaphoreBeltStatus($elementDetails),
                            'safety_condition' => $this->buildSemaphoreSafetyCondition($elementDetails),
                            'discharge' => $this->buildSemaphoreComponentColumn($elementDetails, ['descarga']),
                            'cleaner' => $this->buildSemaphoreComponentColumn($elementDetails, ['limpiador']),
                        ];
                    })
                    ->values(),
            ];
        })
        ->sortBy('name')
        ->values();

    return response()->json([
        'success' => true,
        'meta' => [
            'year' => (int) $validated['year'],
            'week' => (int) $validated['week'],
            'elements_count' => $elements->count(),
            'details_count' => $details->count(),
        ],
        'areas' => $areas,
    ]);
}

    private function canAccessIndicators(?string $roleKey): bool
    {
        return in_array($roleKey, [
            'superadmin',
            'admin_global',
            'admin',
            'admin_cliente',
            'observador',
            'observador_cliente',
        ], true);
    }

    private function getScopedClients($user): Collection
    {
        $roleKey = $user->role?->key;

        if (in_array($roleKey, ['superadmin', 'admin_global', 'observador'], true)) {
            return Client::query()
                ->where('status', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return $user->clients()
            ->where('clients.status', true)
            ->orderBy('clients.name')
            ->get(['clients.id', 'clients.name']);
    }

    private function getScopedGroups($user, array $clientIds): Collection
    {
        $roleKey = $user->role?->key;

        $query = Group::query()
            ->with(['client:id,name'])
            ->where('status', true)
            ->whereIn('client_id', $clientIds)
            ->orderBy('client_id')
            ->orderBy('name');

        if (in_array($roleKey, ['admin_cliente', 'observador_cliente'], true)) {
            $allowedGroupIds = $user->groups()
                ->pluck('groups.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $query->whereIn('id', $allowedGroupIds);
        }

        return $query->get(['id', 'client_id', 'name', 'description', 'status']);
    }

    private function buildElementTypeOptions(Collection $groups): Collection
    {
        $groupIds = $groups->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (empty($groupIds)) {
            return collect();
        }

        return Element::query()
            ->with([
                'group:id,client_id,name',
                'elementType:id,name,has_semaphore',
            ])
            ->where('status', true)
            ->whereIn('group_id', $groupIds)
            ->get(['id', 'group_id', 'element_type_id', 'status'])
            ->map(function (Element $element) {
                return [
                    'client_id' => $element->group?->client_id,
                    'group_id' => $element->group_id,
                    'element_type_id' => $element->element_type_id,
                    'element_type_name' => $element->elementType?->name,
                    'has_semaphore' => (bool) $element->elementType?->has_semaphore,
                ];
            })
            ->filter(fn ($item) => $item['client_id'] && $item['group_id'] && $item['element_type_id'])
            ->unique(fn ($item) => $item['client_id'] . '-' . $item['group_id'] . '-' . $item['element_type_id'])
            ->sortBy([
                ['client_id', 'asc'],
                ['group_id', 'asc'],
                ['element_type_name', 'asc'],
            ])
            ->values();
    }

    private function buildWeekPairs(Carbon $dateFrom, Carbon $dateTo): array
    {
        $period = CarbonPeriod::create(
            $dateFrom->copy()->startOfWeek(),
            '1 week',
            $dateTo->copy()->endOfWeek()
        );

        $weeks = [];

        foreach ($period as $date) {
            $weeks[] = [
                'year' => (int) $date->isoWeekYear(),
                'week' => (int) $date->isoWeek(),
            ];
        }

        return collect($weeks)
            ->unique(fn ($item) => $item['year'] . '-' . $item['week'])
            ->values()
            ->all();
    }

    private function countPreventiveReports(Collection $details): int
    {
        return $details
            ->map(function ($detail) {
                if (!empty($detail->report_id)) {
                    return 'report-' . $detail->report_id;
                }

                return 'fallback-' . $detail->element_id . '-' . $detail->year . '-' . $detail->week;
            })
            ->unique()
            ->count();
    }

    private function countAttentionLike(Collection $details): int
    {
        return $details->filter(function ($detail) {
            $severity = $detail->condition?->severity;

            if ($severity === null) {
                return false;
            }

            return (int) $severity > 0;
        })->count();
    }

    private function buildSeverityDistribution(Collection $details): Collection
    {
        return $details
            ->groupBy(fn ($detail) => $detail->condition?->severity ?? 'sin_criticidad')
            ->map(fn ($items, $severity) => [
                'label' => $this->severityLabel($severity),
                'severity' => is_numeric($severity) ? (int) $severity : null,
                'total' => $items->count(),
            ])
            ->sortBy(fn ($row) => $row['severity'] ?? 999)
            ->values();
    }

    private function buildConditionDistribution(Collection $details, bool $singleTypeMode): Collection
    {
        return $details
            ->groupBy(function ($detail) use ($singleTypeMode) {
                if ($singleTypeMode) {
                    return $detail->condition_id ?: 'none';
                }

                return ($detail->element?->element_type_id ?: 'none') . '-' . ($detail->condition_id ?: 'none');
            })
            ->map(function ($items) use ($singleTypeMode) {
                $first = $items->first();
                $condition = $first?->condition;
                $typeName = $first?->element?->elementType?->name ?: 'Sin tipo';

                $conditionLabel = trim(($condition?->code ? $condition->code . ' - ' : '') . ($condition?->name ?: 'Sin condición'));

                return [
                    'label' => $singleTypeMode
                        ? $conditionLabel
                        : $typeName . ' / ' . $conditionLabel,
                    'type' => $typeName,
                    'code' => $condition?->code ?: '—',
                    'condition' => $condition?->name ?: 'Sin condición',
                    'severity' => $condition?->severity,
                    'severity_label' => $this->severityLabel($condition?->severity),
                    'total' => $items->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    private function buildSummaryByElementType(Collection $elements, Collection $details): Collection
    {
        return $elements
            ->groupBy('element_type_id')
            ->map(function ($elementsByType, $elementTypeId) use ($details) {
                $firstElement = $elementsByType->first();
                $typeName = $firstElement?->elementType?->name ?: 'Sin tipo';

                $elementIds = $elementsByType->pluck('id')->map(fn ($id) => (int) $id)->all();

                $detailsForType = $details->filter(
                    fn ($detail) => in_array((int) $detail->element_id, $elementIds, true)
                );

                $inspectedElements = $detailsForType->pluck('element_id')->unique()->count();
                $totalElements = $elementsByType->count();

                return [
                    'element_type_id' => (int) $elementTypeId,
                    'name' => $typeName,
                    'elements' => $totalElements,
                    'inspected' => $inspectedElements,
                    'coverage' => $totalElements > 0 ? round(($inspectedElements / $totalElements) * 100, 1) : 0,
                    'findings' => $detailsForType->count(),
                    'attention' => $this->countAttentionLike($detailsForType),
                ];
            })
            ->sortByDesc('findings')
            ->values();
    }

    private function buildSemaphoreChangeBelt(Collection $details): array
{
    $hasChange = $details->contains(fn ($detail) => (bool) $detail->is_belt_change);

    return [
        'label' => $hasChange ? 'SI' : 'NO',
        'level' => $hasChange ? 'warning' : 'neutral',
        'detail' => $hasChange ? 'Tiene cambio de banda registrado.' : 'Sin cambio de banda.',
    ];
}

private function buildSemaphoreBeltStatus(Collection $details): array
{
    $condition = $details
        ->pluck('condition')
        ->filter()
        ->sortByDesc(fn ($condition) => (int) ($condition->severity ?? 0))
        ->first();

    if (!$condition) {
        return [
            'label' => 'N/A',
            'level' => 'neutral',
            'detail' => 'Sin condición registrada.',
        ];
    }

    return [
        'label' => $this->conditionDisplayLabel($condition),
        'level' => $this->semaphoreLevelFromSeverity($condition->severity),
        'detail' => $condition->description ?: $condition->name,
    ];
}

private function buildSemaphoreSafetyCondition(Collection $details): array
{
    $safetyDetails = $details->filter(function ($detail) {
        $code = $this->normalizeSemaphoreText($detail->condition?->code);
        $name = $this->normalizeSemaphoreText($detail->condition?->name);

        return str_starts_with($code, 'seg')
            || str_contains($code, 'seg')
            || str_starts_with($name, 'seg')
            || str_contains($name, 'seguridad');
    });

    if ($safetyDetails->isEmpty()) {
        return [
            'label' => 'N/A',
            'level' => 'neutral',
            'detail' => 'Sin condición de seguridad registrada.',
        ];
    }

    $condition = $safetyDetails
        ->pluck('condition')
        ->filter()
        ->sortByDesc(fn ($condition) => (int) ($condition->severity ?? 0))
        ->first();

    return [
        'label' => $this->conditionDisplayLabel($condition),
        'level' => $this->semaphoreLevelFromSeverity($condition?->severity),
        'detail' => $condition?->description ?: $condition?->name,
    ];
}

private function buildSemaphoreComponentColumn(Collection $details, array $needles): array
{
    $matchedDetails = $details->filter(function ($detail) use ($needles) {
        $componentName = $this->normalizeSemaphoreText($detail->component?->name);
        $componentCode = $this->normalizeSemaphoreText($detail->component?->code);

        foreach ($needles as $needle) {
            $needle = $this->normalizeSemaphoreText($needle);

            if (str_contains($componentName, $needle) || str_contains($componentCode, $needle)) {
                return true;
            }
        }

        return false;
    });

    if ($matchedDetails->isEmpty()) {
        return [
            'label' => 'N/A',
            'level' => 'neutral',
            'detail' => 'Sin registro para esta columna.',
        ];
    }

    $condition = $matchedDetails
        ->pluck('condition')
        ->filter()
        ->sortByDesc(fn ($condition) => (int) ($condition->severity ?? 0))
        ->first();

    if (!$condition) {
        return [
            'label' => 'OK',
            'level' => 'ok',
            'detail' => 'Registrado sin condición asociada.',
        ];
    }

    return [
        'label' => $this->conditionDisplayLabel($condition),
        'level' => $this->semaphoreLevelFromSeverity($condition->severity),
        'detail' => $condition->description ?: $condition->name,
    ];
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

private function semaphoreLevelFromSeverity($severity): string
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

private function normalizeSemaphoreText($value): string
{
    $value = mb_strtolower((string) $value);
    $value = trim($value);

    $replacements = [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u',
        'ñ' => 'n',
    ];

    return strtr($value, $replacements);
}

    private function severityLabel($severity): string
    {
        if ($severity === null || $severity === 'sin_criticidad') {
            return 'Sin criticidad';
        }

        return match ((int) $severity) {
            0 => 'Normal / OK',
            1 => 'Alta',
            2 => 'Media',
            3 => 'Baja',
            default => 'Criticidad ' . $severity,
        };
    }
}