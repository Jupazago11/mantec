<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Services\GroupReportConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupReportConfigController extends Controller
{
    public function __construct(private GroupReportConfigService $service) {}

    public function show(Group $group): JsonResponse
    {
        $this->authorize($group);

        return response()->json([
            'success'    => true,
            'group_name' => $group->name,
            'columns'    => $this->service->getColumnsForGroup($group->id),
        ]);
    }

    public function save(Request $request, Group $group): JsonResponse
    {
        $this->authorize($group);

        $validated = $request->validate([
            'columns'                              => ['required', 'array', 'min:1'],
            'columns.*.column_key'                 => ['required', 'string'],
            'columns.*.visible'                    => ['required', 'boolean'],
            'columns.*.can_edit_admin_cliente'     => ['boolean'],
            'columns.*.can_edit_observador'        => ['boolean'],
            'columns.*.can_edit_observador_cliente'=> ['boolean'],
        ]);

        $this->service->saveColumns($group->id, $validated['columns']);

        return response()->json([
            'success' => true,
            'message' => 'Configuración guardada correctamente.',
        ]);
    }

    public function reset(Group $group): JsonResponse
    {
        $this->authorize($group);

        $this->service->resetToDefault($group->id);

        return response()->json([
            'success' => true,
            'message' => 'Configuración restablecida a los valores predeterminados.',
            'columns' => $this->service->getDefaultColumns(),
        ]);
    }

    private function authorize(Group $group): void
    {
        $user    = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless(
            in_array($roleKey, ['superadmin', 'admin_global', 'admin'], true),
            403,
            'No tienes permisos para configurar agrupaciones.'
        );

        if ($roleKey === 'admin') {
            $clientIds = $user->clients()->pluck('clients.id')->map(fn ($id) => (int) $id)->all();
            abort_unless(in_array((int) $group->client_id, $clientIds, true), 403);
        }
    }
}
