<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ElementType;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminElementTypeController extends Controller
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

        $selectedClientIds = $showClientColumn
            ? collect($request->input('client_ids', []))->filter()->map(fn ($id) => (string) $id)->values()->all()
            : ($singleClient ? [(string) $singleClient->id] : []);

        $selectedNames = collect($request->input('names', []))
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        $baseQuery = ElementType::query()
            ->with('client')
            ->whereIn('client_id', $clients->pluck('id'));

        if (!empty($selectedClientIds)) {
            $baseQuery->whereIn('client_id', $selectedClientIds);
        }

        if (!empty($selectedNames)) {
            $baseQuery->whereIn('name', $selectedNames);
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

        $filterOptions = [
            'client_ids' => $clientFilterOptions,
            'names' => $nameFilterOptions,
        ];

        $activeFilters = [
            'client_ids' => $selectedClientIds,
            'names' => $selectedNames,
        ];

        return view('admin.managed-element-types.index', [
            'clients' => $clients,
            'singleClient' => $singleClient,
            'showClientColumn' => $showClientColumn,
            'elementTypes' => $elementTypes,
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
            'client_id' => [
                'required',
                'integer',
                Rule::in($allowedClientIds),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('element_types', 'name')->where(function ($query) use ($request) {
                    return $query->where('client_id', $request->input('client_id'));
                }),
            ],
        ], [
            'name.unique' => 'Ya existe un tipo de activo con ese nombre para este cliente.',
        ]);

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
        $user = auth()->user();

        $allowedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->toArray();

        abort_unless(in_array($elementType->client_id, $allowedClientIds), 403);

        $validated = $request->validate([
            'client_id' => [
                'required',
                'integer',
                Rule::in($allowedClientIds),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('element_types', 'name')
                    ->ignore($elementType->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('client_id', $request->input('client_id'));
                    }),
            ],
            'status' => ['required', 'boolean'],
        ], [
            'name.unique' => 'Ya existe un tipo de activo con ese nombre para este cliente.',
        ]);

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
        $user = auth()->user();

        $allowedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->toArray();

        abort_unless(in_array($elementType->client_id, $allowedClientIds), 403);

        $elementType->delete();

        return redirect()
            ->route('admin.managed-element-types.index', $this->buildRedirectQuery($request))
            ->with('success', 'Tipo de activo eliminado correctamente.');
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

        if ($request->filled('redirect_page')) {
            $query['page'] = $request->input('redirect_page');
        }

        return $query;
    }
}
