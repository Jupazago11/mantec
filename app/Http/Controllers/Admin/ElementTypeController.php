<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ElementType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ElementTypeController extends Controller
{
    public function index(): View
    {
        $elementTypes = ElementType::orderByDesc('id')->get();

        return view('admin.element-types.index', compact('elementTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ]);

        ElementType::create($validated);

        return redirect()
            ->route('admin.element-types.index')
            ->with('success', 'Tipo de elemento creado correctamente.');
    }

    public function update(Request $request, ElementType $elementType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ]);

        $elementType->update($validated);

        return redirect()
            ->route('admin.element-types.index')
            ->with('success', 'Tipo de elemento actualizado correctamente.');
    }

    public function destroy(ElementType $elementType): RedirectResponse
    {
        $elementType->delete();

        return redirect()
            ->route('admin.element-types.index')
            ->with('success', 'Tipo de elemento eliminado correctamente.');
    }
}