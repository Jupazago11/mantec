<?php

namespace App\Http\Controllers\Inspector;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Client;
use App\Models\User;
use App\Models\Component;
use App\Models\Condition;
use App\Models\Diagnostic;
use App\Models\Element;
use App\Models\Group;
use App\Models\ReportDetail;
use App\Services\Execution\ExecutionStatusResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Support\ReportFilePathBuilder;

class InspectorReportController extends Controller
{
    public function __construct(
        private readonly ExecutionStatusResolver $executionStatusResolver,
    ) {
    }

    public function index(): View
    {
        $user = Auth::user();
        $isInspector = ($user->role?->key ?? null) === 'inspector';

        $allowedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id');

        $specializedElementTypes = $user->allowedElementTypes()
            ->whereIn('user_client_element_type.client_id', $allowedClientIds)
            ->where('element_types.status', true)
            ->get()
            ->groupBy(fn ($item) => $item->pivot->client_id);

        $clientIdsForForm = $isInspector
            ? $allowedClientIds
            : $specializedElementTypes->keys();

        $assignedClients = Client::whereIn('id', $clientIdsForForm)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $selectedClientId = null;
        $selectedGroupId = null;
        $selectedAreaId = null;
        $selectedElementId = null;

        $assignedGroups = collect();
        $areas = collect();
        $elements = collect();
        $conditions = collect();
        $allowedElementTypesForSelectedClient = collect();

        $sessionClientId = (int) old('client_id', session('inspector_last_client_id'));

        if ($sessionClientId && $assignedClients->pluck('id')->contains($sessionClientId)) {
            $selectedClientId = $sessionClientId;
        } elseif ($assignedClients->isNotEmpty()) {
            $selectedClientId = (int) $assignedClients->first()->id;
        }

        $assignedClient = $selectedClientId
            ? $assignedClients->firstWhere('id', $selectedClientId)
            : null;

        if ($selectedClientId) {
            $allowedElementTypesForSelectedClient = $specializedElementTypes->get($selectedClientId, collect());

            if ($isInspector) {
                $assignedGroups = $this->inspectorGroupsForClientQuery($user, $selectedClientId)
                    ->get(['groups.id', 'groups.client_id', 'groups.name', 'groups.description']);

                $sessionGroupId = (int) old('group_id', session('inspector_last_group_id'));

                if ($sessionGroupId && $assignedGroups->pluck('id')->contains($sessionGroupId)) {
                    $selectedGroupId = $sessionGroupId;
                } elseif ($assignedGroups->count() === 1) {
                    $selectedGroupId = (int) $assignedGroups->first()->id;
                }

                if ($selectedGroupId) {
                    $areas = $this->areasForInspectorGroupQuery($selectedClientId, $selectedGroupId)
                        ->with('client')
                        ->orderBy('name')
                        ->get();

                    $sessionAreaId = (int) old('area_id', session('inspector_last_area_id'));

                    if ($sessionAreaId && $areas->pluck('id')->contains($sessionAreaId)) {
                        $selectedAreaId = $sessionAreaId;
                    }

                    if ($selectedAreaId) {
                        $elements = $this->elementsForInspectorGroupAreaQuery($selectedGroupId, $selectedAreaId)
                            ->get();

                        $sessionElementId = (int) old('element_id', session('inspector_last_element_id'));

                        if ($sessionElementId && $elements->pluck('id')->contains($sessionElementId)) {
                            $selectedElementId = $sessionElementId;
                        }
                    }
                }
            } else {
                $areas = $this->allowedAreasQuery($user, $selectedClientId)
                    ->with('client')
                    ->orderBy('name')
                    ->get();

                $sessionAreaId = (int) old('area_id', session('inspector_last_area_id'));

                if ($sessionAreaId && $areas->pluck('id')->contains($sessionAreaId)) {
                    $selectedAreaId = $sessionAreaId;
                }

                if ($selectedAreaId) {
                    $allowedElementTypeIds = $this->allowedElementTypeIdsForClient($user, $selectedClientId);

                    $elements = Element::with('elementType')
                        ->where('area_id', $selectedAreaId)
                        ->where('status', true)
                        ->whereIn('element_type_id', $allowedElementTypeIds)
                        ->orderBy('name')
                        ->get();

                    $sessionElementId = (int) old('element_id', session('inspector_last_element_id'));

                    if ($sessionElementId && $elements->pluck('id')->contains($sessionElementId)) {
                        $selectedElementId = $sessionElementId;
                    }
                }
            }
        }

        $specialtiesByClient = $specializedElementTypes->map(function ($group) {
            return $group->pluck('name')->values();
        });

        $recentReports = ReportDetail::with([
            'element.area.client',
            'element.elementType',
            'component',
            'diagnostic',
            'condition',
            'executionStatus',
            'files:id,report_detail_id,file_type,disk,path,original_name',
        ])
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(168))
            ->whereHas('element', function ($query) use ($specializedElementTypes) {
                $query->where(function ($elementQuery) use ($specializedElementTypes) {
                    foreach ($specializedElementTypes as $clientId => $types) {
                        $typeIds = $types->pluck('id')->toArray();

                        $elementQuery->orWhere(function ($sub) use ($clientId, $typeIds) {
                            $sub->whereIn('element_type_id', $typeIds)
                                ->whereHas('area', function ($areaQuery) use ($clientId) {
                                    $areaQuery->where('client_id', $clientId);
                                });
                        });
                    }
                });
            })
            ->orderByDesc('created_at')
            ->get();

