<?php

namespace App\Http\Controllers\Admin\SystemModules;

use App\Http\Controllers\Controller;
use App\Models\BandStateDraft;
use App\Models\BandStateReport;
use App\Models\BandEvent;
use App\Models\ClientElementTypeModule;
use App\Models\Element;
use App\Models\MeasurementThicknessDraft;
use App\Models\MeasurementThicknessDraftLine;
use App\Models\MeasurementThicknessReport;
use App\Models\MeasurementThicknessReportLine;
use App\Models\SystemModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MeasurementController extends Controller
{
    public function index(): View
    {
        return view('admin.system-modules.measurements.index');
    }

    public function levelOne(): View
    {
        $user = auth()->user();
        abort_unless($user, 403);
        abort_unless($user->canViewSystemModule('mediciones'), 403);

        $module = SystemModule::query()
            ->where('key', 'mediciones')
            ->where('status', true)
            ->firstOrFail();

        $roleKey = $user->role->key ?? null;
        $isPowerAdmin = in_array($roleKey, ['superadmin', 'admin_global'], true);

        $userClientIds = collect();

        if (!$isPowerAdmin && method_exists($user, 'clients')) {
            $userClientIds = $user->clients()->pluck('clients.id');
        }

        $configsQuery = ClientElementTypeModule::query()
            ->with([
                'client:id,name',
                'elementType:id,client_id,name',
            ])
            ->where('system_module_id', $module->id)
            ->where('status', true)
            ->where('module_enabled', true);

        if (!$isPowerAdmin) {
            if ($userClientIds->isEmpty()) {
                $configsQuery->whereRaw('1 = 0');
            } else {
                $configsQuery->whereIn('client_id', $userClientIds);
            }
        }

        $configs = $configsQuery
            ->orderBy('client_id')
            ->orderBy('element_type_id')
            ->get();

        $sections = $configs->map(function (ClientElementTypeModule $config) {
            $elements = Element::query()
                ->with([
                    'area:id,client_id,name',
                ])
                ->where('element_type_id', $config->element_type_id)
                ->where('status', true)
                ->whereHas('area', function ($query) use ($config) {
                    $query->where('client_id', $config->client_id)
                        ->where('status', true);
                })
                ->orderBy('name')
                ->get(['id', 'area_id', 'element_type_id', 'name', 'status']);

            $areas = $elements
                ->filter(fn ($element) => $element->area)
                ->groupBy(fn ($element) => $element->area->id)
                ->map(function (Collection $group) {
                    $area = $group->first()->area;

                    return [
                        'id' => $area->id,
                        'name' => $area->name,
                        'elements_count' => $group->count(),
                        'elements' => $group
                            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                            ->values()
                            ->map(function ($element) {
                                return [
                                    'id' => $element->id,
                                    'name' => $element->name,
                                    'url' => route('admin.system-modules.measurements.show', $element->id),
                                ];
                            })
                            ->values(),
                    ];
                })
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            return [
                'client_id' => $config->client_id,
                'client_name' => $config->client?->name ?? '—',
                'element_type_id' => $config->element_type_id,
                'element_type_name' => $config->elementType?->name ?? '—',
                'creation_enabled' => $config->creation_enabled,
                'elements_count' => $elements->count(),
                'areas_count' => $areas->count(),
                'areas' => $areas,
            ];
        })->values();

        return view('admin.system-modules.measurements.level-one', [
            'sections' => $sections,
        ]);
    }

    public function show(int $element): View
    {
        $measurementElement = $this->resolveAuthorizedMeasurementElement($element);

        $draft = MeasurementThicknessDraft::query()
            ->with(['lines' => fn ($query) => $query->orderBy('cover_number')])
            ->where('element_id', $measurementElement->id)
            ->first();

        $latestReport = MeasurementThicknessReport::query()
            ->with([
                'lines' => fn ($query) => $query->orderBy('cover_number'),
                'creator:id,name',
            ])
            ->where('element_id', $measurementElement->id)
            ->orderByDesc('report_date')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        $historicalReports = MeasurementThicknessReport::query()
            ->with(['creator:id,name'])
            ->where('element_id', $measurementElement->id)
            ->orderByDesc('report_date')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        $bandStateDraft = BandStateDraft::query()
            ->where('element_id', $measurementElement->id)
            ->first();

        $latestBandStateReport = BandStateReport::query()
            ->with(['creator:id,name'])
            ->where('element_id', $measurementElement->id)
            ->orderByDesc('report_date')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        $bandStateHistoricalReports = BandStateReport::query()
            ->with(['creator:id,name'])
            ->where('element_id', $measurementElement->id)
            ->orderByDesc('report_date')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        $bandEventLatestReport = BandEvent::query()
            ->where('element_id', $measurementElement->id)
            ->where('status', true)
            ->orderByDesc('report_date')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        $bandEventActiveBand = BandEvent::query()
            ->where('element_id', $measurementElement->id)
            ->where('type', 'band')
            ->where('status', true)
            ->orderByDesc('report_date')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        $bandEventBands = BandEvent::query()
            ->where('element_id', $measurementElement->id)
            ->where('type', 'band')
            ->where('status', true)
            ->orderByDesc('report_date')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        $bandEventHistoricalTree = BandEvent::query()
            ->with(['children' => function ($query) {
                $query->where('status', true)
                    ->orderBy('report_date')
                    ->orderBy('published_at')
                    ->orderBy('id');
            }])
            ->where('element_id', $measurementElement->id)
            ->where('type', 'band')
            ->where('status', true)
            ->orderByDesc('report_date')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        return view('admin.system-modules.measurements.show', [
            'element' => $measurementElement,
            'client' => $measurementElement->area->client,
            'area' => $measurementElement->area,
            'elementType' => $measurementElement->elementType,

            'thicknessDraft' => $draft,
            'thicknessDraftData' => $this->serializeThicknessDraft($draft),
            'latestThicknessReport' => $latestReport,
            'latestThicknessReportData' => $this->serializeThicknessReport($latestReport),
            'thicknessHistoricalReportsData' => $historicalReports
                ->map(fn (MeasurementThicknessReport $report) => $this->serializeThicknessReportSummary($report))
                ->values(),

            'bandStateDraft' => $bandStateDraft,
            'bandStateDraftData' => $this->serializeBandStateDraft($bandStateDraft),
            'latestBandStateReport' => $latestBandStateReport,
            'latestBandStateReportData' => $this->serializeBandStateReport($latestBandStateReport),
            'bandStateHistoricalReportsData' => $bandStateHistoricalReports
                ->map(fn (BandStateReport $report) => $this->serializeBandStateReportSummary($report))
                ->values(),
            'bandEventLatestReportData' => $this->serializeBandEvent($bandEventLatestReport),
            'bandEventActiveBandData' => $this->serializeBandEvent($bandEventActiveBand),
            'bandEventBandsData' => $bandEventBands
                ->map(fn (BandEvent $event) => $this->serializeBandEventSummary($event))
                ->values(),
            'bandEventHistoricalTreeData' => $bandEventHistoricalTree
                ->map(function (BandEvent $band) {
                    return [
                        ...$this->serializeBandEvent($band),
                        'children' => $band->children
                            ->map(fn (BandEvent $child) => $this->serializeBandEvent($child))
                            ->values(),
                    ];
                })
                ->values(),
        ]);
    }

    public function createThicknessDraft(int $element): JsonResponse
    {
        $measurementElement = $this->resolveAuthorizedMeasurementElement($element);
        $user = auth()->user();

        $draft = MeasurementThicknessDraft::query()->firstOrCreate(
            ['element_id' => $measurementElement->id],
            [
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]
        );

        if (!$draft->lines()->exists()) {
            $draft->lines()->create([
                'cover_number' => 1,
            ]);
        }

        $this->normalizeThicknessDraftCoverNumbers($draft);

        $draft->load(['lines' => fn ($query) => $query->orderBy('cover_number')]);

        return response()->json([
            'success' => true,
            'message' => 'Borrador creado correctamente.',
            'draft' => $this->serializeThicknessDraft($draft),
        ]);
    }

    public function updateThicknessDraft(Request $request, int $element): JsonResponse
    {
        $measurementElement = $this->resolveAuthorizedMeasurementElement($element);
        $user = auth()->user();

        $validated = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.cover_number' => ['required', 'integer', 'min:1'],
            'lines.*.top_left' => ['nullable', 'numeric'],
            'lines.*.top_center' => ['nullable', 'numeric'],
            'lines.*.top_right' => ['nullable', 'numeric'],
            'lines.*.bottom_left' => ['nullable', 'numeric'],
            'lines.*.bottom_center' => ['nullable', 'numeric'],
            'lines.*.bottom_right' => ['nullable', 'numeric'],
            'lines.*.hardness_left' => ['nullable', 'numeric'],
            'lines.*.hardness_center' => ['nullable', 'numeric'],
            'lines.*.hardness_right' => ['nullable', 'numeric'],
        ]);

        $draft = MeasurementThicknessDraft::query()->firstOrCreate(
            ['element_id' => $measurementElement->id],
            [
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]
        );

        DB::transaction(function () use ($draft, $validated, $user) {
            $incoming = collect($validated['lines'])
                ->sortBy('cover_number')
                ->values();

            $draft->lines()->delete();

            foreach ($incoming as $line) {
                $draft->lines()->create([
                    'cover_number' => $line['cover_number'],
                    'top_left' => $line['top_left'] ?? null,
                    'top_center' => $line['top_center'] ?? null,
                    'top_right' => $line['top_right'] ?? null,
                    'bottom_left' => $line['bottom_left'] ?? null,
                    'bottom_center' => $line['bottom_center'] ?? null,
                    'bottom_right' => $line['bottom_right'] ?? null,
                    'hardness_left' => $line['hardness_left'] ?? null,
                    'hardness_center' => $line['hardness_center'] ?? null,
                    'hardness_right' => $line['hardness_right'] ?? null,
                ]);
            }

            $draft->update([
                'updated_by' => $user?->id,
            ]);
        });

        $this->normalizeThicknessDraftCoverNumbers($draft);

        $draft->load(['lines' => fn ($query) => $query->orderBy('cover_number')]);

        return response()->json([
            'success' => true,
            'message' => 'Borrador guardado correctamente.',
            'draft' => $this->serializeThicknessDraft($draft),
        ]);
    }

    public function addThicknessDraftCover(int $element): JsonResponse
    {
        $measurementElement = $this->resolveAuthorizedMeasurementElement($element);
        $user = auth()->user();

        $draft = MeasurementThicknessDraft::query()->firstOrCreate(
            ['element_id' => $measurementElement->id],
            [
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]
        );

        $this->normalizeThicknessDraftCoverNumbers($draft);

        $currentLinesCount = $draft->lines()->count();

        $draft->lines()->create([
            'cover_number' => $currentLinesCount + 1,
        ]);

        $draft->update([
            'updated_by' => $user?->id,
        ]);

        $this->normalizeThicknessDraftCoverNumbers($draft);

        $draft->load(['lines' => fn ($query) => $query->orderBy('cover_number')]);

        return response()->json([
            'success' => true,
            'message' => 'Se agregó una nueva cubierta al borrador.',
            'draft' => $this->serializeThicknessDraft($draft),
        ]);
    }

    public function removeLastThicknessDraftCover(int $element): JsonResponse
    {
        $measurementElement = $this->resolveAuthorizedMeasurementElement($element);
        $user = auth()->user();

        $draft = MeasurementThicknessDraft::query()
            ->with(['lines' => fn ($query) => $query->orderByDesc('cover_number')])
            ->where('element_id', $measurementElement->id)
            ->first();

        if (!$draft || $draft->lines->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay cubiertas para eliminar.',
            ], 422);
        }

        $lastLine = $draft->lines->first();

        $hasValues = collect([
            $lastLine->top_left,
            $lastLine->top_center,
            $lastLine->top_right,
            $lastLine->bottom_left,
            $lastLine->bottom_center,
            $lastLine->bottom_right,
            $lastLine->hardness_left,
            $lastLine->hardness_center,
            $lastLine->hardness_right,
        ])->contains(fn ($value) => $value !== null && $value !== '');

        if ($hasValues) {
            return response()->json([
                'success' => false,
                'message' => 'La última cubierta tiene datos. Debes limpiarla antes de eliminarla.',
            ], 422);
        }

        $lastLine->delete();

        $this->normalizeThicknessDraftCoverNumbers($draft);

        $draft->update([
            'updated_by' => $user?->id,
        ]);

        $draft->load(['lines' => fn ($query) => $query->orderBy('cover_number')]);

        return response()->json([
            'success' => true,
            'message' => 'Última cubierta eliminada correctamente.',
            'draft' => $this->serializeThicknessDraft($draft),
        ]);
    }

    protected function resolveAuthorizedMeasurementElement(int $elementId): Element
    {
        $user = auth()->user();
        abort_unless($user, 403);
        abort_unless($user->canViewSystemModule('mediciones'), 403);

        $module = SystemModule::query()
            ->where('key', 'mediciones')
            ->where('status', true)
            ->firstOrFail();

        $measurementElement = Element::query()
            ->with([
                'area:id,client_id,name,status',
                'area.client:id,name,status',
                'elementType:id,client_id,name,status',
            ])
            ->where('id', $elementId)
            ->where('status', true)
            ->firstOrFail();

        abort_unless($measurementElement->area, 404);
        abort_unless($measurementElement->elementType, 404);
        abort_unless($measurementElement->area->client, 404);

        $clientId = $measurementElement->area->client_id;
        $elementTypeId = $measurementElement->element_type_id;

        $enabledConfigExists = ClientElementTypeModule::query()
            ->where('client_id', $clientId)
            ->where('element_type_id', $elementTypeId)
            ->where('system_module_id', $module->id)
            ->where('status', true)
            ->where('module_enabled', true)
            ->exists();

        abort_unless($enabledConfigExists, 403);

        $roleKey = $user->role->key ?? null;
        $isPowerAdmin = in_array($roleKey, ['superadmin', 'admin_global'], true);

        if (!$isPowerAdmin) {
            $userClientIds = method_exists($user, 'clients')
                ? $user->clients()->pluck('clients.id')
                : collect();

            abort_unless($userClientIds->contains($clientId), 403);
        }

        return $measurementElement;
    }

    protected function serializeThicknessDraft(?MeasurementThicknessDraft $draft): ?array
    {
        if (!$draft) {
            return null;
        }

        return [
            'id' => $draft->id,
            'element_id' => $draft->element_id,
            'lines' => $draft->lines
                ->sortBy('cover_number')
                ->values()
                ->map(function (MeasurementThicknessDraftLine $line) {
                    return [
                        'id' => $line->id,
                        'cover_number' => $line->cover_number,
                        'top_left' => $line->top_left,
                        'top_center' => $line->top_center,
                        'top_right' => $line->top_right,
                        'bottom_left' => $line->bottom_left,
                        'bottom_center' => $line->bottom_center,
                        'bottom_right' => $line->bottom_right,
                        'hardness_left' => $line->hardness_left,
                        'hardness_center' => $line->hardness_center,
                        'hardness_right' => $line->hardness_right,
                    ];
                })
                ->values(),
        ];
    }

    protected function normalizeThicknessDraftCoverNumbers(MeasurementThicknessDraft $draft): void
    {
        $draft->lines()
            ->orderBy('cover_number')
            ->get()
            ->values()
            ->each(function (MeasurementThicknessDraftLine $line, int $index) {
                $expectedCoverNumber = $index + 1;

                if ((int) $line->cover_number !== $expectedCoverNumber) {
                    $line->update([
                        'cover_number' => $expectedCoverNumber,
                    ]);
                }
            });
    }

    public function removeThicknessDraftCover(int $element, int $coverNumber): JsonResponse
    {
        $measurementElement = $this->resolveAuthorizedMeasurementElement($element);
        $user = auth()->user();

        $draft = MeasurementThicknessDraft::query()
            ->with(['lines' => fn ($query) => $query->orderBy('cover_number')])
            ->where('element_id', $measurementElement->id)
            ->first();

        if (!$draft || $draft->lines->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay cubiertas para eliminar.',
            ], 422);
        }

        $lineToDelete = $draft->lines->firstWhere('cover_number', $coverNumber);

        if (!$lineToDelete) {
            return response()->json([
                'success' => false,
                'message' => 'La cubierta seleccionada no existe.',
            ], 422);
        }

        $hasValues = collect([
            $lineToDelete->top_left,
            $lineToDelete->top_center,
            $lineToDelete->top_right,
            $lineToDelete->bottom_left,
            $lineToDelete->bottom_center,
            $lineToDelete->bottom_right,
            $lineToDelete->hardness_left,
            $lineToDelete->hardness_center,
            $lineToDelete->hardness_right,
        ])->contains(fn ($value) => $value !== null && $value !== '');

        if ($hasValues) {
            return response()->json([
                'success' => false,
                'message' => 'La cubierta seleccionada tiene datos. Debes limpiarla antes de eliminarla.',
            ], 422);
        }

        $lineToDelete->delete();

        $this->normalizeThicknessDraftCoverNumbers($draft);

        $draft->update([
            'updated_by' => $user?->id,
        ]);

        $draft->load(['lines' => fn ($query) => $query->orderBy('cover_number')]);

        return response()->json([
            'success' => true,
            'message' => 'Cubierta eliminada correctamente.',
            'draft' => $this->serializeThicknessDraft($draft),
        ]);
    }

    public function publishThicknessDraft(Request $request, int $element): JsonResponse
    {
        $measurementElement = $this->resolveAuthorizedMeasurementElement($element);
        $user = auth()->user();

        $validated = $request->validate([
            'report_date' => ['required', 'date'],
        ]);

        $draft = MeasurementThicknessDraft::query()
            ->with(['lines' => fn ($query) => $query->orderBy('cover_number')])
            ->where('element_id', $measurementElement->id)
            ->first();

        if (!$draft || $draft->lines->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No existe un borrador con cubiertas para publicar.',
            ], 422);
        }

        $missingFields = [];

        foreach ($draft->lines as $line) {
            $requiredFields = [
                'top_left' => 'cubierta superior izquierda',
                'top_center' => 'cubierta superior centro',
                'top_right' => 'cubierta superior derecha',
                'bottom_left' => 'cubierta inferior izquierda',
                'bottom_center' => 'cubierta inferior centro',
                'bottom_right' => 'cubierta inferior derecha',
                'hardness_left' => 'dureza izquierda',
                'hardness_center' => 'dureza centro',
                'hardness_right' => 'dureza derecha',
            ];

            foreach ($requiredFields as $field => $label) {
                $value = $line->{$field};

                if ($value === null || $value === '') {
                    $missingFields[] = 'Cubierta ' . $line->cover_number . ': falta ' . $label . '.';
                }
            }
        }

        if (!empty($missingFields)) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede publicar porque hay cubiertas incompletas.',
                'errors' => $missingFields,
            ], 422);
        }

        $report = DB::transaction(function () use ($draft, $validated, $measurementElement, $user) {
            $report = MeasurementThicknessReport::query()->create([
                'element_id' => $measurementElement->id,
                'report_date' => $validated['report_date'],
                'created_by' => $user?->id,
                'published_at' => now(),
            ]);

            foreach ($draft->lines as $line) {
                MeasurementThicknessReportLine::query()->create([
                    'report_id' => $report->id,
                    'cover_number' => $line->cover_number,
                    'top_left' => $line->top_left,
                    'top_center' => $line->top_center,
                    'top_right' => $line->top_right,
                    'bottom_left' => $line->bottom_left,
                    'bottom_center' => $line->bottom_center,
                    'bottom_right' => $line->bottom_right,
                    'hardness_left' => $line->hardness_left,
                    'hardness_center' => $line->hardness_center,
                    'hardness_right' => $line->hardness_right,
                ]);
            }

            $draft->delete();

            return $report;
        });

        $report->load([
            'lines' => fn ($query) => $query->orderBy('cover_number'),
            'creator:id,name',
        ]);

        $latestReport = MeasurementThicknessReport::query()
            ->with([
                'lines' => fn ($query) => $query->orderBy('cover_number'),
                'creator:id,name',
            ])
            ->where('element_id', $measurementElement->id)
            ->orderByDesc('report_date')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Reporte publicado correctamente. El borrador fue convertido en reporte oficial.',
            'report' => $this->serializeThicknessReport($report),
            'latest_report' => $this->serializeThicknessReport($latestReport),
            'draft' => null,
        ]);
    }

    public function listThicknessReports(int $element): JsonResponse
    {
        $measurementElement = $this->resolveAuthorizedMeasurementElement($element);

        $reports = MeasurementThicknessReport::query()
            ->with(['creator:id,name'])
            ->where('element_id', $measurementElement->id)
            ->orderByDesc('report_date')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'reports' => $reports
                ->map(fn (MeasurementThicknessReport $report) => $this->serializeThicknessReportSummary($report))
                ->values(),
        ]);
    }

    public function showThicknessReport(int $element, int $report): JsonResponse
    {
        $measurementElement = $this->resolveAuthorizedMeasurementElement($element);

        $reportModel = MeasurementThicknessReport::query()
            ->with([
                'lines' => fn ($query) => $query->orderBy('cover_number'),
                'creator:id,name',
            ])
            ->where('element_id', $measurementElement->id)
            ->findOrFail($report);

        return response()->json([
            'success' => true,
            'report' => $this->serializeThicknessReport($reportModel),
        ]);
    }

    protected function serializeThicknessReport(?MeasurementThicknessReport $report): ?array
    {
        if (!$report) {
            return null;
        }

        return [
            'id' => $report->id,
            'element_id' => $report->element_id,
            'report_date' => optional($report->report_date)?->format('Y-m-d'),
            'published_at' => optional($report->published_at)?->format('Y-m-d H:i:s'),
            'published_by' => $report->creator?->name,
            'lines' => $report->lines
                ->sortBy('cover_number')
                ->values()
                ->map(function (MeasurementThicknessReportLine $line) {
                    return [
                        'id' => $line->id,
                        'cover_number' => $line->cover_number,
                        'top_left' => $line->top_left,
                        'top_center' => $line->top_center,
                        'top_right' => $line->top_right,
                        'bottom_left' => $line->bottom_left,
                        'bottom_center' => $line->bottom_center,
                        'bottom_right' => $line->bottom_right,
                        'hardness_left' => $line->hardness_left,
                        'hardness_center' => $line->hardness_center,
                        'hardness_right' => $line->hardness_right,
                    ];
                })
                ->values(),
        ];
    }

    protected function serializeThicknessReportSummary(MeasurementThicknessReport $report): array
    {
        return [
            'id' => $report->id,
            'report_date' => optional($report->report_date)?->format('Y-m-d'),
            'published_at' => optional($report->published_at)?->format('Y-m-d H:i:s'),
            'published_by' => $report->creator?->name,
        ];
    }

    public function createBandStateDraft(int $element): JsonResponse
    {
        $measurementElement = $this->resolveAuthorizedMeasurementElement($element);
        $user = auth()->user();

        $draft = BandStateDraft::query()->firstOrCreate(
            ['element_id' => $measurementElement->id],
            [
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]
        );

        if ($user && !$draft->created_by) {
            $draft->created_by = $user->id;
            $draft->updated_by = $user->id;
            $draft->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Borrador del informe de estado de banda creado correctamente.',
            'draft' => $this->serializeBandStateDraft($draft->fresh()),
        ]);
    }

    public function updateBandStateDraft(Request $request, int $element): JsonResponse
    {
        $measurementElement = $this->resolveAuthorizedMeasurementElement($element);
        $user = auth()->user();

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:255'],
            'width' => ['nullable', 'numeric'],
            'top_cover' => ['nullable', 'numeric'],
            'bottom_cover' => ['nullable', 'numeric'],
        ]);

        $draft = BandStateDraft::query()->firstOrCreate(
            ['element_id' => $measurementElement->id],
            [
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]
        );

        $draft->fill([
            'description' => $validated['description'] ?? null,
            'width' => $validated['width'] ?? null,
            'top_cover' => $validated['top_cover'] ?? null,
            'bottom_cover' => $validated['bottom_cover'] ?? null,
            'updated_by' => $user?->id,
        ]);

        if (!$draft->created_by && $user) {
            $draft->created_by = $user->id;
        }

        $draft->save();

        return response()->json([
            'success' => true,
            'message' => 'Borrador del informe de estado de banda guardado correctamente.',
            'draft' => $this->serializeBandStateDraft($draft->fresh()),
        ]);
    }

    public function publishBandStateDraft(Request $request, int $element): JsonResponse
    {
        $measurementElement = $this->resolveAuthorizedMeasurementElement($element);
        $user = auth()->user();

        $validated = $request->validate([
            'report_date' => ['required', 'date'],
        ]);

        $draft = BandStateDraft::query()
            ->where('element_id', $measurementElement->id)
            ->first();

        if (!$draft) {
            return response()->json([
                'success' => false,
                'message' => 'No existe un borrador del informe de estado de banda para publicar.',
            ], 422);
        }

        $missingFields = [];

        if ($draft->description === null || trim((string) $draft->description) === '') {
            $missingFields[] = 'Falta la descripción.';
        }

        if ($draft->width === null || $draft->width === '') {
            $missingFields[] = 'Falta el ancho.';
        }

        if ($draft->top_cover === null || $draft->top_cover === '') {
            $missingFields[] = 'Falta la cubierta superior.';
        }

        if ($draft->bottom_cover === null || $draft->bottom_cover === '') {
            $missingFields[] = 'Falta la cubierta inferior.';
        }

        if (!empty($missingFields)) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede publicar porque el informe de estado de banda está incompleto.',
                'errors' => $missingFields,
            ], 422);
        }

        $report = DB::transaction(function () use ($draft, $validated, $measurementElement, $user) {
            $report = BandStateReport::query()->create([
                'element_id' => $measurementElement->id,
                'report_date' => $validated['report_date'],
                'description' => $draft->description,
                'width' => $draft->width,
                'top_cover' => $draft->top_cover,
                'bottom_cover' => $draft->bottom_cover,
                'created_by' => $user?->id,
                'published_at' => now(),
            ]);

            $draft->delete();

            return $report;
        });

        $report->load(['creator:id,name']);

        $latestReport = BandStateReport::query()
            ->with(['creator:id,name'])
            ->where('element_id', $measurementElement->id)
            ->orderByDesc('report_date')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Informe de estado de banda publicado correctamente.',
            'report' => $this->serializeBandStateReport($report),
            'latest_report' => $this->serializeBandStateReport($latestReport),
            'draft' => null,
        ]);
    }

    public function listBandStateReports(int $element): JsonResponse
    {
        $measurementElement = $this->resolveAuthorizedMeasurementElement($element);

        $reports = BandStateReport::query()
            ->with(['creator:id,name'])
            ->where('element_id', $measurementElement->id)
            ->orderByDesc('report_date')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'reports' => $reports
                ->map(fn (BandStateReport $report) => $this->serializeBandStateReportSummary($report))
                ->values(),
        ]);
    }

    public function showBandStateReport(int $element, int $report): JsonResponse
    {
        $measurementElement = $this->resolveAuthorizedMeasurementElement($element);

        $reportModel = BandStateReport::query()
            ->with(['creator:id,name'])
            ->where('element_id', $measurementElement->id)
            ->findOrFail($report);

        return response()->json([
            'success' => true,
            'report' => $this->serializeBandStateReport($reportModel),
        ]);
    }
    protected function resolveLatestThicknessMaxHardnessForElement(int $elementId): ?string
    {
        $latestThicknessReport = MeasurementThicknessReport::query()
            ->with(['lines' => fn ($query) => $query->orderBy('cover_number')])
            ->where('element_id', $elementId)
            ->orderByDesc('report_date')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        if (!$latestThicknessReport || $latestThicknessReport->lines->isEmpty()) {
            return null;
        }

        $values = [];

        foreach ($latestThicknessReport->lines as $line) {
            foreach ([
                $line->hardness_left,
                $line->hardness_center,
                $line->hardness_right,
            ] as $value) {
                if ($value !== null && $value !== '') {
                    $values[] = (float) $value;
                }
            }
        }

        if (empty($values)) {
            return null;
        }

        return number_format(max($values), 2, '.', '');
    }

    protected function serializeBandStateDraft(?BandStateDraft $draft): ?array
    {
        if (!$draft) {
            return null;
        }

        return [
            'id' => $draft->id,
            'element_id' => $draft->element_id,
            'description' => $draft->description,
            'width' => $draft->width,
            'top_cover' => $draft->top_cover,
            'bottom_cover' => $draft->bottom_cover,
        ];
    }

    protected function serializeBandStateReport(?BandStateReport $report): ?array
    {
        if (!$report) {
            return null;
        }

        return [
            'id' => $report->id,
            'element_id' => $report->element_id,
            'report_date' => optional($report->report_date)?->format('Y-m-d'),
            'published_at' => optional($report->published_at)?->format('Y-m-d H:i:s'),
            'published_by' => $report->creator?->name,
            'description' => $report->description,
            'width' => $report->width,
            'top_cover' => $report->top_cover,
            'bottom_cover' => $report->bottom_cover,
            'calculated_hardness' => $this->resolveLatestThicknessMaxHardnessForElement($report->element_id),
        ];
    }

    protected function serializeBandStateReportSummary(BandStateReport $report): array
    {
        return [
            'id' => $report->id,
            'report_date' => optional($report->report_date)?->format('Y-m-d'),
            'published_at' => optional($report->published_at)?->format('Y-m-d H:i:s'),
            'published_by' => $report->creator?->name,
        ];
    }

    protected function serializeBandEvent(?BandEvent $event): ?array
    {
        if (!$event) {
            return null;
        }

        return [
            'id' => $event->id,
            'element_id' => $event->element_id,
            'parent_id' => $event->parent_id,
            'type' => $event->type,
            'brand' => $event->brand,
            'width' => $event->width,
            'length' => $event->length,
            'roll_count' => $event->roll_count,
            'temperature' => $event->temperature,
            'pressure' => $event->pressure,
            'time' => $event->time,
            'section_length' => $event->section_length,
            'section_width' => $event->section_width,
            'observation' => $event->observation,
            'report_date' => optional($event->report_date)?->format('Y-m-d'),
            'published_at' => optional($event->published_at)?->format('Y-m-d H:i:s'),
            'status' => (bool) $event->status,
        ];
    }

    protected function serializeBandEventSummary(BandEvent $event): array
    {
        return [
            'id' => $event->id,
            'parent_id' => $event->parent_id,
            'type' => $event->type,
            'brand' => $event->brand,
            'width' => $event->width,
            'length' => $event->length,
            'roll_count' => $event->roll_count,
            'report_date' => optional($event->report_date)?->format('Y-m-d'),
            'published_at' => optional($event->published_at)?->format('Y-m-d H:i:s'),
            'observation' => $event->observation,
        ];
    }
}