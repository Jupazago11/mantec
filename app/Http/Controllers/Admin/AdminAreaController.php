<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminAreaController extends Controller
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

        $selectedCodes = collect($request->input('codes', []))
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        $selectedStatuses = collect($request->input('statuses', []))
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        $baseQuery = Area::query()
            ->with('client')
            ->withCount('elements')
            ->whereIn('client_id', $clients->pluck('id'));

        if (!empty($selectedClientIds)) {
            $baseQuery->whereIn('client_id', $selectedClientIds);
        }

        if (!empty($selectedNames)) {
            $baseQuery->whereIn('name', $selectedNames);
        }

        if (!empty($selectedCodes)) {
            $baseQuery->whereIn('code', $selectedCodes);
        }

        if (!empty($selectedStatuses)) {
            $baseQuery->whereIn('status', array_map(fn ($v) => (int) $v, $selectedStatuses));
        }

        $areas = (clone $baseQuery)
            ->orderBy('client_id')
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        $allAreas = Area::query()
            ->whereIn('client_id', $clients->pluck('id'))
            ->orderBy('name')
            ->get(['id', 'client_id', 'name', 'code']);

        $clientFilterOptions = $showClientColumn
            ? $clients->map(fn ($client) => [
                'value' => (string) $client->id,
                'label' => $client->name,
            ])->values()
            : collect();

        $nameFilterOptions = $allAreas->pluck('name')
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $codeFilterOptions = $allAreas->pluck('code')
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
            'codes' => $codeFilterOptions,
            'statuses' => $statusFilterOptions,
        ];

        $activeFilters = [
            'client_ids' => $selectedClientIds,
            'names' => $selectedNames,
            'codes' => $selectedCodes,
            'statuses' => $selectedStatuses,
        ];

        return view('admin.managed-areas.index', [
            'clients' => $clients,
            'singleClient' => $singleClient,
            'showClientColumn' => $showClientColumn,
            'areas' => $areas,
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('areas', 'name')->where(function ($query) use ($request) {
                    return $query->where('client_id', $request->input('client_id'));
                }),
            ],
            'code' => ['nullable', 'string', 'max:255'],
        ], [
            'name.unique' => 'Ya existe un área con ese nombre para este cliente.',
        ]);

        $code = trim((string) ($validated['code'] ?? ''));

        if ($code !== '') {
            $codeExists = Area::query()
                ->where('client_id', $validated['client_id'])
                ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
                ->exists();

            if ($codeExists) {
                return back()
                    ->withErrors(['code' => 'Ya existe un área con ese código para este cliente.'])
                    ->withInput();
            }
        }

        Area::create([
            'client_id' => $validated['client_id'],
            'name' => trim($validated['name']),
            'code' => $code !== '' ? $code : null,
            'status' => true,
        ]);

        return redirect()
            ->route('admin.managed-areas.index', $this->buildRedirectQuery($request))
            ->with('success', 'Área creada correctamente.');
    }

    public function update(Request $request, Area $area): RedirectResponse
    {
        $user = auth()->user();

        $allowedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->toArray();

        abort_unless(in_array($area->client_id, $allowedClientIds), 403);

        $validated = $request->validate([
            'client_id' => ['required', 'integer', Rule::in($allowedClientIds)],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('areas', 'name')
                    ->ignore($area->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('client_id', $request->input('client_id'));
                    }),
            ],
            'code' => ['nullable', 'string', 'max:255'],
        ], [
            'name.unique' => 'Ya existe un área con ese nombre para este cliente.',
        ]);

        $code = trim((string) ($validated['code'] ?? ''));

        if ($code !== '') {
            $codeExists = Area::query()
                ->where('id', '!=', $area->id)
                ->where('client_id', $validated['client_id'])
                ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
                ->exists();

            if ($codeExists) {
                return back()
                    ->withErrors(['code' => 'Ya existe un área con ese código para este cliente.'])
                    ->withInput();
            }
        }

        $area->update([
            'client_id' => $validated['client_id'],
            'name' => trim($validated['name']),
            'code' => $code !== '' ? $code : null,
        ]);

        return redirect()
            ->route('admin.managed-areas.index', $this->buildRedirectQuery($request))
            ->with('success', 'Área actualizada correctamente.');
    }

    public function destroy(Request $request, Area $area): RedirectResponse
    {
        $user = auth()->user();

        $allowedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->toArray();

        abort_unless(in_array($area->client_id, $allowedClientIds), 403);

        $area->delete();

        return redirect()
            ->route('admin.managed-areas.index', $this->buildRedirectQuery($request))
            ->with('success', 'Área eliminada correctamente.');
    }

    public function toggleStatus(Request $request, Area $area): RedirectResponse
    {
        $user = auth()->user();

        $allowedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->toArray();

        abort_unless(in_array($area->client_id, $allowedClientIds), 403);

        $area->update([
            'status' => !$area->status,
        ]);

        return redirect()
            ->route('admin.managed-areas.index', $this->buildRedirectQuery($request))
            ->with('success', 'Estado del área actualizado correctamente.');
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

        foreach ((array) $request->input('redirect_codes', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['codes'][] = $value;
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
