<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Client;
use App\Models\Component;
use App\Models\Element;
use App\Models\ElementType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminElementController extends Controller
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

        $elementsQuery = Element::with(['area.client', 'elementType', 'components'])
            ->whereHas('area', function ($query) use ($allowedClientIds, $selectedClientId) {
                $query->whereIn('client_id', $allowedClientIds);

                if ($selectedClientId && in_array($selectedClientId, $allowedClientIds)) {
                    $query->where('client_id', $selectedClientId);
                }
            })
            ->withCount(['components', 'reportDetails']);

        if ($request->filled('name')) {
            $value = trim($request->name);
            $elementsQuery->where('name', 'ilike', '%' . $value . '%');
        }

        $elements = $elementsQuery
            ->orderByDesc('id')
            ->paginate(8)
            ->withQueryString();

        $componentsByClientAndType = Component::with('elementType')
            ->whereHas('elementType')
            ->where('status', true)
            ->whereIn('client_id', $allowedClientIds)
            ->orderBy('name')
            ->get()
            ->groupBy(function ($component) {
                return $component->client_id . '_' . $component->element_type_id;
            });

        return view('admin.managed-elements.index', compact(
            'clients',
            'singleClient',
            'selectedClientId',
            'elements',
            'componentsByClientAndType'
        ));
    }

    public function getAreasByClient(Client $client): JsonResponse
    {
        $this->authorizeClient($client->id);

        $areas = Area::where('client_id', $client->id)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($areas);
    }

    public function getElementTypesByClient(Client $client): JsonResponse
    {
        $this->authorizeClient($client->id);

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
            'area_id' => ['required', 'exists:areas,id'],
            'element_type_id' => ['required', 'exists:element_types,id'],
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:100'],
        ]);

        $area = Area::findOrFail($validated['area_id']);
        $elementType = ElementType::findOrFail($validated['element_type_id']);

        if ((int) $area->client_id !== (int) $validated['client_id']) {
            return back()
                ->withErrors(['area_id' => 'El área no pertenece al cliente seleccionado.'])
                ->withInput();
        }

        if ((int) $elementType->client_id !== (int) $validated['client_id']) {
            return back()
                ->withErrors(['element_type_id' => 'El tipo de activo no pertenece al cliente seleccionado.'])
                ->withInput();
        }

        $exists = Element::where('area_id', $validated['area_id'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($validated['name'])])
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['name' => 'Ya existe un activo con ese nombre en el área seleccionada.'])
                ->withInput();
        }

        Element::create([
            'area_id' => $validated['area_id'],
            'element_type_id' => $validated['element_type_id'],
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'status' => true,
        ]);

        return redirect()
            ->route('admin.managed-elements.index', $this->filterQuery($request))
            ->with('success', 'Activo creado correctamente.');
    }

    public function update(Request $request, Element $element): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfElementOutsideScope($element, $allowedClientIds);

        $validated = $request->validate([
            'client_id' => ['required', Rule::in($allowedClientIds)],
            'area_id' => ['required', 'exists:areas,id'],
            'element_type_id' => ['required', 'exists:element_types,id'],
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:100'],
        ]);

        $area = Area::findOrFail($validated['area_id']);
        $elementType = ElementType::findOrFail($validated['element_type_id']);

        if ((int) $area->client_id !== (int) $validated['client_id']) {
            return back()
                ->withErrors(['area_id' => 'El área no pertenece al cliente seleccionado.'])
                ->withInput();
        }

        if ((int) $elementType->client_id !== (int) $validated['client_id']) {
            return back()
                ->withErrors(['element_type_id' => 'El tipo de activo no pertenece al cliente seleccionado.'])
                ->withInput();
        }

        $exists = Element::where('area_id', $validated['area_id'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($validated['name'])])
            ->where('id', '<>', $element->id)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['name' => 'Ya existe un activo con ese nombre en el área seleccionada.'])
                ->withInput();
        }

        $element->update([
            'area_id' => $validated['area_id'],
            'element_type_id' => $validated['element_type_id'],
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
        ]);

        return redirect()
            ->route('admin.managed-elements.index', $this->filterQuery($request))
            ->with('success', 'Activo actualizado correctamente.');
    }

    public function syncComponents(Request $request, Element $element): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfElementOutsideScope($element, $allowedClientIds);

        $element->loadMissing('area');

        $clientId = $element->area->client_id;

        $validated = $request->validate([
            'components' => ['nullable', 'array'],
            'components.*' => ['integer'],
        ]);

        $componentIds = collect($validated['components'] ?? [])->map(fn ($id) => (int) $id)->values();

        $validComponentIds = Component::where('client_id', $clientId)
            ->where('status', true)
            ->pluck('id');

        $invalidIds = $componentIds->diff($validComponentIds);

        if ($invalidIds->isNotEmpty()) {
            return redirect()
                ->route('admin.managed-elements.index', $this->filterQuery($request))
                ->with('success', 'Se detectaron componentes inválidos para este cliente.');
        }

        $element->components()->sync($componentIds->toArray());

        return redirect()
            ->route('admin.managed-elements.index', $this->filterQuery($request))
            ->with('success', 'Componentes del activo actualizados correctamente.');
    }

    public function destroy(Request $request, Element $element): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfElementOutsideScope($element, $allowedClientIds);

        if ($element->hasDependencies()) {
            return redirect()
                ->route('admin.managed-elements.index', $this->filterQuery($request))
                ->with('success', 'El activo tiene registros asociados y no puede eliminarse.');
        }

        $element->delete();

        return redirect()
            ->route('admin.managed-elements.index', $this->filterQuery($request))
            ->with('success', 'Activo eliminado correctamente.');
    }

    public function toggleStatus(Request $request, Element $element): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfElementOutsideScope($element, $allowedClientIds);

        if (!$element->hasDependencies()) {
            return redirect()
                ->route('admin.managed-elements.index', $this->filterQuery($request))
                ->with('success', 'Este activo no tiene dependencias. Puedes eliminarlo si lo deseas.');
        }

        $element->update([
            'status' => !$element->status,
        ]);

        return redirect()
            ->route('admin.managed-elements.index', $this->filterQuery($request))
            ->with('success', 'Estado del activo actualizado correctamente.');
    }

    private function authorizeClient(int $clientId): void
    {
        $allowedClientIds = Auth::user()->clients()->pluck('clients.id')->toArray();

        abort_unless(in_array($clientId, $allowedClientIds), 403);
    }

    private function abortIfElementOutsideScope(Element $element, array $allowedClientIds): void
    {
        $element->loadMissing('area');

        if (!$element->area || !in_array($element->area->client_id, $allowedClientIds)) {
            abort(403, 'No autorizado para gestionar este activo.');
        }
    }

    private function filterQuery(Request $request): array
    {
        return $request->only(['client_id', 'name', 'page']);
    }
}