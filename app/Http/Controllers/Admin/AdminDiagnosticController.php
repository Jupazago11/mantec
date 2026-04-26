<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Diagnostic;
use App\Models\ElementType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class AdminDiagnosticController extends Controller
{
    public function index(Request $request): View
    {
        $clients = $this->getScopedClients();

        $singleClient = $clients->count() === 1 ? $clients->first() : null;
        $showClientColumn = $clients->count() > 1;

        $elementTypes = ElementType::query()
            ->whereIn('client_id', $clients->pluck('id'))
            ->where('status', true)
            ->with('client')
            ->orderBy('name')
            ->get();

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



        $selectedDiagnosticNames = collect($request->input('diagnostic_names', []))
            ->filter()
            ->map(fn ($name) => (string) $name)
            ->values()
            ->all();

        $selectedStatuses = collect($request->input('statuses', []))
            ->filter(fn ($status) => $status !== null && $status !== '')
            ->map(fn ($status) => (string) $status)
            ->values()
            ->all();

        $baseQuery = Diagnostic::query()
            ->with(['client', 'elementType'])
            ->withCount(['components', 'reportDetails'])
            ->whereIn('client_id', $clients->pluck('id'));

        if (!empty($selectedClientIds)) {
            $baseQuery->whereIn('client_id', $selectedClientIds);
        }

        if (!empty($selectedElementTypeIds)) {
            $baseQuery->whereIn('element_type_id', $selectedElementTypeIds);
        }

        if (!empty($selectedDiagnosticNames)) {
            $baseQuery->whereIn('name', $selectedDiagnosticNames);
        }

        if (!empty($selectedStatuses)) {
            $baseQuery->whereIn('status', array_map(fn ($value) => (int) $value, $selectedStatuses));
}

        $diagnostics = (clone $baseQuery)
            ->orderBy('client_id')
            ->orderBy('element_type_id')
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        $clientFilterOptions = $showClientColumn
            ? $clients->map(fn ($client) => [
                'value' => (string) $client->id,
                'label' => $client->name,
            ])->values()
            : collect();


        $elementTypeFilterOptions = $elementTypes->map(fn ($elementType) => [
            'value' => (string) $elementType->id,
            'label' => $showClientColumn
                ? (($elementType->client?->name ?? '—') . ' - ' . $elementType->name)
                : $elementType->name,
        ])->values();


        $elementTypeFilterOptions = $elementTypes->map(fn ($elementType) => [
            'value' => (string) $elementType->id,
            'label' => $showClientColumn
                ? (($elementType->client?->name ?? '—') . ' - ' . $elementType->name)
                : $elementType->name,
        ])->values();

        $diagnosticNameFilterOptions = Diagnostic::query()
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
            'element_type_ids' => $elementTypeFilterOptions,
            'diagnostic_names' => $diagnosticNameFilterOptions,
            'statuses' => $statusFilterOptions,
        ];


        $activeFilters = [
            'client_ids' => $selectedClientIds,
            'element_type_ids' => $selectedElementTypeIds,
            'diagnostic_names' => $selectedDiagnosticNames,
            'statuses' => $selectedStatuses,
        ];

        $preferredClientId = old('client_id');

        if (!$preferredClientId) {
            $preferredClientId = session('preferred_diagnostic_client_id');
        }

        if (!$preferredClientId && $singleClient) {
            $preferredClientId = (string) $singleClient->id;
        }

        $preferredElementTypeId = old('element_type_id');

        if (!$preferredElementTypeId) {
            $preferredElementTypeId = session('preferred_diagnostic_element_type_id');
        }


        return view('admin.managed-diagnostics.index', compact(
            'clients',
            'singleClient',
            'showClientColumn',
            'elementTypes',
            'diagnostics',
            'filterOptions',
            'activeFilters',
            'preferredClientId',
            'preferredElementTypeId'
        ));

    }

