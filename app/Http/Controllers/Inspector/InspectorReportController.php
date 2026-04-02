<?php

namespace App\Http\Controllers\Inspector;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Client;
use App\Models\Component;
use App\Models\Condition;
use App\Models\Element;
use App\Models\ReportDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
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

            $conditions = Condition::where('client_id', $selectedClientId)
                ->where('status', true)
                ->orderBy('severity')
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
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($areas);
    }

    public function getConditionsByClient(Client $client): JsonResponse
    {
        $user = Auth::user();

        abort_unless($this->userHasClientAccess($user, $client->id), 403);

        $conditions = Condition::where('client_id', $client->id)
            ->where('status', true)
            ->orderBy('severity')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'severity', 'color']);

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
            ->orderBy('name')
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
            ->orderBy('components.name')
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
            ->orderBy('diagnostics.name')
            ->get([
                'diagnostics.id',
                'diagnostics.name',
            ]);

        return response()->json($diagnostics);
    }









    public function getPendingDiagnostics(Element $element): JsonResponse
    {
        $user = Auth::user();

        abort_unless($this->userCanAccessElement($user, $element), 403);

        $week = now()->weekOfYear;
        $year = now()->year;

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
        $condition = Condition::findOrFail($validated['condition_id']);

        abort_unless(
            $this->userHasClientAccess($user, $client->id),
            403,
            'No tienes acceso a este cliente.'
        );

        if ($area->client_id !== $client->id) {
            return back()
                ->withErrors(['area_id' => 'El área no pertenece al cliente seleccionado.'])
                ->withInput();
        }

        if ($element->area_id !== $area->id) {
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

        if ($condition->client_id !== $client->id) {
            return back()
                ->withErrors(['condition_id' => 'La condición no pertenece al cliente seleccionado.'])
                ->withInput();
        }

        $currentWeek = now()->weekOfYear;
        $currentYear = now()->year;

        $existingReport = ReportDetail::where('element_id', $element->id)
            ->where('component_id', $component->id)
            ->where('diagnostic_id', $validated['diagnostic_id'])
            ->where('created_at', '>=', now()->subHours(24))
            ->latest('created_at')
            ->first();

        if ($existingReport) {
            $newRecommendation = trim((string) ($validated['recommendation'] ?? ''));

            if ($newRecommendation !== '') {
                $currentRecommendation = trim((string) ($existingReport->recommendation ?? ''));

                $existingReport->update([
                    'condition_id' => $validated['condition_id'],
                    'recommendation' => $currentRecommendation !== ''
                        ? $currentRecommendation . PHP_EOL . $newRecommendation
                        : $newRecommendation,
                ]);
            } else {
                $existingReport->update([
                    'condition_id' => $validated['condition_id'],
                ]);
            }

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

        return $this->userHasClientAccess($user, $element->area->client_id)
            && $user->hasElementTypeAccess($element->area->client_id, $element->element_type_id);
    }
}
