<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ElementType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

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

        $preferredClientId = old('client_id');

        if (!$preferredClientId) {
            $preferredClientId = session('preferred_element_type_client_id');
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

        return view('admin.managed-element-types.index', compact(
            'clients',
            'singleClient',
            'showClientColumn',
            'elementTypes',
            'filterOptions',
            'activeFilters',
            'preferredClientId'
        ));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        $validated = $request->validate([
            'client_id' => ['required', 'integer', Rule::in($allowedClientIds)],
            'name' => ['required', 'string', 'max:255'],
            'has_semaphore' => ['nullable', 'boolean'],
        ]);

        $exists = ElementType::query()
            ->where('client_id', $validated['client_id'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($validated['name']))])
            ->exists();

        if ($exists) {
            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un tipo de activo con ese nombre para el cliente seleccionado.',
                    'errors' => [
                        'name' => ['Ya existe un tipo de activo con ese nombre para el cliente seleccionado.'],
                    ],
                ], 422);
            }

            return back()
                ->withErrors([
                    'name' => 'Ya existe un tipo de activo con ese nombre para el cliente seleccionado.',
                ])
                ->withInput();
        }

        $elementType = ElementType::create([
            'client_id' => (int) $validated['client_id'],
            'name' => trim($validated['name']),
            'has_semaphore' => $request->boolean('has_semaphore'),
            'status' => true,
        ]);

        $elementType->load('client');
        $elementType->loadCount(['components', 'elements']);

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Tipo de activo creado correctamente.',
                'element_type' => $this->elementTypePayload($elementType),
            ]);
        }

        return redirect()
            ->route('admin.managed-element-types.index', $this->buildRedirectQuery($request))
            ->with([
                'success' => 'Tipo de activo creado correctamente.',
                'preferred_element_type_client_id' => (string) $validated['client_id'],
            ]);
    }

public function update(Request $request, ElementType $elementType): RedirectResponse|JsonResponse
{
    $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

    abort_unless(in_array((int) $elementType->client_id, $allowedClientIds, true), 403);

    $validated = $request->validate([
        'client_id' => ['required', 'integer', Rule::in($allowedClientIds)],
        'name' => ['required', 'string', 'max:255'],
        'has_semaphore' => ['nullable', 'boolean'],
        'status' => ['required', 'boolean'],
    ]);

    $exists = ElementType::query()
        ->where('id', '!=', $elementType->id)
        ->where('client_id', $validated['client_id'])
        ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($validated['name']))])
        ->exists();

    if ($exists) {
        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un tipo de activo con ese nombre para el cliente seleccionado.',
                'errors' => [
                    'name' => ['Ya existe un tipo de activo con ese nombre para el cliente seleccionado.'],
                ],
            ], 422);
        }

        return back()
            ->withErrors([
                'name' => 'Ya existe un tipo de activo con ese nombre para el cliente seleccionado.',
            ])
            ->withInput();
    }

    $elementType->update([
        'client_id' => (int) $validated['client_id'],
        'name' => trim($validated['name']),
        'has_semaphore' => $request->boolean('has_semaphore'),
        'status' => (bool) $validated['status'],
    ]);

    $elementType->load('client');
    $elementType->loadCount(['components', 'elements']);

    if ($this->isAjaxRequest($request)) {
        return response()->json([
            'success' => true,
            'message' => 'Tipo de activo actualizado correctamente.',
            'element_type' => $this->elementTypePayload($elementType),
        ]);
    }

    return redirect()
        ->route('admin.managed-element-types.index', $this->buildRedirectQuery($request))
        ->with('success', 'Tipo de activo actualizado correctamente.');
}

public function destroy(Request $request, ElementType $elementType): RedirectResponse|JsonResponse
{
    $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

    abort_unless(in_array((int) $elementType->client_id, $allowedClientIds, true), 403);

    $elementType->loadCount(['components', 'elements']);

    $hasDependencies = (($elementType->components_count ?? 0) + ($elementType->elements_count ?? 0)) > 0;

    if ($hasDependencies) {
        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Este tipo de activo no se puede eliminar porque ya tiene uso. Solo puedes inactivarlo.',
            ], 422);
        }

        return redirect()
            ->route('admin.managed-element-types.index', $this->buildRedirectQuery($request))
            ->with('error', 'Este tipo de activo no se puede eliminar porque ya tiene uso. Solo puedes inactivarlo.');
    }

    $elementTypeId = $elementType->id;
    $elementType->delete();

    if ($this->isAjaxRequest($request)) {
        return response()->json([
            'success' => true,
            'message' => 'Tipo de activo eliminado correctamente.',
            'element_type_id' => $elementTypeId,
        ]);
    }

    return redirect()
        ->route('admin.managed-element-types.index', $this->buildRedirectQuery($request))
        ->with('success', 'Tipo de activo eliminado correctamente.');
}

    public function toggleStatus(Request $request, ElementType $elementType)
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        abort_unless(in_array($elementType->client_id, $allowedClientIds), 403);

        $elementType->update([
            'status' => !$elementType->status,
        ]);

        $message = $elementType->status
            ? 'Tipo de activo activado correctamente.'
            : 'Tipo de activo inactivado correctamente.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => (bool) $elementType->status,
            ]);
        }

        return redirect()
            ->route('admin.managed-element-types.index', $this->buildRedirectQuery($request))
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

    public function toggleSemaphore(Request $request, ElementType $elementType)
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        abort_unless(in_array($elementType->client_id, $allowedClientIds), 403);

        $elementType->update([
            'has_semaphore' => !$elementType->has_semaphore,
        ]);

        return response()->json([
            'success' => true,
            'message' => $elementType->has_semaphore
                ? 'Semáforo semanal activado para este tipo de activo.'
                : 'Semáforo semanal desactivado para este tipo de activo.',
            'has_semaphore' => (bool) $elementType->has_semaphore,
        ]);
    }

    private function isAjaxRequest(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }

    private function elementTypePayload(ElementType $elementType): array
    {
        return [
            'id' => $elementType->id,
            'client_id' => $elementType->client_id,
            'client_name' => $elementType->client?->name ?? '—',
            'name' => $elementType->name,
            'has_semaphore' => (bool) $elementType->has_semaphore,
            'status' => (bool) $elementType->status,
            'components_count' => (int) ($elementType->components_count ?? 0),
            'elements_count' => (int) ($elementType->elements_count ?? 0),
            'update_url' => route('admin.managed-element-types.update', $elementType),
            'destroy_url' => route('admin.managed-element-types.destroy', $elementType),
            'toggle_status_url' => route('admin.managed-element-types.toggle-status', $elementType),
            'toggle_semaphore_url' => route('admin.managed-element-types.toggle-semaphore', $elementType),
        ];
    }
}
