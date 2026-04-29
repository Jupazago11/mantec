<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Client;
use App\Models\Component;
use App\Models\Condition;
use App\Models\Diagnostic;
use App\Models\Element;
use Illuminate\Http\JsonResponse;
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

        $elements = Element::query()
            ->with([
                'area:id,client_id,name,code,status',
                'elementType:id,client_id,name,description,status',
            ])
            ->where('group_id', $group->id)
            ->where('status', true)
            ->whereHas('area', function ($query) use ($clientId) {
                $query->where('client_id', $clientId)
                    ->where('status', true);
            })
            ->whereHas('elementType', function ($query) use ($clientId) {
                $query->where('client_id', $clientId)
                    ->where('status', true);
            })
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
            'element_component_relations' => $elementComponentRelations->map(function ($item) {
                return [
                    'element_id' => (int) $item->element_id,
                    'component_id' => (int) $item->component_id,
                ];
            })->values(),
            'component_diagnostic_relations' => $componentDiagnosticRelations->map(function ($item) {
                return [
                    'component_id' => (int) $item->component_id,
                    'diagnostic_id' => (int) $item->diagnostic_id,
                ];
            })->values(),
            'component_condition_relations' => $componentConditionRelations->map(function ($item) {
                return [
                    'component_id' => (int) $item->component_id,
                    'condition_id' => (int) $item->condition_id,
                ];
            })->values(),
        ]);
    }
}