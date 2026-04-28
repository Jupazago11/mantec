<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        $clients = Client::withCount([
            'areas',
            'users',
            'elementTypes',
            'components',
            'diagnostics',
            'conditions',
        ])
            ->orderByDesc('id')
            ->get();

        return view('admin.clients.index', compact('clients'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:clients,name'],
            'obs' => ['nullable', 'string'],
        ]);

        $client = Client::create([
            'name' => trim($validated['name']),
            'obs' => filled($validated['obs'] ?? null) ? trim($validated['obs']) : null,
            'status' => true,
        ]);

        $client->loadCount([
            'areas',
            'users',
            'elementTypes',
            'components',
            'diagnostics',
            'conditions',
        ]);

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Cliente creado correctamente.',
                'client' => $this->clientPayload($client),
            ]);
        }

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Cliente creado correctamente.');
    }

    public function update(Request $request, Client $client): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:clients,name,' . $client->id],
            'obs' => ['nullable', 'string'],
        ]);

        $client->update([
            'name' => trim($validated['name']),
            'obs' => filled($validated['obs'] ?? null) ? trim($validated['obs']) : null,
        ]);

        $client->loadCount([
            'areas',
            'users',
            'elementTypes',
            'components',
            'diagnostics',
            'conditions',
        ]);

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Cliente actualizado correctamente.',
                'client' => $this->clientPayload($client),
            ]);
        }

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Request $request, Client $client): RedirectResponse|JsonResponse
    {
        $client->loadCount([
            'areas',
            'users',
            'elementTypes',
            'components',
            'diagnostics',
            'conditions',
        ]);

        if ($client->hasDependencies()) {
            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El cliente tiene registros asociados y no puede eliminarse. Solo puedes inactivarlo.',
                ], 422);
            }

            return redirect()
                ->route('admin.clients.index')
                ->with('error', 'El cliente tiene registros asociados y no puede eliminarse. Solo puedes inactivarlo.');
        }

        $clientId = $client->id;
        $client->delete();

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Cliente eliminado correctamente.',
                'client_id' => $clientId,
            ]);
        }

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }

    public function toggleStatus(Request $request, Client $client): RedirectResponse|JsonResponse
    {
        $client->update([
            'status' => !$client->status,
        ]);

        $message = $client->status
            ? 'Cliente activado correctamente.'
            : 'Cliente inactivado correctamente.';

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => (bool) $client->status,
                'label' => $client->status ? 'Activo' : 'Inactivo',
            ]);
        }

        return redirect()
            ->route('admin.clients.index')
            ->with('success', $message);
    }

    private function isAjaxRequest(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }

    private function clientPayload(Client $client): array
    {
        $client->loadCount([
            'areas',
            'users',
            'elementTypes',
            'components',
            'diagnostics',
            'conditions',
        ]);

        $dependencyCount =
            (int) ($client->areas_count ?? 0) +
            (int) ($client->users_count ?? 0) +
            (int) ($client->element_types_count ?? 0) +
            (int) ($client->components_count ?? 0) +
            (int) ($client->diagnostics_count ?? 0) +
            (int) ($client->conditions_count ?? 0);

        return [
            'id' => $client->id,
            'name' => $client->name,
            'obs' => $client->obs,
            'obs_label' => $client->obs ?: '—',
            'status' => (bool) $client->status,
            'dependency_count' => $dependencyCount,
            'areas_count' => (int) ($client->areas_count ?? 0),
            'users_count' => (int) ($client->users_count ?? 0),
            'element_types_count' => (int) ($client->element_types_count ?? 0),
            'components_count' => (int) ($client->components_count ?? 0),
            'diagnostics_count' => (int) ($client->diagnostics_count ?? 0),
            'conditions_count' => (int) ($client->conditions_count ?? 0),
            'update_url' => route('admin.clients.update', $client),
            'destroy_url' => route('admin.clients.destroy', $client),
            'toggle_status_url' => route('admin.clients.toggle-status', $client),
        ];
    }
}