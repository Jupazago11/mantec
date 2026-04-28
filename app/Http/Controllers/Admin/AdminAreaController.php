<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminAreaController extends Controller
{
    public function index(Request $request): View
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

        $baseQuery = Area::query()
            ->with('client')
            ->withCount(['elements'])
            ->whereIn('client_id', $clients->pluck('id'));

        if (!empty($selectedClientIds)) {
            $baseQuery->whereIn('client_id', $selectedClientIds);
        }

        if (!empty($selectedAreaNames)) {
            $baseQuery->whereIn('name', $selectedAreaNames);
        }

        if (!empty($selectedStatuses)) {
            $baseQuery->whereIn('status', array_map(fn ($value) => (int) $value, $selectedStatuses));
        }

        $areas = (clone $baseQuery)
            ->orderBy('client_id')
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        $allAreasForFilters = Area::query()
            ->with('client:id,name')
            ->whereIn('client_id', $clients->pluck('id'))
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

        $statusFilterOptions = collect([
            ['value' => '1', 'label' => 'Activo'],
            ['value' => '0', 'label' => 'Inactivo'],
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

        return view('admin.managed-areas.index', [
            'areas' => $areas,
            'clients' => $clients,
            'showClientColumn' => $showClientColumn,
            'singleClient' => $singleClient,
            'filterOptions' => $filterOptions,
            'activeFilters' => $activeFilters,
            'preferredClientId' => $preferredClientId,
        ]);
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
}
