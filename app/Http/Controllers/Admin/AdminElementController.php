<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Client;
use App\Models\Component;
use App\Models\Element;
use App\Models\Group;
use App\Models\ElementType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminElementController extends Controller
{
    public function index(Request $request): View
    {
        $clients = $this->getScopedClients();
        $clientIds = $clients->pluck('id')->all();

        $showClientColumn = $clients->count() > 1;
        $singleClient = $clients->count() === 1 ? $clients->first() : null;

        $areas = Area::query()
            ->with('client')
            ->whereIn('client_id', $clients->pluck('id'))
            ->where('status', true)
            ->orderBy('name')
            ->get();

         $elementTypes = ElementType::query()
            ->whereIn('client_id', $clientIds)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $groupsByClient = Group::query()
            ->whereIn('client_id', $clientIds)
            ->where('status', true)
            ->orderBy('name')
            ->get()
            ->groupBy('client_id');

        $components = Component::query()
            ->whereIn('client_id', $clientIds)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $selectedClientIds = $showClientColumn
            ? collect($request->input('client_ids', []))
                ->filter()
                ->map(fn ($id) => (string) $id)
                ->values()
                ->all()
            : ($singleClient ? [(string) $singleClient->id] : []);

        $selectedAreaIds = collect($request->input('area_ids', []))
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        $selectedElementTypeIds = collect($request->input('element_type_ids', []))
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        $selectedNames = collect($request->input('names', []))
            ->filter()
            ->map(fn ($name) => (string) $name)
            ->values()
            ->all();

        $selectedWarehouseCodes = collect($request->input('warehouse_codes', []))
            ->filter()
            ->map(fn ($code) => (string) $code)
            ->values()
            ->all();

        $selectedStatuses = collect($request->input('statuses', []))
            ->filter(fn ($status) => $status !== null && $status !== '')
            ->map(fn ($status) => (string) $status)
            ->values()
            ->all();

        $baseQuery = Element::query()
            ->with(['area.client', 'elementType', 'group', 'components'])
            ->withCount(['components', 'reportDetails'])
            ->whereHas('area', function ($query) use ($clients) {
                $query->whereIn('client_id', $clients->pluck('id'));
            });

        if (!empty($selectedClientIds)) {
            $baseQuery->whereHas('area', function ($query) use ($selectedClientIds) {
                $query->whereIn('client_id', $selectedClientIds);
            });
        }

        if (!empty($selectedAreaIds)) {
            $baseQuery->whereIn('area_id', $selectedAreaIds);
        }

        if (!empty($selectedElementTypeIds)) {
            $baseQuery->whereIn('element_type_id', $selectedElementTypeIds);
        }

        if (!empty($selectedNames)) {
            $baseQuery->whereIn('name', $selectedNames);
        }

        if (!empty($selectedWarehouseCodes)) {
            $baseQuery->whereIn('warehouse_code', $selectedWarehouseCodes);
        }

        if (!empty($selectedStatuses)) {
            $baseQuery->whereIn('status', array_map(fn ($value) => (int) $value, $selectedStatuses));
        }

        $elements = (clone $baseQuery)
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        $filterClientOptions = $showClientColumn
            ? $clients->map(fn ($client) => [
                'value' => (string) $client->id,
                'label' => $client->name,
            ])->values()
            : collect();

        $filterAreaOptions = $areas->map(fn ($area) => [
            'value' => (string) $area->id,
            'label' => $showClientColumn ? (($area->client?->name ?? '—') . ' - ' . $area->name) : $area->name,
        ])->values();

        $filterElementTypeOptions = $elementTypes->map(fn ($type) => [
            'value' => (string) $type->id,
            'label' => $type->name,
        ])->values();

        $filterNameOptions = Element::query()
            ->whereHas('area', function ($query) use ($clients) {
                $query->whereIn('client_id', $clients->pluck('id'));
            })
            ->pluck('name')
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $filterWarehouseOptions = Element::query()
            ->whereHas('area', function ($query) use ($clients) {
                $query->whereIn('client_id', $clients->pluck('id'));
            })
            ->pluck('warehouse_code')
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $filterStatusOptions = collect([
            ['value' => '1', 'label' => 'Activo'],
            ['value' => '0', 'label' => 'Inactivo'],
        ]);

        $filterOptions = [
            'client_ids' => $filterClientOptions,
            'area_ids' => $filterAreaOptions,
            'element_type_ids' => $filterElementTypeOptions,
            'names' => $filterNameOptions,
            'warehouse_codes' => $filterWarehouseOptions,
            'statuses' => $filterStatusOptions,
        ];

        $activeFilters = [
            'client_ids' => $selectedClientIds,
            'area_ids' => $selectedAreaIds,
            'element_type_ids' => $selectedElementTypeIds,
            'names' => $selectedNames,
            'warehouse_codes' => $selectedWarehouseCodes,
            'statuses' => $selectedStatuses,
        ];

        return view('admin.managed-elements.index', [
            'clients' => $clients,
            'singleClient' => $singleClient,
            'areas' => $areas,
            'elementTypes' => $elementTypes,
            'components' => $components,
            'elements' => $elements,
            'groupsByClient' => $groupsByClient,
            'filterOptions' => $filterOptions,
            'activeFilters' => $activeFilters,
            'showClientColumn' => $showClientColumn,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        $validated = $request->validate([
            'client_id' => ['required', 'integer', Rule::in($allowedClientIds)],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'element_type_id' => ['required', 'integer', 'exists:element_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'code' => ['nullable', 'string', 'max:255'],
            'warehouse_code' => ['nullable', 'string', 'max:255'],
        ]);

        $area = Area::findOrFail($validated['area_id']);
        if ((int) $area->client_id !== (int) $validated['client_id']) {
            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El área no pertenece al cliente seleccionado.',
                    'errors' => [
                        'area_id' => ['El área no pertenece al cliente seleccionado.'],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['area_id' => 'El área no pertenece al cliente seleccionado.'])
                ->withInput();
        }

        $elementType = ElementType::findOrFail($validated['element_type_id']);
        if ((int) $elementType->client_id !== (int) $validated['client_id']) {
            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El tipo de activo no pertenece al cliente seleccionado.',
                    'errors' => [
                        'element_type_id' => ['El tipo de activo no pertenece al cliente seleccionado.'],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['element_type_id' => 'El tipo de activo no pertenece al cliente seleccionado.'])
                ->withInput();
        }

        if (!empty($validated['group_id'])) {
            $group = Group::findOrFail($validated['group_id']);

            if ((int) $group->client_id !== (int) $validated['client_id']) {
                if ($this->isAjaxRequest($request)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La agrupación no pertenece al cliente seleccionado.',
                        'errors' => [
                            'group_id' => ['La agrupación no pertenece al cliente seleccionado.'],
                        ],
                    ], 422);
                }

                return back()
                    ->withErrors(['group_id' => 'La agrupación no pertenece al cliente seleccionado.'])
                    ->withInput();
            }
        }


        $exists = Element::query()
            ->where('area_id', $validated['area_id'])
            ->where('element_type_id', $validated['element_type_id'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($validated['name']))])
            ->exists();

        if ($exists) {
            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un activo con ese nombre para esa área y tipo de activo.',
                    'errors' => [
                        'name' => ['Ya existe un activo con ese nombre para esa área y tipo de activo.'],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['name' => 'Ya existe un activo con ese nombre para esa área y tipo de activo.'])
                ->withInput();
        }

        $element = Element::create([
            'area_id' => $validated['area_id'],
            'element_type_id' => $validated['element_type_id'],
            'name' => trim($validated['name']),
            'group_id' => !empty($validated['group_id']) ? (int) $validated['group_id'] : null,
            'code' => $validated['code'] ? trim($validated['code']) : null,
            'warehouse_code' => $validated['warehouse_code'] ? trim($validated['warehouse_code']) : null,
            'status' => true,
        ]);

        $defaultComponentIds = Component::query()
            ->where('client_id', $validated['client_id'])
            ->where('element_type_id', $validated['element_type_id'])
            ->where('status', true)
            ->where('is_default', true)
            ->pluck('id')
            ->values()
            ->all();

        if (!empty($defaultComponentIds)) {
            $element->components()->sync($defaultComponentIds);
        }

        $element->load([
            'area.client',
            'elementType',
            'group',
            'components',
        ]);

        $element->loadCount(['components', 'reportDetails']);

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Activo creado correctamente.',
                'element' => $this->elementPayload($element),
            ]);
        }


        return redirect()
            ->route('admin.managed-elements.index', $this->buildRedirectQuery($request))
            ->with('success', 'Activo creado correctamente.');
    }

    public function update(Request $request, Element $element): RedirectResponse|JsonResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        $element->loadMissing('area');
        abort_unless(in_array($element->area?->client_id, $allowedClientIds), 403);

        $validated = $request->validate([
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'element_type_id' => ['required', 'integer', 'exists:element_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'code' => ['nullable', 'string', 'max:255'],
            'warehouse_code' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ]);

        $area = Area::findOrFail($validated['area_id']);
        abort_unless(in_array($area->client_id, $allowedClientIds), 403);

        $elementType = ElementType::findOrFail($validated['element_type_id']);
        if ((int) $elementType->client_id !== (int) $area->client_id) {
            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El tipo de activo no pertenece al cliente del área seleccionada.',
                    'errors' => [
                        'element_type_id' => ['El tipo de activo no pertenece al cliente del área seleccionada.'],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['element_type_id' => 'El tipo de activo no pertenece al cliente del área seleccionada.'])
                ->withInput();
        }

        if (!empty($validated['group_id'])) {
            $group = Group::findOrFail($validated['group_id']);

            if ((int) $group->client_id !== (int) $area->client_id) {
                if ($this->isAjaxRequest($request)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La agrupación no pertenece al cliente del área seleccionada.',
                        'errors' => [
                            'group_id' => ['La agrupación no pertenece al cliente del área seleccionada.'],
                        ],
                    ], 422);
                }

                return back()
                    ->withErrors(['group_id' => 'La agrupación no pertenece al cliente del área seleccionada.'])
                    ->withInput();
            }
        }


        $exists = Element::query()
            ->where('id', '!=', $element->id)
            ->where('area_id', $validated['area_id'])
            ->where('element_type_id', $validated['element_type_id'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($validated['name']))])
            ->exists();

        if ($exists) {
            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un activo con ese nombre para esa área y tipo de activo.',
                    'errors' => [
                        'name' => ['Ya existe un activo con ese nombre para esa área y tipo de activo.'],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['name' => 'Ya existe un activo con ese nombre para esa área y tipo de activo.'])
                ->withInput();
        }

        $element->update([
            'area_id' => $validated['area_id'],
            'element_type_id' => $validated['element_type_id'],
            'name' => trim($validated['name']),
            'group_id' => !empty($validated['group_id']) ? (int) $validated['group_id'] : null,
            'code' => $validated['code'] ? trim($validated['code']) : null,
            'warehouse_code' => $validated['warehouse_code'] ? trim($validated['warehouse_code']) : null,
            'status' => (bool) $validated['status'],
        ]);

        $element->load([
            'area.client',
            'elementType',
            'group',
            'components',
        ]);

        $element->loadCount(['components', 'reportDetails']);

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Activo actualizado correctamente.',
                'element' => $this->elementPayload($element),
            ]);
        }

        return redirect()
            ->route('admin.managed-elements.index', $this->buildRedirectQuery($request))
            ->with('success', 'Activo actualizado correctamente.');
    }

    public function destroy(Request $request, Element $element): RedirectResponse|JsonResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        $element->loadMissing('area');
        abort_unless(in_array((int) $element->area?->client_id, $allowedClientIds, true), 403);

        $element->loadCount(['components', 'reportDetails']);

        $hasDependencies = (($element->components_count ?? 0) + ($element->report_details_count ?? 0)) > 0;

        if ($hasDependencies) {
            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este activo no se puede eliminar porque ya tiene uso. Solo puedes inactivarlo.',
                ], 422);
            }

            return redirect()
                ->route('admin.managed-elements.index', $this->buildRedirectQuery($request))
                ->with('error', 'Este activo no se puede eliminar porque ya tiene uso. Solo puedes inactivarlo.');
        }

        $elementId = $element->id;
        $element->delete();

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Activo eliminado correctamente.',
                'element_id' => $elementId,
            ]);
        }

        return redirect()
            ->route('admin.managed-elements.index', $this->buildRedirectQuery($request))
            ->with('success', 'Activo eliminado correctamente.');
    }

    public function toggleStatus(Request $request, Element $element): RedirectResponse|JsonResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        $element->loadMissing('area');
        abort_unless(in_array((int) $element->area?->client_id, $allowedClientIds, true), 403);

        $element->update([
            'status' => !$element->status,
        ]);

        $message = $element->status
            ? 'Activo activado correctamente.'
            : 'Activo inactivado correctamente.';

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => (bool) $element->status,
                'label' => $element->status ? 'Activo' : 'Inactivo',
            ]);
        }

        return redirect()
            ->route('admin.managed-elements.index', $this->buildRedirectQuery($request))
            ->with('success', $message);
    }

    public function syncComponents(Request $request, Element $element): RedirectResponse|JsonResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        $element->loadMissing('area');
        abort_unless(in_array($element->area?->client_id, $allowedClientIds), 403);

        $validated = $request->validate([
            'component_ids' => ['nullable', 'array'],
            'component_ids.*' => ['integer', 'exists:components,id'],
        ]);

        $componentIds = collect($validated['component_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (!empty($componentIds)) {
            $validCount = Component::query()
                ->whereIn('id', $componentIds)
                ->where('client_id', $element->area->client_id)
                ->where('element_type_id', $element->element_type_id)
                ->count();

            if ($validCount !== count($componentIds)) {
                if ($this->isAjaxRequest($request)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Uno o más componentes no pertenecen al cliente o al tipo de activo del elemento.',
                    ], 422);
                }

                return back()->withErrors([
                    'component_ids' => 'Uno o más componentes no pertenecen al cliente o al tipo de activo del elemento.',
                ]);
            }
        }

        $element->components()->sync($componentIds);

        $element->load([
            'area.client',
            'elementType',
            'group',
            'components',
        ]);

        $element->loadCount(['components', 'reportDetails']);

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Componentes del activo actualizados correctamente.',
                'element' => $this->elementPayload($element),
            ]);
        }

        return redirect()
            ->route('admin.managed-elements.index', $this->buildRedirectQuery($request))
            ->with('success', 'Componentes del activo actualizados correctamente.');
    }

    private function getScopedClients()
    {
        $user = auth()->user();
        $roleKey = $user->role?->key;

        if (in_array($roleKey, ['superadmin', 'admin_global'], true)) {
            return Client::query()
                ->where('status', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return $user->clients()
            ->where('clients.status', true)
            ->orderBy('clients.name')
            ->get(['clients.id', 'clients.name']);
    }

    private function buildRedirectQuery(Request $request): array
    {
        $query = [];

        foreach ((array) $request->input('redirect_client_ids', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['client_ids'][] = $value;
            }
        }

        foreach ((array) $request->input('redirect_area_ids', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['area_ids'][] = $value;
            }
        }

        foreach ((array) $request->input('redirect_element_type_ids', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['element_type_ids'][] = $value;
            }
        }

        foreach ((array) $request->input('redirect_names', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['names'][] = $value;
            }
        }

        foreach ((array) $request->input('redirect_warehouse_codes', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['warehouse_codes'][] = $value;
            }
        }

        foreach ((array) $request->input('redirect_statuses', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['statuses'][] = $value;
            }
        }

        if ($request->filled('redirect_page')) {
            $query['page'] = $request->input('redirect_page');
        }

        return $query;
    }

private function isAjaxRequest(Request $request): bool
{
    return $request->expectsJson() || $request->ajax();
}

private function elementPayload(Element $element): array
{
    $element->loadMissing([
        'area.client',
        'elementType',
        'group',
        'components',
    ]);

    $element->loadCount(['components', 'reportDetails']);

    return [
        // tu array actual
    ];
}
}
