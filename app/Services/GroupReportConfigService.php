<?php

namespace App\Services;

use App\Models\GroupReportConfig;
use Illuminate\Support\Facades\DB;

class GroupReportConfigService
{
    // Columnas que SIEMPRE deben ser visibles — no se pueden ocultar, solo reordenar
    public const ALWAYS_VISIBLE_KEYS = ['area', 'element_name', 'week'];

    // Columnas que admiten toggle de edición por rol
    public const EDITABLE_KEYS = [
        'recommendation',
        'recommendation_2',
        'orden',
        'aviso',
        'execution_date',
    ];

    // Definición canónica de TODAS las columnas (incluidas las estructurales)
    // El orden aquí es el predeterminado cuando no existe configuración guardada
    public const COLUMN_DEFINITIONS = [
        ['key' => 'area',             'label' => 'Área'],
        ['key' => 'element_name',     'label' => 'Nombre del activo'],
        ['key' => 'diagnostic',       'label' => 'Componente'],
        ['key' => 'recommendation',   'label' => 'Hallazgo'],
        ['key' => 'recommendation_2', 'label' => 'Recomendación'],
        ['key' => 'evidence',         'label' => 'Evidencia'],
        ['key' => 'condition',        'label' => 'Condición'],
        ['key' => 'orden',            'label' => 'Orden'],
        ['key' => 'aviso',            'label' => 'Aviso'],
        ['key' => 'inspector',        'label' => 'Inspector'],
        ['key' => 'responsable',      'label' => 'Responsable'],
        ['key' => 'report_date',      'label' => 'Fecha de reporte'],
        ['key' => 'execution_date',   'label' => 'Fecha de ejecución'],
        ['key' => 'condition_name',   'label' => 'Condición del activo'],
        ['key' => 'execution_status', 'label' => 'Ejecución orden'],
        ['key' => 'week',             'label' => 'Semana'],
        ['key' => 'warehouse_code',   'label' => 'Ubicación Técnica'],
    ];

    // Permisos de edición predeterminados (refleja el comportamiento actual del backend)
    private const DEFAULT_EDIT_PERMISSIONS = [
        'recommendation'   => ['admin_cliente' => false, 'observador' => false, 'observador_cliente' => false],
        'recommendation_2' => ['admin_cliente' => false, 'observador' => false, 'observador_cliente' => false],
        'orden'            => ['admin_cliente' => true,  'observador' => false, 'observador_cliente' => false],
        'aviso'            => ['admin_cliente' => true,  'observador' => false, 'observador_cliente' => false],
        'execution_date'   => ['admin_cliente' => true,  'observador' => false, 'observador_cliente' => false],
    ];

    public function getDefaultColumns(): array
    {
        return collect(self::COLUMN_DEFINITIONS)
            ->values()
            ->map(function ($def, $index) {
                $perms = self::DEFAULT_EDIT_PERMISSIONS[$def['key']] ?? [];
                return [
                    'column_key'               => $def['key'],
                    'label'                    => $def['label'],
                    'editable'                 => in_array($def['key'], self::EDITABLE_KEYS, true),
                    'position'                 => $index + 1,
                    'visible'                  => true,
                    'can_edit_admin_cliente'   => $perms['admin_cliente'] ?? false,
                    'can_edit_observador'      => $perms['observador'] ?? false,
                    'can_edit_observador_cliente' => $perms['observador_cliente'] ?? false,
                ];
            })
            ->all();
    }

