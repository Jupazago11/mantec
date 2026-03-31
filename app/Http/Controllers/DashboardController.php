<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ElementType;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $role = $user->role->key ?? null;

        return match ($role) {
            'superadmin' => view('admin.dashboard.superadmin'),
            'admin_global' => view('admin.dashboard.admin'),
            'admin' => $this->adminDashboard($user),
            'admin_cliente' => view('admin.dashboard.admin_cliente'),
            'inspector' => view('admin.dashboard.inspector'),
            default => abort(403, 'Rol no autorizado.'),
        };
    }

    private function adminDashboard($user): View
    {
        $clientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id');

        $clients = Client::whereIn('id', $clientIds)
            ->where('status', true)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $elementTypes = ElementType::whereIn('client_id', $clientIds)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $currentYear = now()->year;

        $reportModules = $elementTypes->map(function ($elementType) use ($clients, $currentYear) {
            $client = $clients->get($elementType->client_id);

            return [
                'client_id' => $client?->id,
                'client_name' => $client?->name,
                'element_type_id' => $elementType->id,
                'element_type_name' => $elementType->name,
                'title' => 'Reporte preventivo ' . $elementType->name . ' Planta ' . $client?->name . ' ' . $currentYear,
                'year' => $currentYear,
            ];
        })->values();

        return view('admin.dashboard.admin', compact('reportModules'));
    }
}