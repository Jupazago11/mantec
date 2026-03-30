<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminAreaController extends Controller
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

        $areasQuery = Area::with('client')
            ->whereIn('client_id', $allowedClientIds)
            ->withCount('elements');

        if ($selectedClientId && in_array($selectedClientId, $allowedClientIds)) {
            $areasQuery->where('client_id', $selectedClientId);
        }

        $areas = $areasQuery
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.managed-areas.index', compact(
            'clients',
            'singleClient',
            'areas',
            'selectedClientId'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50'],
            'client_id' => ['required', Rule::in($allowedClientIds)],
        ]);

        Area::create([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'client_id' => $validated['client_id'],
            'status' => true,
        ]);

        return redirect()
            ->route('admin.managed-areas.index')
            ->with('success', 'Área creada correctamente.');
    }

    public function update(Request $request, Area $area): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfAreaOutsideScope($area, $allowedClientIds);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50'],
            'client_id' => ['required', Rule::in($allowedClientIds)],
        ]);

        $area->update([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'client_id' => $validated['client_id'],
        ]);

        return redirect()
            ->route('admin.managed-areas.index')
            ->with('success', 'Área actualizada correctamente.');
    }

    public function destroy(Area $area): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfAreaOutsideScope($area, $allowedClientIds);

        if ($area->hasDependencies()) {
            return redirect()
                ->route('admin.managed-areas.index')
                ->with('success', 'El área tiene registros asociados y no puede eliminarse.');
        }

        $area->delete();

        return redirect()
            ->route('admin.managed-areas.index')
            ->with('success', 'Área eliminada correctamente.');
    }

    public function toggleStatus(Area $area): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfAreaOutsideScope($area, $allowedClientIds);

        if (!$area->hasDependencies()) {
            return redirect()
                ->route('admin.managed-areas.index')
                ->with('success', 'Esta área no tiene dependencias. Puedes eliminarla si lo deseas.');
        }

        $area->update([
            'status' => !$area->status,
        ]);

        return redirect()
            ->route('admin.managed-areas.index')
            ->with('success', 'Estado del área actualizado correctamente.');
    }

    private function abortIfAreaOutsideScope(Area $area, array $allowedClientIds): void
    {
        if (!in_array($area->client_id, $allowedClientIds)) {
            abort(403, 'No autorizado para gestionar esta área.');
        }
    }
}