        return view('inspector.reports.index', compact(
            'assignedClients',
            'assignedClient',
            'assignedGroups',
            'areas',
            'elements',
            'conditions',
            'recentReports',
            'specializedElementTypes',
            'specialtiesByClient',
            'allowedElementTypesForSelectedClient',
            'selectedClientId',
            'selectedGroupId',
            'selectedAreaId',
            'selectedElementId'
        ));
    }

    public function getGroupsByClient(Client $client): JsonResponse
    {
        $user = Auth::user();

        abort_unless($this->userCanAccessClient($user, $client->id), 403);

        $groups = $this->inspectorGroupsForClientQuery($user, $client->id)
            ->get(['groups.id', 'groups.client_id', 'groups.name', 'groups.description']);

        return response()->json($groups);
    }

    public function getAreasByGroup(Group $group): JsonResponse
    {
        $user = Auth::user();

        abort_unless($this->userCanAccessGroup($user, $group), 403);

        $areas = $this->areasForInspectorGroupQuery((int) $group->client_id, (int) $group->id)
            ->orderBy('name')
            ->get(['id', 'client_id', 'name', 'code']);

        return response()->json($areas);
    }

    public function getElementsByGroupArea(Group $group, Area $area): JsonResponse
    {
        $user = Auth::user();

        abort_unless($this->userCanAccessGroup($user, $group), 403);
        abort_unless((int) $area->client_id === (int) $group->client_id, 403);

        $elements = $this->elementsForInspectorGroupAreaQuery((int) $group->id, (int) $area->id)
            ->get()
            ->map(function ($element) {
                return [
                    'id' => $element->id,
                    'name' => $element->name,
                    'code' => $element->code,
                    'group_id' => $element->group_id,
                    'element_type_id' => $element->element_type_id,
                    'element_type_name' => optional($element->elementType)->name,
                ];
            })
            ->values();

        return response()->json($elements);
    }

    public function getAreasByClient(Client $client): JsonResponse
    {
        $user = Auth::user();

        abort_unless($this->userHasClientAccess($user, $client->id), 403);

        $areas = $this->allowedAreasQuery($user, $client->id)
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        return response()->json($areas);
    }

    public function getConditionsByElement(Request $request, Element $element): JsonResponse
    {
        $user = Auth::user();

        $element->loadMissing('area');

        abort_unless($this->userCanAccessElement($user, $element), 403);

        $componentId = (int) $request->query('component_id');

        if (!$componentId) {
            return response()->json([]);
        }

        abort_unless(
            $element->components()->where('components.id', $componentId)->exists(),
            403
        );

        $component = Component::findOrFail($componentId);

        $conditions = $component->conditions()
            ->where('conditions.status', true)
            ->where('conditions.client_id', $element->area->client_id)
            ->where('conditions.element_type_id', $element->element_type_id)
            ->orderBy('conditions.severity')
            ->orderBy('conditions.name')
            ->get([
                'conditions.id',
                'conditions.client_id',
                'conditions.element_type_id',
                'conditions.name',
                'conditions.code',
                'conditions.description',
                'conditions.severity',
                'conditions.color',
            ]);

        return response()->json($conditions);
    }


    public function getElementsByArea(Area $area): JsonResponse
    {
        $user = Auth::user();

        abort_unless($this->userHasClientAccess($user, $area->client_id), 403);

        $allowedElementTypeIds = $this->allowedElementTypeIdsForClient($user, $area->client_id);

        $elements = $area->elements()
            ->with('elementType')
            ->where('status', true)
            ->whereIn('element_type_id', $allowedElementTypeIds)
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($element) {
                return [
                    'id' => $element->id,
                    'name' => $element->name,
                    'code' => $element->code,
                    'element_type_id' => $element->element_type_id,
                    'element_type_name' => optional($element->elementType)->name,
                ];
            })
            ->values();

        return response()->json($elements);
    }

    public function getComponentsByElement(Element $element): JsonResponse
    {
        $user = Auth::user();

        $element->loadMissing('area');

        abort_unless($this->userCanAccessElement($user, $element), 403);

        $components = $element->components()
            ->where('components.status', true)
            ->where('components.element_type_id', $element->element_type_id)
            ->orderBy('components.name', 'asc')
            ->get([
                'components.id',
                'components.name',
                'components.code',
                'components.element_type_id',
            ]);

        return response()->json($components);
    }

    public function getDiagnosticsByComponent(Request $request, Component $component): JsonResponse
    {
        $user = Auth::user();

        $elementId = (int) $request->query('element_id');
        $element = Element::findOrFail($elementId);

        abort_unless($this->userCanAccessElement($user, $element), 403);

        abort_unless(
            $element->components()->where('components.id', $component->id)->exists(),
            403
        );

        $diagnostics = $component->diagnostics()
            ->where('diagnostics.status', true)
            ->where('diagnostics.element_type_id', $element->element_type_id)
            ->orderBy('diagnostics.name', 'asc')
            ->get([
                'diagnostics.id',
                'diagnostics.client_id',
                'diagnostics.element_type_id',
                'diagnostics.name',
                'diagnostics.description',
                'diagnostics.status',
            ]);

        return response()->json($diagnostics);
    }


    public function getPendingDiagnostics(Element $element): JsonResponse
    {
        $user = Auth::user();

        abort_unless($this->userCanAccessElement($user, $element), 403);

        $now = Carbon::now();
        $week = (int) $now->isoWeek();
        $year = (int) $now->isoWeekYear();

        $expected = $element->components()
            ->with(['diagnostics' => function ($query) {
                $query->where('diagnostics.status', true)
                    ->orderBy('diagnostics.name');
            }])
            ->where('components.status', true)
            ->where('components.element_type_id', $element->element_type_id)
            ->orderBy('components.name')
            ->get();

        $doneKeys = ReportDetail::where('element_id', $element->id)
            ->where('week', $week)
            ->where('year', $year)
            ->get(['component_id', 'diagnostic_id'])
            ->map(fn ($row) => $row->component_id . '-' . $row->diagnostic_id)
            ->toArray();

        $pending = [];

        foreach ($expected as $component) {
            foreach ($component->diagnostics as $diagnostic) {
                $key = $component->id . '-' . $diagnostic->id;

                if (!in_array($key, $doneKeys)) {
                    $pending[] = [
                        'component_id' => $component->id,
                        'component_name' => $component->name,
                        'component_code' => $component->code,
                        'diagnostic_id' => $diagnostic->id,
                        'diagnostic_name' => $diagnostic->name,
                    ];
                }
            }
        }

        return response()->json([
            'total_pending' => count($pending),
            'items' => $pending,
        ]);
    }


