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
use App\Models\ReportDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Support\ReportFilePathBuilder;

class InspectorReportController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $allowedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id');

        $specializedElementTypes = $user->allowedElementTypes()
            ->whereIn('user_client_element_type.client_id', $allowedClientIds)
            ->where('element_types.status', true)
            ->get()
            ->groupBy(fn ($item) => $item->pivot->client_id);

        $assignedClients = Client::whereIn('id', $specializedElementTypes->keys())
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $assignedClient = $assignedClients->count() === 1 ? $assignedClients->first() : null;

        $selectedClientId = null;
        $selectedAreaId = null;
        $selectedElementId = null;

        $areas = collect();
        $elements = collect();
        $conditions = collect();
        $allowedElementTypesForSelectedClient = collect();

        if ($assignedClient) {
            $selectedClientId = $assignedClient->id;
        } else {
            $sessionClientId = (int) session('inspector_last_client_id');

            if ($sessionClientId && $assignedClients->pluck('id')->contains($sessionClientId)) {
                $selectedClientId = $sessionClientId;
            }
        }

        if ($selectedClientId) {
            $allowedElementTypesForSelectedClient = $specializedElementTypes->get($selectedClientId, collect());

            $areas = $this->allowedAreasQuery($user, $selectedClientId)
                ->with('client')
                ->orderBy('name')
                ->get();

            $sessionAreaId = (int) session('inspector_last_area_id');

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

                $sessionElementId = (int) session('inspector_last_element_id');

                if ($sessionElementId && $elements->pluck('id')->contains($sessionElementId)) {
                    $selectedElementId = $sessionElementId;
                }

                if ($selectedElementId) {
                    $selectedElement = $elements->firstWhere('id', $selectedElementId);

                    if ($selectedElement) {
                        $conditions = collect();
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
            'areas',
            'elements',
            'conditions',
            'recentReports',
            'specializedElementTypes',
            'specialtiesByClient',
            'allowedElementTypesForSelectedClient',
            'selectedClientId',
            'selectedAreaId',
            'selectedElementId'
        ));
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
        $area = Area::findOrFail($validated['area_id']);
        $element = Element::findOrFail($validated['element_id']);
        $component = Component::findOrFail($validated['component_id']);
        $diagnostic = Diagnostic::findOrFail($validated['diagnostic_id']);
        $condition = Condition::findOrFail($validated['condition_id']);

        abort_unless(
            $this->userHasClientAccess($user, $client->id),
            403,
            'No tienes acceso a este cliente.'
        );

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

            $updateData = [
                'condition_id' => $validated['condition_id'],
            ];

            if ($isBeltEstado) {
                $updateData['is_belt_change'] = $isBeltChangeValue;
            }

            if ($newRecommendation !== '') {
                $currentRecommendation = trim((string) ($existingReport->recommendation ?? ''));

                $updateData['recommendation'] = $currentRecommendation !== ''
                    ? $currentRecommendation . PHP_EOL . $newRecommendation
                    : $newRecommendation;
            }

            $existingReport->update($updateData);

            if ($request->hasFile('attachments')) {
                $existingReport->loadMissing('element');
                $this->storeAttachments($existingReport, $request->file('attachments'), $user->id);
            }

            $this->storeLastSelectionInSession($client->id, $area->id, $element->id);

            return redirect()
                ->route('inspector.reports.index')
                ->with('success', 'El reporte existente fue complementado correctamente.');
        }

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
            'recommendation' => $validated['recommendation'] ?? null,
            'is_belt_change' => $isBeltChangeValue,
            'orden' => null,
            'aviso' => null,
            'execution_status_id' => null,
            'execution_date' => now()->toDateString(),
        ]);

        if ($request->hasFile('attachments')) {
            $reportDetail->loadMissing('element');
            $this->storeAttachments($reportDetail, $request->file('attachments'), $user->id);
        }

        $this->storeLastSelectionInSession($client->id, $area->id, $element->id);

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

    private function storeLastSelectionInSession(int $clientId, int $areaId, int $elementId): void
    {
        session([
            'inspector_last_client_id' => $clientId,
            'inspector_last_area_id' => $areaId,
            'inspector_last_element_id' => $elementId,
        ]);
    }

    private function userHasClientAccess($user, int $clientId): bool
    {
        return $user->clients()->where('clients.id', $clientId)->exists()
            && $user->allowedElementTypesForClient($clientId)->exists();
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
        if (!$element->group_id) {
            return false;
        }

        $hasClientAccess = $user->clients()
            ->where('clients.id', $element->area->client_id)
            ->where('clients.status', true)
            ->exists();

        if (!$hasClientAccess) {
            return false;
        }

        return $user->groups()
            ->where('groups.id', $element->group_id)
            ->where('groups.client_id', $element->area->client_id)
            ->where('groups.status', true)
            ->exists();
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
