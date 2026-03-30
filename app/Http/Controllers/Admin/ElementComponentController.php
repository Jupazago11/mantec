<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Element;
use App\Models\ElementComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ElementComponentController extends Controller
{
    public function index(): View
    {
        $elementComponents = ElementComponent::with([
            'element.area.client',
            'element.elementType',
            'component.elementType',
        ])->orderByDesc('id')->get();

        $elements = Element::with(['area.client', 'elementType'])
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $components = Component::with('elementType')
            ->where('status', true)
            ->orderBy('name')
            ->get();

        return view('admin.element-components.index', compact(
            'elementComponents',
            'elements',
            'components'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'element_id' => ['required', 'exists:elements,id'],
            'component_id' => [
                'required',
                'exists:components,id',
                Rule::unique('element_components')->where(function ($query) use ($request) {
                    return $query->where('element_id', $request->element_id)
                        ->where('component_id', $request->component_id);
                }),
            ],
        ]);

        $element = Element::findOrFail($validated['element_id']);
        $component = Component::findOrFail($validated['component_id']);

        if ($element->element_type_id !== $component->element_type_id) {
            return back()
                ->withErrors([
                    'component_id' => 'El componente seleccionado no corresponde al tipo de elemento del elemento elegido.',
                ])
                ->withInput();
        }

        ElementComponent::create($validated);

        return redirect()
            ->route('admin.element-components.index')
            ->with('success', 'Relación elemento-componente creada correctamente.');
    }

    public function update(Request $request, ElementComponent $elementComponent): RedirectResponse
    {
        $validated = $request->validate([
            'element_id' => ['required', 'exists:elements,id'],
            'component_id' => [
                'required',
                'exists:components,id',
                Rule::unique('element_components')
                    ->ignore($elementComponent->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('element_id', $request->element_id)
                            ->where('component_id', $request->component_id);
                    }),
            ],
        ]);

        $element = Element::findOrFail($validated['element_id']);
        $component = Component::findOrFail($validated['component_id']);

        if ($element->element_type_id !== $component->element_type_id) {
            return back()
                ->withErrors([
                    'component_id' => 'El componente seleccionado no corresponde al tipo de elemento del elemento elegido.',
                ])
                ->withInput();
        }

        $elementComponent->update($validated);

        return redirect()
            ->route('admin.element-components.index')
            ->with('success', 'Relación elemento-componente actualizada correctamente.');
    }

    public function destroy(ElementComponent $elementComponent): RedirectResponse
    {
        $elementComponent->delete();

        return redirect()
            ->route('admin.element-components.index')
            ->with('success', 'Relación elemento-componente eliminada correctamente.');
    }
}