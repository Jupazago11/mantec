<?php

namespace App\Http\Controllers\Admin\SystemModules;

use App\Http\Controllers\Controller;
use App\Models\ClientElementTypeModule;
use App\Models\Element;
use App\Models\MeasurementThicknessDraft;
use App\Models\MeasurementThicknessDraftLine;
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

        return view('admin.system-modules.measurements.show', [
            'element' => $measurementElement,
            'client' => $measurementElement->area->client,
            'area' => $measurementElement->area,
            'elementType' => $measurementElement->elementType,
            'thicknessDraft' => $draft,
            'thicknessDraftData' => $this->serializeThicknessDraft($draft),
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
}