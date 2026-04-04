<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ElementType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminElementTypeController extends Controller
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

        $selectedNames = collect($request->input('names', []))
            ->filter()
            ->map(fn ($name) => (string) $name)
            ->values()
            ->all();

        $selectedStatuses = collect($request->input('statuses', []))
            ->filter()
            ->map(fn ($status) => (string) $status)
            ->values()
            ->all();

        $baseQuery = ElementType::query()
            ->with(['client'])
            ->withCount(['components', 'elements'])
            ->whereIn('client_id', $clients->pluck('id'));

        if (!empty($selectedClientIds)) {
            $baseQuery->whereIn('client_id', $selectedClientIds);
        }

        if (!empty($selectedNames)) {
            $baseQuery->whereIn('name', $selectedNames);
        }

        if (!empty($selectedStatuses)) {
            $baseQuery->whereIn('status', array_map(fn ($value) => (int) $value, $selectedStatuses));
        }

        $elementTypes = (clone $baseQuery)
            ->orderBy('client_id')
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        $clientFilterOptions = $showClientColumn
            ? $clients->map(fn ($client) => [
                'value' => (string) $client->id,
                'label' => $client->name,
            ])->values()
            : collect();

        $nameFilterOptions = ElementType::query()
            ->whereIn('client_id', $clients->pluck('id'))
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
            'names' => $nameFilterOptions,
            'statuses' => $statusFilterOptions,
        ];

        $activeFilters = [
            'client_ids' => $selectedClientIds,
            'names' => $selectedNames,
            'statuses' => $selectedStatuses,
        ];

        return view('admin.managed-element-types.index', compact(
            'clients',
            'singleClient',
            'showClientColumn',
            'elementTypes',
            'filterOptions',
            'activeFilters'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        $validated = $request->validate([
            'client_id' => ['required', 'integer', Rule::in($allowedClientIds)],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $exists = ElementType::query()
            ->where('client_id', $validated['client_id'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($validated['name']))])
            ->exists();

        if ($exists) {
            return back()
                ->withErrors([
                    'name' => 'Ya existe un tipo de activo con ese nombre para el cliente seleccionado.',
                ])
                ->withInput();
        }

        ElementType::create([
            'client_id' => $validated['client_id'],
            'name' => trim($validated['name']),
            'status' => true,
        ]);

        return redirect()
            ->route('admin.managed-element-types.index', $this->buildRedirectQuery($request))
            ->with('success', 'Tipo de activo creado correctamente.');
    }

    public function update(Request $request, ElementType $elementType): RedirectResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        abort_unless(in_array($elementType->client_id, $allowedClientIds), 403);

        $validated = $request->validate([
            'client_id' => ['required', 'integer', Rule::in($allowedClientIds)],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ]);

        $exists = ElementType::query()
            ->where('id', '!=', $elementType->id)
            ->where('client_id', $validated['client_id'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($validated['name']))])
            ->exists();

        if ($exists) {
            return back()
                ->withErrors([
                    'name' => 'Ya existe un tipo de activo con ese nombre para el cliente seleccionado.',
                ])
                ->withInput();
        }

        $elementType->update([
            'client_id' => $validated['client_id'],
            'name' => trim($validated['name']),
            'status' => (bool) $validated['status'],
        ]);

        return redirect()
            ->route('admin.managed-element-types.index', $this->buildRedirectQuery($request))
            ->with('success', 'Tipo de activo actualizado correctamente.');
    }

public function destroy(Request $request, ElementType $elementType): RedirectResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        abort_unless(in_array($elementType->client_id, $allowedClientIds), 403);

        $elementType->loadCount(['components', 'elements']);

        $hasDependencies = (($elementType->components_count ?? 0) + ($elementType->elements_count ?? 0)) > 0;

        if ($hasDependencies) {
            return redirect()
                ->route('admin.managed-element-types.index', $this->buildRedirectQuery($request))
                ->with('error', 'Este tipo de activo no se puede eliminar porque ya tiene uso. Solo puedes inactivarlo.');
        }

        $elementType->delete();

        return redirect()
            ->route('admin.managed-element-types.index', $this->buildRedirectQuery($request))
            ->with('success', 'Tipo de activo eliminado correctamente.');
    }

    public function toggleStatus(Request $request, ElementType $elementType): RedirectResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        abort_unless(in_array($elementType->client_id, $allowedClientIds), 403);

        $elementType->loadCount(['components', 'elements']);

        $hasDependencies = (($elementType->components_count ?? 0) + ($elementType->elements_count ?? 0)) > 0;

        if (!$hasDependencies) {
            return redirect()
                ->route('admin.managed-element-types.index', $this->buildRedirectQuery($request))
                ->with('error', 'Este tipo de activo no tiene dependencias. Puedes eliminarlo si lo deseas.');
        }

        $elementType->update([
            'status' => !$elementType->status,
        ]);

        return redirect()
            ->route('admin.managed-element-types.index', $this->buildRedirectQuery($request))
            ->with('success', 'Estado del tipo de activo actualizado correctamente.');
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

        foreach ((array) $request->input('redirect_names', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['names'][] = $value;
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
