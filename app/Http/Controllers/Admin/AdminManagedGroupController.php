<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Element;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminManagedGroupController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = auth()->user();
        $authRoleKey = $authUser->role?->key;

        abort_unless(in_array($authRoleKey, ['superadmin', 'admin_global', 'admin'], true), 403);

        if (in_array($authRoleKey, ['superadmin', 'admin_global'], true)) {
            $clients = Client::query()
                ->where('status', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        } else {
            $clients = $authUser->clients()
                ->where('clients.status', true)
                ->orderBy('clients.name')
                ->get(['clients.id', 'clients.name']);
        }

        $clientIds = $clients->pluck('id')->all();
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
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        $selectedStatuses = collect($request->input('statuses', []))
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        $baseQuery = Group::query()
            ->with([
                'client',
                'elements:id,group_id,name,element_type_id',
                'elements.elementType:id,name',
            ])
            ->withCount('elements')
            ->whereIn('client_id', $clientIds);

        if (!empty($selectedClientIds)) {
            $baseQuery->whereIn('client_id', array_map('intval', $selectedClientIds));
        }

        if (!empty($selectedNames)) {
            $baseQuery->whereIn('name', $selectedNames);
        }

        if (!empty($selectedStatuses)) {
            $baseQuery->whereIn('status', array_map(fn ($v) => (int) $v, $selectedStatuses));
        }

        $groups = (clone $baseQuery)
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        $allVisibleGroups = (clone $baseQuery)
            ->orderBy('name')
            ->get();

        $clientFilterOptions = $showClientColumn
            ? $clients->map(fn ($client) => [
                'value' => (string) $client->id,
                'label' => $client->name,
            ])->values()
            : collect();

        $nameFilterOptions = $allVisibleGroups->pluck('name')
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

        $availableElements = Element::query()
            ->with([
                'area:id,client_id,name',
                'elementType:id,name',
                'group:id,name',
            ])
            ->whereHas('area', function ($query) use ($clientIds) {
                $query->whereIn('client_id', $clientIds)
                    ->where('status', true);
            })
            ->where('status', true)
            ->orderBy('element_type_id')
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

        return view('admin.managed-groups.index', [
            'groups' => $groups,
            'clients' => $clients,
            'singleClient' => $singleClient,
            'showClientColumn' => $showClientColumn,
            'filterOptions' => $filterOptions,
            'activeFilters' => $activeFilters,
            'availableElements' => $availableElements,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $authUser = auth()->user();
        $allowedClientIds = $this->allowedClientIds($authUser);

        $validated = $request->validate([
            'client_id' => ['required', Rule::in($allowedClientIds)],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('groups', 'name')->where(function ($query) use ($request) {
                    return $query->where('client_id', $request->input('client_id'));
                }),
            ],
            'description' => ['nullable', 'string'],
        ]);

        Group::create([
            'client_id' => (int) $validated['client_id'],
            'name' => trim($validated['name']),
            'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
            'status' => true,
        ]);

        return redirect()
            ->route('admin.managed-groups.index', $this->buildRedirectQuery($request))
            ->with('success', 'Agrupación creada correctamente.');
    }

    public function update(Request $request, Group $group): RedirectResponse
    {
        $authUser = auth()->user();
        $allowedClientIds = $this->allowedClientIds($authUser);

        abort_unless(in_array((int) $group->client_id, $allowedClientIds, true), 403);

        $validated = $request->validate([
            'client_id' => ['required', Rule::in($allowedClientIds)],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('groups', 'name')
                    ->ignore($group->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('client_id', $request->input('client_id'));
                    }),
            ],
            'description' => ['nullable', 'string'],
        ]);

        $group->update([
            'client_id' => (int) $validated['client_id'],
            'name' => trim($validated['name']),
            'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
        ]);

        return redirect()
            ->route('admin.managed-groups.index', $this->buildRedirectQuery($request))
            ->with('success', 'Agrupación actualizada correctamente.');
    }

    public function toggleStatus(Request $request, Group $group): RedirectResponse
    {
        $authUser = auth()->user();
        $allowedClientIds = $this->allowedClientIds($authUser);

        abort_unless(in_array((int) $group->client_id, $allowedClientIds, true), 403);

        if (!$group->elements()->exists()) {
            return redirect()
                ->route('admin.managed-groups.index', $this->buildRedirectQuery($request))
                ->with('error', 'Esta agrupación no tiene activos asociados. Puedes eliminarla si lo deseas.');
        }

        $group->update([
            'status' => !$group->status,
        ]);

        return redirect()
            ->route('admin.managed-groups.index', $this->buildRedirectQuery($request))
            ->with('success', 'Estado de la agrupación actualizado correctamente.');
    }

    public function destroy(Request $request, Group $group): RedirectResponse
    {
        $authUser = auth()->user();
        $allowedClientIds = $this->allowedClientIds($authUser);

        abort_unless(in_array((int) $group->client_id, $allowedClientIds, true), 403);

        if ($group->elements()->exists()) {
            return redirect()
                ->route('admin.managed-groups.index', $this->buildRedirectQuery($request))
                ->with('error', 'No se puede eliminar la agrupación porque tiene activos asociados.');
        }

        $group->delete();

        return redirect()
            ->route('admin.managed-groups.index', $this->buildRedirectQuery($request))
            ->with('success', 'Agrupación eliminada correctamente.');
    }

    public function syncElements(Request $request, Group $group): RedirectResponse
    {
        $authUser = auth()->user();
        $allowedClientIds = $this->allowedClientIds($authUser);

        abort_unless(in_array((int) $group->client_id, $allowedClientIds, true), 403);

        $validated = $request->validate([
            'element_ids' => ['nullable', 'array'],
            'element_ids.*' => ['integer', 'exists:elements,id'],
        ]);

        $selectedElementIds = collect($validated['element_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (!empty($selectedElementIds)) {
            $validCount = Element::query()
                ->whereIn('id', $selectedElementIds)
                ->whereHas('area', function ($query) use ($group) {
                    $query->where('client_id', $group->client_id);
                })
                ->where('status', true)
                ->count();

            if ($validCount !== count($selectedElementIds)) {
                return redirect()
                    ->route('admin.managed-groups.index', $this->buildRedirectQuery($request))
                    ->with('error', 'Uno o más activos no pertenecen al cliente de la agrupación o no están activos.');
            }
        }

        DB::transaction(function () use ($group, $selectedElementIds) {
            Element::query()
                ->where('group_id', $group->id)
                ->update(['group_id' => null]);

            if (!empty($selectedElementIds)) {
                Element::query()
                    ->whereIn('id', $selectedElementIds)
                    ->update(['group_id' => $group->id]);
            }
        });

        return redirect()
            ->route('admin.managed-groups.index', $this->buildRedirectQuery($request))
            ->with('success', 'Activos de la agrupación actualizados correctamente.');
    }

    private function allowedClientIds($authUser): array
    {
        $authRoleKey = $authUser->role?->key;

        abort_unless(in_array($authRoleKey, ['superadmin', 'admin_global', 'admin'], true), 403);

        if (in_array($authRoleKey, ['superadmin', 'admin_global'], true)) {
            return Client::query()
                ->where('status', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return $authUser->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->map(fn ($id) => (int) $id)
            ->all();
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
