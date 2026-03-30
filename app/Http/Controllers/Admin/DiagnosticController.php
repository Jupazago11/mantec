<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Diagnostic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiagnosticController extends Controller
{
    public function index(): View
    {
        $diagnostics = Diagnostic::orderByDesc('id')->get();

        return view('admin.diagnostics.index', compact('diagnostics'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', 'unique:diagnostics,code'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ]);

        Diagnostic::create($validated);

        return redirect()
            ->route('admin.diagnostics.index')
            ->with('success', 'Diagnóstico creado correctamente.');
    }

    public function update(Request $request, Diagnostic $diagnostic): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', 'unique:diagnostics,code,' . $diagnostic->id],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ]);

        $diagnostic->update($validated);

        return redirect()
            ->route('admin.diagnostics.index')
            ->with('success', 'Diagnóstico actualizado correctamente.');
    }

    public function destroy(Diagnostic $diagnostic): RedirectResponse
    {
        $diagnostic->delete();

        return redirect()
            ->route('admin.diagnostics.index')
            ->with('success', 'Diagnóstico eliminado correctamente.');
    }
}