<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientElementTypeModule;
use App\Models\Element;
use App\Models\SystemModule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSystemModuleController extends Controller
{
    public function measurements(Request $request): View
    {
        $user = auth()->user();
        abort_if(!$user, 403);

        $module = SystemModule::query()
            ->where('key', 'mediciones')
            ->where('status', true)
            ->firstOrFail();

        if (!$user->canViewSystemModule('mediciones')) {
            abort(403);
        }

        $roleKey = $user->role?->key;

        if (in_array($roleKey, ['superadmin', 'admin_global'], true)) {
            $configs = ClientElementTypeModule::query()
                ->with([
                    'client:id,name',
                    'elementType:id,client_id,name',
                    'module:id,name,key',
                ])
                ->where('system_module_id', $module->id)
                ->where('status', true)
                ->where('module_enabled', true)
                ->get();
        } else {
            $clientIds = $user->clients()->pluck('clients.id');

            $configs = ClientElementTypeModule::query()
                ->with([
                    'client:id,name',
                    'elementType:id,client_id,name',
                    'module:id,name,key',
                ])
                ->where('system_module_id', $module->id)
                ->where('status', true)
                ->where('module_enabled', true)
                ->whereIn('client_id', $clientIds)
                ->get();
        }

        $cards = $configs
            ->map(function (ClientElementTypeModule $config) {
                $elementsCount = Element::query()
                    ->where('client_id', $config->client_id)
                    ->where('element_type_id', $config->element_type_id)
                    ->where('status', true)
                    ->count();

                return [
                    'client_id' => $config->client_id,
                    'client_name' => $config->client?->name,
                    'element_type_id' => $config->element_type_id,
                    'element_type_name' => $config->elementType?->name,
                    'creation_enabled' => $config->creation_enabled,
                    'elements_count' => $elementsCount,
                ];
            })
            ->sortBy([
                ['client_name', 'asc'],
                ['element_type_name', 'asc'],
            ])
            ->values();

        return view('admin/system-modules/measurements/index', [
            'cards' => $cards,
        ]);
    }
}