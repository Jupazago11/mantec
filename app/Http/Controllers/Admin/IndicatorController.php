<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Element;
use App\Models\Group;
use App\Models\ReportDetail;
use App\Models\SemaphoreBeltChange;
use App\Models\SemaphoreTemplate;
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
        $defaultDateFrom = $this->resolveDefaultDateFrom($groups, $defaultScope);

        return view('admin.indicators.index', [
            'clients' => $clients,
            'groups' => $groups,
            'elementTypeOptions' => $elementTypeOptions,
            'defaultScope' => $defaultScope,
            'roleKey' => $roleKey,
            'isReadOnly' => in_array($roleKey, ['observador', 'observador_cliente'], true),
            'canEditSemaphore' => $this->canEditSemaphore($roleKey),
            'defaultDateFrom' => $defaultDateFrom,
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
        $areaDistribution = $this->buildAreaDistribution($elements, $details);

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
                $total = $items->count();
                $attention = $this->countAttentionLike($items);

                return [
                    'name' => $first?->component?->name ?: 'Sin componente',
                    'total' => $total,
                    'attention' => $attention,
                    'attention_rate' => $total > 0 ? round(($attention / $total) * 100, 1) : 0,
                ];
            })
            ->sortByDesc(function ($row) {
                return sprintf('%012.4f-%012d', $row['attention_rate'], $row['total']);
            })
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
                $name = $condition?->name ?: 'Sin condición';
                $description = trim((string) ($condition?->description ?: ''));

                return [
                    'type' => $first?->element?->elementType?->name ?: 'Sin tipo',
                    'code' => $condition?->code ?: '—',
                    'name' => $name,
                    'description' => $description,
                    'label' => $description !== ''
                        ? $name . ' · ' . $description
                        : $name,
                    'severity' => $condition?->severity,
                    'severity_label' => $this->severityLabel($condition?->severity),
                    'color' => $condition?->color ?: $this->indicatorColorFromSeverity($condition?->severity),
                    'order' => $this->semaphoreOrderFromSeverity($condition?->severity),
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
                'weekly_asset_coverage' => $weeklyAssetCoverage,
                'summary_by_element_type' => $summaryByElementType,
                'top_elements' => $topElements,
                'top_components' => $topComponents,
                'top_diagnostics' => $topDiagnostics,
                'top_conditions' => $topConditions,
                'area_distribution' => $areaDistribution,
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

    $activeTemplate = $this->resolveSemaphoreTemplate(
        !empty($validated['client_id']) ? (int) $validated['client_id'] : null,
        !empty($validated['group_id']) ? (int) $validated['group_id'] : null,
        $elementTypeId,
        $elements
    );

    $semaphoreColumns = $activeTemplate
        ? $this->serializeSemaphoreTemplateColumns($activeTemplate)
        : $this->legacySemaphoreColumns();

    $areas = $elements
        ->filter(fn ($element) => $element->area)
        ->groupBy(fn ($element) => $element->area->id)
        ->map(function ($areaElements) use ($detailsByElement, $beltChangeOverrides, $activeTemplate) {
            $area = $areaElements->first()->area;

            return [
                'id' => $area->id,
                'name' => $area->name,
                'elements_count' => $areaElements->count(),
                'rows' => $areaElements
                    ->sortBy('name')
                    ->map(function (Element $element) use ($detailsByElement, $beltChangeOverrides, $activeTemplate) {
                        $elementDetails = $detailsByElement->get($element->id, collect());
                        $beltChangeOverride = $beltChangeOverrides->get($element->id);
                        $cells = $activeTemplate
                            ? $this->buildSemaphoreTemplateRowCells($elementDetails, $beltChangeOverride, $activeTemplate)
                            : $this->buildLegacySemaphoreRowCells($elementDetails, $beltChangeOverride);

                        return array_merge([
                            'element_id' => $element->id,
                            'element_name' => $element->name,
                            'element_code' => $element->code,
                            'cells' => $cells,
                        ], $cells);
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
            'template_id' => $activeTemplate?->id,
            'template_name' => $activeTemplate?->name ?: 'Modelo legado',
            'template_source' => $activeTemplate ? 'template' : 'legacy',
        ],
        'columns' => $semaphoreColumns,
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

    private function resolveDefaultDateFrom(Collection $groups, array $defaultScope): string
    {
        $currentYear = (int) now()->isoWeekYear();

        $groupIds = collect();

        if (!empty($defaultScope['group_id'])) {
            $groupIds->push((int) $defaultScope['group_id']);
        }

        $groupIds = $groupIds
            ->merge($groups->pluck('id')->map(fn ($id) => (int) $id))
            ->filter()
            ->unique()
            ->values();

        foreach ($groupIds as $groupId) {
            $elementIds = $this->elementIdsForGroups([(int) $groupId]);

            if (empty($elementIds)) {
                continue;
            }

            $firstReport = ReportDetail::query()
                ->where('status', true)
                ->whereIn('element_id', $elementIds)
                ->where('year', $currentYear)
                ->orderBy('week')
                ->first(['year', 'week']);

            if ($firstReport) {
                return Carbon::now()
                    ->setISODate((int) $firstReport->year, (int) $firstReport->week)
                    ->startOfWeek()
                    ->toDateString();
            }
        }

        return now()->startOfYear()->toDateString();
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

    private function buildAreaDistribution(Collection $elements, Collection $details): Collection
    {
        $detailsByArea = $details->groupBy(fn ($detail) => $detail->element?->area_id ?: 'none');

        return $elements
            ->groupBy(fn ($element) => $element->area_id ?: 'none')
            ->map(function ($areaElements, $areaId) use ($detailsByArea) {
                $firstElement = $areaElements->first();
                $items = $detailsByArea->get($areaId, collect());
                $total = $items->count();
                $attention = $this->countAttentionLike($items);

                return [
                    'label' => $firstElement?->area?->name ?: 'Sin área',
                    'total' => $total,
                    'attention' => $attention,
                    'inspected' => $items->pluck('element_id')->unique()->count(),
                    'elements' => $areaElements->count(),
                    'attention_rate' => $total > 0 ? round(($attention / $total) * 100, 1) : 0,
                ];
            })
            ->sortByDesc(function ($row) {
                return sprintf('%012.4f-%012d', $row['attention_rate'], $row['total']);
            })
            ->values();
    }

private function resolveSemaphoreTemplate(?int $clientId, ?int $groupId, int $elementTypeId, Collection $elements): ?SemaphoreTemplate
{
    $resolvedClientId = $clientId;

    if (!$resolvedClientId) {
        $clientIds = $elements
            ->pluck('area.client_id')
            ->filter()
            ->unique()
            ->values();

        if ($clientIds->count() !== 1) {
            return null;
        }

        $resolvedClientId = (int) $clientIds->first();
    }

    $candidateGroupIds = collect();

    if ($groupId) {
        $candidateGroupIds->push((int) $groupId);
    } else {
        $uniqueGroupIds = $elements
            ->pluck('group_id')
            ->filter()
            ->unique()
            ->values();

        if ($uniqueGroupIds->count() === 1) {
            $candidateGroupIds->push((int) $uniqueGroupIds->first());
        }
    }

    $candidateGroupIds->push(null);

    foreach ($candidateGroupIds->uniqueStrict() as $candidateGroupId) {
        $template = SemaphoreTemplate::query()
            ->with([
                'columns' => fn ($query) => $query
                    ->where('status', true)
                    ->with(['rules.component:id,code,name', 'rules.diagnostic:id,name']),
            ])
            ->where('status', true)
            ->where('client_id', $resolvedClientId)
            ->where('element_type_id', $elementTypeId)
            ->when(
                $candidateGroupId !== null,
                fn ($query) => $query->where('group_id', $candidateGroupId),
                fn ($query) => $query->whereNull('group_id')
            )
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if ($template) {
            return $template;
        }
    }

    return null;
}

private function serializeSemaphoreTemplateColumns(SemaphoreTemplate $template): array
{
    return $this->orderSemaphoreTemplateColumns($template->columns)
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

private function orderSemaphoreTemplateColumns(Collection $columns): Collection
{
    $ordered = $columns->values();

    foreach ($ordered->values() as $column) {
        if (($column->column_type ?? null) !== 'belt_change_manual') {
            continue;
        }

        $sourceKey = trim((string) ($column->source_column_key ?? ''));

        if ($sourceKey === '') {
            continue;
        }

        $currentIndex = $ordered->search(fn ($item) => $item->id === $column->id);
        $sourceIndex = $ordered->search(fn ($item) => $item->key === $sourceKey);

        if ($currentIndex === false || $sourceIndex === false || $currentIndex < $sourceIndex) {
            continue;
        }

        $item = $ordered->pull($currentIndex);
        $sourceIndex = $ordered->search(fn ($entry) => $entry->key === $sourceKey);

        if ($sourceIndex === false) {
            $ordered->push($item);
            continue;
        }

        $ordered = $ordered->splice(0, $sourceIndex)
            ->push($item)
            ->merge($ordered);
    }

    return $ordered->values();
}

private function legacySemaphoreColumns(): array
{
    return [
        ['key' => 'change_belt', 'label' => 'Cambio banda', 'type' => 'belt_change_manual', 'source_column_key' => 'belt_status'],
        ['key' => 'belt_status', 'label' => 'Estado banda', 'type' => 'condition_aggregate', 'source_column_key' => null],
        ['key' => 'safety_condition', 'label' => 'Seguridad', 'type' => 'condition_aggregate', 'source_column_key' => null],
        ['key' => 'discharge', 'label' => 'Descarga', 'type' => 'condition_aggregate', 'source_column_key' => null],
        ['key' => 'cleaner', 'label' => 'Limpiador', 'type' => 'condition_aggregate', 'source_column_key' => null],
    ];
}

private function buildLegacySemaphoreRowCells(Collection $details, ?SemaphoreBeltChange $override = null): array
{
    $beltStatus = $this->buildSemaphoreBeltStatus($details);

    return [
        'change_belt' => $this->buildSemaphoreChangeBelt($details, $override, $beltStatus),
        'belt_status' => $beltStatus,
        'safety_condition' => $this->buildSemaphoreSafetyCondition($details),
        'discharge' => $this->buildSemaphoreComponentColumn($details, ['Tolva de alimentación']),
        'cleaner' => $this->buildSemaphoreComponentColumn($details, [
            'Limpiador primario',
            'Limpiador secundario',
            'Limpiador transversal',
            'Limpiador en V',
        ]),
    ];
}

private function buildSemaphoreTemplateRowCells(Collection $details, ?SemaphoreBeltChange $override, SemaphoreTemplate $template): array
{
    $columns = $this->orderSemaphoreTemplateColumns($template->columns)
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
                : $this->buildSemaphoreBeltStatus($details);

            $resolved[$key] = $this->buildSemaphoreChangeBelt($details, $override, $sourceCell);
        } else {
            $resolved[$key] = $this->buildSemaphoreConfiguredAggregateColumn($details, $column);
        }

        unset($building[$key]);

        return $resolved[$key];
    };

    foreach ($columns as $column) {
        $resolved[$column->key] = $resolveColumn($column->key);
    }

    return $resolved;
}

private function buildSemaphoreConfiguredAggregateColumn(Collection $details, $column): array
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
                'color' => $this->resolveSemaphoreConditionColor($condition, $severity),
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
                ?: ($selected['detail']
                    ?: 'Reglas evaluadas sin criticidad relevante.'),
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
        'level' => $this->semaphoreLevelFromConfiguredSeverity($selected['severity'], $column->severity_direction),
        'detail' => $selected['condition_description'] ?: $selected['detail'],
        'breakdown' => $breakdown->all(),
        'missing_components' => $breakdown->where('evaluated', false)->pluck('component')->values()->all(),
        'color' => $selected['color'] ?? $this->resolveSemaphoreConditionColor(null, $selected['severity']),
        'severity' => $selected['severity'],
        'order' => $this->semaphoreOrderFromConfiguredSeverity($selected['severity'], $column->severity_direction),
    ];
}

private function buildSemaphoreChangeBelt(Collection $details, ?SemaphoreBeltChange $override = null, ?array $beltStatus = null): array
{
    $beltEstadoDetails = $details
        ->filter(function ($detail) {
            return $this->normalizeSemaphoreText($detail->component?->name) === 'banda'
                && $this->normalizeSemaphoreText($detail->diagnostic?->name) === 'estado';
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
        $visual = $this->resolveSemaphoreBeltChangeVisual($hasChange, $beltStatus);

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
    $visual = $this->resolveSemaphoreBeltChangeVisual($hasChange, $beltStatus);

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

private function resolveSemaphoreBeltChangeVisual(bool $hasChange, ?array $beltStatus = null): array
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

private function buildSemaphoreBeltStatus(Collection $details): array
{
    return $this->buildSemaphoreAggregatedStateColumn(
        $details,
        ['Banda'],
        [
            'missing_detail' => 'Sin evaluación del componente Banda con diagnóstico Estado.',
            'normal_label' => 'N/A',
            'normal_detail' => 'Banda evaluada sin criticidad.',
            'include_breakdown' => false,
        ]
    );
}

private function buildSemaphoreSafetyCondition(Collection $details): array
{
    return $this->buildSemaphoreAggregatedStateColumn(
        $details,
        ['Guardas de seguridad', 'Cubiertas', 'Plataforma y estructura'],
        [
            'missing_detail' => 'Sin evaluación de componentes de seguridad con diagnóstico Estado.',
            'normal_label' => 'N/A',
            'normal_detail' => 'Componentes de seguridad evaluados sin criticidad.',
            'include_breakdown' => true,
        ]
    );
}

private function buildSemaphoreComponentColumn(Collection $details, array $needles): array
{
    return $this->buildSemaphoreAggregatedStateColumn(
        $details,
        $needles,
        [
            'missing_detail' => 'Sin evaluación de componentes asociados con diagnóstico Estado.',
            'normal_label' => 'N/A',
            'normal_detail' => 'Componentes evaluados sin criticidad.',
        ]
    );
}

private function buildSemaphoreAggregatedStateColumn(Collection $details, array $componentNames, array $options = []): array
{
    $normalizedTargets = collect($componentNames)
        ->map(fn ($name) => $this->normalizeSemaphoreText($name))
        ->filter()
        ->values();

    $breakdown = $normalizedTargets->map(function ($normalizedName, $index) use ($details, $componentNames) {
        $componentLabel = $componentNames[$index] ?? $normalizedName;

        $detail = $details
            ->filter(function ($detail) use ($normalizedName) {
                return $this->normalizeSemaphoreText($detail->component?->name) === $normalizedName
                    && $this->normalizeSemaphoreText($detail->diagnostic?->name) === 'estado';
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
            'color' => $this->resolveSemaphoreConditionColor($condition, $severity),
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
        'level' => $this->semaphoreLevelFromSeverity($selected['severity']),
        'detail' => $selected['condition_description'] ?: $selected['detail'],
        'breakdown' => $breakdown->all(),
        'missing_components' => $breakdown->where('evaluated', false)->pluck('component')->values()->all(),
        'color' => $selected['color'] ?? $this->resolveSemaphoreConditionColor(null, $selected['severity']),
        'severity' => $selected['severity'],
        'order' => $this->semaphoreOrderFromSeverity($selected['severity']),
    ];
}

private function resolveSemaphoreConditionColor($condition, ?int $severity = null): string
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

private function semaphoreLevelFromConfiguredSeverity($severity, ?string $direction): string
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

    return $this->semaphoreLevelFromSeverity($severity);
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

private function semaphoreOrderFromConfiguredSeverity($severity, ?string $direction): int
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

    return $this->semaphoreOrderFromSeverity($severity);
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
