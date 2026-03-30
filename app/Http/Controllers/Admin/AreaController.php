<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AreaController extends Controller
{
    public function index(): View
    {
        $areas = Area::with('client')
            ->orderByDesc('id')
            ->get();

        $clients = Client::where('status', true)
            ->orderBy('name')
            ->get();

        return view('admin.areas.index', compact('areas', 'clients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('areas')->where(function ($query) use ($request) {
                    return $query->where('client_id', $request->client_id)
                        ->where('code', $request->code);
                }),
            ],
            'status' => ['required', 'boolean'],
        ]);

        Area::create($validated);

        return redirect()
            ->route('admin.areas.index')
            ->with('success', 'Área creada correctamente.');
    }

    public function update(Request $request, Area $area): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('areas')
                    ->ignore($area->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('client_id', $request->client_id)
                            ->where('code', $request->code);
                    }),
            ],
            'status' => ['required', 'boolean'],
        ]);

        $area->update($validated);

        return redirect()
            ->route('admin.areas.index')
            ->with('success', 'Área actualizada correctamente.');
    }

    public function destroy(Area $area): RedirectResponse
    {
        $area->delete();

        return redirect()
            ->route('admin.areas.index')
            ->with('success', 'Área eliminada correctamente.');
    }
}