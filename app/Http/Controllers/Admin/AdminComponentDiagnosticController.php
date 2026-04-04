<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Component;
use App\Models\Diagnostic;
use App\Models\ElementType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminComponentDiagnosticController extends Controller
{
    public function index(): View
    {
        $clients = $this->getScopedClients();

        $singleClient = $clients->count() === 1 ? $clients->first() : null;

        return view('admin.managed-component-diagnostics.index', compact(
            'clients',
            'singleClient'
        ));
    }

    public function getElementTypes(Client $client): JsonResponse
    {
        $this->authorizeClient($client->id);

        $data = ElementType::query()
            ->where('client_id', $client->id)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($data);
    }

    public function getComponents(ElementType $elementType): JsonResponse
    {
        $this->authorizeClient($elementType->client_id);

        $data = Component::query()
            ->where('element_type_id', $elementType->id)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($data);
    }

    public function getDiagnostics(Client $client): JsonResponse
    {
        $this->authorizeClient($client->id);

        $data = Diagnostic::query()
            ->where('client_id', $client->id)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($data);
    }

    public function getAssigned(Component $component): JsonResponse
    {
        $this->authorizeClient($component->client_id);

        return response()->json(
            $component->diagnostics()->pluck('diagnostic_id')
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'component_id' => ['required', 'integer', 'exists:components,id'],
            'diagnostics' => ['nullable', 'array'],
            'diagnostics.*' => ['integer', 'exists:diagnostics,id'],
        ]);

        $component = Component::query()->findOrFail($validated['component_id']);
        $this->authorizeClient($component->client_id);

        $diagnosticIds = collect($validated['diagnostics'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $validDiagnosticIds = Diagnostic::query()
            ->where('client_id', $component->client_id)
            ->whereIn('id', $diagnosticIds)
            ->pluck('id')
            ->all();

        $component->diagnostics()->sync($validDiagnosticIds);

        return back()->with('success', 'Diagnósticos asignados correctamente.');
    }
    private function getScopedClients()
    {
        $user = Auth::user();
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

    private function authorizeClient(int $clientId): void
    {
        $allowed = $this->getScopedClients()->pluck('id')->map(fn ($id) => (int) $id)->toArray();

        abort_unless(in_array((int) $clientId, $allowed, true), 403);
    }
}