    public function getColumnsForGroup(int $groupId): array
    {
        $config = GroupReportConfig::query()
            ->where('group_id', $groupId)
            ->with(['columns' => fn ($q) => $q->orderBy('position')])
            ->first();

        if (! $config) {
            return $this->getDefaultColumns();
        }

        $definitions = collect(self::COLUMN_DEFINITIONS)->keyBy('key');

        $result = $config->columns
            ->map(function ($col) use ($definitions) {
                return [
                    'column_key'                  => $col->column_key,
                    'label'                       => $definitions->get($col->column_key)['label'] ?? $col->label,
                    'editable'                    => in_array($col->column_key, self::EDITABLE_KEYS, true),
                    'position'                    => $col->position,
                    'visible'                     => $col->visible,
                    'can_edit_admin_cliente'      => $col->can_edit_admin_cliente,
                    'can_edit_observador'         => $col->can_edit_observador,
                    'can_edit_observador_cliente' => $col->can_edit_observador_cliente,
                ];
            })
            ->values()
            ->all();

        // Completar columnas nuevas que no existan en la config guardada
        $savedKeys = collect($result)->pluck('column_key')->all();
        foreach (self::COLUMN_DEFINITIONS as $def) {
            if (in_array($def['key'], $savedKeys, true)) {
                continue;
            }
            $perms = self::DEFAULT_EDIT_PERMISSIONS[$def['key']] ?? [];
            $result[] = [
                'column_key'                  => $def['key'],
                'label'                       => $def['label'],
                'editable'                    => in_array($def['key'], self::EDITABLE_KEYS, true),
                'position'                    => count($result) + 1,
                'visible'                     => in_array($def['key'], self::ALWAYS_VISIBLE_KEYS, true),
                'can_edit_admin_cliente'      => $perms['admin_cliente'] ?? false,
                'can_edit_observador'         => $perms['observador'] ?? false,
                'can_edit_observador_cliente' => $perms['observador_cliente'] ?? false,
            ];
        }

        return $result;
    }

    // Devuelve solo las columnas visibles en orden, con can_edit resuelto para el rol dado.
    // Las columnas en ALWAYS_VISIBLE_KEYS se incluyen siempre, ignorando el flag visible de la config.
    public function resolveForRole(int $groupId, string $roleKey): array
    {
        $columns = $this->getColumnsForGroup($groupId);

        return collect($columns)
            ->filter(fn ($col) => $col['visible'] || in_array($col['column_key'], self::ALWAYS_VISIBLE_KEYS, true))
            ->map(function ($col) use ($roleKey) {
                $col['can_edit'] = match ($roleKey) {
                    'admin_cliente'      => $col['can_edit_admin_cliente'],
                    'observador'         => $col['can_edit_observador'],
                    'observador_cliente' => $col['can_edit_observador_cliente'],
                    default              => false, // superadmin/admin/admin_global siempre editan por su propio flujo
                };
                return $col;
            })
            ->values()
            ->all();
    }

    public function saveColumns(int $groupId, array $columnsPayload): void
    {
        $validKeys = collect(self::COLUMN_DEFINITIONS)->pluck('key')->all();

        DB::transaction(function () use ($groupId, $columnsPayload, $validKeys) {
            $config = GroupReportConfig::firstOrCreate(['group_id' => $groupId]);

            $config->columns()->delete();

            foreach ($columnsPayload as $index => $col) {
                $key = $col['column_key'] ?? '';

                if (! in_array($key, $validKeys, true)) {
                    continue;
                }

                $config->columns()->create([
                    'column_key'               => $key,
                    'label'                    => $col['label'] ?? '',
                    'position'                 => $index + 1,
                    // Las columnas protegidas siempre se guardan como visibles
                    'visible'                  => in_array($key, self::ALWAYS_VISIBLE_KEYS, true)
                                                    ? true
                                                    : (bool) ($col['visible'] ?? true),
                    'can_edit_admin_cliente'   => (bool) ($col['can_edit_admin_cliente'] ?? false),
                    'can_edit_observador'      => (bool) ($col['can_edit_observador'] ?? false),
                    'can_edit_observador_cliente' => (bool) ($col['can_edit_observador_cliente'] ?? false),
                ]);
            }
        });
    }

    public function resetToDefault(int $groupId): void
    {
        GroupReportConfig::query()->where('group_id', $groupId)->delete();
    }
}
