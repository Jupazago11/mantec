<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Component;
use App\Models\Condition;
use App\Models\ElementType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminConditionController extends Controller
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

        $selectedElementTypeIds = collect($request->input('element_type_ids', []))
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

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

        $selectedStatuses = collect($request->input('statuses', []))
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        $elementTypes = ElementType::query()
            ->whereIn('client_id', $clients->pluck('id'))
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'client_id', 'name']);

        $baseQuery = Condition::query()
            ->with(['client', 'elementType'])
            ->withCount('reportDetails')
            ->whereIn('client_id', $clients->pluck('id'));

        if (!empty($selectedClientIds)) {
            $baseQuery->whereIn('client_id', $selectedClientIds);
        }

        if (!empty($selectedElementTypeIds)) {
            $baseQuery->whereIn('element_type_id', $selectedElementTypeIds);
        }

        if (!empty($selectedCodes)) {
            $baseQuery->whereIn('code', $selectedCodes);
        }

        if (!empty($selectedNames)) {
            $baseQuery->whereIn('name', $selectedNames);
        }

        if (!empty($selectedStatuses)) {
            $baseQuery->whereIn('status', array_map(fn ($value) => (int) $value, $selectedStatuses));
        }

        $conditions = (clone $baseQuery)
            ->orderBy('client_id')
            ->orderBy('element_type_id')
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

        $elementTypeFilterOptions = $elementTypes->map(function ($type) use ($showClientColumn, $clients) {
            $clientName = $clients->firstWhere('id', $type->client_id)?->name ?? '—';

            return [
                'value' => (string) $type->id,
                'label' => $showClientColumn
                    ? $clientName . ' - ' . $type->name
                    : $type->name,
            ];
        })->values();

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

        $statusFilterOptions = collect([
            ['value' => '1', 'label' => 'Activo'],
            ['value' => '0', 'label' => 'Inactivo'],
        ]);

        $filterOptions = [
            'client_ids' => $clientFilterOptions,
            'element_type_ids' => $elementTypeFilterOptions,
            'codes' => $codeFilterOptions,
            'names' => $nameFilterOptions,
            'statuses' => $statusFilterOptions,
        ];

        $activeFilters = [
            'client_ids' => $selectedClientIds,
            'element_type_ids' => $selectedElementTypeIds,
            'codes' => $selectedCodes,
            'names' => $selectedNames,
            'statuses' => $selectedStatuses,
        ];

        $preferredClientId = old('client_id');

        if (!$preferredClientId) {
            $preferredClientId = session('preferred_condition_client_id');
        }

        if (!$preferredClientId && $singleClient) {
            $preferredClientId = (string) $singleClient->id;
        }

        $preferredElementTypeId = old('element_type_id');

        if (!$preferredElementTypeId) {
            $preferredElementTypeId = session('preferred_condition_element_type_id');
        }

        return view('admin.managed-conditions.index', [
            'clients' => $clients,
            'singleClient' => $singleClient,
            'showClientColumn' => $showClientColumn,
            'conditions' => $conditions,
            'elementTypes' => $elementTypes,
            'preferredClientId',
            'preferredElementTypeId',
            'filterOptions' => $filterOptions,
            'activeFilters' => $activeFilters,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        $validated = $request->validate([
            'client_id' => ['required', 'integer', Rule::in($allowedClientIds)],
            'element_type_id' => ['required', 'integer', 'exists:element_types,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('conditions', 'code')->where(function ($query) use ($request) {
                    return $query
                        ->where('client_id', $request->input('client_id'))
                        ->where('element_type_id', $request->input('element_type_id'));
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'severity' => ['required', 'integer', 'min:0'],
            'color' => ['required', 'string', 'max:20'],
        ], [
            'code.unique' => 'Ya existe una condición con ese código para este cliente y tipo de activo.',
        ]);

        $elementType = ElementType::findOrFail($validated['element_type_id']);

        if ((int) $elementType->client_id !== (int) $validated['client_id']) {
            return back()
                ->withErrors(['element_type_id' => 'El tipo de activo no pertenece al cliente seleccionado.'])
                ->withInput();
        }

        $condition = Condition::create([
            'client_id' => (int) $validated['client_id'],
            'element_type_id' => (int) $validated['element_type_id'],
            'code' => trim($validated['code']),
            'name' => trim($validated['name']),
            'description' => filled($validated['description'] ?? null)
                ? trim($validated['description'])
                : null,
            'severity' => (int) $validated['severity'],
            'color' => trim($validated['color']),
            'status' => true,
        ]);

        if ($this->isAjaxRequest($request)) {
            $condition->load(['client', 'elementType']);

            return response()->json([
                'success' => true,
                'message' => 'Condición creada correctamente.',
                'condition' => [
                    'id' => $condition->id,
                    'client_id' => $condition->client_id,
                    'client_name' => $condition->client?->name ?? '—',
                    'element_type_id' => $condition->element_type_id,
                    'element_type_name' => $condition->elementType?->name ?? '—',
                    'code' => $condition->code,
                    'name' => $condition->name,
                    'description' => $condition->description,
                    'severity' => $condition->severity,
                    'color' => $condition->color,
                    'status' => (bool) $condition->status,
                    'report_details_count' => 0,
                    'update_url' => route('admin.managed-conditions.update', $condition),
                    'destroy_url' => route('admin.managed-conditions.destroy', $condition),
                ],
            ]);
        }

        return redirect()
            ->route('admin.managed-conditions.index', $this->buildRedirectQuery($request))
            ->with([
                'success' => 'Condición creada correctamente.',
                'preferred_condition_client_id' => (string) $validated['client_id'],
                'preferred_condition_element_type_id' => (string) $validated['element_type_id'],
            ]);
    }

    public function update(Request $request, Condition $condition): RedirectResponse|JsonResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        abort_unless(in_array($condition->client_id, $allowedClientIds), 403);

        $validated = $request->validate([
            'client_id' => ['required', 'integer', Rule::in($allowedClientIds)],
            'element_type_id' => ['required', 'integer', 'exists:element_types,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('conditions', 'code')
                    ->ignore($condition->id)
                    ->where(function ($query) use ($request) {
                        return $query
                            ->where('client_id', $request->input('client_id'))
                            ->where('element_type_id', $request->input('element_type_id'));
                    }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'severity' => ['required', 'integer', 'min:0'],
            'color' => ['required', 'string', 'max:20'],
        ], [
            'code.unique' => 'Ya existe una condición con ese código para este cliente y tipo de activo.',
        ]);

        $elementType = ElementType::findOrFail($validated['element_type_id']);

        if ((int) $elementType->client_id !== (int) $validated['client_id']) {
            return back()
                ->withErrors(['element_type_id' => 'El tipo de activo no pertenece al cliente seleccionado.'])
                ->withInput();
        }

        $condition->update([
            'client_id' => (int) $validated['client_id'],
            'element_type_id' => (int) $validated['element_type_id'],
            'code' => trim($validated['code']),
            'name' => trim($validated['name']),
            'description' => filled($validated['description'] ?? null)
                ? trim($validated['description'])
                : null,
            'severity' => (int) $validated['severity'],
            'color' => trim($validated['color']),
        ]);

        $condition->load('elementType');

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Condición actualizada correctamente.',
                'condition' => [
                    'id' => $condition->id,
                    'client_id' => $condition->client_id,
                    'element_type_id' => $condition->element_type_id,
                    'element_type_name' => $condition->elementType?->name ?? '—',
                    'code' => $condition->code,
                    'name' => $condition->name,
                    'description' => $condition->description,
                    'severity' => $condition->severity,
                    'color' => $condition->color,
                ],
            ]);
        }

        return redirect()
            ->route('admin.managed-conditions.index', $this->buildRedirectQuery($request))
            ->with('success', 'Condición actualizada correctamente.');
    }

    public function destroy(Request $request, Condition $condition): RedirectResponse|JsonResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        abort_unless(in_array((int) $condition->client_id, $allowedClientIds, true), 403);

        if ($condition->reportDetails()->exists()) {
            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la condición porque tiene reportes asociados.',
                ], 422);
            }

            return redirect()
                ->route('admin.managed-conditions.index', $this->buildRedirectQuery($request))
                ->with('error', 'No se puede eliminar la condición porque tiene reportes asociados.');
        }

        $condition->delete();

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Condición eliminada correctamente.',
                'condition_id' => $condition->id,
            ]);
        }

        return redirect()
            ->route('admin.managed-conditions.index', $this->buildRedirectQuery($request))
            ->with('success', 'Condición eliminada correctamente.');
    }

    public function toggleStatus(Request $request, Condition $condition): RedirectResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        abort_unless(in_array($condition->client_id, $allowedClientIds), 403);

        $condition->loadCount('reportDetails');

        $hasDependencies = ($condition->report_details_count ?? 0) > 0;

        if (!$hasDependencies) {
            return redirect()
                ->route('admin.managed-conditions.index', $this->buildRedirectQuery($request))
                ->with('error', 'Esta condición no tiene dependencias. Puedes eliminarla si lo deseas.');
        }

        $condition->update([
            'status' => !$condition->status,
        ]);

        return redirect()
            ->route('admin.managed-conditions.index', $this->buildRedirectQuery($request))
            ->with('success', 'Estado de la condición actualizado correctamente.');
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

    public function getComponents(Condition $condition): JsonResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        abort_unless(in_array($condition->client_id, $allowedClientIds), 403);

        $components = Component::query()
            ->where('client_id', $condition->client_id)
            ->where('element_type_id', $condition->element_type_id)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $assignedIds = $condition->components()
            ->pluck('components.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return response()->json([
            'components' => $components,
            'assigned_ids' => $assignedIds,
        ]);
    }

    public function syncComponents(Request $request, Condition $condition): RedirectResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        abort_unless(in_array($condition->client_id, $allowedClientIds), 403);

        $validated = $request->validate([
            'component_ids' => ['nullable', 'array'],
            'component_ids.*' => ['integer', 'exists:components,id'],
        ]);

        $componentIds = collect($validated['component_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (!empty($componentIds)) {
            $validCount = Component::query()
                ->whereIn('id', $componentIds)
                ->where('client_id', $condition->client_id)
                ->where('element_type_id', $condition->element_type_id)
                ->count();

            if ($validCount !== count($componentIds)) {
                return back()->withErrors([
                    'component_ids' => 'Uno o más componentes no pertenecen al cliente o tipo de activo de la condición.',
                ]);
            }
        }

        $condition->components()->sync($componentIds);

        return redirect()
            ->route('admin.managed-conditions.index', $this->buildRedirectQuery($request))
            ->with('success', 'Componentes de la condición actualizados correctamente.');
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

    public function toggleStatusAjax(Condition $condition, Request $request): JsonResponse
    {
        $allowedClientIds = $this->getScopedClients()->pluck('id')->toArray();

        abort_unless(
            in_array((int) $condition->client_id, $allowedClientIds, true),
            403,
            'No autorizado para modificar esta condición.'
        );

        $condition->status = !$condition->status;
        $condition->save();

        return response()->json([
            'success' => true,
            'status' => (bool) $condition->status,
            'label' => $condition->status ? 'Activo' : 'Inactivo',
            'message' => $condition->status
                ? 'Condición activada correctamente.'
                : 'Condición inactivada correctamente.',
        ]);
    }

    private function isAjaxRequest(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }
}
