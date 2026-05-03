<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Element;
use App\Models\Group;
use App\Models\ReportDetail;
use App\Models\SemaphoreBeltChange;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        $defaultScope = $this->resolveDefaultScope($user, $clients, $groups);

        return view('admin.indicators.index', [
            'clients' => $clients,
            'groups' => $groups,
            'elementTypeOptions' => $elementTypeOptions,
            'defaultScope' => $defaultScope,
            'roleKey' => $roleKey,
            'isReadOnly' => in_array($roleKey, ['observador', 'observador_cliente'], true),
            'canEditSemaphore' => $this->canEditSemaphore($roleKey),
            'defaultDateFrom' => now()->startOfYear()->toDateString(),
            'defaultDateTo' => now()->toDateString(),
            'dataRoute' => route('admin.indicators.data'),
            'semaphoreDataRoute' => route('admin.indicators.semaphore.data'),
            'semaphoreBeltChangeUpdateRoute' => route('admin.indicators.semaphore.belt-change.update'),
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
        $elementIdsForGroups = $this->elementIdsForGroups($groupIds);

        $elementsQuery = Element::query()
            ->with([
                'area:id,name',
                'elementType:id,name',
                'group:id,name,client_id',
            ])
            ->where('status', true)
            ->whereIn('id', $elementIdsForGroups);

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
                    'user:id,name',
                    'element:id,name,group_id,element_type_id,area_id',
                    'element.area:id,name',
                    'element.elementType:id,name',
                    'component:id,name',
                    'diagnostic:id,name',
                    'condition:id,code,name,description,severity,color',
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

        $weeklyAssetCoverage = $this->buildWeeklyAssetCoverage($elements, $details, $weekPairs);

        $summaryByElementType = $this->buildSummaryByElementType($elements, $details);
        $areaDistribution = $this->buildAreaDistribution($details);
        $inspectorDistribution = $this->buildInspectorDistribution($details);
        $attentionTrend = $this->buildAttentionTrend($details);

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
                    'color' => $condition?->color ?: $this->indicatorColorFromSeverity($condition?->severity),
                    'order' => $this->semaphoreOrderFromSeverity($condition?->severity),
                    'total' => $items->count(),
                ];
            })
            ->sortBy(fn ($row) => $row['order'])
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
                'weekly_asset_coverage' => $weeklyAssetCoverage,
                'summary_by_element_type' => $summaryByElementType,
                'top_elements' => $topElements,
                'top_components' => $topComponents,
                'top_diagnostics' => $topDiagnostics,
                'top_conditions' => $topConditions,
                'area_distribution' => $areaDistribution,
                'inspector_distribution' => $inspectorDistribution,
                'attention_trend' => $attentionTrend,
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
    $elementIdsForGroups = $this->elementIdsForGroups($groupIds);

    $elementTypeId = (int) $validated['element_type_id'];

    $elementTypeHasSemaphore = Element::query()
        ->whereIn('id', $elementIdsForGroups)
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
        ->whereIn('id', $elementIdsForGroups)
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
                'condition:id,code,name,description,severity,color',
            ])
            ->where('status', true)
            ->whereIn('element_id', $elementIds)
            ->where('year', (int) $validated['year'])
            ->where('week', (int) $validated['week'])
            ->get();
    }

    $detailsByElement = $details->groupBy('element_id');

    $beltChangeOverrides = empty($elementIds) || !Schema::hasTable('semaphore_belt_changes')
        ? collect()
        : SemaphoreBeltChange::query()
            ->whereIn('element_id', $elementIds)
            ->where('year', (int) $validated['year'])
            ->where('week', (int) $validated['week'])
            ->get()
            ->keyBy('element_id');

    $areas = $elements
        ->filter(fn ($element) => $element->area)
        ->groupBy(fn ($element) => $element->area->id)
        ->map(function ($areaElements) use ($detailsByElement, $beltChangeOverrides) {
            $area = $areaElements->first()->area;

            return [
                'id' => $area->id,
                'name' => $area->name,
                'elements_count' => $areaElements->count(),
                'rows' => $areaElements
                    ->sortBy('name')
                    ->map(function (Element $element) use ($detailsByElement, $beltChangeOverrides) {
                        $elementDetails = $detailsByElement->get($element->id, collect());
                        $beltChangeOverride = $beltChangeOverrides->get($element->id);

                        return [
                            'element_id' => $element->id,
                            'element_name' => $element->name,
                            'element_code' => $element->code,
                            'change_belt' => $this->buildSemaphoreChangeBelt($elementDetails, $beltChangeOverride),
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

    public function updateSemaphoreBeltChange(Request $request): JsonResponse
    {
        $user = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless(
            $this->canEditSemaphore($roleKey),
            403,
            'Rol no autorizado para editar el semÃ¡foro.'
        );

        $validated = $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'element_type_id' => ['required', 'integer', 'exists:element_types,id'],
            'element_id' => ['required', 'integer', 'exists:elements,id'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'week' => ['required', 'integer', 'min:1', 'max:53'],
            'is_belt_change' => ['required', 'boolean'],
        ]);

        if (!Schema::hasTable('semaphore_belt_changes')) {
            return response()->json([
                'success' => false,
                'message' => 'Falta ejecutar la migraciÃ³n de cambios de banda del semÃ¡foro.',
            ], 409);
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
                abort(403, 'No tienes acceso a esta agrupaciÃ³n.');
            }

            $groups = $groups->where('id', $requestedGroupId)->values();
        }

        $elementIdsForGroups = $this->elementIdsForGroups(
            $groups->pluck('id')->map(fn ($id) => (int) $id)->all()
        );

        $element = Element::query()
            ->with(['area:id,client_id,status', 'elementType:id,has_semaphore,status'])
            ->where('id', (int) $validated['element_id'])
            ->where('status', true)
            ->whereIn('id', $elementIdsForGroups)
            ->where('element_type_id', (int) $validated['element_type_id'])
            ->whereHas('area', fn ($query) => $query->where('status', true))
            ->whereHas('elementType', fn ($query) => $query
                ->where('status', true)
                ->where('has_semaphore', true)
            )
            ->firstOrFail();

        $override = SemaphoreBeltChange::updateOrCreate(
            [
                'element_id' => $element->id,
                'year' => (int) $validated['year'],
                'week' => (int) $validated['week'],
            ],
            [
                'is_belt_change' => (bool) $validated['is_belt_change'],
                'updated_by' => $user->id,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Cambio de banda actualizado correctamente.',
            'cell' => $this->buildSemaphoreChangeBelt(collect(), $override),
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

    private function canEditSemaphore(?string $roleKey): bool
    {
        return in_array($roleKey, [
            'superadmin',
            'admin_global',
            'admin',
            'admin_cliente',
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

    private function resolveDefaultScope($user, Collection $clients, Collection $groups): array
    {
        $clientIds = $clients->pluck('id')->map(fn ($id) => (int) $id)->all();
        $groupIds = $groups->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (empty($clientIds) || empty($groupIds)) {
            return [
                'client_id' => null,
                'group_id' => null,
                'element_type_id' => null,
            ];
        }

        $topGroup = $groups
            ->map(function ($group) {
                $elementIds = $this->elementIdsForGroups([(int) $group->id]);

                $reportsCount = empty($elementIds)
                    ? 0
                    : ReportDetail::query()
                        ->where('status', true)
                        ->whereIn('element_id', $elementIds)
                        ->count();

                return [
                    'client_id' => (int) $group->client_id,
                    'group_id' => (int) $group->id,
                    'reports_count' => $reportsCount,
                ];
            })
            ->sortByDesc('reports_count')
            ->first();

        if ($topGroup && $topGroup['reports_count'] > 0) {
            return [
                'client_id' => (int) $topGroup['client_id'],
                'group_id' => (int) $topGroup['group_id'],
                'element_type_id' => null,
            ];
        }

        $fallbackGroup = $groups->first();

        return [
            'client_id' => $fallbackGroup ? (int) $fallbackGroup->client_id : (int) $clients->first()->id,
            'group_id' => $fallbackGroup ? (int) $fallbackGroup->id : null,
            'element_type_id' => null,
        ];
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
            ->whereIn('id', $this->elementIdsForGroups($groupIds))
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

    private function elementIdsForGroups(array $groupIds): array
    {
        $groupIds = collect($groupIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($groupIds->isEmpty()) {
            return [];
        }

        $ids = collect();

        if (Schema::hasColumn('elements', 'group_id')) {
            $ids = $ids->merge(
                Element::query()
                    ->whereIn('group_id', $groupIds)
                    ->pluck('id')
            );
        }

        foreach ($groupIds as $groupId) {
            $ids = $ids->merge($this->resolveElementIdsFromGroupPivot((int) $groupId));
        }

        return $ids
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function resolveElementIdsFromGroupPivot(int $groupId): Collection
    {
        $pivotCandidates = [
            ['table' => 'group_elements', 'group_column' => 'group_id', 'element_column' => 'element_id'],
            ['table' => 'group_element', 'group_column' => 'group_id', 'element_column' => 'element_id'],
            ['table' => 'element_group', 'group_column' => 'group_id', 'element_column' => 'element_id'],
            ['table' => 'element_groups', 'group_column' => 'group_id', 'element_column' => 'element_id'],
            ['table' => 'group_assets', 'group_column' => 'group_id', 'element_column' => 'element_id'],
            ['table' => 'group_asset', 'group_column' => 'group_id', 'element_column' => 'element_id'],
            ['table' => 'agrupacion_activos', 'group_column' => 'group_id', 'element_column' => 'element_id'],
            ['table' => 'agrupacion_activo', 'group_column' => 'group_id', 'element_column' => 'element_id'],
        ];

        $ids = collect();

        foreach ($pivotCandidates as $candidate) {
            $table = $candidate['table'];
            $groupColumn = $candidate['group_column'];
            $elementColumn = $candidate['element_column'];

            if (
                Schema::hasTable($table) &&
                Schema::hasColumn($table, $groupColumn) &&
                Schema::hasColumn($table, $elementColumn)
            ) {
                $ids = $ids->merge(
                    DB::table($table)
                        ->where($groupColumn, $groupId)
                        ->whereNotNull($elementColumn)
                        ->pluck($elementColumn)
                );
            }
        }

        return $ids
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
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
                'color' => $this->indicatorColorFromSeverity($severity),
                'order' => $this->semaphoreOrderFromSeverity(is_numeric($severity) ? (int) $severity : null),
                'total' => $items->count(),
            ])
            ->sortBy(fn ($row) => $row['order'])
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
                    'color' => $condition?->color ?: $this->indicatorColorFromSeverity($condition?->severity),
                    'order' => $this->semaphoreOrderFromSeverity($condition?->severity),
                    'total' => $items->count(),
                ];
            })
            ->sortBy(fn ($row) => $row['order'])
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

    private function buildWeeklyAssetCoverage(Collection $elements, Collection $details, array $weekPairs): Collection
    {
        $totalElements = $elements->count();

        return collect($weekPairs)
            ->map(function ($pair) use ($details, $totalElements) {
                $weekDetails = $details
                    ->where('year', $pair['year'])
                    ->where('week', $pair['week']);

                $inspected = $weekDetails
                    ->pluck('element_id')
                    ->filter()
                    ->unique()
                    ->count();

                return [
                    'label' => 'S' . str_pad((string) $pair['week'], 2, '0', STR_PAD_LEFT) . ' / ' . $pair['year'],
                    'inspected' => $inspected,
                    'not_inspected' => max($totalElements - $inspected, 0),
                    'coverage' => $totalElements > 0 ? round(($inspected / $totalElements) * 100, 1) : 0,
                    'reports' => $this->countPreventiveReports($weekDetails),
                    'attention' => $this->countAttentionLike($weekDetails),
                ];
            })
            ->values();
    }

    private function buildAreaDistribution(Collection $details): Collection
    {
        return $details
            ->groupBy(fn ($detail) => $detail->element?->area_id ?: 'none')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'label' => $first?->element?->area?->name ?: 'Sin área',
                    'total' => $items->count(),
                    'attention' => $this->countAttentionLike($items),
                    'inspected' => $items->pluck('element_id')->unique()->count(),
                ];
            })
            ->sortByDesc('total')
            ->take(10)
            ->values();
    }

    private function buildInspectorDistribution(Collection $details): Collection
    {
        return $details
            ->groupBy(fn ($detail) => $detail->user_id ?: 'none')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'label' => $first?->user?->name ?: 'Sin inspector',
                    'total' => $this->countPreventiveReports($items),
                    'findings' => $items->count(),
                    'attention' => $this->countAttentionLike($items),
                ];
            })
            ->sortByDesc('findings')
            ->take(10)
            ->values();
    }

    private function buildAttentionTrend(Collection $details): Collection
    {
        return $details
            ->groupBy(fn ($detail) => "{$detail->year}-" . str_pad((string) $detail->week, 2, '0', STR_PAD_LEFT))
            ->map(function ($items, $key) {
                $attention = $this->countAttentionLike($items);

                return [
                    'label' => 'S' . substr($key, -2) . ' / ' . substr($key, 0, 4),
                    'attention' => $attention,
                    'normal' => max($items->count() - $attention, 0),
                ];
            })
            ->sortBy('label')
            ->values();
    }

    private function buildSemaphoreChangeBelt(Collection $details, ?SemaphoreBeltChange $override = null): array
{
    $latestInspectorBeltChange = $details
        ->filter(fn ($detail) => $detail->is_belt_change !== null)
        ->sortByDesc(fn ($detail) => optional($detail->updated_at ?? $detail->created_at)->timestamp ?? 0)
        ->first();

    $hasFreshInspectorReport = $override !== null
        && $latestInspectorBeltChange !== null
        && optional($latestInspectorBeltChange->updated_at ?? $latestInspectorBeltChange->created_at)->gt($override->updated_at);

    $hasOverride = $override !== null && !$hasFreshInspectorReport;
    $hasChange = $hasOverride
        ? (bool) $override->is_belt_change
        : ($latestInspectorBeltChange !== null
            ? (bool) $latestInspectorBeltChange->is_belt_change
            : $details->contains(fn ($detail) => (bool) $detail->is_belt_change));

    return [
        'label' => $hasChange ? 'SI' : 'NO',
        'level' => $hasChange ? 'warning' : 'neutral',
        'detail' => $hasFreshInspectorReport
            ? 'Valor tomado de un reporte preventivo posterior al ajuste manual.'
            : ($hasOverride
                ? 'Valor ajustado desde el semÃ¡foro.'
                : ($hasChange ? 'Tiene cambio de banda registrado.' : 'Sin cambio de banda.')),
        'value' => $hasChange,
        'has_override' => $hasOverride,
        'color' => $hasChange ? '#fb923c' : '#38bdf8',
        'order' => $hasChange ? 10 : 20,
    ];
}

private function buildSemaphoreBeltStatus(Collection $details): array
{
    $condition = $details
        ->pluck('condition')
        ->filter()
        ->sortBy(fn ($condition) => $this->semaphoreOrderFromSeverity($condition->severity ?? null))
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
        'color' => $condition->color,
        'severity' => $condition->severity,
        'order' => $this->semaphoreOrderFromSeverity($condition->severity),
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
        ->sortBy(fn ($condition) => $this->semaphoreOrderFromSeverity($condition->severity ?? null))
        ->first();

    return [
        'label' => $this->conditionDisplayLabel($condition),
        'level' => $this->semaphoreLevelFromSeverity($condition?->severity),
        'detail' => $condition?->description ?: $condition?->name,
        'color' => $condition?->color,
        'severity' => $condition?->severity,
        'order' => $this->semaphoreOrderFromSeverity($condition?->severity),
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
        ->sortBy(fn ($condition) => $this->semaphoreOrderFromSeverity($condition->severity ?? null))
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
        'color' => $condition->color,
        'severity' => $condition->severity,
        'order' => $this->semaphoreOrderFromSeverity($condition->severity),
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

    private function semaphoreOrderFromSeverity($severity): int
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
