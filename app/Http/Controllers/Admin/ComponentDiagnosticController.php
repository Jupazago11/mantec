<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\ComponentDiagnostic;
use App\Models\Diagnostic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ComponentDiagnosticController extends Controller
{
    public function index(): View
    {
        $componentDiagnostics = ComponentDiagnostic::with(['component.elementType', 'diagnostic'])
            ->orderByDesc('id')
            ->get();

        $components = Component::with('elementType')
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $diagnostics = Diagnostic::where('status', true)
            ->orderBy('name')
            ->get();

        return view('admin.component-diagnostics.index', compact(
            'componentDiagnostics',
            'components',
            'diagnostics'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'component_id' => ['required', 'exists:components,id'],
            'diagnostic_id' => [
                'required',
                'exists:diagnostics,id',
                Rule::unique('component_diagnostics')->where(function ($query) use ($request) {
                    return $query->where('component_id', $request->component_id)
                        ->where('diagnostic_id', $request->diagnostic_id);
                }),
            ],
        ]);

        ComponentDiagnostic::create($validated);

        return redirect()
            ->route('admin.component-diagnostics.index')
            ->with('success', 'Relación componente-diagnóstico creada correctamente.');
    }

    public function update(Request $request, ComponentDiagnostic $componentDiagnostic): RedirectResponse
    {
        $validated = $request->validate([
            'component_id' => ['required', 'exists:components,id'],
            'diagnostic_id' => [
                'required',
                'exists:diagnostics,id',
                Rule::unique('component_diagnostics')
                    ->ignore($componentDiagnostic->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('component_id', $request->component_id)
                            ->where('diagnostic_id', $request->diagnostic_id);
                    }),
            ],
        ]);

        $componentDiagnostic->update($validated);

        return redirect()
            ->route('admin.component-diagnostics.index')
            ->with('success', 'Relación componente-diagnóstico actualizada correctamente.');
    }

    public function destroy(ComponentDiagnostic $componentDiagnostic): RedirectResponse
    {
        $componentDiagnostic->delete();

        return redirect()
            ->route('admin.component-diagnostics.index')
            ->with('success', 'Relación componente-diagnóstico eliminada correctamente.');
    }
}