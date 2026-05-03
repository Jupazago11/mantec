<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Client;
use App\Models\Component;
use App\Models\Condition;
use App\Models\Diagnostic;
use App\Models\Element;
use App\Models\ClientElementTypeModule;
use App\Models\SystemModule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InspectorOfflineCatalogController extends Controller
{
    public function show(): JsonResponse
    {
        $user = Auth::user();

        if (($user->role?->key ?? null) !== 'inspector') {
            return response()->json([
                'success' => false,
                'message' => 'Este usuario no tiene acceso al catálogo móvil de inspector.',
            ], 403);
        }

        $assignedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->values();

        if ($assignedClientIds->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'El inspector no tiene cliente asignado.',
            ], 422);
        }

        if ($assignedClientIds->count() > 1) {
            return response()->json([
                'success' => false,
                'message' => 'El inspector tiene múltiples clientes asignados. La app móvil requiere un único cliente por inspector.',
            ], 422);
        }

        $clientId = (int) $assignedClientIds->first();

        $client = Client::query()
            ->where('id', $clientId)
            ->where('status', true)
            ->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró un cliente activo para el inspector.',
            ], 422);
        }

        $assignedGroups = $user->groups()
            ->where('groups.client_id', $clientId)
            ->where('groups.status', true)
            ->orderBy('groups.name')
            ->get([
                'groups.id',
                'groups.client_id',
                'groups.name',
                'groups.description',
                'groups.auto_sync',
                'groups.status',
            ]);

        if ($assignedGroups->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'El inspector no tiene una agrupación activa asignada.',
            ], 422);
        }

        if ($assignedGroups->count() > 1) {
            return response()->json([
                'success' => false,
                'message' => 'El inspector tiene múltiples agrupaciones activas asignadas. La app móvil requiere una única agrupación por inspector.',
            ], 422);
        }

        $group = $assignedGroups->first();

        $elements = $this->resolveElementsForInspectorGroup(
            clientId: $clientId,
            groupId: (int) $group->id
        );

        if ($elements->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Catálogo offline cargado correctamente. La agrupación no tiene activos asociados.',
                'client' => [
                    'id' => (int) $client->id,
                    'name' => (string) $client->name,
                    'obs' => $client->obs,
                    'status' => (bool) $client->status,
                ],
                'group' => [
                    'id' => (int) $group->id,
                    'client_id' => (int) $group->client_id,
                    'name' => (string) $group->name,
                    'description' => $group->description,
                    'auto_sync' => (bool) $group->auto_sync,
                    'status' => (bool) $group->status,
                ],
                'element_types' => [],
                'areas' => [],
                'conditions' => [],
                'elements' => [],
                'components' => [],
                'diagnostics' => [],
                'element_component_relations' => [],
                'component_diagnostic_relations' => [],
                'component_condition_relations' => [],
                'measurement_element_types' => [],
            ]);
        }

        $areaIds = $elements->pluck('area_id')->unique()->values();
        $elementTypeIds = $elements->pluck('element_type_id')->unique()->values();
        $elementIds = $elements->pluck('id')->unique()->values();

        $areas = Area::query()
            ->where('client_id', $clientId)
            ->where('status', true)
            ->whereIn('id', $areaIds)
            ->orderBy('name')
            ->get([
                'id',
                'client_id',
                'name',
                'code',
                'status',
            ]);

        $elementTypes = DB::table('element_types')
            ->where('client_id', $clientId)
            ->where('status', true)
            ->whereIn('id', $elementTypeIds)
            ->orderBy('name')
            ->get([
                'id',
                'client_id',
                'name',
                'description',
                'status',
            ]);

        $elementComponentRelations = DB::table('element_components')
            ->whereIn('element_id', $elementIds)
            ->get([
                'element_id',
                'component_id',
            ]);

        $componentIds = $elementComponentRelations
            ->pluck('component_id')
            ->unique()
            ->values();

        $components = Component::query()
            ->whereIn('id', $componentIds)
            ->whereIn('element_type_id', $elementTypeIds)
            ->where('status', true)
            ->orderBy('element_type_id')
            ->orderBy('name')
            ->get([
                'id',
                'client_id',
                'name',
                'code',
                'element_type_id',
                'is_required',
                'is_default',
                'status',
            ]);

        $validComponentIds = $components
            ->pluck('id')
            ->unique()
            ->values();

        $elementComponentRelations = $elementComponentRelations
            ->whereIn('component_id', $validComponentIds)
            ->values();

        $diagnosticIds = DB::table('component_diagnostics')
            ->whereIn('component_id', $validComponentIds)
            ->pluck('diagnostic_id')
            ->unique()
            ->values();

        $diagnostics = Diagnostic::query()
            ->whereIn('id', $diagnosticIds)
            ->whereIn('element_type_id', $elementTypeIds)
            ->where('status', true)
            ->orderBy('element_type_id')
            ->orderBy('name')
            ->get([
                'id',
                'client_id',
                'element_type_id',
                'name',
                'description',
                'status',
            ]);

        $validDiagnosticIds = $diagnostics
            ->pluck('id')
            ->unique()
            ->values();

        $componentDiagnosticRelations = DB::table('component_diagnostics')
            ->whereIn('component_id', $validComponentIds)
            ->whereIn('diagnostic_id', $validDiagnosticIds)
            ->get([
                'component_id',
                'diagnostic_id',
            ]);

        $conditionIds = DB::table('component_conditions')
            ->whereIn('component_id', $validComponentIds)
            ->pluck('condition_id')
            ->unique()
            ->values();

        $conditions = Condition::query()
            ->where('client_id', $clientId)
            ->whereIn('element_type_id', $elementTypeIds)
            ->whereIn('id', $conditionIds)
            ->where('status', true)
            ->orderBy('element_type_id')
            ->orderBy('severity')
            ->orderBy('name')
            ->get([
                'id',
                'client_id',
                'element_type_id',
                'name',
                'code',
                'description',
                'severity',
                'color',
                'status',
            ]);

        $validConditionIds = $conditions
            ->pluck('id')
            ->unique()
            ->values();

        $componentConditionRelations = DB::table('component_conditions')
            ->whereIn('component_id', $validComponentIds)
            ->whereIn('condition_id', $validConditionIds)
            ->get([
                'component_id',
                'condition_id',
            ]);

        $measurementModuleId = $this->resolveMeasurementModuleId();

        $measurementElementTypes = collect();

        if ($measurementModuleId) {
            $measurementElementTypes = ClientElementTypeModule::query()
                ->with('elementType:id,client_id,name,description,status')
                ->where('client_id', $clientId)
                ->where('system_module_id', $measurementModuleId)
                ->where('module_enabled', true)
                ->where('creation_enabled', true)
                ->where('status', true)
                ->whereIn('element_type_id', $elementTypeIds)
                ->get()
                ->filter(fn ($config) => $config->elementType !== null)
                ->map(function ($config) {
                    return [
                        'element_type_id' => (int) $config->element_type_id,
                        'client_id' => (int) $config->client_id,
                        'name' => (string) $config->elementType->name,
                        'description' => $config->elementType->description,
                        'module_enabled' => (bool) $config->module_enabled,
                        'creation_enabled' => (bool) $config->creation_enabled,
                        'status' => (bool) $config->status,
                    ];
                })
                ->sortBy('name')
                ->values();
        }

        return response()->json([
            'success' => true,
            'message' => 'Catálogo offline cargado correctamente.',
            'client' => [
                'id' => (int) $client->id,
                'name' => (string) $client->name,
                'obs' => $client->obs,
                'status' => (bool) $client->status,
            ],
            'group' => [
                'id' => (int) $group->id,
                'client_id' => (int) $group->client_id,
                'name' => (string) $group->name,
                'description' => $group->description,
                'auto_sync' => (bool) $group->auto_sync,
                'status' => (bool) $group->status,
            ],
            'element_types' => $elementTypes->map(function ($item) {
                return [
                    'id' => (int) $item->id,
                    'client_id' => (int) $item->client_id,
                    'name' => (string) $item->name,
                    'description' => $item->description,
                    'status' => (bool) $item->status,
                ];
            })->values(),
            'areas' => $areas->map(function ($item) {
                return [
                    'id' => (int) $item->id,
                    'client_id' => (int) $item->client_id,
                    'name' => (string) $item->name,
                    'code' => (string) ($item->code ?? ''),
                    'status' => (bool) $item->status,
                ];
            })->values(),
            'conditions' => $conditions->map(function ($item) {
                return [
                    'id' => (int) $item->id,
                    'client_id' => (int) $item->client_id,
                    'element_type_id' => (int) $item->element_type_id,
                    'name' => (string) $item->name,
                    'code' => (string) $item->code,
                    'description' => $item->description,
                    'severity' => (int) $item->severity,
                    'color' => (string) ($item->color ?? ''),
                    'status' => (bool) $item->status,
                ];
            })->values(),
            'elements' => $elements->map(function ($item) {
                return [
                    'id' => (int) $item->id,
                    'area_id' => (int) $item->area_id,
                    'group_id' => (int) $item->group_id,
                    'element_type_id' => (int) $item->element_type_id,
                    'name' => (string) $item->name,
                    'code' => (string) ($item->code ?? $item->name),
                    'warehouse_code' => $item->warehouse_code,
                    'status' => (bool) $item->status,
                ];
            })->values(),
            'components' => $components->map(function ($item) {
                return [
                    'id' => (int) $item->id,
                    'client_id' => (int) ($item->client_id ?? 0),
                    'name' => (string) $item->name,
                    'code' => $item->code,
                    'element_type_id' => (int) $item->element_type_id,
                    'is_required' => (bool) ($item->is_required ?? false),
                    'is_default' => (bool) ($item->is_default ?? false),
                    'status' => (bool) $item->status,
                ];
            })->values(),
            'diagnostics' => $diagnostics->map(function ($item) {
                return [
                    'id' => (int) $item->id,
                    'client_id' => (int) ($item->client_id ?? 0),
                    'element_type_id' => (int) $item->element_type_id,
                    'name' => (string) $item->name,
                    'description' => $item->description,
                    'status' => (bool) $item->status,
                ];
            })->values(),
            'element_component_relations' => $elementComponentRelations
                ->filter(function ($item) {
                    return is_object($item)
                        && isset($item->element_id)
                        && isset($item->component_id)
                        && is_numeric($item->element_id)
                        && is_numeric($item->component_id);
                })
                ->map(function ($item) {
                    return [
                        'element_id' => (int) $item->element_id,
                        'component_id' => (int) $item->component_id,
                    ];
                })
                ->values(),
            'component_diagnostic_relations' => $componentDiagnosticRelations
                ->filter(function ($item) {
                    return is_object($item)
                        && isset($item->component_id)
                        && isset($item->diagnostic_id)
                        && is_numeric($item->component_id)
                        && is_numeric($item->diagnostic_id);
                })
                ->map(function ($item) {
                    return [
                        'component_id' => (int) $item->component_id,
                        'diagnostic_id' => (int) $item->diagnostic_id,
                    ];
                })
                ->values(),
            'component_condition_relations' => $componentConditionRelations
                ->filter(function ($item) {
                    return is_object($item)
                        && isset($item->component_id)
                        && isset($item->condition_id)
                        && is_numeric($item->component_id)
                        && is_numeric($item->condition_id);
                })
                ->map(function ($item) {
                    return [
                        'component_id' => (int) $item->component_id,
                        'condition_id' => (int) $item->condition_id,
                    ];
                })
                ->values(),
            'measurement_element_types' => $measurementElementTypes,
        ]);
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

    private function resolveElementsForInspectorGroup(int $clientId, int $groupId)
    {
        $pivotElementIds = $this->resolveElementIdsFromGroupPivot($groupId);

        return Element::query()
            ->with([
                'area:id,client_id,name,code,status',
                'elementType:id,client_id,name,description,status',
            ])
            ->where('status', true)
            ->whereHas('area', function (Builder $query) use ($clientId) {
                $query->where('client_id', $clientId)
                    ->where('status', true);
            })
            ->whereHas('elementType', function (Builder $query) use ($clientId) {
                $query->where('client_id', $clientId)
                    ->where('status', true);
            })
            ->where(function (Builder $query) use ($groupId, $pivotElementIds) {
                /*
                * Soporta ambos modelos:
                *
                * 1. Modelo directo:
                *    elements.group_id = groups.id
                *
                * 2. Modelo por relación/pivote:
                *    group_elements / element_group / equivalente.
                *
                * Esto evita romper reportes preventivos cuando la agrupación
                * no depende únicamente de elements.group_id.
                */
                if (Schema::hasColumn('elements', 'group_id')) {
                    $query->where('group_id', $groupId);
                }

                if ($pivotElementIds->isNotEmpty()) {
                    if (Schema::hasColumn('elements', 'group_id')) {
                        $query->orWhereIn('id', $pivotElementIds);
                    } else {
                        $query->whereIn('id', $pivotElementIds);
                    }
                }
            })
            ->orderBy('area_id')
            ->orderBy('name')
            ->get([
                'id',
                'area_id',
                'group_id',
                'element_type_id',
                'name',
                'code',
                'warehouse_code',
                'status',
            ]);
    }

    private function resolveElementIdsFromGroupPivot(int $groupId)
    {
        /*
        * Candidatos defensivos. Solo se usa la tabla que exista realmente.
        * Esto permite mantener compatibilidad si el proyecto cambió de
        * elements.group_id a una relación independiente agrupación-activo.
        */
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
}