public function getWeeklyDiagnosticStatus(Element $element): JsonResponse
{
    $user = Auth::user();

    abort_unless($this->userCanAccessElement($user, $element), 403);

    $now = Carbon::now();
    $week = (int) $now->isoWeek();
    $year = (int) $now->isoWeekYear();

    /*
     * Matriz esperada del activo:
     * element_components -> components -> component_diagnostics -> diagnostics
     *
     * Se hace por consulta directa para evitar que una relación Eloquent
     * incompleta o un filtro lateral deje el resultado en [].
     */
    $expectedRows = DB::table('element_components')
        ->join('components', 'components.id', '=', 'element_components.component_id')
        ->join('component_diagnostics', 'component_diagnostics.component_id', '=', 'components.id')
        ->join('diagnostics', 'diagnostics.id', '=', 'component_diagnostics.diagnostic_id')
        ->where('element_components.element_id', $element->id)
        ->where('components.status', true)
        ->where('components.element_type_id', $element->element_type_id)
        ->where('diagnostics.status', true)
        ->where('diagnostics.element_type_id', $element->element_type_id)
        ->orderBy('components.name')
        ->orderBy('diagnostics.name')
        ->get([
            'components.id as component_id',
            'components.name as component_name',
            'components.code as component_code',
            'diagnostics.id as diagnostic_id',
            'diagnostics.name as diagnostic_name',
        ]);

    if ($expectedRows->isEmpty()) {
        return response()->json([]);
    }

    $componentIds = $expectedRows
        ->pluck('component_id')
        ->unique()
        ->values();

    $diagnosticIds = $expectedRows
        ->pluck('diagnostic_id')
        ->unique()
        ->values();

    $doneKeys = ReportDetail::query()
        ->where('element_id', $element->id)
        ->where('week', $week)
        ->where('year', $year)
        ->whereIn('component_id', $componentIds)
        ->whereIn('diagnostic_id', $diagnosticIds)
        ->get(['component_id', 'diagnostic_id'])
        ->map(fn ($row) => $row->component_id . '-' . $row->diagnostic_id)
        ->flip();

    $items = $expectedRows->map(function ($row) use ($doneKeys) {
        $key = $row->component_id . '-' . $row->diagnostic_id;

        return [
            'component_id' => (int) $row->component_id,
            'component_name' => (string) $row->component_name,
            'component_code' => $row->component_code,
            'diagnostic_id' => (int) $row->diagnostic_id,
            'diagnostic_name' => (string) $row->diagnostic_name,
            'status' => $doneKeys->has($key) ? 'DONE' : 'PENDING',
        ];
    })->values();

    return response()->json($items);
}

    public function getWeeklyElementsStatus(Request $request, Area $area): JsonResponse
    {
        $user = Auth::user();

        abort_unless($this->userCanAccessArea($user, $area), 403);

        $elementTypeId = (int) $request->query('element_type_id');

        if (!$elementTypeId) {
            return response()->json([
                'message' => 'El parámetro element_type_id es obligatorio.'
            ], 422);
        }

        $now = Carbon::now();
        $week = (int) $now->isoWeek();
        $year = (int) $now->isoWeekYear();

        $elements = Element::query()
            ->where('area_id', $area->id)
            ->where('element_type_id', $elementTypeId)
            ->where('status', true)
            ->with(['components' => function ($query) use ($elementTypeId) {
                $query->where('components.status', true)
                    ->where('components.element_type_id', $elementTypeId)
                    ->with(['diagnostics' => function ($q) use ($elementTypeId) {
                        $q->where('diagnostics.status', true)
                            ->where('diagnostics.element_type_id', $elementTypeId)
                            ->orderBy('diagnostics.name');
                    }])
                    ->orderBy('components.name');
            }])
            ->orderBy('name')
            ->get();

        $doneRows = ReportDetail::query()
            ->whereIn('element_id', $elements->pluck('id'))
            ->where('week', $week)
            ->where('year', $year)
            ->get(['element_id', 'component_id', 'diagnostic_id']);

        $doneKeys = $doneRows
            ->map(fn ($row) => $row->element_id . '-' . $row->component_id . '-' . $row->diagnostic_id)
            ->flip();

        $items = $elements->map(function ($element) use ($doneKeys) {
            $expectedCount = 0;
            $doneCount = 0;

            foreach ($element->components as $component) {
                foreach ($component->diagnostics as $diagnostic) {
                    $expectedCount++;

                    $key = $element->id . '-' . $component->id . '-' . $diagnostic->id;

                    if ($doneKeys->has($key)) {
                        $doneCount++;
                    }
                }
            }

            $status = ($expectedCount > 0 && $doneCount === $expectedCount)
                ? 'DONE'
                : 'PENDING';

            return [
                'element_id' => (int) $element->id,
                'element_name' => $element->name,
                'status' => $status,
                'expected_count' => $expectedCount,
                'done_count' => $doneCount,
            ];
        })->values();

        return response()->json($items);
    }


    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'area_id' => ['required', 'exists:areas,id'],
            'element_id' => ['required', 'exists:elements,id'],
            'component_id' => ['required', 'exists:components,id'],
            'diagnostic_id' => ['required', 'exists:diagnostics,id'],
            'condition_id' => ['required', 'exists:conditions,id'],
            'recommendation' => ['nullable', 'string'],
            'is_belt_change' => ['nullable', 'in:0,1'],

            'attachments' => ['nullable', 'array', 'max:6'],
            'attachments.*' => [
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm',
                'max:102400',
            ],
        ]);

        $user = Auth::user();

        $client = Client::findOrFail($validated['client_id']);
        $group = Group::findOrFail($validated['group_id']);
        $area = Area::findOrFail($validated['area_id']);
        $element = Element::findOrFail($validated['element_id']);
        $component = Component::findOrFail($validated['component_id']);
        $diagnostic = Diagnostic::findOrFail($validated['diagnostic_id']);
        $condition = Condition::findOrFail($validated['condition_id']);

        abort_unless(
            $this->userCanAccessClient($user, $client->id),
            403,
            'No tienes acceso a este cliente.'
        );

        if (!$this->userCanAccessGroup($user, $group)) {
            return back()
                ->withErrors(['group_id' => 'La agrupación no está asignada a tu usuario.'])
                ->withInput();
        }

        if ((int) $group->client_id !== (int) $client->id) {
            return back()
                ->withErrors(['group_id' => 'La agrupación no pertenece al cliente seleccionado.'])
                ->withInput();
        }

        if ((int) $area->client_id !== (int) $client->id) {
            return back()
                ->withErrors(['area_id' => 'El área no pertenece al cliente seleccionado.'])
                ->withInput();
        }

        if ((int) $element->area_id !== (int) $area->id) {
            return back()
                ->withErrors(['element_id' => 'El activo no pertenece al área seleccionada.'])
                ->withInput();
        }

        if (!$this->elementBelongsToGroup($element, (int) $group->id)) {
            return back()
                ->withErrors(['element_id' => 'El activo no pertenece a la agrupación seleccionada.'])
                ->withInput();
        }

        abort_unless(
            $this->userCanAccessElement($user, $element),
            403,
            'No tienes permiso para reportar este tipo de activo.'
        );

        if (!$element->components()->where('components.id', $component->id)->exists()) {
            return back()
                ->withErrors(['component_id' => 'El componente no pertenece al activo seleccionado.'])
                ->withInput();
        }

        if (!$component->diagnostics()->where('diagnostics.id', $validated['diagnostic_id'])->exists()) {
            return back()
                ->withErrors(['diagnostic_id' => 'El diagnóstico no pertenece al componente seleccionado.'])
                ->withInput();
        }
        if ((int) $diagnostic->element_type_id !== (int) $element->element_type_id) {
            return back()
                ->withErrors(['diagnostic_id' => 'El diagnóstico no pertenece al tipo de activo del elemento seleccionado.'])
                ->withInput();
        }



        $isBeltEstado =
            mb_strtolower(trim((string) $component->name)) === 'banda' &&
            mb_strtolower(trim((string) $diagnostic->name)) === 'estado';

        if ($isBeltEstado && !array_key_exists('is_belt_change', $validated)) {
            return back()
                ->withErrors(['is_belt_change' => 'Debes indicar si hubo cambio de banda.'])
                ->withInput();
        }

        $isBeltChangeValue = $isBeltEstado
            ? (isset($validated['is_belt_change']) ? (bool) ((int) $validated['is_belt_change']) : null)
            : null;

        if ((int) $condition->client_id !== (int) $client->id) {
            return back()
                ->withErrors(['condition_id' => 'La condición no pertenece al cliente seleccionado.'])
                ->withInput();
        }

        if ((int) $condition->element_type_id !== (int) $element->element_type_id) {
            return back()
                ->withErrors(['condition_id' => 'La condición no pertenece al tipo de activo del elemento seleccionado.'])
                ->withInput();
        }

        $isDetenidoCondition = $this->isDetenidoCondition($condition);
        $isOkCondition = $this->executionStatusResolver->isOkCondition($condition);
        $executionStatusId = $this->executionStatusResolver->resolveStatusIdForCondition($condition);
        $lastNonDetenidoReport = $isDetenidoCondition
            ? $this->findLatestNonDetenidoReport($element->id, $component->id, $diagnostic->id)
            : null;

        $now = Carbon::now();
        $currentWeek = (int) $now->isoWeek();
        $currentYear = (int) $now->isoWeekYear();

        $existingReport = ReportDetail::where('element_id', $element->id)
            ->where('component_id', $component->id)
            ->where('diagnostic_id', $validated['diagnostic_id'])
            ->where('created_at', '>=', now()->subHours(24))
            ->latest('created_at')
            ->first();

        if ($existingReport) {
            $newRecommendation = trim((string) ($validated['recommendation'] ?? ''));
            $previousMatchingReport = $this->findPreviousMatchingReport(
                $element->id,
                $component->id,
                $diagnostic->id,
                $existingReport->id
            );

            $updateData = [
                'condition_id' => $validated['condition_id'],
            ];

            if ($isBeltEstado) {
                $updateData['is_belt_change'] = $isBeltChangeValue;
            }

            if ($isDetenidoCondition && $lastNonDetenidoReport) {
                $updateData['recommendation'] = $lastNonDetenidoReport->recommendation;
            } elseif ($newRecommendation !== '') {
                $currentRecommendation = trim((string) ($existingReport->recommendation ?? ''));

                $updateData['recommendation'] = $currentRecommendation !== ''
                    ? $currentRecommendation . PHP_EOL . $newRecommendation
                    : $newRecommendation;
            }

            if ($isOkCondition) {
                $updateData['orden'] = null;
                $updateData['aviso'] = null;
                $updateData['execution_status_id'] = $executionStatusId;
                $updateData['execution_date'] = null;
            } else {
                $updateData['orden'] = $previousMatchingReport?->orden;
                $updateData['aviso'] = $previousMatchingReport?->aviso;
                $updateData['execution_status_id'] = $executionStatusId;
                $updateData['execution_date'] = $existingReport->execution_date ?: now()->toDateString();
            }

            $existingReport->update($updateData);

            if ($request->hasFile('attachments')) {
                $existingReport->loadMissing('element');
                $this->storeAttachments($existingReport, $request->file('attachments'), $user->id);
            }

            $this->storeLastSelectionInSession($client->id, $group->id, $area->id, $element->id);

            return redirect()
                ->route('inspector.reports.index')
                ->with('success', 'El reporte existente fue complementado correctamente.');
        }

        $previousMatchingReport = $this->findPreviousMatchingReport(
            $element->id,
            $component->id,
            $diagnostic->id
        );

        $reportDetail = ReportDetail::create([
            'report_id' => null,
            'user_id' => $user->id,
            'element_id' => $element->id,
            'component_id' => $component->id,
            'diagnostic_id' => $validated['diagnostic_id'],
            'year' => $currentYear,
            'week' => $currentWeek,
            'condition_id' => $validated['condition_id'],
            'observation' => null,
            'recommendation' => $isDetenidoCondition && $lastNonDetenidoReport
                ? $lastNonDetenidoReport->recommendation
                : ($validated['recommendation'] ?? null),
            'orden' => $isOkCondition ? null : $previousMatchingReport?->orden,
            'aviso' => $isOkCondition ? null : $previousMatchingReport?->aviso,
            'is_belt_change' => $isBeltChangeValue,
            'execution_status_id' => $executionStatusId,
            'execution_date' => $isOkCondition ? null : now()->toDateString(),
        ]);

        if ($request->hasFile('attachments')) {
            $reportDetail->loadMissing('element');
            $this->storeAttachments($reportDetail, $request->file('attachments'), $user->id);
        }

        $this->storeLastSelectionInSession($client->id, $group->id, $area->id, $element->id);

        return redirect()
            ->route('inspector.reports.index')
            ->with('success', 'Reporte registrado correctamente.');
    }

    private function storeAttachments(ReportDetail $reportDetail, array $files, int $uploadedBy): void
    {
        foreach ($files as $index => $file) {
            $built = ReportFilePathBuilder::build($reportDetail->element, $file);

            $stream = fopen($file->getRealPath(), 'r');

            if ($stream === false) {
                throw new \RuntimeException('No se pudo abrir el archivo temporal para subirlo.');
            }

            try {
                Storage::disk('r2')->writeStream(
                    $built['path'],
                    $stream,
                    [
                        'ContentType' => $file->getMimeType(),
                    ]
                );
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if (!Storage::disk('r2')->exists($built['path'])) {
                throw new \RuntimeException('El archivo no quedó almacenado en R2 después de la subida.');
            }

            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $fileType = str_starts_with($mime, 'video/') ? 'video' : 'image';

            $reportDetail->files()->create([
                'uploaded_by' => $uploadedBy,
                'disk' => 'r2',
                'path' => $built['path'],
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $built['stored_name'],
                'mime_type' => $mime,
                'extension' => $built['extension'],
                'file_type' => $fileType,
                'size_bytes' => $file->getSize() ?: 0,
                'sort_order' => $index,
            ]);
        }
    }

    private function storeLastSelectionInSession(int $clientId, int $groupId, int $areaId, int $elementId): void
    {
        session([
            'inspector_last_client_id' => $clientId,
            'inspector_last_group_id' => $groupId,
            'inspector_last_area_id' => $areaId,
            'inspector_last_element_id' => $elementId,
        ]);
    }

    private function isDetenidoCondition(Condition $condition): bool
    {
        return mb_strtolower(trim((string) $condition->code)) === 'detenido';
    }

    private function findLatestNonDetenidoReport(int $elementId, int $componentId, int $diagnosticId): ?ReportDetail
    {
        return ReportDetail::query()
            ->where('element_id', $elementId)
            ->where('component_id', $componentId)
            ->where('diagnostic_id', $diagnosticId)
            ->whereHas('condition', function ($query) {
                $query->whereRaw('LOWER(TRIM(code)) <> ?', ['detenido']);
            })
            ->latest('created_at')
            ->first();
    }

    private function findPreviousMatchingReport(
        int $elementId,
        int $componentId,
        int $diagnosticId,
        ?int $excludeReportId = null
    ): ?ReportDetail
    {
        return ReportDetail::query()
            ->where('element_id', $elementId)
            ->where('component_id', $componentId)
            ->where('diagnostic_id', $diagnosticId)
            ->when($excludeReportId !== null, function ($query) use ($excludeReportId) {
                $query->where('id', '<>', $excludeReportId);
            })
            ->latest('created_at')
            ->first();
    }

    private function userHasClientAccess($user, int $clientId): bool
    {
        return $user->clients()->where('clients.id', $clientId)->exists()
            && $user->allowedElementTypesForClient($clientId)->exists();
    }

    private function userCanAccessClient($user, int $clientId): bool
    {
        if (($user->role?->key ?? null) === 'inspector') {
            return $user->clients()
                ->where('clients.id', $clientId)
                ->where('clients.status', true)
                ->exists();
        }

        return $this->userHasClientAccess($user, $clientId);
    }

    private function userCanAccessGroup($user, Group $group): bool
    {
        return $this->userCanAccessClient($user, (int) $group->client_id)
            && $user->groups()
                ->where('groups.id', $group->id)
                ->where('groups.client_id', $group->client_id)
                ->where('groups.status', true)
                ->exists();
    }

    private function inspectorGroupsForClientQuery($user, int $clientId)
    {
        return $user->groups()
            ->where('groups.client_id', $clientId)
            ->where('groups.status', true)
            ->orderBy('groups.name');
    }

    private function areasForInspectorGroupQuery(int $clientId, int $groupId)
    {
        $elementIds = $this->elementIdsForGroup($groupId);

        return Area::query()
            ->where('client_id', $clientId)
            ->where('status', true)
            ->whereHas('elements', function ($query) use ($elementIds) {
                $query->where('status', true)
                    ->whereIn('id', $elementIds);
            });
    }

    private function elementsForInspectorGroupAreaQuery(int $groupId, int $areaId)
    {
        $elementIds = $this->elementIdsForGroup($groupId);

        return Element::with('elementType')
            ->whereIn('id', $elementIds)
            ->where('area_id', $areaId)
            ->where('status', true)
            ->orderBy('name');
    }

    private function elementBelongsToGroup(Element $element, int $groupId): bool
    {
        if (Schema::hasColumn('elements', 'group_id') && (int) $element->group_id === $groupId) {
            return true;
        }

        return $this->resolveElementIdsFromGroupPivot($groupId)
            ->contains((int) $element->id);
    }

    private function elementIdsForGroup(int $groupId)
    {
        $ids = collect();

        if (Schema::hasColumn('elements', 'group_id')) {
            $ids = $ids->merge(
                Element::query()
                    ->where('group_id', $groupId)
                    ->pluck('id')
            );
        }

        return $ids
            ->merge($this->resolveElementIdsFromGroupPivot($groupId))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function resolveElementIdsFromGroupPivot(int $groupId)
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

    private function allowedElementTypeIdsForClient($user, int $clientId): array
    {
        return $user->allowedElementTypesForClient($clientId)
            ->where('element_types.status', true)
            ->pluck('element_types.id')
            ->toArray();
    }

    private function allowedAreasQuery($user, int $clientId)
    {
        $allowedElementTypeIds = $this->allowedElementTypeIdsForClient($user, $clientId);

        return Area::query()
            ->where('client_id', $clientId)
            ->where('status', true)
            ->whereHas('elements', function ($query) use ($allowedElementTypeIds) {
                $query->where('status', true)
                    ->whereIn('element_type_id', $allowedElementTypeIds);
            });
    }

private function userCanAccessElement($user, Element $element): bool
{
    $element->loadMissing('area');

    if (!$element->area) {
        return false;
    }

    $roleKey = $user->role->key ?? null;

    if ($roleKey === 'inspector') {
        $hasClientAccess = $user->clients()
            ->where('clients.id', $element->area->client_id)
            ->where('clients.status', true)
            ->exists();

        if (!$hasClientAccess) {
            return false;
        }

        return $user->groups()
            ->where('groups.client_id', $element->area->client_id)
            ->where('groups.status', true)
            ->get()
            ->contains(fn ($group) => $this->elementBelongsToGroup($element, (int) $group->id));
    }

    return $this->userHasClientAccess($user, $element->area->client_id)
        && $user->hasElementTypeAccess($element->area->client_id, $element->element_type_id);
}

    private function userCanAccessArea(User $user, Area $area): bool
    {
        if (in_array($user->role->key ?? null, ['superadmin', 'admin_global', 'admin'])) {
            return true;
        }

        if (($user->role->key ?? null) === 'admin_cliente') {
            return $user->clients()->where('clients.id', $area->client_id)->exists();
        }

        if (($user->role->key ?? null) === 'observador_cliente') {
            return $user->clients()->where('clients.id', $area->client_id)->exists();
        }

        if (($user->role->key ?? null) === 'inspector') {
            return $user->clients()->where('clients.id', $area->client_id)->exists();
        }

        return false;
    }
}
