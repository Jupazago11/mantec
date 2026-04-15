<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:clients,name'],
            'obs' => ['nullable', 'string'],
        ]);

        Client::create([
            'name' => $validated['name'],
            'obs' => $validated['obs'] ?? null,
            'status' => true,
        ]);

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Cliente creado correctamente.');
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:clients,name,' . $client->id],
            'obs' => ['nullable', 'string'],
            'auto_sync' => ['required', 'boolean'],
        ]);

        $client->update([
            'name' => $validated['name'],
            'obs' => $validated['obs'] ?? null,
            'auto_sync' => (bool) $validated['auto_sync'],
        ]);

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->hasDependencies()) {
            return redirect()
                ->route('admin.clients.index')
                ->with('success', 'El cliente tiene registros asociados y no puede eliminarse.');
        }

        $client->delete();

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }

    public function toggleStatus(Client $client): RedirectResponse
    {
        if (!$client->hasDependencies()) {
            return redirect()
                ->route('admin.clients.index')
                ->with('success', 'Este cliente no tiene dependencias. Puedes eliminarlo si lo deseas.');
        }

        $client->update([
            'status' => !$client->status,
        ]);

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Estado del cliente actualizado correctamente.');
    }
}