<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientElementTypeModule;
use App\Models\Element;
use App\Models\ElementType;
use App\Models\SystemModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminClientElementTypeModuleController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->canManageSystemModule('mediciones'), 403);

        $clients = Client::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $elementTypes = ElementType::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'client_id', 'name']);

        $modules = SystemModule::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'key']);

        $rows = ClientElementTypeModule::query()
            ->with([
                'client:id,name',
                'elementType:id,client_id,name',
                'module:id,name,key',
            ])
            ->where('status', true)
            ->get()
            ->map(function (ClientElementTypeModule $row) {
                $relatedElementsCount = Element::query()
                    ->where('element_type_id', $row->element_type_id)
                    ->where('status', true)
                    ->whereHas('area', function ($query) use ($row) {
                        $query->where('client_id', $row->client_id);
                    })
                    ->count();

                return [
                    'id' => $row->id,
                    'client_name' => $row->client?->name,
                    'element_type_name' => $row->elementType?->name,
                    'module_name' => $row->module?->name,
                    'module_key' => $row->module?->key,
                    'related_elements_count' => $relatedElementsCount,
                    'module_enabled' => $row->module_enabled,
                    'creation_enabled' => $row->creation_enabled,
                ];
            })
            ->sortBy([
                ['client_name', 'asc'],
                ['module_name', 'asc'],
                ['element_type_name', 'asc'],
            ])
            ->values();

        return view('admin.client-element-type-modules.index', [
            'clients' => $clients,
            'elementTypes' => $elementTypes,
            'modules' => $modules,
            'rows' => $rows,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()?->canManageSystemModule('mediciones'), 403);

        $validated = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'element_type_id' => ['required', 'integer', 'exists:element_types,id'],
            'system_module_id' => [
                'required',
                'integer',
                'exists:system_modules,id',
                Rule::unique('client_element_type_modules')->where(function ($query) use ($request) {
                    return $query
                        ->where('client_id', $request->input('client_id'))
                        ->where('element_type_id', $request->input('element_type_id'))
                        ->where('system_module_id', $request->input('system_module_id'));
                }),
            ],
        ], [
            'system_module_id.unique' => 'Ya existe una configuración para este cliente, tipo de activo y módulo.',
        ]);

        $elementType = ElementType::query()->findOrFail($validated['element_type_id']);

        if ((int) $elementType->client_id !== (int) $validated['client_id']) {
            $message = 'El tipo de activo no pertenece al cliente seleccionado.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors' => [
                        'element_type_id' => [$message],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['element_type_id' => $message])
                ->withInput();
        }

        $row = ClientElementTypeModule::create([
            'client_id' => $validated['client_id'],
            'element_type_id' => $validated['element_type_id'],
            'system_module_id' => $validated['system_module_id'],
            'module_enabled' => false,
            'creation_enabled' => false,
            'status' => true,
        ]);

        $row->load([
            'client:id,name',
            'elementType:id,client_id,name',
            'module:id,name,key',
        ]);

        $relatedElementsCount = Element::query()
            ->where('element_type_id', $row->element_type_id)
            ->where('status', true)
            ->whereHas('area', function ($query) use ($row) {
                $query->where('client_id', $row->client_id);
            })
            ->count();

        $payload = [
            'id' => $row->id,
            'client_name' => $row->client?->name,
            'element_type_name' => $row->elementType?->name,
            'module_name' => $row->module?->name,
            'module_key' => $row->module?->key,
            'related_elements_count' => $relatedElementsCount,
            'module_enabled' => $row->module_enabled,
            'creation_enabled' => $row->creation_enabled,
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Configuración creada correctamente.',
                'row' => $payload,
            ]);
        }

        return redirect()
            ->route('admin.client-element-type-modules.index')
            ->with('success', 'Configuración creada correctamente.');
    }

    public function toggleModuleEnabled(ClientElementTypeModule $clientElementTypeModule): JsonResponse
    {
        abort_unless(auth()->user()?->canManageSystemModule('mediciones'), 403);

        $newModuleEnabled = !$clientElementTypeModule->module_enabled;

        $clientElementTypeModule->update([
            'module_enabled' => $newModuleEnabled,
            'creation_enabled' => $newModuleEnabled ? $clientElementTypeModule->creation_enabled : false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Estado del módulo actualizado correctamente.',
            'module_enabled' => $clientElementTypeModule->fresh()->module_enabled,
            'creation_enabled' => $clientElementTypeModule->fresh()->creation_enabled,
        ]);
    }

    public function toggleCreationEnabled(ClientElementTypeModule $clientElementTypeModule): JsonResponse
    {
        abort_unless(auth()->user()?->canManageSystemModule('mediciones'), 403);

        if (!$clientElementTypeModule->module_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes habilitar creación si el módulo está inactivo.',
            ], 422);
        }

        $clientElementTypeModule->update([
            'creation_enabled' => !$clientElementTypeModule->creation_enabled,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Estado de creación actualizado correctamente.',
            'module_enabled' => $clientElementTypeModule->module_enabled,
            'creation_enabled' => $clientElementTypeModule->creation_enabled,
        ]);
    }
}