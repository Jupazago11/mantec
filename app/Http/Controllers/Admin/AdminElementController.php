<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Client;
use App\Models\Component;
use App\Models\Element;
use App\Models\ElementType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminElementController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        $clients = $user->clients()
            ->where('clients.status', true)
            ->orderBy('clients.name')
            ->get(['clients.id', 'clients.name']);

        $singleClient = $clients->count() === 1 ? $clients->first() : null;
        $showClientColumn = $clients->count() > 1;
        $allowedClientIds = $clients->pluck('id');

        $areas = Area::query()
            ->with('client')
            ->whereIn('client_id', $allowedClientIds)
            ->where('status', true)
            ->orderBy('client_id')
            ->orderBy('name')
            ->get();

        $elementTypes = ElementType::query()
            ->whereIn('client_id', $allowedClientIds)
            ->where('status', true)
            ->orderBy('client_id')
            ->orderBy('name')
            ->get();

        $components = Component::query()
            ->whereIn('client_id', $allowedClientIds)
            ->where('status', true)
            ->orderBy('client_id')
            ->orderBy('name')
            ->get();

        $selectedClientIds = $showClientColumn
            ? collect($request->input('client_ids', []))->filter()->map(fn ($id) => (string) $id)->values()->all()
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
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        $selectedWarehouseCodes = collect($request->input('warehouse_codes', []))
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        $selectedStatuses = collect($request->input('statuses', []))
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        $baseQuery = Element::query()
            ->with([
                'area.client',
                'elementType',
                'components',
            ])
            ->withCount(['components', 'reportDetails'])
            ->whereHas('area', function ($query) use ($allowedClientIds) {
                $query->whereIn('client_id', $allowedClientIds);
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
            $baseQuery->whereIn('status', array_map(fn ($v) => (int) $v, $selectedStatuses));
        }

        $elements = (clone $baseQuery)
            ->orderBy('area_id')
            ->orderBy('element_type_id')
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        $allElements = Element::query()
            ->with(['area.client', 'elementType'])
            ->whereHas('area', function ($query) use ($allowedClientIds) {
                $query->whereIn('client_id', $allowedClientIds);
            })
            ->orderBy('name')
            ->get();

        $clientFilterOptions = $showClientColumn
            ? $clients->map(fn ($client) => [
                'value' => (string) $client->id,
                'label' => $client->name,
            ])->values()
            : collect();

        $areaFilterOptions = $areas
            ->map(fn ($area) => [
                'value' => (string) $area->id,
                'label' => ($showClientColumn ? ($area->client?->name . ' - ') : '') . $area->name,
            ])
            ->values();

        $elementTypeFilterOptions = $elementTypes
            ->map(fn ($type) => [
                'value' => (string) $type->id,
                'label' => $type->name,
            ])
            ->unique('value')
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $nameFilterOptions = $allElements
            ->pluck('name')
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $warehouseCodeFilterOptions = $allElements
            ->pluck('warehouse_code')
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $statusFilterOptions = collect([
            ['value' => '1', 'label' => 'Activo'],
            ['value' => '0', 'label' => 'Inactivo'],
        ]);

        $filterOptions = [
            'client_ids' => $clientFilterOptions,
            'area_ids' => $areaFilterOptions,
            'element_type_ids' => $elementTypeFilterOptions,
            'names' => $nameFilterOptions,
            'warehouse_codes' => $warehouseCodeFilterOptions,
            'statuses' => $statusFilterOptions,
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
            'showClientColumn' => $showClientColumn,
            'areas' => $areas,
            'elementTypes' => $elementTypes,
            'components' => $components,
            'elements' => $elements,
            'filterOptions' => $filterOptions,
            'activeFilters' => $activeFilters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $allowedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->toArray();

        $validated = $request->validate([
            'client_id' => ['required', 'integer', Rule::in($allowedClientIds)],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'element_type_id' => ['required', 'integer', 'exists:element_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'warehouse_code' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ]);

        $area = Area::findOrFail($validated['area_id']);
        if ((int) $area->client_id !== (int) $validated['client_id']) {
            return back()
                ->withErrors(['area_id' => 'El área no pertenece al cliente seleccionado.'])
                ->withInput();
        }

        $elementType = ElementType::findOrFail($validated['element_type_id']);
        if ((int) $elementType->client_id !== (int) $validated['client_id']) {
            return back()
                ->withErrors(['element_type_id' => 'El tipo de activo no pertenece al cliente seleccionado.'])
                ->withInput();
        }

        $nameExists = Element::query()
            ->whereHas('area', function ($query) use ($validated) {
                $query->where('client_id', $validated['client_id']);
            })
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($validated['name']))])
            ->exists();

        if ($nameExists) {
            return back()
                ->withErrors(['name' => 'Ya existe un activo con ese nombre en este cliente.'])
                ->withInput();
        }

        $code = trim((string) ($validated['code'] ?? ''));
        $warehouseCode = trim((string) ($validated['warehouse_code'] ?? ''));

        if ($code !== '') {
            $codeExists = Element::query()
                ->whereHas('area', function ($query) use ($validated) {
                    $query->where('client_id', $validated['client_id']);
                })
                ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
                ->exists();

            if ($codeExists) {
                return back()
                    ->withErrors(['code' => 'Ya existe un activo con ese código en este cliente.'])
                    ->withInput();
            }
        }

        if ($warehouseCode !== '') {
            $warehouseCodeExists = Element::query()
                ->whereHas('area', function ($query) use ($validated) {
                    $query->where('client_id', $validated['client_id']);
                })
                ->whereRaw('LOWER(warehouse_code) = ?', [mb_strtolower($warehouseCode)])
                ->exists();

            if ($warehouseCodeExists) {
                return back()
                    ->withErrors(['warehouse_code' => 'Ya existe un activo con ese código de almacén en este cliente.'])
                    ->withInput();
            }
        }

        Element::create([
            'area_id' => $validated['area_id'],
            'element_type_id' => $validated['element_type_id'],
            'name' => trim($validated['name']),
            'code' => $code !== '' ? $code : null,
            'warehouse_code' => $warehouseCode !== '' ? $warehouseCode : null,
            'status' => (bool) $validated['status'],
        ]);

        return redirect()
            ->route('admin.managed-elements.index', $this->buildRedirectQuery($request))
            ->with('success', 'Activo creado correctamente.');
    }

    public function update(Request $request, Element $element): RedirectResponse
    {
        $user = auth()->user();

        $allowedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->toArray();

        abort_unless(in_array(optional($element->area)->client_id, $allowedClientIds), 403);

        $validated = $request->validate([
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'element_type_id' => ['required', 'integer', 'exists:element_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'warehouse_code' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ]);

        $area = Area::findOrFail($validated['area_id']);
        $clientId = (int) $area->client_id;

        abort_unless(in_array($clientId, $allowedClientIds), 403);

        $elementType = ElementType::findOrFail($validated['element_type_id']);
        if ((int) $elementType->client_id !== $clientId) {
            return back()
                ->withErrors(['element_type_id' => 'El tipo de activo no pertenece al cliente del área seleccionada.'])
                ->withInput();
        }

        $nameExists = Element::query()
            ->where('id', '!=', $element->id)
            ->whereHas('area', function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            })
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($validated['name']))])
            ->exists();

        if ($nameExists) {
            return back()
                ->withErrors(['name' => 'Ya existe un activo con ese nombre en este cliente.'])
                ->withInput();
        }

        $code = trim((string) ($validated['code'] ?? ''));
        $warehouseCode = trim((string) ($validated['warehouse_code'] ?? ''));

        if ($code !== '') {
            $codeExists = Element::query()
                ->where('id', '!=', $element->id)
                ->whereHas('area', function ($query) use ($clientId) {
                    $query->where('client_id', $clientId);
                })
                ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
                ->exists();

            if ($codeExists) {
                return back()
                    ->withErrors(['code' => 'Ya existe un activo con ese código en este cliente.'])
                    ->withInput();
            }
        }

        if ($warehouseCode !== '') {
            $warehouseCodeExists = Element::query()
                ->where('id', '!=', $element->id)
                ->whereHas('area', function ($query) use ($clientId) {
                    $query->where('client_id', $clientId);
                })
                ->whereRaw('LOWER(warehouse_code) = ?', [mb_strtolower($warehouseCode)])
                ->exists();

            if ($warehouseCodeExists) {
                return back()
                    ->withErrors(['warehouse_code' => 'Ya existe un activo con ese código de almacén en este cliente.'])
                    ->withInput();
            }
        }

        $element->update([
            'area_id' => $validated['area_id'],
            'element_type_id' => $validated['element_type_id'],
            'name' => trim($validated['name']),
            'code' => $code !== '' ? $code : null,
            'warehouse_code' => $warehouseCode !== '' ? $warehouseCode : null,
            'status' => (bool) $validated['status'],
        ]);

        return redirect()
            ->route('admin.managed-elements.index', $this->buildRedirectQuery($request))
            ->with('success', 'Activo actualizado correctamente.');
    }

    public function destroy(Request $request, Element $element): RedirectResponse
    {
        $user = auth()->user();

        $allowedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->toArray();

        abort_unless(in_array(optional($element->area)->client_id, $allowedClientIds), 403);

        $element->delete();

        return redirect()
            ->route('admin.managed-elements.index', $this->buildRedirectQuery($request))
            ->with('success', 'Activo eliminado correctamente.');
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

    public function syncComponents(Request $request, Element $element): RedirectResponse
    {
        $user = auth()->user();

        $allowedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->toArray();

        abort_unless(in_array(optional($element->area)->client_id, $allowedClientIds), 403);

        $validated = $request->validate([
            'component_ids' => ['nullable', 'array'],
            'component_ids.*' => ['integer', 'exists:components,id'],
        ]);

        $componentIds = collect($validated['component_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->values();

        $validComponentIds = Component::query()
            ->whereIn('id', $componentIds)
            ->where('client_id', optional($element->area)->client_id)
            ->pluck('id')
            ->toArray();

        $element->components()->sync($validComponentIds);

        return redirect()
            ->route('admin.managed-elements.index', $this->buildRedirectQuery($request))
            ->with('success', 'Componentes actualizados correctamente.');
    }

}
