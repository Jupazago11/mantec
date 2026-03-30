<?php

namespace App\Http\Controllers;

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
            'admin' => view('admin.dashboard.admin_'),
            'admin_cliente' => view('admin.dashboard.admin_cliente'),
            'inspector' => view('admin.dashboard.inspector'),
            default => abort(403, 'Rol no autorizado.'),
        };
    }
}