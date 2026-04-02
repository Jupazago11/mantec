<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Diagnostic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminDiagnosticController extends Controller
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

        $selectedDiagnosticNames = collect($request->input('diagnostic_names', []))
            ->filter()
            ->map(fn ($name) => (string) $name)
            ->values()
            ->all();

        $baseQuery = Diagnostic::query()
            ->with('client')
            ->whereIn('client_id', $clients->pluck('id'));

        if (!empty($selectedClientIds)) {
            $baseQuery->whereIn('client_id', $selectedClientIds);
        }

        if (!empty($selectedDiagnosticNames)) {
            $baseQuery->whereIn('name', $selectedDiagnosticNames);
        }

        $diagnostics = (clone $baseQuery)
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

        $diagnosticNameFilterOptions = Diagnostic::query()
            ->whereIn('client_id', $clients->pluck('id'))
            ->pluck('name')
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $filterOptions = [
            'client_ids' => $clientFilterOptions,
            'diagnostic_names' => $diagnosticNameFilterOptions,
        ];

        $activeFilters = [
            'client_ids' => $selectedClientIds,
            'diagnostic_names' => $selectedDiagnosticNames,
        ];

        return view('admin.managed-diagnostics.index', [
            'clients' => $clients,
            'singleClient' => $singleClient,
            'showClientColumn' => $showClientColumn,
            'diagnostics' => $diagnostics,
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
                Rule::unique('diagnostics', 'name')->where(function ($query) use ($request) {
                    return $query->where('client_id', $request->input('client_id'));
                }),
            ],
            'status' => ['required', 'boolean'],
        ], [
            'name.unique' => 'Ya existe un diagnóstico con ese nombre para este cliente.',
        ]);

        Diagnostic::create([
            'client_id' => $validated['client_id'],
            'name' => trim($validated['name']),
            'status' => (bool) $validated['status'],
        ]);

        return redirect()
            ->route('admin.managed-diagnostics.index', $this->buildRedirectQuery($request))
            ->with('success', 'Diagnóstico creado correctamente.');
    }

    public function update(Request $request, Diagnostic $diagnostic): RedirectResponse
    {
        $user = auth()->user();

        $allowedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->toArray();

        abort_unless(in_array($diagnostic->client_id, $allowedClientIds), 403);

        $validated = $request->validate([
            'client_id' => ['required', 'integer', Rule::in($allowedClientIds)],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('diagnostics', 'name')
                    ->ignore($diagnostic->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('client_id', $request->input('client_id'));
                    }),
            ],
            'status' => ['required', 'boolean'],
        ], [
            'name.unique' => 'Ya existe un diagnóstico con ese nombre para este cliente.',
        ]);

        $diagnostic->update([
            'client_id' => $validated['client_id'],
            'name' => trim($validated['name']),
            'status' => (bool) $validated['status'],
        ]);

        return redirect()
            ->route('admin.managed-diagnostics.index', $this->buildRedirectQuery($request))
            ->with('success', 'Diagnóstico actualizado correctamente.');
    }

    public function destroy(Request $request, Diagnostic $diagnostic): RedirectResponse
    {
        $user = auth()->user();

        $allowedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->toArray();

        abort_unless(in_array($diagnostic->client_id, $allowedClientIds), 403);

        $diagnostic->delete();

        return redirect()
            ->route('admin.managed-diagnostics.index', $this->buildRedirectQuery($request))
            ->with('success', 'Diagnóstico eliminado correctamente.');
    }

    private function buildRedirectQuery(Request $request): array
    {
        $query = [];

        foreach ((array) $request->input('redirect_client_ids', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['client_ids'][] = $value;
            }
        }

        foreach ((array) $request->input('redirect_diagnostic_names', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['diagnostic_names'][] = $value;
            }
        }

        if ($request->filled('redirect_page')) {
            $query['page'] = $request->input('redirect_page');
        }

        return $query;
    }
}

