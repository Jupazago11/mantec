<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Component;
use App\Models\ElementType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminComponentController extends Controller
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

        $componentsQuery = Component::with(['client', 'elementType'])
            ->whereIn('client_id', $allowedClientIds)
            ->withCount(['elements', 'diagnostics', 'reportDetails']);

        if ($selectedClientId && in_array($selectedClientId, $allowedClientIds)) {
            $componentsQuery->where('client_id', $selectedClientId);
        }

        $components = $componentsQuery
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $elementTypes = ElementType::whereIn('client_id', $allowedClientIds)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        return view('admin.managed-components.index', compact(
            'clients',
            'singleClient',
            'selectedClientId',
            'components',
            'elementTypes'
        ));
    }

    public function getElementTypesByClient(Client $client): JsonResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        abort_unless(in_array($client->id, $allowedClientIds), 403);

        $elementTypes = ElementType::where('client_id', $client->id)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($elementTypes);
    }

    public function store(Request $request): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $validated = $request->validate([
            'client_id' => ['required', Rule::in($allowedClientIds)],
            'element_type_id' => ['required', 'exists:element_types,id'],
            'name' => ['required', 'string', 'max:120'],
            'is_default' => ['required', 'boolean'],
        ]);

        $elementType = ElementType::findOrFail($validated['element_type_id']);

        if ($elementType->client_id != $validated['client_id']) {
            return back()
                ->withErrors(['element_type_id' => 'El tipo de activo no pertenece al cliente seleccionado.'])
                ->withInput();
        }

        $exists = Component::where('client_id', $validated['client_id'])
            ->where('element_type_id', $validated['element_type_id'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($validated['name'])])
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['name' => 'Ya existe un componente con ese nombre para este cliente y tipo de activo.'])
                ->withInput();
        }

        Component::create([
            'client_id' => $validated['client_id'],
            'element_type_id' => $validated['element_type_id'],
            'name' => $validated['name'],
            'code' => null,
            'is_required' => false,
            'is_default' => $validated['is_default'],
            'status' => true,
        ]);

        return redirect()
            ->route('admin.managed-components.index')
            ->with('success', 'Componente creado correctamente.');
    }

    public function update(Request $request, Component $component): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfComponentOutsideScope($component, $allowedClientIds);

        $validated = $request->validate([
            'client_id' => ['required', Rule::in($allowedClientIds)],
            'element_type_id' => ['required', 'exists:element_types,id'],
            'name' => ['required', 'string', 'max:120'],
            'is_default' => ['required', 'boolean'],
        ]);

        $elementType = ElementType::findOrFail($validated['element_type_id']);

        if ($elementType->client_id != $validated['client_id']) {
            return back()
                ->withErrors(['element_type_id' => 'El tipo de activo no pertenece al cliente seleccionado.'])
                ->withInput();
        }

        $exists = Component::where('client_id', $validated['client_id'])
            ->where('element_type_id', $validated['element_type_id'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($validated['name'])])
            ->where('id', '<>', $component->id)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['name' => 'Ya existe un componente con ese nombre para este cliente y tipo de activo.'])
                ->withInput();
        }

        $component->update([
            'client_id' => $validated['client_id'],
            'element_type_id' => $validated['element_type_id'],
            'name' => $validated['name'],
            'is_default' => $validated['is_default'],
        ]);

        return redirect()
            ->route('admin.managed-components.index')
            ->with('success', 'Componente actualizado correctamente.');
    }

    public function destroy(Component $component): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfComponentOutsideScope($component, $allowedClientIds);

        if ($component->hasDependencies()) {
            return redirect()
                ->route('admin.managed-components.index')
                ->with('success', 'El componente tiene registros asociados y no puede eliminarse.');
        }

        $component->delete();

        return redirect()
            ->route('admin.managed-components.index')
            ->with('success', 'Componente eliminado correctamente.');
    }

    public function toggleStatus(Component $component): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfComponentOutsideScope($component, $allowedClientIds);

        if (!$component->hasDependencies()) {
            return redirect()
                ->route('admin.managed-components.index')
                ->with('success', 'Este componente no tiene dependencias. Puedes eliminarlo si lo deseas.');
        }

        $component->update([
            'status' => !$component->status,
        ]);

        return redirect()
            ->route('admin.managed-components.index')
            ->with('success', 'Estado del componente actualizado correctamente.');
    }

    private function abortIfComponentOutsideScope(Component $component, array $allowedClientIds): void
    {
        if (!in_array($component->client_id, $allowedClientIds)) {
            abort(403, 'No autorizado para gestionar este componente.');
        }
    }
}