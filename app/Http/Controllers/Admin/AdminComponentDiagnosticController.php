<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Component;
use App\Models\Diagnostic;
use App\Models\ElementType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminComponentDiagnosticController extends Controller
{
    public function index(): View
    {
        $authUser = Auth::user();

        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $clients = Client::whereIn('id', $allowedClientIds)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $singleClient = $clients->count() === 1 ? $clients->first() : null;

        return view('admin.managed-component-diagnostics.index', compact(
            'clients',
            'singleClient'
        ));
    }

    public function getElementTypes(Client $client): JsonResponse
    {
        $this->authorizeClient($client->id);

        $data = ElementType::where('client_id', $client->id)
            ->where('status', true)
            ->get(['id', 'name']);

        return response()->json($data);
    }

    public function getComponents(ElementType $elementType): JsonResponse
    {
        $this->authorizeClient($elementType->client_id);

        $data = Component::where('element_type_id', $elementType->id)
            ->where('status', true)
            ->get(['id', 'name']);

        return response()->json($data);
    }

    public function getDiagnostics(Client $client): JsonResponse
    {
        $this->authorizeClient($client->id);

        $data = Diagnostic::where('client_id', $client->id)
            ->where('status', true)
            ->get(['id', 'name']);

        return response()->json($data);
    }

    public function getAssigned(Component $component): JsonResponse
    {
        $this->authorizeClient($component->client_id);

        return response()->json(
            $component->diagnostics()->pluck('diagnostic_id')
        );
    }

    public function store(Request $request)
    {
        $component = Component::findOrFail($request->component_id);

        $this->authorizeClient($component->client_id);

        $component->diagnostics()->sync($request->diagnostics ?? []);

        return back()->with('success', 'Diagnósticos asignados correctamente.');
    }

    private function authorizeClient($clientId)
    {
        $allowed = Auth::user()->clients()->pluck('clients.id')->toArray();

        abort_unless(in_array($clientId, $allowed), 403);
    }
}