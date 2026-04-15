<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Group;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless(
            in_array($roleKey, [
                'superadmin',
                'admin_global',
                'admin',
                'admin_cliente',
                'observador',
                'observador_cliente',
            ], true),
            403,
            'Rol no autorizado.'
        );

        $clients = $this->getScopedClients($user);
        $groupModules = $this->buildGroupModules($clients);

        $dateFrom = now()->startOfYear()->toDateString();
        $dateTo = now()->toDateString();

        return view('admin.dashboard.admin', [
            'groupModules' => $groupModules,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'isReadOnly' => $this->isReadOnlyRole($user),
            'roleKey' => $roleKey,
        ]);
    }

    private function buildGroupModules(Collection $clients): Collection
    {
        $clientIds = $clients->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (empty($clientIds)) {
            return collect();
        }

        return Group::query()
            ->with(['client:id,name'])
            ->where('status', true)
            ->whereIn('client_id', $clientIds)
            ->orderBy('client_id')
            ->orderBy('name')
            ->get(['id', 'client_id', 'name', 'description'])
            ->map(function ($group) {
                return [
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'group_description' => $group->description,
                    'client_id' => $group->client_id,
                    'client_name' => $group->client?->name,
                ];
            })
            ->values();
    }

    private function getScopedClients($user): Collection
    {
        $roleKey = $user->role?->key;

        if (in_array($roleKey, ['superadmin', 'admin_global', 'observador'], true)) {
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

    private function isReadOnlyRole($user): bool
    {
        return in_array($user->role?->key, ['observador', 'observador_cliente'], true);
    }
}