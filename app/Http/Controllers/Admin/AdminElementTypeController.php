<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ElementType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminElementTypeController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = Auth::user();

        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $clients = Client::whereIn('id', $allowedClientIds)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $singleClient = $clients->count() === 1 ? $clients->first() : null;
        $selectedClientId = $request->filled('client_id') ? (int) $request->client_id : null;

        $elementTypesQuery = ElementType::with('client')
            ->whereIn('client_id', $allowedClientIds)
            ->withCount(['components', 'elements']);

        if ($selectedClientId && in_array($selectedClientId, $allowedClientIds)) {
            $elementTypesQuery->where('client_id', $selectedClientId);
        }

        $elementTypes = $elementTypesQuery
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.managed-element-types.index', compact(
            'clients',
            'singleClient',
            'selectedClientId',
            'elementTypes'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $validated = $request->validate([
            'client_id' => ['required', Rule::in($allowedClientIds)],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('element_types', 'name')->where(function ($query) use ($request) {
                    return $query->where('client_id', $request->client_id);
                }),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        ElementType::create([
            'client_id' => $validated['client_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => true,
        ]);

        return redirect()
            ->route('admin.managed-element-types.index')
            ->with('success', 'Tipo de activo creado correctamente.');
    }

    public function update(Request $request, ElementType $elementType): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfElementTypeOutsideScope($elementType, $allowedClientIds);

        $validated = $request->validate([
            'client_id' => ['required', Rule::in($allowedClientIds)],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('element_types', 'name')
                    ->ignore($elementType->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('client_id', $request->client_id);
                    }),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $elementType->update([
            'client_id' => $validated['client_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('admin.managed-element-types.index')
            ->with('success', 'Tipo de activo actualizado correctamente.');
    }

    public function destroy(ElementType $elementType): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfElementTypeOutsideScope($elementType, $allowedClientIds);

        if ($elementType->hasDependencies()) {
            return redirect()
                ->route('admin.managed-element-types.index')
                ->with('success', 'El tipo de activo tiene registros asociados y no puede eliminarse.');
        }

        $elementType->delete();

        return redirect()
            ->route('admin.managed-element-types.index')
            ->with('success', 'Tipo de activo eliminado correctamente.');
    }

    public function toggleStatus(ElementType $elementType): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfElementTypeOutsideScope($elementType, $allowedClientIds);

        if (!$elementType->hasDependencies()) {
            return redirect()
                ->route('admin.managed-element-types.index')
                ->with('success', 'Este tipo de activo no tiene dependencias. Puedes eliminarlo si lo deseas.');
        }

        $elementType->update([
            'status' => !$elementType->status,
        ]);

        return redirect()
            ->route('admin.managed-element-types.index')
            ->with('success', 'Estado del tipo de activo actualizado correctamente.');
    }

    private function abortIfElementTypeOutsideScope(ElementType $elementType, array $allowedClientIds): void
    {
        if (!in_array($elementType->client_id, $allowedClientIds)) {
            abort(403, 'No autorizado para gestionar este tipo de activo.');
        }
    }
}