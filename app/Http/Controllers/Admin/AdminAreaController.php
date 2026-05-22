<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminAreaController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $clients = $this->getScopedClients();

        $showClientColumn = $clients->count() > 1;
        $singleClient = $clients->count() === 1 ? $clients->first() : null;

        $selectedClientIds = $showClientColumn
            ? collect($request->input('client_ids', []))
                ->filter()
                ->map(fn ($id) => (string) $id)
                ->values()
                ->all()
            : ($singleClient ? [(string) $singleClient->id] : []);

        $selectedAreaNames = collect($request->input('area_names', []))
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        $selectedStatuses = collect($request->input('statuses', []))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        $baseQuery = $this->buildAreaBaseQuery($clients);
        $filteredQuery = $this->applyAreaFilters(
            clone $baseQuery,
            $selectedClientIds,
            $selectedAreaNames,
            $selectedStatuses
        );

        $areas = (clone $filteredQuery)
            ->orderBy('client_id')
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        $allAreasForFilters = (clone $filteredQuery)
            ->orderBy('name')
            ->get();

        $clientFilterOptions = $showClientColumn
            ? $clients->map(fn ($client) => [
                'value' => (string) $client->id,
                'label' => $client->name,
            ])->values()
            : collect();

        $areaNameFilterOptions = $allAreasForFilters->pluck('name')
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $statusFilterOptions = $allAreasForFilters
            ->pluck('status')
            ->map(fn ($status) => (string) ((int) $status))
            ->unique()
            ->sortDesc()
            ->values()
            ->map(fn ($value) => [
                'value' => $value,
                'label' => $value === '1' ? 'Activo' : 'Inactivo',
            ]);

        $filterOptions = [
            'client_ids' => $clientFilterOptions,
            'area_names' => $areaNameFilterOptions,
            'statuses' => $statusFilterOptions,
        ];

        $activeFilters = [
            'client_ids' => $selectedClientIds,
            'area_names' => $selectedAreaNames,
            'statuses' => $selectedStatuses,
        ];

        $preferredClientId = old('client_id');

        if (!$preferredClientId) {
            $preferredClientId = session('preferred_area_client_id');
        }

        if (!$preferredClientId && $singleClient) {
            $preferredClientId = (string) $singleClient->id;
        }

        if (
            !$preferredClientId &&
            !$showClientColumn &&
            $clients->count() === 1
        ) {
            $preferredClientId = (string) $clients->first()->id;
        }

        $viewData = [
            'areas' => $areas,
            'clients' => $clients,
            'showClientColumn' => $showClientColumn,
            'singleClient' => $singleClient,
            'filterOptions' => $filterOptions,
            'activeFilters' => $activeFilters,
            'preferredClientId' => $preferredClientId,
        ];

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'list_html' => view('admin.managed-areas.partials.list', array_merge(
                    $viewData,
                    ['hasFilter' => $this->hasFilterResolver($activeFilters)]
                ))->render(),
                'filter_options' => [
                    'client_ids' => $clientFilterOptions->values()->all(),
                    'area_names' => $areaNameFilterOptions->values()->all(),
                    'statuses' => $statusFilterOptions->values()->all(),
                ],
                'has_any_active_filter' => $this->hasAnyActiveFilter($activeFilters),
                'current_page' => $areas->currentPage(),
                'query' => $request->query(),
            ]);
        }

        return view('admin.managed-areas.index', $viewData);
    }

public function store(Request $request): RedirectResponse|JsonResponse
{
    $clients = $this->getScopedClients();
    $allowedClientIds = $clients->pluck('id')->toArray();

    $validated = $request->validate([
        'client_id' => ['required', 'integer', Rule::in($allowedClientIds)],
        'name' => ['required', 'string', 'max:255'],
        'code' => ['nullable', 'string', 'max:100'],
    ]);

    $exists = Area::query()
        ->where('client_id', $validated['client_id'])
        ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($validated['name']))])
        ->exists();

    if ($exists) {
        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un área con ese nombre para el cliente seleccionado.',
                'errors' => [
                    'name' => ['Ya existe un área con ese nombre para el cliente seleccionado.'],
                ],
            ], 422);
        }

        return back()
            ->withErrors([
                'name' => 'Ya existe un área con ese nombre para el cliente seleccionado.',
            ])
            ->withInput();
    }

    $area = Area::create([
        'client_id' => (int) $validated['client_id'],
        'name' => trim($validated['name']),
        'code' => filled($validated['code'] ?? null) ? trim($validated['code']) : null,
        'status' => true,
    ]);

    $area->load('client');
    $area->loadCount('elements');

    if ($this->isAjaxRequest($request)) {
        return response()->json([
            'success' => true,
            'message' => 'Área creada correctamente.',
            'area' => $this->areaPayload($area),
        ]);
    }

    return redirect()
        ->route('admin.managed-areas.index', $this->buildRedirectQuery($request))
        ->with([
            'success' => 'Área creada correctamente.',
            'preferred_area_client_id' => (string) $validated['client_id'],
        ]);
}

