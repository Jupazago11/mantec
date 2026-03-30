<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Diagnostic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminDiagnosticController extends Controller
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

        $diagnosticsQuery = Diagnostic::with('client')
            ->whereIn('client_id', $allowedClientIds)
            ->withCount(['components', 'reportDetails']);

        if ($selectedClientId && in_array($selectedClientId, $allowedClientIds)) {
            $diagnosticsQuery->where('client_id', $selectedClientId);
        }

        $diagnostics = $diagnosticsQuery
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.managed-diagnostics.index', compact(
            'clients',
            'singleClient',
            'selectedClientId',
            'diagnostics'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $validated = $request->validate([
            'client_id' => ['required', Rule::in($allowedClientIds)],
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('diagnostics', 'code')->where(function ($query) use ($request) {
                    return $query->where('client_id', $request->client_id);
                }),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        Diagnostic::create([
            'client_id' => $validated['client_id'],
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'] ?? null,
            'status' => true,
        ]);

        return redirect()
            ->route('admin.managed-diagnostics.index')
            ->with('success', 'Diagnóstico creado correctamente.');
    }

    public function update(Request $request, Diagnostic $diagnostic): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfDiagnosticOutsideScope($diagnostic, $allowedClientIds);

        $validated = $request->validate([
            'client_id' => ['required', Rule::in($allowedClientIds)],
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('diagnostics', 'code')
                    ->ignore($diagnostic->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('client_id', $request->client_id);
                    }),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $diagnostic->update([
            'client_id' => $validated['client_id'],
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('admin.managed-diagnostics.index')
            ->with('success', 'Diagnóstico actualizado correctamente.');
    }

    public function destroy(Diagnostic $diagnostic): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfDiagnosticOutsideScope($diagnostic, $allowedClientIds);

        if ($diagnostic->hasDependencies()) {
            return redirect()
                ->route('admin.managed-diagnostics.index')
                ->with('success', 'El diagnóstico tiene registros asociados y no puede eliminarse.');
        }

        $diagnostic->delete();

        return redirect()
            ->route('admin.managed-diagnostics.index')
            ->with('success', 'Diagnóstico eliminado correctamente.');
    }

    public function toggleStatus(Diagnostic $diagnostic): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfDiagnosticOutsideScope($diagnostic, $allowedClientIds);

        if (!$diagnostic->hasDependencies()) {
            return redirect()
                ->route('admin.managed-diagnostics.index')
                ->with('success', 'Este diagnóstico no tiene dependencias. Puedes eliminarlo si lo deseas.');
        }

        $diagnostic->update([
            'status' => !$diagnostic->status,
        ]);

        return redirect()
            ->route('admin.managed-diagnostics.index')
            ->with('success', 'Estado del diagnóstico actualizado correctamente.');
    }

    private function abortIfDiagnosticOutsideScope(Diagnostic $diagnostic, array $allowedClientIds): void
    {
        if (!in_array($diagnostic->client_id, $allowedClientIds)) {
            abort(403, 'No autorizado para gestionar este diagnóstico.');
        }
    }
}