public function store(Request $request): RedirectResponse|JsonResponse
{
    $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

    $validated = $request->validate([
        'client_id' => ['required', 'integer', Rule::in($allowedClientIds)],
        'element_type_id' => ['required', 'integer', 'exists:element_types,id'],
        'name' => ['required', 'string', 'max:255'],
    ]);

    $elementTypeBelongs = ElementType::query()
        ->where('id', $validated['element_type_id'])
        ->where('client_id', $validated['client_id'])
        ->exists();

    if (!$elementTypeBelongs) {
        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => 'El tipo de activo no pertenece al cliente seleccionado.',
                'errors' => [
                    'element_type_id' => ['El tipo de activo no pertenece al cliente seleccionado.'],
                ],
            ], 422);
        }

        return back()
            ->withErrors([
                'element_type_id' => 'El tipo de activo no pertenece al cliente seleccionado.',
            ])
            ->withInput();
    }

    $exists = Diagnostic::query()
        ->where('client_id', $validated['client_id'])
        ->where('element_type_id', $validated['element_type_id'])
        ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($validated['name']))])
        ->exists();

    if ($exists) {
        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un diagnóstico con ese nombre para ese cliente y tipo de activo.',
                'errors' => [
                    'name' => ['Ya existe un diagnóstico con ese nombre para ese cliente y tipo de activo.'],
                ],
            ], 422);
        }

        return back()
            ->withErrors([
                'name' => 'Ya existe un diagnóstico con ese nombre para ese cliente y tipo de activo.',
            ])
            ->withInput();
    }

    $diagnostic = Diagnostic::create([
        'client_id' => (int) $validated['client_id'],
        'element_type_id' => (int) $validated['element_type_id'],
        'name' => trim($validated['name']),
        'status' => true,
    ]);

    $diagnostic->load(['client', 'elementType']);
    $diagnostic->loadCount(['components', 'reportDetails']);

    if ($this->isAjaxRequest($request)) {
        return response()->json([
            'success' => true,
            'message' => 'Diagnóstico creado correctamente.',
            'diagnostic' => $this->diagnosticPayload($diagnostic),
        ]);
    }

    return redirect()
        ->route('admin.managed-diagnostics.index', $this->buildRedirectQuery($request))
        ->with([
            'success' => 'Diagnóstico creado correctamente.',
            'preferred_diagnostic_client_id' => (string) $validated['client_id'],
            'preferred_diagnostic_element_type_id' => (string) $validated['element_type_id'],
        ]);
}

public function update(Request $request, Diagnostic $diagnostic): RedirectResponse|JsonResponse
{
    $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

    abort_unless(in_array((int) $diagnostic->client_id, $allowedClientIds, true), 403);

    $validated = $request->validate([
        'client_id' => ['required', 'integer', Rule::in($allowedClientIds)],
        'element_type_id' => ['required', 'integer', 'exists:element_types,id'],
        'name' => ['required', 'string', 'max:255'],
        'status' => ['required', 'boolean'],
    ]);

    $elementTypeBelongs = ElementType::query()
        ->where('id', $validated['element_type_id'])
        ->where('client_id', $validated['client_id'])
        ->exists();

    if (!$elementTypeBelongs) {
        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => 'El tipo de activo no pertenece al cliente seleccionado.',
                'errors' => [
                    'element_type_id' => ['El tipo de activo no pertenece al cliente seleccionado.'],
                ],
            ], 422);
        }

        return back()
            ->withErrors([
                'element_type_id' => 'El tipo de activo no pertenece al cliente seleccionado.',
            ])
            ->withInput();
    }

    $exists = Diagnostic::query()
        ->where('id', '!=', $diagnostic->id)
        ->where('client_id', $validated['client_id'])
        ->where('element_type_id', $validated['element_type_id'])
        ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($validated['name']))])
        ->exists();

    if ($exists) {
        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un diagnóstico con ese nombre para ese cliente y tipo de activo.',
                'errors' => [
                    'name' => ['Ya existe un diagnóstico con ese nombre para ese cliente y tipo de activo.'],
                ],
            ], 422);
        }

        return back()
            ->withErrors([
                'name' => 'Ya existe un diagnóstico con ese nombre para ese cliente y tipo de activo.',
            ])
            ->withInput();
    }

    $diagnostic->update([
        'client_id' => (int) $validated['client_id'],
        'element_type_id' => (int) $validated['element_type_id'],
        'name' => trim($validated['name']),
        'status' => (bool) $validated['status'],
    ]);

    $diagnostic->load(['client', 'elementType']);
    $diagnostic->loadCount(['components', 'reportDetails']);

    if ($this->isAjaxRequest($request)) {
        return response()->json([
            'success' => true,
            'message' => 'Diagnóstico actualizado correctamente.',
            'diagnostic' => $this->diagnosticPayload($diagnostic),
        ]);
    }

    return redirect()
        ->route('admin.managed-diagnostics.index', $this->buildRedirectQuery($request))
        ->with('success', 'Diagnóstico actualizado correctamente.');
}

