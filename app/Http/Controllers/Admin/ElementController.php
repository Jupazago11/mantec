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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'area_id' => ['required', 'exists:areas,id'],
            'element_type_id' => ['required', 'exists:element_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'warehouse_code' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
        ]);

        $user = auth()->user();

        $area = \App\Models\Area::with('client')->findOrFail($validated['area_id']);
        $clientId = $area->client_id;

        abort_unless(
            $user->clients()->where('clients.id', $clientId)->exists(),
            403,
            'No tienes permiso para crear activos en este cliente.'
        );

        $name = trim($validated['name']);
        $code = isset($validated['code']) ? trim((string) $validated['code']) : null;
        $warehouseCode = isset($validated['warehouse_code']) ? trim((string) $validated['warehouse_code']) : null;

        $code = $code !== '' ? $code : null;
        $warehouseCode = $warehouseCode !== '' ? $warehouseCode : null;

        // Nombre único por cliente
        $nameExists = \App\Models\Element::query()
            ->whereHas('area', function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            })
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($nameExists) {
            return back()
                ->withErrors(['name' => 'Ya existe un activo con ese nombre en este cliente.'])
                ->withInput();
        }

        // Código único por cliente
        if ($code !== null) {
            $codeExists = \App\Models\Element::query()
                ->whereHas('area', function ($query) use ($clientId) {
                    $query->where('client_id', $clientId);
                })
                ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
                ->exists();

            if ($codeExists) {
                return back()
                    ->withErrors(['code' => 'Ya existe un activo con ese código en este cliente.'])
                    ->withInput();
            }
        }

        // Código de almacén único por cliente
        if ($warehouseCode !== null) {
            $warehouseExists = \App\Models\Element::query()
                ->whereHas('area', function ($query) use ($clientId) {
                    $query->where('client_id', $clientId);
                })
                ->whereRaw('LOWER(warehouse_code) = ?', [mb_strtolower($warehouseCode)])
                ->exists();

            if ($warehouseExists) {
                return back()
                    ->withErrors(['warehouse_code' => 'Ya existe un activo con ese código de almacén en este cliente.'])
                    ->withInput();
            }
        }

        \App\Models\Element::create([
            'area_id' => $validated['area_id'],
            'element_type_id' => $validated['element_type_id'],
            'name' => $name,
            'code' => $code,
            'warehouse_code' => $warehouseCode,
            'status' => $request->boolean('status', true),
        ]);

        return back()->with('success', 'Activo creado correctamente.');
    }



public function update(Request $request, \App\Models\Element $element)
{
    $validated = $request->validate([
        'area_id' => ['required', 'exists:areas,id'],
        'element_type_id' => ['required', 'exists:element_types,id'],
        'name' => ['required', 'string', 'max:255'],
        'code' => ['nullable', 'string', 'max:255'],
        'warehouse_code' => ['nullable', 'string', 'max:255'],
        'status' => ['nullable', 'boolean'],
    ]);

    $user = auth()->user();

    $area = \App\Models\Area::with('client')->findOrFail($validated['area_id']);
    $clientId = $area->client_id;

    abort_unless(
        $user->clients()->where('clients.id', $clientId)->exists(),
        403,
        'No tienes permiso para actualizar activos en este cliente.'
    );

    $name = trim($validated['name']);
    $code = isset($validated['code']) ? trim((string) $validated['code']) : null;
    $warehouseCode = isset($validated['warehouse_code']) ? trim((string) $validated['warehouse_code']) : null;

    $code = $code !== '' ? $code : null;
    $warehouseCode = $warehouseCode !== '' ? $warehouseCode : null;

    $nameExists = \App\Models\Element::query()
        ->where('id', '!=', $element->id)
        ->whereHas('area', function ($query) use ($clientId) {
            $query->where('client_id', $clientId);
        })
        ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
        ->exists();

    if ($nameExists) {
        return back()
            ->withErrors(['name' => 'Ya existe un activo con ese nombre en este cliente.'])
            ->withInput();
    }

    if ($code !== null) {
        $codeExists = \App\Models\Element::query()
            ->where('id', '!=', $element->id)
            ->whereHas('area', function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            })
            ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
            ->exists();

        if ($codeExists) {
            return back()
                ->withErrors(['code' => 'Ya existe un activo con ese código en este cliente.'])
                ->withInput();
        }
    }

    if ($warehouseCode !== null) {
        $warehouseExists = \App\Models\Element::query()
            ->where('id', '!=', $element->id)
            ->whereHas('area', function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            })
            ->whereRaw('LOWER(warehouse_code) = ?', [mb_strtolower($warehouseCode)])
            ->exists();

        if ($warehouseExists) {
            return back()
                ->withErrors(['warehouse_code' => 'Ya existe un activo con ese código de almacén en este cliente.'])
                ->withInput();
        }
    }

    $element->update([
        'area_id' => $validated['area_id'],
        'element_type_id' => $validated['element_type_id'],
        'name' => $name,
        'code' => $code,
        'warehouse_code' => $warehouseCode,
        'status' => $request->boolean('status', true),
    ]);

    return back()->with('success', 'Activo actualizado correctamente.');
}



    public function destroy(Element $element): RedirectResponse
    {
        $element->delete();

        return redirect()
            ->route('admin.elements.index')
            ->with('success', 'Elemento eliminado correctamente.');
    }
}