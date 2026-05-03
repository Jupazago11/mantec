<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\ClientElementTypeModule;
use App\Models\Element;
use App\Models\ElementType;
use App\Models\MeasurementThicknessDraft;
use App\Models\MeasurementThicknessDraftLine;
use App\Models\MeasurementThicknessReport;
use App\Models\SystemModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class InspectorMeasurementThicknessController extends Controller
{
    public function elementTypes(): JsonResponse
    {
        $user = Auth::user();
        $client = $this->resolveInspectorClient($user);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'El inspector no tiene cliente asignado.',
            ], 403);
        }

        $group = $this->resolveInspectorGroup($user, $client->id);

        if (!$group) {
            return response()->json([
                'success' => false,
                'message' => 'El inspector no tiene agrupación asignada para este cliente.',
            ], 403);
        }

        $measurementModuleId = $this->resolveMeasurementModuleId();

        if (!$measurementModuleId) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el módulo de mediciones configurado.',
                'element_types' => [],
            ]);
        }

        $elementTypeIdsWithElements = Element::query()
            ->where('group_id', $group->id)
            ->where('status', true)
            ->distinct()
            ->pluck('element_type_id');

        $items = ClientElementTypeModule::query()
            ->with('elementType')
            ->where('client_id', $client->id)
            ->where('system_module_id', $measurementModuleId)
            ->where('module_enabled', true)
            ->where('creation_enabled', true)
            ->where('status', true)
            ->whereIn('element_type_id', $elementTypeIdsWithElements)
            ->get()
            ->filter(fn ($config) => $config->elementType !== null)
            ->map(function ($config) {
                return [
                    'element_type_id' => (int) $config->element_type_id,
                    'name' => (string) $config->elementType->name,
                    'module_enabled' => (bool) $config->module_enabled,
                    'creation_enabled' => (bool) $config->creation_enabled,
                ];
            })
            ->sortBy('name')
            ->values();

        return response()->json([
            'success' => true,
            'client' => [
                'id' => (int) $client->id,
                'name' => (string) $client->name,
            ],
            'group' => [
                'id' => (int) $group->id,
                'name' => (string) $group->name,
            ],
            'element_types' => $items,
        ]);
    }

    public function areasByElementType(ElementType $elementType): JsonResponse
    {
        $user = Auth::user();
        $client = $this->resolveInspectorClient($user);
        $group = $client ? $this->resolveInspectorGroup($user, $client->id) : null;

        if (!$client || !$group) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a esta información.',
            ], 403);
        }

        $this->abortUnlessMeasurementCreationEnabled($client->id, $elementType->id);

        $areaIds = Element::query()
            ->where('group_id', $group->id)
            ->where('element_type_id', $elementType->id)
            ->where('status', true)
            ->distinct()
            ->pluck('area_id');

        $areas = Area::query()
            ->where('client_id', $client->id)
            ->whereIn('id', $areaIds)
            ->where('status', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($area) => [
                'id' => (int) $area->id,
                'name' => (string) $area->name,
                'code' => $area->code,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'element_type' => [
                'id' => (int) $elementType->id,
                'name' => (string) $elementType->name,
            ],
            'areas' => $areas,
        ]);
    }

    public function elementsByAreaAndElementType(Area $area, ElementType $elementType): JsonResponse
    {
        $user = Auth::user();
        $client = $this->resolveInspectorClient($user);
        $group = $client ? $this->resolveInspectorGroup($user, $client->id) : null;

        if (!$client || !$group || (int) $area->client_id !== (int) $client->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a esta área.',
            ], 403);
        }

        $this->abortUnlessMeasurementCreationEnabled($client->id, $elementType->id);

        $elements = Element::query()
            ->where('group_id', $group->id)
            ->where('area_id', $area->id)
            ->where('element_type_id', $elementType->id)
            ->where('status', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($element) => [
                'id' => (int) $element->id,
                'name' => (string) $element->name,
                'code' => $element->code,
                'warehouse_code' => $element->warehouse_code,
                'area_id' => (int) $element->area_id,
                'element_type_id' => (int) $element->element_type_id,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'area' => [
                'id' => (int) $area->id,
                'name' => (string) $area->name,
            ],
            'element_type' => [
                'id' => (int) $elementType->id,
                'name' => (string) $elementType->name,
            ],
            'elements' => $elements,
        ]);
    }

    public function showThickness(Element $element): JsonResponse
    {
        $this->abortUnlessCanUseElementForMeasurements($element);

        $draft = MeasurementThicknessDraft::query()
            ->with('lines')
            ->where('element_id', $element->id)
            ->first();

        $latestReport = MeasurementThicknessReport::query()
            ->with('lines')
            ->where('element_id', $element->id)
            ->orderByDesc('report_date')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        $historicalReports = MeasurementThicknessReport::query()
            ->where('element_id', $element->id)
            ->orderByDesc('report_date')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'element' => $this->elementPayload($element),
            'draft' => $draft ? $this->draftPayload($draft) : null,
            'latest_report' => $latestReport ? $this->reportPayload($latestReport, true) : null,
            'historical_reports' => $historicalReports
                ->map(fn ($report) => $this->reportPayload($report, false))
                ->values(),
        ]);
    }

    public function syncDraft(Request $request, Element $element): JsonResponse
    {
        $user = Auth::user();

        $this->abortUnlessCanUseElementForMeasurements($element);

        $validated = $request->validate([
            'last_known_updated_at' => ['nullable', 'date'],
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

        $lines = collect($validated['lines'])
            ->sortBy('cover_number')
            ->values();

        $duplicatedCover = $lines
            ->groupBy('cover_number')
            ->first(fn ($group) => $group->count() > 1);

        if ($duplicatedCover) {
            throw ValidationException::withMessages([
                'lines' => 'No puede haber cubiertas repetidas.',
            ]);
        }

        $draft = MeasurementThicknessDraft::query()
            ->where('element_id', $element->id)
            ->first();

        if (
            $draft &&
            !empty($validated['last_known_updated_at']) &&
            $draft->updated_at &&
            $draft->updated_at->gt($validated['last_known_updated_at'])
        ) {
            return response()->json([
                'success' => false,
                'conflict' => true,
                'message' => 'El borrador fue modificado desde otro dispositivo o desde la web. Actualiza la información antes de sobrescribir.',
                'draft' => $this->draftPayload($draft->load('lines')),
            ], 409);
        }

        $draft = DB::transaction(function () use ($element, $user, $lines, $draft) {
            if (!$draft) {
                $draft = MeasurementThicknessDraft::query()->create([
                    'element_id' => $element->id,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            } else {
                $draft->update([
                    'updated_by' => $user->id,
                ]);
            }

            MeasurementThicknessDraftLine::query()
                ->where('draft_id', $draft->id)
                ->delete();

            foreach ($lines as $index => $line) {
                MeasurementThicknessDraftLine::query()->create([
                    'draft_id' => $draft->id,
                    'cover_number' => $index + 1,

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

            return $draft->fresh('lines');
        });

        return response()->json([
            'success' => true,
            'message' => 'Borrador sincronizado correctamente.',
            'draft' => $this->draftPayload($draft),
        ]);
    }

    private function abortUnlessCanUseElementForMeasurements(Element $element): void
    {
        $user = Auth::user();

        $element->loadMissing('area', 'group', 'elementType');

        $client = $this->resolveInspectorClient($user);

        abort_unless($client, 403);
        abort_unless($element->area, 403);
        abort_unless((int) $element->area->client_id === (int) $client->id, 403);

        $group = $this->resolveInspectorGroup($user, $client->id);

        abort_unless($group, 403);
        abort_unless((int) $element->group_id === (int) $group->id, 403);

        $this->abortUnlessMeasurementCreationEnabled(
            clientId: $client->id,
            elementTypeId: $element->element_type_id
        );
    }

    private function abortUnlessMeasurementCreationEnabled(int $clientId, int $elementTypeId): void
    {
        $measurementModuleId = $this->resolveMeasurementModuleId();

        abort_unless($measurementModuleId, 403);

        $allowed = ClientElementTypeModule::query()
            ->where('client_id', $clientId)
            ->where('element_type_id', $elementTypeId)
            ->where('system_module_id', $measurementModuleId)
            ->where('module_enabled', true)
            ->where('creation_enabled', true)
            ->where('status', true)
            ->exists();

        abort_unless($allowed, 403);
    }

    private function resolveMeasurementModuleId(): ?int
    {
        $query = SystemModule::query();

        $query->where(function ($q) {
            if (Schema::hasColumn('system_modules', 'key')) {
                $q->orWhere('key', 'measurements');
            }

            if (Schema::hasColumn('system_modules', 'slug')) {
                $q->orWhere('slug', 'measurements');
            }

            if (Schema::hasColumn('system_modules', 'name')) {
                $q->orWhere('name', 'like', '%Mediciones%')
                  ->orWhere('name', 'like', '%Measurements%');
            }
        });

        return $query->value('id');
    }

    private function resolveInspectorClient($user)
    {
        if (method_exists($user, 'clients')) {
            return $user->clients()
                ->where('clients.status', true)
                ->orderBy('clients.id')
                ->first();
        }

        return null;
    }

    private function resolveInspectorGroup($user, int $clientId)
    {
        if (method_exists($user, 'groups')) {
            return $user->groups()
                ->where('groups.client_id', $clientId)
                ->where('groups.status', true)
                ->orderBy('groups.id')
                ->first();
        }

        return null;
    }

    private function elementPayload(Element $element): array
    {
        $element->loadMissing('area', 'elementType', 'group');

        return [
            'id' => (int) $element->id,
            'name' => (string) $element->name,
            'code' => $element->code,
            'warehouse_code' => $element->warehouse_code,
            'area' => $element->area ? [
                'id' => (int) $element->area->id,
                'name' => (string) $element->area->name,
            ] : null,
            'element_type' => $element->elementType ? [
                'id' => (int) $element->elementType->id,
                'name' => (string) $element->elementType->name,
            ] : null,
            'group' => $element->group ? [
                'id' => (int) $element->group->id,
                'name' => (string) $element->group->name,
            ] : null,
        ];
    }

    private function draftPayload(MeasurementThicknessDraft $draft): array
    {
        $draft->loadMissing('lines');

        return [
            'id' => (int) $draft->id,
            'element_id' => (int) $draft->element_id,
            'created_by' => $draft->created_by ? (int) $draft->created_by : null,
            'updated_by' => $draft->updated_by ? (int) $draft->updated_by : null,
            'created_at' => optional($draft->created_at)->toISOString(),
            'updated_at' => optional($draft->updated_at)->toISOString(),
            'lines' => $draft->lines
                ->map(fn ($line) => $this->linePayload($line))
                ->values(),
        ];
    }

    private function reportPayload(MeasurementThicknessReport $report, bool $includeLines): array
    {
        if ($includeLines) {
            $report->loadMissing('lines');
        }

        return [
            'id' => (int) $report->id,
            'element_id' => (int) $report->element_id,
            'report_date' => optional($report->report_date)->format('Y-m-d'),
            'published_at' => optional($report->published_at)->toISOString(),
            'notes' => $report->notes,
            'lines' => $includeLines
                ? $report->lines->map(fn ($line) => $this->linePayload($line))->values()
                : [],
        ];
    }

    private function linePayload($line): array
    {
        return [
            'id' => (int) $line->id,
            'cover_number' => (int) $line->cover_number,

            'top_left' => $line->top_left !== null ? (float) $line->top_left : null,
            'top_center' => $line->top_center !== null ? (float) $line->top_center : null,
            'top_right' => $line->top_right !== null ? (float) $line->top_right : null,

            'bottom_left' => $line->bottom_left !== null ? (float) $line->bottom_left : null,
            'bottom_center' => $line->bottom_center !== null ? (float) $line->bottom_center : null,
            'bottom_right' => $line->bottom_right !== null ? (float) $line->bottom_right : null,

            'hardness_left' => $line->hardness_left !== null ? (float) $line->hardness_left : null,
            'hardness_center' => $line->hardness_center !== null ? (float) $line->hardness_center : null,
            'hardness_right' => $line->hardness_right !== null ? (float) $line->hardness_right : null,
        ];
    }
}