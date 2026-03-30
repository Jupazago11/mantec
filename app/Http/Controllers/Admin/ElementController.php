<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Element;
use App\Models\ElementType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ElementController extends Controller
{
    public function index(): View
    {
        $elements = Element::with(['area.client', 'elementType'])
            ->orderByDesc('id')
            ->get();

        $areas = Area::with('client')
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $elementTypes = ElementType::where('status', true)
            ->orderBy('name')
            ->get();

        return view('admin.elements.index', compact('elements', 'areas', 'elementTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:100', 'unique:elements,code'],
            'area_id' => ['required', 'exists:areas,id'],
            'element_type_id' => ['required', 'exists:element_types,id'],
            'warehouse_code' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'boolean'],
        ]);

        Element::create($validated);

        return redirect()
            ->route('admin.elements.index')
            ->with('success', 'Elemento creado correctamente.');
    }

    public function update(Request $request, Element $element): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('elements', 'code')->ignore($element->id),
            ],
            'area_id' => ['required', 'exists:areas,id'],
            'element_type_id' => ['required', 'exists:element_types,id'],
            'warehouse_code' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'boolean'],
        ]);

        $element->update($validated);

        return redirect()
            ->route('admin.elements.index')
            ->with('success', 'Elemento actualizado correctamente.');
    }

    public function destroy(Element $element): RedirectResponse
    {
        $element->delete();

        return redirect()
            ->route('admin.elements.index')
            ->with('success', 'Elemento eliminado correctamente.');
    }
}