public function update(Request $request, Area $area): RedirectResponse|JsonResponse
{
    $clients = $this->getScopedClients();
    $allowedClientIds = $clients->pluck('id')->toArray();

    abort_unless(in_array((int) $area->client_id, $allowedClientIds, true), 403);

    $validated = $request->validate([
        'client_id' => ['required', 'integer', Rule::in($allowedClientIds)],
        'name' => ['required', 'string', 'max:255'],
        'code' => ['nullable', 'string', 'max:100'],
    ]);

    $exists = Area::query()
        ->where('id', '!=', $area->id)
        ->where('client_id', $validated['client_id'])
        ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($validated['name']))])
        ->exists();

    if ($exists) {
        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un área con ese nombre para el cliente seleccionado.',
                'errors' => [
                    'name' => ['Ya existe un área con ese nombre para el cliente seleccionado.'],
                ],
            ], 422);
        }

        return back()
            ->withErrors([
                'name' => 'Ya existe un área con ese nombre para el cliente seleccionado.',
            ])
            ->withInput();
    }

    $area->update([
        'client_id' => (int) $validated['client_id'],
        'name' => trim($validated['name']),
        'code' => filled($validated['code'] ?? null) ? trim($validated['code']) : null,
    ]);

    $area->load('client');
    $area->loadCount('elements');

    if ($this->isAjaxRequest($request)) {
        return response()->json([
            'success' => true,
            'message' => 'Área actualizada correctamente.',
            'area' => $this->areaPayload($area),
        ]);
    }

    return redirect()
        ->route('admin.managed-areas.index', $this->buildRedirectQuery($request))
        ->with('success', 'Área actualizada correctamente.');
}

public function destroy(Request $request, Area $area): RedirectResponse|JsonResponse
{
    $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

    abort_unless(in_array((int) $area->client_id, $allowedClientIds, true), 403);

    if ($area->elements()->exists()) {
        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => 'El área tiene activos asociados. No puede eliminarse; solo puede inactivarse.',
            ], 422);
        }

        return redirect()
            ->route('admin.managed-areas.index', $this->buildRedirectQuery($request))
            ->with('error', 'El área tiene activos asociados. No puede eliminarse; solo puede inactivarse.');
    }

    $areaId = $area->id;
    $area->delete();

    if ($this->isAjaxRequest($request)) {
        return response()->json([
            'success' => true,
            'message' => 'Área eliminada correctamente.',
            'area_id' => $areaId,
        ]);
    }

    return redirect()
        ->route('admin.managed-areas.index', $this->buildRedirectQuery($request))
        ->with('success', 'Área eliminada correctamente.');
}

public function toggleStatus(Request $request, Area $area): RedirectResponse|JsonResponse
{
    $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

    abort_unless(in_array((int) $area->client_id, $allowedClientIds, true), 403);

    $area->update([
        'status' => !$area->status,
    ]);

    $message = $area->status
        ? 'Área activada correctamente.'
        : 'Área inactivada correctamente.';

    if ($this->isAjaxRequest($request)) {
        return response()->json([
            'success' => true,
            'message' => $message,
            'status' => (bool) $area->status,
            'label' => $area->status ? 'Activo' : 'Inactivo',
        ]);
    }

    return redirect()
        ->route('admin.managed-areas.index', $this->buildRedirectQuery($request))
        ->with('success', $message);
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

    private function buildAreaBaseQuery(Collection $clients)
    {
        return Area::query()
            ->with('client')
            ->withCount(['elements'])
            ->whereIn('client_id', $clients->pluck('id'));
    }

    private function applyAreaFilters($query, array $selectedClientIds, array $selectedAreaNames, array $selectedStatuses)
    {
        if (!empty($selectedClientIds)) {
            $query->whereIn('client_id', $selectedClientIds);
        }

        if (!empty($selectedAreaNames)) {
            $query->whereIn('name', $selectedAreaNames);
        }

        if (!empty($selectedStatuses)) {
            $query->whereIn('status', array_map(fn ($value) => (int) $value, $selectedStatuses));
        }

        return $query;
    }

    private function buildRedirectQuery(Request $request): array
    {
        $query = [];

        foreach ((array) $request->input('redirect_client_ids', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['client_ids'][] = $value;
            }
        }

        foreach ((array) $request->input('redirect_area_names', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['area_names'][] = $value;
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

private function areaPayload(Area $area): array
{
    $area->loadMissing('client');
    $area->loadCount('elements');

    return [
        'id' => $area->id,
        'client_id' => $area->client_id,
        'client_name' => $area->client?->name ?? '—',
        'name' => $area->name,
        'code' => $area->code,
        'code_label' => $area->code ?: '—',
        'status' => (bool) $area->status,
        'elements_count' => (int) ($area->elements_count ?? 0),
        'update_url' => route('admin.managed-areas.update', $area),
        'destroy_url' => route('admin.managed-areas.destroy', $area),
        'toggle_status_url' => route('admin.managed-areas.toggle-status', $area),
    ];
}

private function hasFilterResolver(array $activeFilters): \Closure
{
    return function (string $key) use ($activeFilters): bool {
        $value = $activeFilters[$key] ?? null;

        if (is_array($value)) {
            return count(array_filter($value, fn ($item) => $item !== null && $item !== '')) > 0;
        }

        return $value !== null && $value !== '';
    };
}

private function hasAnyActiveFilter(array $activeFilters): bool
{
    foreach ($activeFilters as $value) {
        if (is_array($value) && count(array_filter($value, fn ($item) => $item !== null && $item !== '')) > 0) {
            return true;
        }

        if (!is_array($value) && $value !== null && $value !== '') {
            return true;
        }
    }

    return false;
}
}
