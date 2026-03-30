<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Condition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConditionController extends Controller
{
    public function index(): View
    {
        $conditions = Condition::withCount('reportDetails')
            ->orderByDesc('id')
            ->get();

        return view('admin.conditions.index', compact('conditions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:conditions,code'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'severity' => ['required', 'integer'],
        ]);

        Condition::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'severity' => $validated['severity'],
            'status' => true,
        ]);

        return redirect()
            ->route('admin.conditions.index')
            ->with('success', 'Condición creada correctamente.');
    }

    public function update(Request $request, Condition $condition): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:conditions,code,' . $condition->id],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'severity' => ['required', 'integer'],
        ]);

        $condition->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'severity' => $validated['severity'],
        ]);

        return redirect()
            ->route('admin.conditions.index')
            ->with('success', 'Condición actualizada correctamente.');
    }

    public function destroy(Condition $condition): RedirectResponse
    {
        if ($condition->reportDetails()->exists()) {
            return redirect()
                ->route('admin.conditions.index')
                ->with('success', 'La condición ya tiene uso y no puede eliminarse.');
        }

        $condition->delete();

        return redirect()
            ->route('admin.conditions.index')
            ->with('success', 'Condición eliminada correctamente.');
    }

    public function toggleStatus(Condition $condition): RedirectResponse
    {
        $condition->update([
            'status' => !$condition->status,
        ]);

        return redirect()
            ->route('admin.conditions.index')
            ->with('success', 'Estado de la condición actualizado correctamente.');
    }
}