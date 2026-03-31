<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Condition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminConditionController extends Controller
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

        $conditionsQuery = Condition::with('client')
            ->whereIn('client_id', $allowedClientIds)
            ->withCount('reportDetails');

        if ($selectedClientId && in_array($selectedClientId, $allowedClientIds)) {
            $conditionsQuery->where('client_id', $selectedClientId);
        }

        $conditions = $conditionsQuery
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.managed-conditions.index', compact(
            'clients',
            'singleClient',
            'selectedClientId',
            'conditions'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $validated = $request->validate([
            'client_id' => ['required', Rule::in($allowedClientIds)],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('conditions', 'code')->where(function ($query) use ($request) {
                    return $query->where('client_id', $request->client_id);
                }),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'severity' => ['required', 'integer'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        Condition::create([
            'client_id' => $validated['client_id'],
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'severity' => $validated['severity'],
            'color' => $validated['color'] ?? null,
            'status' => true,
        ]);

        return redirect()
            ->route('admin.managed-conditions.index')
            ->with('success', 'Condición creada correctamente.');
    }

    public function update(Request $request, Condition $condition): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfConditionOutsideScope($condition, $allowedClientIds);

        $validated = $request->validate([
            'client_id' => ['required', Rule::in($allowedClientIds)],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('conditions', 'code')
                    ->ignore($condition->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('client_id', $request->client_id);
                    }),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'severity' => ['required', 'integer'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $condition->update([
            'client_id' => $validated['client_id'],
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'severity' => $validated['severity'],
            'color' => $validated['color'] ?? null,
        ]);

        return redirect()
            ->route('admin.managed-conditions.index')
            ->with('success', 'Condición actualizada correctamente.');
    }

    public function destroy(Condition $condition): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfConditionOutsideScope($condition, $allowedClientIds);

        if ($condition->hasDependencies()) {
            return redirect()
                ->route('admin.managed-conditions.index')
                ->with('success', 'La condición tiene registros asociados y no puede eliminarse.');
        }

        $condition->delete();

        return redirect()
            ->route('admin.managed-conditions.index')
            ->with('success', 'Condición eliminada correctamente.');
    }

    public function toggleStatus(Condition $condition): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfConditionOutsideScope($condition, $allowedClientIds);

        if (!$condition->hasDependencies()) {
            return redirect()
                ->route('admin.managed-conditions.index')
                ->with('success', 'Esta condición no tiene dependencias. Puedes eliminarla si lo deseas.');
        }

        $condition->update([
            'status' => !$condition->status,
        ]);

        return redirect()
            ->route('admin.managed-conditions.index')
            ->with('success', 'Estado de la condición actualizado correctamente.');
    }

    private function abortIfConditionOutsideScope(Condition $condition, array $allowedClientIds): void
    {
        if (!in_array($condition->client_id, $allowedClientIds)) {
            abort(403, 'No autorizado para gestionar esta condición.');
        }
    }
}