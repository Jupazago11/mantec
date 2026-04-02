<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Condition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminConditionController extends Controller
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

        $selectedCodes = collect($request->input('codes', []))
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        $selectedNames = collect($request->input('names', []))
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        $baseQuery = Condition::query()
            ->with('client')
            ->withCount('reportDetails')
            ->whereIn('client_id', $clients->pluck('id'));

        if (!empty($selectedClientIds)) {
            $baseQuery->whereIn('client_id', $selectedClientIds);
        }

        if (!empty($selectedCodes)) {
            $baseQuery->whereIn('code', $selectedCodes);
        }

        if (!empty($selectedNames)) {
            $baseQuery->whereIn('name', $selectedNames);
        }

        $conditions = (clone $baseQuery)
            ->orderBy('client_id')
            ->orderBy('severity')
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        $clientFilterOptions = $showClientColumn
            ? $clients->map(fn ($client) => [
                'value' => (string) $client->id,
                'label' => $client->name,
            ])->values()
            : collect();

        $allConditions = Condition::query()
            ->whereIn('client_id', $clients->pluck('id'))
            ->orderBy('severity')
            ->orderBy('name')
            ->get(['code', 'name']);

        $codeFilterOptions = $allConditions
            ->pluck('code')
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $nameFilterOptions = $allConditions
            ->pluck('name')
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $filterOptions = [
            'client_ids' => $clientFilterOptions,
            'codes' => $codeFilterOptions,
            'names' => $nameFilterOptions,
        ];

        $activeFilters = [
            'client_ids' => $selectedClientIds,
            'codes' => $selectedCodes,
            'names' => $selectedNames,
        ];

        return view('admin.managed-conditions.index', [
            'clients' => $clients,
            'singleClient' => $singleClient,
            'showClientColumn' => $showClientColumn,
            'conditions' => $conditions,
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
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('conditions', 'code')->where(function ($query) use ($request) {
                    return $query->where('client_id', $request->input('client_id'));
                }),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('conditions', 'name')->where(function ($query) use ($request) {
                    return $query->where('client_id', $request->input('client_id'));
                }),
            ],
            'description' => ['nullable', 'string'],
            'severity' => ['required', 'integer', 'min:1'],
            'color' => ['required', 'string', 'max:20'],
        ], [
            'code.unique' => 'Ya existe una condición con ese código para este cliente.',
            'name.unique' => 'Ya existe una condición con ese nombre para este cliente.',
        ]);

        Condition::create([
            'client_id' => $validated['client_id'],
            'code' => trim($validated['code']),
            'name' => trim($validated['name']),
            'description' => $validated['description'] ? trim($validated['description']) : null,
            'severity' => (int) $validated['severity'],
            'color' => trim($validated['color']),
            'status' => true,
        ]);

        return redirect()
            ->route('admin.managed-conditions.index', $this->buildRedirectQuery($request))
            ->with('success', 'Condición creada correctamente.');
    }

    public function update(Request $request, Condition $condition): RedirectResponse
    {
        $user = auth()->user();

        $allowedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->toArray();

        abort_unless(in_array($condition->client_id, $allowedClientIds), 403);

        $validated = $request->validate([
            'client_id' => ['required', 'integer', Rule::in($allowedClientIds)],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('conditions', 'code')
                    ->ignore($condition->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('client_id', $request->input('client_id'));
                    }),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('conditions', 'name')
                    ->ignore($condition->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('client_id', $request->input('client_id'));
                    }),
            ],
            'description' => ['nullable', 'string'],
            'severity' => ['required', 'integer', 'min:1'],
            'color' => ['required', 'string', 'max:20'],
        ], [
            'code.unique' => 'Ya existe una condición con ese código para este cliente.',
            'name.unique' => 'Ya existe una condición con ese nombre para este cliente.',
        ]);

        $condition->update([
            'client_id' => $validated['client_id'],
            'code' => trim($validated['code']),
            'name' => trim($validated['name']),
            'description' => $validated['description'] ? trim($validated['description']) : null,
            'severity' => (int) $validated['severity'],
            'color' => trim($validated['color']),
            'status' => $condition->status,
        ]);

        return redirect()
            ->route('admin.managed-conditions.index', $this->buildRedirectQuery($request))
            ->with('success', 'Condición actualizada correctamente.');
    }

    public function destroy(Request $request, Condition $condition): RedirectResponse
    {
        $user = auth()->user();

        $allowedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->toArray();

        abort_unless(in_array($condition->client_id, $allowedClientIds), 403);

        $condition->delete();

        return redirect()
            ->route('admin.managed-conditions.index', $this->buildRedirectQuery($request))
            ->with('success', 'Condición eliminada correctamente.');
    }

    public function toggleStatus(Request $request, Condition $condition): RedirectResponse
    {
        $user = auth()->user();

        $allowedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->toArray();

        abort_unless(in_array($condition->client_id, $allowedClientIds), 403);

        $condition->update([
            'status' => !$condition->status,
        ]);

        return redirect()
            ->route('admin.managed-conditions.index', $this->buildRedirectQuery($request))
            ->with('success', 'Estado de la condición actualizado correctamente.');
    }

    private function buildRedirectQuery(Request $request): array
    {
        $query = [];

        foreach ((array) $request->input('redirect_client_ids', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['client_ids'][] = $value;
            }
        }

        foreach ((array) $request->input('redirect_codes', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['codes'][] = $value;
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
