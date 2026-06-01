<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Client;
use App\Models\Parada;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ParadaController extends Controller
{
    private const WRITE_ROLES = ['superadmin', 'admin_global', 'admin'];

    public function index(Request $request): View|JsonResponse
    {
        $user    = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless(
            in_array($roleKey, self::WRITE_ROLES, true),
            403
        );

        $canManage = true;
        $clients   = $this->getScopedClients($user, $roleKey);

        $selectedClientId = $request->input('client_id')
            ?? ($clients->count() === 1 ? $clients->first()->id : null);

        $areas = collect();

        // Carga de paradas según rol y cliente seleccionado
        if ($selectedClientId) {
            abort_unless($this->clientAllowed($user, $roleKey, (int) $selectedClientId), 403);

            $paradas = Parada::query()
                ->with(['areas:id,name,code'])
                ->where('client_id', $selectedClientId)
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get();

            if ($canManage) {
                $areas = Area::query()
                    ->where('client_id', $selectedClientId)
                    ->where('status', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'code']);
            }
        } elseif (in_array($roleKey, ['superadmin', 'admin_global'], true)) {
            // Power admins sin filtro de cliente: muestran todas las paradas
            $paradas = Parada::query()
                ->with(['areas:id,name,code', 'client:id,name'])
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get();
        } elseif ($roleKey === 'admin') {
            // Admin sin selección: muestra paradas de todos sus clientes asignados
            $clientIds = $clients->pluck('id');
            $paradas = Parada::query()
                ->with(['areas:id,name,code', 'client:id,name'])
                ->whereIn('client_id', $clientIds)
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get();
        } else {
            $paradas = collect();
        }

        $singleClient     = $clients->count() === 1 ? $clients->first() : null;
        $showClientColumn = !$selectedClientId && $paradas->isNotEmpty();

        if ($request->expectsJson()) {
            return response()->json([
                'success'      => true,
                'paradas_html' => view('admin.paradas.partials.list', compact(
                    'paradas', 'areas', 'selectedClientId', 'roleKey', 'canManage', 'showClientColumn'
                ))->render(),
                'areas'        => $areas,
            ]);
        }

        return view('admin.paradas.index', compact(
            'clients',
            'singleClient',
            'selectedClientId',
            'paradas',
            'areas',
            'roleKey',
            'canManage',
            'showClientColumn',
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $user    = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless(in_array($roleKey, self::WRITE_ROLES, true), 403);

        $clientId = (int) $request->input('client_id');
        abort_unless($this->clientAllowed($user, $roleKey, $clientId), 403);

        $validated = $request->validate([
            'client_id'  => ['required', 'integer', 'exists:clients,id'],
            'name'       => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'area_ids'   => ['nullable', 'array'],
            'area_ids.*' => ['integer', Rule::exists('areas', 'id')->where('client_id', $clientId)],
        ]);

        $parada = DB::transaction(function () use ($validated) {
            $parada = Parada::create([
                'client_id'  => $validated['client_id'],
                'name'       => $validated['name'],
                'start_date' => $validated['start_date'],
                'end_date'   => $validated['end_date'],
            ]);

            if (!empty($validated['area_ids'])) {
                $parada->areas()->sync($validated['area_ids']);
            }

            return $parada->load('areas:id,name,code');
        });

        return response()->json([
            'success' => true,
            'message' => 'Parada creada correctamente.',
            'parada'  => $this->formatParada($parada),
        ]);
    }

    public function update(Request $request, Parada $parada): JsonResponse
    {
        $user    = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless(in_array($roleKey, self::WRITE_ROLES, true), 403);
        abort_unless($this->clientAllowed($user, $roleKey, (int) $parada->client_id), 403);

        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'area_ids'   => ['nullable', 'array'],
            'area_ids.*' => ['integer', Rule::exists('areas', 'id')->where('client_id', $parada->client_id)],
        ]);

        DB::transaction(function () use ($parada, $validated) {
            $parada->update([
                'name'       => $validated['name'],
                'start_date' => $validated['start_date'],
                'end_date'   => $validated['end_date'],
            ]);

            $parada->areas()->sync($validated['area_ids'] ?? []);
        });

        $parada->load('areas:id,name,code');

        return response()->json([
            'success' => true,
            'message' => 'Parada actualizada correctamente.',
            'parada'  => $this->formatParada($parada),
        ]);
    }

    public function destroy(Parada $parada): JsonResponse
    {
        $user    = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless(in_array($roleKey, self::WRITE_ROLES, true), 403);
        abort_unless($this->clientAllowed($user, $roleKey, (int) $parada->client_id), 403);

        $parada->delete();

        return response()->json([
            'success' => true,
            'message' => 'Parada eliminada correctamente.',
        ]);
    }

    public function areasByClient(Request $request): JsonResponse
    {
        $user    = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless(in_array($roleKey, self::WRITE_ROLES, true), 403);

        $clientId = (int) $request->input('client_id');
        abort_unless($this->clientAllowed($user, $roleKey, $clientId), 403);

        $areas = Area::query()
            ->where('client_id', $clientId)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json(['success' => true, 'areas' => $areas]);
    }

    private function getScopedClients($user, string $roleKey)
    {
        if (in_array($roleKey, ['superadmin', 'admin_global'], true)) {
            return Client::query()
                ->where('status', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return $user->clients()
            ->where('clients.status', true)
            ->orderBy('clients.name')
            ->get(['clients.id', 'clients.name']);
    }

    private function clientAllowed($user, string $roleKey, int $clientId): bool
    {
        if (in_array($roleKey, ['superadmin', 'admin_global'], true)) {
            return true;
        }

        return $user->clients()->where('clients.id', $clientId)->exists();
    }

    private function formatParada(Parada $parada): array
    {
        return [
            'id'          => $parada->id,
            'name'        => $parada->name,
            'start_date'  => $parada->start_date->format('Y-m-d'),
            'end_date'    => $parada->end_date->format('Y-m-d'),
            'is_active'   => $parada->isActive(),
            'areas'       => $parada->areas->map(fn ($a) => [
                'id'   => $a->id,
                'name' => $a->name,
                'code' => $a->code,
            ])->values(),
            'update_url'  => route('admin.paradas.update', $parada),
            'destroy_url' => route('admin.paradas.destroy', $parada),
        ];
    }
}
