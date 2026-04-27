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

        $allowedElementTypes = $user->allowedElementTypesForClient($clientId)
            ->where('element_types.status', true)
            ->get([
                'element_types.id',
                'element_types.client_id',
                'element_types.name',
                'element_types.description',
                'element_types.status',
            ]);

        if ($allowedElementTypes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'El inspector no tiene tipos de activo permitidos para este cliente.',
            ], 422);
        }

        $allowedElementTypeIds = $allowedElementTypes
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $elements = Element::query()
            ->whereHas('area', function ($query) use ($clientId) {
                $query->where('client_id', $clientId)
                    ->where('status', true);
            })
            ->whereIn('element_type_id', $allowedElementTypeIds)
            ->where('status', true)
            ->orderBy('name')
            ->get([
                'id',
                'area_id',
                'element_type_id',
                'name',
                'code',
                'warehouse_code',
                'status',
            ]);

        $areas = Area::query()
            ->where('client_id', $clientId)
            ->where('status', true)
            ->whereIn('id', $elements->pluck('area_id')->unique()->values())
            ->orderBy('name')
            ->get([
                'id',
                'client_id',
                'name',
                'code',
                'status',
            ]);

        $conditions = Condition::query()
            ->where('client_id', $clientId)
            ->whereIn('element_type_id', $allowedElementTypeIds)
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

        $components = Component::query()
            ->whereIn('element_type_id', $allowedElementTypeIds)
            ->where('status', true)
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

        $elementComponentRelations = DB::table('element_components')
            ->whereIn('element_id', $elements->pluck('id'))
            ->whereIn('component_id', $components->pluck('id'))
            ->get([
                'element_id',
                'component_id',
            ]);

        $diagnosticIds = DB::table('component_diagnostics')
            ->whereIn('component_id', $components->pluck('id'))
            ->pluck('diagnostic_id')
            ->unique()
            ->values();

        $diagnostics = Diagnostic::query()
            ->whereIn('id', $diagnosticIds)
            ->whereIn('element_type_id', $allowedElementTypeIds)
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

        $componentDiagnosticRelations = DB::table('component_diagnostics')
            ->whereIn('component_id', $components->pluck('id'))
            ->whereIn('diagnostic_id', $diagnostics->pluck('id'))
            ->get([
                'component_id',
                'diagnostic_id',
            ]);

        $componentConditionRelations = DB::table('component_conditions')
            ->whereIn('component_id', $components->pluck('id'))
            ->whereIn('condition_id', $conditions->pluck('id'))
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
            'element_types' => $allowedElementTypes->map(function ($item) {
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