public function destroy(Request $request, Diagnostic $diagnostic): RedirectResponse|JsonResponse
{
    $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

    abort_unless(in_array((int) $diagnostic->client_id, $allowedClientIds, true), 403);

    $diagnostic->loadCount(['components', 'reportDetails']);

    $hasDependencies = (($diagnostic->components_count ?? 0) + ($diagnostic->report_details_count ?? 0)) > 0;

    if ($hasDependencies) {
        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Este diagnóstico no se puede eliminar porque ya tiene uso. Solo puedes inactivarlo.',
            ], 422);
        }

        return redirect()
            ->route('admin.managed-diagnostics.index', $this->buildRedirectQuery($request))
            ->with('error', 'Este diagnóstico no se puede eliminar porque ya tiene uso. Solo puedes inactivarlo.');
    }

    $diagnosticId = $diagnostic->id;
    $diagnostic->delete();

    if ($this->isAjaxRequest($request)) {
        return response()->json([
            'success' => true,
            'message' => 'Diagnóstico eliminado correctamente.',
            'diagnostic_id' => $diagnosticId,
        ]);
    }

    return redirect()
        ->route('admin.managed-diagnostics.index', $this->buildRedirectQuery($request))
        ->with('success', 'Diagnóstico eliminado correctamente.');
}

public function toggleStatus(Request $request, Diagnostic $diagnostic): RedirectResponse|JsonResponse
{
    $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

    abort_unless(in_array((int) $diagnostic->client_id, $allowedClientIds, true), 403);

    $diagnostic->update([
        'status' => !$diagnostic->status,
    ]);

    $message = $diagnostic->status
        ? 'Diagnóstico activado correctamente.'
        : 'Diagnóstico inactivado correctamente.';

    if ($this->isAjaxRequest($request)) {
        return response()->json([
            'success' => true,
            'message' => $message,
            'status' => (bool) $diagnostic->status,
            'label' => $diagnostic->status ? 'Activo' : 'Inactivo',
        ]);
    }

    return redirect()
        ->route('admin.managed-diagnostics.index', $this->buildRedirectQuery($request))
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

        foreach ((array) $request->input('redirect_element_type_ids', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['element_type_ids'][] = $value;
            }
        }

        foreach ((array) $request->input('redirect_diagnostic_names', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['diagnostic_names'][] = $value;
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

    private function diagnosticPayload(Diagnostic $diagnostic): array
    {
        return [
            'id' => $diagnostic->id,
            'client_id' => $diagnostic->client_id,
            'client_name' => $diagnostic->client?->name ?? '—',
            'element_type_id' => $diagnostic->element_type_id,
            'element_type_name' => $diagnostic->elementType?->name ?? '—',
            'name' => $diagnostic->name,
            'status' => (bool) $diagnostic->status,
            'components_count' => (int) ($diagnostic->components_count ?? 0),
            'report_details_count' => (int) ($diagnostic->report_details_count ?? 0),
            'update_url' => route('admin.managed-diagnostics.update', $diagnostic),
            'destroy_url' => route('admin.managed-diagnostics.destroy', $diagnostic),
            'toggle_status_url' => route('admin.managed-diagnostics.toggle-status', $diagnostic),
        ];
    }
}
