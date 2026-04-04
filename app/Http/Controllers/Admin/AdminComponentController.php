<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Component;
use App\Models\ElementType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminComponentController extends Controller
{
    public function index(Request $request): View
    {
        $clients = $this->getScopedClients();

        $singleClient = $clients->count() === 1 ? $clients->first() : null;
        $showClientColumn = $clients->count() > 1;

        $selectedClientIds = $showClientColumn
            ? collect($request->input('client_ids', []))
                ->filter()
                ->map(fn ($id) => (string) $id)
                ->values()
                ->all()
            : ($singleClient ? [(string) $singleClient->id] : []);

        $selectedElementTypeIds = collect($request->input('element_type_ids', []))
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        $selectedComponentNames = collect($request->input('component_names', []))
            ->filter()
            ->map(fn ($name) => (string) $name)
            ->values()
            ->all();

        $selectedStatuses = collect($request->input('statuses', []))
            ->filter()
            ->map(fn ($status) => (string) $status)
            ->values()
            ->all();

        $baseQuery = Component::query()
            ->with(['client', 'elementType'])
            ->withCount(['elements', 'diagnostics', 'reportDetails'])
            ->whereIn('client_id', $clients->pluck('id'));

        if (!empty($selectedClientIds)) {
            $baseQuery->whereIn('client_id', $selectedClientIds);
        }

        if (!empty($selectedElementTypeIds)) {
            $baseQuery->whereIn('element_type_id', $selectedElementTypeIds);
        }

        if (!empty($selectedComponentNames)) {
            $baseQuery->whereIn('name', $selectedComponentNames);
        }

        if (!empty($selectedStatuses)) {
            $baseQuery->whereIn('status', array_map(fn ($value) => (int) $value, $selectedStatuses));
        }

        $components = (clone $baseQuery)
            ->orderBy('client_id')
            ->orderBy('element_type_id')
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        $filterOptionsQuery = Component::query()
            ->with(['client:id,name', 'elementType:id,name'])
            ->whereIn('client_id', $clients->pluck('id'));

        $clientFilterOptions = $showClientColumn
            ? $clients->map(fn ($client) => [
                'value' => (string) $client->id,
                'label' => $client->name,
            ])->values()
            : collect();

        $elementTypeFilterOptions = (clone $filterOptionsQuery)
            ->get()
            ->map(function ($component) {
                return [
                    'value' => (string) $component->element_type_id,
                    'label' => $component->elementType?->name ?? '—',
                ];
            })
            ->unique('value')
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $componentNameFilterOptions = (clone $filterOptionsQuery)
            ->get()
            ->pluck('name')
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
            'element_type_ids' => $elementTypeFilterOptions,
            'component_names' => $componentNameFilterOptions,
            'statuses' => $statusFilterOptions,
        ];

        $activeFilters = [
            'client_ids' => $selectedClientIds,
            'element_type_ids' => $selectedElementTypeIds,
            'component_names' => $selectedComponentNames,
            'statuses' => $selectedStatuses,
        ];

        $createElementTypes = collect();

        if ($singleClient) {
            $createElementTypes = ElementType::query()
                ->where('client_id', $singleClient->id)
                ->where('status', true)
                ->orderBy('name')
                ->get(['id', 'name', 'client_id']);
        }

        return view('admin.managed-components.index', [
            'clients' => $clients,
            'singleClient' => $singleClient,
            'showClientColumn' => $showClientColumn,
            'components' => $components,
            'filterOptions' => $filterOptions,
            'activeFilters' => $activeFilters,
            'createElementTypes' => $createElementTypes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        $validated = $request->validate([
            'client_id' => ['required', 'integer', Rule::in($allowedClientIds)],
            'element_type_id' => ['required', 'integer', 'exists:element_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'is_default' => ['required', 'boolean'],
        ]);

        $elementTypeBelongs = ElementType::query()
            ->where('id', $validated['element_type_id'])
            ->where('client_id', $validated['client_id'])
            ->exists();

        if (!$elementTypeBelongs) {
            return back()
                ->withErrors([
                    'element_type_id' => 'El tipo de activo no pertenece al cliente seleccionado.',
                ])
                ->withInput();
        }

        $exists = Component::query()
            ->where('client_id', $validated['client_id'])
            ->where('element_type_id', $validated['element_type_id'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($validated['name']))])
            ->exists();

        if ($exists) {
            return back()
                ->withErrors([
                    'name' => 'Ya existe un componente con ese nombre para ese cliente y tipo de activo.',
                ])
                ->withInput();
        }

        Component::create([
            'client_id' => $validated['client_id'],
            'element_type_id' => $validated['element_type_id'],
            'name' => trim($validated['name']),
            'is_default' => (bool) $validated['is_default'],
            'status' => true,
        ]);

        return redirect()
            ->route('admin.managed-components.index', $this->buildRedirectQuery($request))
            ->with('success', 'Componente creado correctamente.');
    }

    public function update(Request $request, Component $component): RedirectResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        abort_unless(in_array($component->client_id, $allowedClientIds), 403);

        $validated = $request->validate([
            'client_id' => ['required', 'integer', Rule::in($allowedClientIds)],
            'element_type_id' => ['required', 'integer', 'exists:element_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'is_default' => ['required', 'boolean'],
        ]);

        $elementTypeBelongs = ElementType::query()
            ->where('id', $validated['element_type_id'])
            ->where('client_id', $validated['client_id'])
            ->exists();

        if (!$elementTypeBelongs) {
            return back()
                ->withErrors([
                    'element_type_id' => 'El tipo de activo no pertenece al cliente seleccionado.',
                ])
                ->withInput();
        }

        $exists = Component::query()
            ->where('id', '!=', $component->id)
            ->where('client_id', $validated['client_id'])
            ->where('element_type_id', $validated['element_type_id'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($validated['name']))])
            ->exists();

        if ($exists) {
            return back()
                ->withErrors([
                    'name' => 'Ya existe un componente con ese nombre para ese cliente y tipo de activo.',
                ])
                ->withInput();
        }

        $component->update([
            'client_id' => $validated['client_id'],
            'element_type_id' => $validated['element_type_id'],
            'name' => trim($validated['name']),
            'is_default' => (bool) $validated['is_default'],
        ]);

        return redirect()
            ->route('admin.managed-components.index', $this->buildRedirectQuery($request))
            ->with('success', 'Componente actualizado correctamente.');
    }
public function destroy(Request $request, Component $component): RedirectResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        abort_unless(in_array($component->client_id, $allowedClientIds), 403);

        $component->loadCount(['elements', 'diagnostics', 'reportDetails']);

        $hasDependencies = (($component->elements_count ?? 0) + ($component->diagnostics_count ?? 0) + ($component->report_details_count ?? 0)) > 0;

        if ($hasDependencies) {
            return redirect()
                ->route('admin.managed-components.index', $this->buildRedirectQuery($request))
                ->with('error', 'Este componente no se puede eliminar porque ya tiene uso. Solo puedes inactivarlo.');
        }

        $component->delete();

        return redirect()
            ->route('admin.managed-components.index', $this->buildRedirectQuery($request))
            ->with('success', 'Componente eliminado correctamente.');
    }

    public function toggleStatus(Request $request, Component $component): RedirectResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        abort_unless(in_array($component->client_id, $allowedClientIds), 403);

        $component->loadCount(['elements', 'diagnostics', 'reportDetails']);

        $hasDependencies = (($component->elements_count ?? 0) + ($component->diagnostics_count ?? 0) + ($component->report_details_count ?? 0)) > 0;

        if (!$hasDependencies) {
            return redirect()
                ->route('admin.managed-components.index', $this->buildRedirectQuery($request))
                ->with('error', 'Este componente no tiene dependencias. Puedes eliminarlo si lo deseas.');
        }

        $component->update([
            'status' => !$component->status,
        ]);

        return redirect()
            ->route('admin.managed-components.index', $this->buildRedirectQuery($request))
            ->with('success', 'Estado del componente actualizado correctamente.');
    }

    public function getElementTypesByClient(Client $client)
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        abort_unless(in_array($client->id, $allowedClientIds), 403);

        return response()->json(
            ElementType::query()
                ->where('client_id', $client->id)
                ->where('status', true)
                ->orderBy('name')
                ->get(['id', 'name'])
        );
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

        foreach ((array) $request->input('redirect_element_type_ids', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['element_type_ids'][] = $value;
            }
        }

        foreach ((array) $request->input('redirect_component_names', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['component_names'][] = $value;
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
}
