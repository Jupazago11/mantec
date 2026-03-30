<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\ElementType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComponentController extends Controller
{
    public function index(): View
    {
        $components = Component::with('elementType')
            ->orderByDesc('id')
            ->get();

        $elementTypes = ElementType::where('status', true)
            ->orderBy('name')
            ->get();

        return view('admin.components.index', compact('components', 'elementTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'element_type_id' => ['required', 'exists:element_types,id'],
            'name' => ['required', 'string', 'max:120'],
            'is_default' => ['required', 'boolean'],
        ]);

        Component::create([
            'element_type_id' => $validated['element_type_id'],
            'name' => $validated['name'],
            'code' => null,
            'is_required' => false,
            'is_default' => $validated['is_default'],
            'status' => true,
        ]);

        return redirect()
            ->route('admin.components.index')
            ->with('success', 'Componente creado correctamente.');
    }

    public function update(Request $request, Component $component): RedirectResponse
    {
        $validated = $request->validate([
            'element_type_id' => ['required', 'exists:element_types,id'],
            'name' => ['required', 'string', 'max:120'],
            'is_default' => ['required', 'boolean'],
        ]);

        $component->update([
            'element_type_id' => $validated['element_type_id'],
            'name' => $validated['name'],
            'is_default' => $validated['is_default'],
        ]);

        return redirect()
            ->route('admin.components.index')
            ->with('success', 'Componente actualizado correctamente.');
    }

    public function toggleStatus(Component $component): RedirectResponse
    {
        $component->update([
            'status' => !$component->status,
        ]);

        return redirect()
            ->route('admin.components.index')
            ->with('success', 'Estado del componente actualizado correctamente.');
    }
}