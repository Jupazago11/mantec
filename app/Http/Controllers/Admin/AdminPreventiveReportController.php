<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ElementType;
use App\Models\ExecutionStatus;
use App\Models\ReportDetail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminPreventiveReportController extends Controller
{
    public function show(Client $client, ElementType $elementType, Request $request): View
    {
        $user = Auth::user();

        $allowedClientIds = $this->getAllowedClientIds($user);

        abort_unless(
            in_array((int) $client->id, $allowedClientIds, true),
            403,
            'No autorizado para ver reportes de este cliente.'
        );

        if ((int) $elementType->client_id !== (int) $client->id) {
            abort(404, 'El tipo de activo no pertenece al cliente indicado.');
        }

        abort_unless(
            $this->canAccessElementType($user, (int) $client->id, (int) $elementType->id),
            403,
            'No autorizado para ver reportes de este tipo de activo.'
        );

        $year = (int) $request->input('year', now()->year);

        $baseQuery = $this->baseScopedQuery($client, $elementType, $year, $user);

        $query = clone $baseQuery;
        $this->applyFilters($query, $request);

        $totalReportsGenerated = (clone $baseQuery)->count();
        $totalReportsFiltered = (clone $query)->count();

        $showWarehouseColumn = (clone $query)
            ->whereHas('element', function ($elementQuery) {
                $elementQuery->whereNotNull('warehouse_code')
                    ->where('warehouse_code', '!=', '');
            })
            ->exists();

        $reports = $query
            ->orderByDesc('week')
            ->orderByDesc('created_at')
            ->paginate(100)
            ->withQueryString();

        $reports->getCollection()->transform(function ($report) {
            $report->responsable_names = $this->resolveAdminClienteResponsables($report);
            return $report;
        });



        $optionsRows = (clone $baseQuery)
            ->with([
                'user:id,name',
                'element:id,name,area_id,element_type_id',
                'element.area:id,name,client_id',
                'component:id,name',
                'diagnostic:id,name',
                'condition:id,name,code,color',
                'executionStatus:id,name',
            ])
            ->orderByDesc('week')
            ->orderByDesc('created_at')
            ->get();

        $filterOptions = [
            'element_names' => $optionsRows
                ->map(fn ($row) => $row->element?->name)
                ->filter()
                ->unique()
                ->sort()
                ->values(),

            'area_names' => $optionsRows
                ->map(fn ($row) => $row->element?->area?->name)
                ->filter()
                ->unique()
                ->sort()
                ->values(),

            'diagnostic_pairs' => $optionsRows
                ->map(function ($row) {
                    if (!$row->component || !$row->diagnostic) {
                        return null;
                    }

                    return [
                        'value' => $row->component->id . '|' . $row->diagnostic->id,
                        'label' => $row->component->name . ' — ' . $row->diagnostic->name,
                    ];
                })
                ->filter()
                ->unique('value')
                ->sortBy('label')
                ->values(),

            'recommendation_values' => $optionsRows
                ->flatMap(function ($row) {
                    $text = (string) ($row->recommendation ?? '');

                    if (trim($text) === '') {
                        return [];
                    }

                    return collect(preg_split('/\r\n|\r|\n/', $text))
                        ->map(fn ($line) => trim($line))
                        ->filter(fn ($line) => $line !== '')
                        ->values();
                })
                ->unique()
                ->sort()
                ->values(),

            'condition_codes' => $optionsRows
                ->map(fn ($row) => $row->condition?->code)
                ->filter()
                ->unique()
                ->sort()
                ->values(),

            'orden_values' => $optionsRows
                ->map(function ($row) {
                    $value = trim((string) ($row->orden ?? ''));
                    return $value !== '' ? $value : null;
                })
                ->filter()
                ->unique()
                ->sort()
                ->values(),

            'aviso_values' => $optionsRows
                ->map(function ($row) {
                    $value = trim((string) ($row->aviso ?? ''));
                    return $value !== '' ? $value : null;
                })
                ->filter()
                ->unique()
                ->sort()
                ->values(),

            'responsable_names' => $optionsRows
                ->map(fn ($row) => $this->resolveAdminClienteResponsables($row))
                ->filter(fn ($value) => $value !== null && $value !== '' && $value !== '—')
                ->flatMap(fn ($value) => collect(explode(', ', $value)))
                ->filter()
                ->unique()
                ->sort()
                ->values(),


            'condition_names' => $optionsRows
                ->map(fn ($row) => $row->condition?->name)
                ->filter()
                ->unique()
                ->sort()
                ->values(),

            'execution_statuses' => collect(['PENDIENTE', 'REALIZADO']),

            'weeks' => $optionsRows
                ->map(fn ($row) => $row->week)
                ->filter()
                ->unique()
                ->sortDesc()
                ->values(),

            'warehouse_ids' => collect(),
        ];

        $activeFilters = [
            'element_names' => $request->input('element_names', []),
            'area_names' => $request->input('area_names', []),
            'diagnostic_pairs' => $request->input('diagnostic_pairs', []),
            'recommendation_values' => $request->input('recommendation_values', []),
            'condition_codes' => $request->input('condition_codes', []),
            'orden_values' => $request->input('orden_values', []),
            'aviso_values' => $request->input('aviso_values', []),
            'responsable_names' => $request->input('responsable_names', []),
            'condition_names' => $request->input('condition_names', []),
            'execution_statuses' => $request->input('execution_statuses', []),
            'weeks' => $request->input('weeks', []),
            'report_date_from' => $request->input('report_date_from'),
            'report_date_to' => $request->input('report_date_to'),
            'execution_date_from' => $request->input('execution_date_from'),
            'execution_date_to' => $request->input('execution_date_to'),
        ];

        return view('admin.reports.preventive.show', [
            'client' => $client,
            'elementType' => $elementType,
            'reports' => $reports,
            'currentYear' => $year,
            'selectedYear' => $year,
            'filterOptions' => $filterOptions,
            'activeFilters' => $activeFilters,
            'totalReportsGenerated' => $totalReportsGenerated,
            'totalReportsFiltered' => $totalReportsFiltered,
            'showWarehouseColumn' => $showWarehouseColumn,
            'isReadOnly' => $this->isReadOnlyRole($user),
            'roleKey' => $user->role?->key,
        ]);

    }

    public function general(Client $client, Request $request): View
    {
        $user = Auth::user();

        $allowedClientIds = $this->getAllowedClientIds($user);

        abort_unless(
            in_array((int) $client->id, $allowedClientIds, true),
            403,
            'No autorizado para ver reportes de este cliente.'
        );

        $year = (int) $request->input('year', now()->year);

        $baseQuery = ReportDetail::query()
            ->with([
                'user',
                'element.area.client',
                'element.elementType',
                'component',
                'diagnostic',
                'condition',
                'executionStatus',
            ])
            ->where('status', true)
            ->where('year', $year)
            ->whereHas('element', function ($query) use ($user, $client) {
                $query->whereHas('area', function ($areaQuery) use ($client) {
                    $areaQuery->where('client_id', $client->id);
                });

                if ($this->mustRestrictByElementTypes($user)) {
                    $allowedElementTypeIds = $this->getAllowedElementTypeIdsForClient($user, (int) $client->id);

                    if (empty($allowedElementTypeIds)) {
                        $query->whereRaw('1 = 0');
                        return;
                    }

                    $query->whereIn('element_type_id', $allowedElementTypeIds);
                }

                if ($this->mustRestrictByAreas($user)) {
                    $allowedAreaMap = $this->getAllowedAreaIdsGroupedByElementType($user, (int) $client->id);

                    if (empty($allowedAreaMap)) {
                        $query->whereRaw('1 = 0');
                        return;
                    }

                    $query->where(function ($outer) use ($allowedAreaMap) {
                        foreach ($allowedAreaMap as $elementTypeId => $areaIds) {
                            $outer->orWhere(function ($inner) use ($elementTypeId, $areaIds) {
                                $inner->where('element_type_id', (int) $elementTypeId)
                                    ->whereIn('area_id', $areaIds);
                            });
                        }
                    });
                }
            });

        $query = clone $baseQuery;
        $this->applyFilters($query, $request);

        $totalReportsGenerated = (clone $baseQuery)->count();
        $totalReportsFiltered = (clone $query)->count();

        $showWarehouseColumn = (clone $query)
            ->whereHas('element', function ($elementQuery) {
                $elementQuery->whereNotNull('warehouse_code')
                    ->where('warehouse_code', '!=', '');
            })
            ->exists();

        $reports = $query
            ->orderByDesc('week')
            ->orderByDesc('created_at')
            ->paginate(100)
            ->withQueryString();

        $reports->getCollection()->transform(function ($report) {
            $report->responsable_names = $this->resolveAdminClienteResponsables($report);
            return $report;
        });


        $optionsRows = (clone $baseQuery)
            ->with([
                'user:id,name',
                'element:id,name,area_id,element_type_id',
                'element.area:id,name,client_id',
                'element.elementType:id,name',
                'component:id,name',
                'diagnostic:id,name',
                'condition:id,name,code,color',
                'executionStatus:id,name',
            ])
            ->orderByDesc('week')
            ->orderByDesc('created_at')
            ->get();

        $filterOptions = [
            'element_type_names' => $optionsRows
                ->map(fn ($row) => $row->element?->elementType?->name)
                ->filter()
                ->unique()
                ->sort()
                ->values(),

            'element_names' => $optionsRows
                ->map(fn ($row) => $row->element?->name)
                ->filter()
                ->unique()
                ->sort()
                ->values(),

            'area_names' => $optionsRows
                ->map(fn ($row) => $row->element?->area?->name)
                ->filter()
                ->unique()
                ->sort()
                ->values(),

            'diagnostic_pairs' => $optionsRows
                ->map(function ($row) {
                    if (!$row->component || !$row->diagnostic) {
                        return null;
                    }

                    return [
                        'value' => $row->component->id . '|' . $row->diagnostic->id,
                        'label' => $row->component->name . ' — ' . $row->diagnostic->name,
                    ];
                })
                ->filter()
                ->unique('value')
                ->sortBy('label')
                ->values(),

            'recommendation_values' => $optionsRows
                ->flatMap(function ($row) {
                    $text = (string) ($row->recommendation ?? '');

                    if (trim($text) === '') {
                        return [];
                    }

                    return collect(preg_split('/\r\n|\r|\n/', $text))
                        ->map(fn ($line) => trim($line))
                        ->filter(fn ($line) => $line !== '')
                        ->values();
                })
                ->unique()
                ->sort()
                ->values(),

            'condition_codes' => $optionsRows
                ->map(fn ($row) => $row->condition?->code)
                ->filter()
                ->unique()
                ->sort()
                ->values(),

            'orden_values' => $optionsRows
                ->map(function ($row) {
                    $value = trim((string) ($row->orden ?? ''));
                    return $value !== '' ? $value : null;
                })
                ->filter()
                ->unique()
                ->sort()
                ->values(),

            'aviso_values' => $optionsRows
                ->map(function ($row) {
                    $value = trim((string) ($row->aviso ?? ''));
                    return $value !== '' ? $value : null;
                })
                ->filter()
                ->unique()
                ->sort()
                ->values(),

            'inspector_names' => $optionsRows
                ->map(fn ($row) => $row->user?->name)
                ->filter()
                ->unique()
                ->sort()
                ->values(),

            'responsable_names' => $optionsRows
                ->map(fn ($row) => $this->resolveAdminClienteResponsables($row))
                ->filter(fn ($value) => $value !== null && $value !== '' && $value !== '—')
                ->flatMap(fn ($value) => collect(explode(', ', $value)))
                ->filter()
                ->unique()
                ->sort()
                ->values(),


            'condition_names' => $optionsRows
                ->map(fn ($row) => $row->condition?->name)
                ->filter()
                ->unique()
                ->sort()
                ->values(),

            'execution_statuses' => collect(['PENDIENTE', 'REALIZADO']),

            'weeks' => $optionsRows
                ->map(fn ($row) => $row->week)
                ->filter()
                ->unique()
                ->sortDesc()
                ->values(),

            'warehouse_ids' => collect(),
        ];

        $activeFilters = [
            'element_type_names' => $request->input('element_type_names', []),
            'area_names' => $request->input('area_names', []),
            'element_names' => $request->input('element_names', []),
            'diagnostic_pairs' => $request->input('diagnostic_pairs', []),
            'recommendation_values' => $request->input('recommendation_values', []),
            'condition_codes' => $request->input('condition_codes', []),
            'orden_values' => $request->input('orden_values', []),
            'aviso_values' => $request->input('aviso_values', []),
            'inspector_names' => $request->input('inspector_names', []),
            'responsable_names' => $request->input('responsable_names', []),
            'condition_names' => $request->input('condition_names', []),
            'execution_statuses' => $request->input('execution_statuses', []),
            'weeks' => $request->input('weeks', []),
            'report_date_from' => $request->input('report_date_from'),
            'report_date_to' => $request->input('report_date_to'),
            'execution_date_from' => $request->input('execution_date_from'),
            'execution_date_to' => $request->input('execution_date_to'),
        ];


        return view('admin.reports.preventive.general', compact(
            'client',
            'year',
            'reports',
            'filterOptions',
            'activeFilters',
            'totalReportsGenerated',
            'totalReportsFiltered',
            'showWarehouseColumn'
        ))->with([
            'currentYear' => $year,
            'selectedYear' => $year,
            'isReadOnly' => $this->isReadOnlyRole($user),
            'roleKey' => $user->role?->key,
        ]);
    }

    public function toggleExecution(\App\Models\ReportDetail $reportDetail)
    {
        $user = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless(
            in_array($roleKey, ['superadmin', 'admin_global', 'admin', 'admin_cliente'], true),
            403,
            'No tienes permisos para modificar la ejecución.'
        );

        $reportDetail->loadMissing([
            'element.area',
            'element.elementType',
        ]);

        abort_unless(
            $this->canAccessReportByCurrentScope($user, $reportDetail),
            403,
            'No autorizado para modificar este reporte.'
        );

        $statuses = \App\Models\ExecutionStatus::query()
            ->where('status', true)
            ->get();

        $pendingStatus = $statuses->first(function ($status) {
            $name = mb_strtoupper(trim((string) ($status->name ?? '')));
            $code = mb_strtoupper(trim((string) ($status->code ?? '')));

            return in_array($name, ['PENDIENTE', 'PENDIENTE DE EJECUCIÓN', 'SIN EJECUTAR'], true)
                || in_array($code, ['PENDIENTE', 'PENDING', 'PEND'], true);
        });

        $doneStatus = $statuses->first(function ($status) {
            $name = mb_strtoupper(trim((string) ($status->name ?? '')));
            $code = mb_strtoupper(trim((string) ($status->code ?? '')));

            return in_array($name, ['EJECUTADO', 'EJECUTADA', 'REALIZADO', 'REALIZADA', 'FINALIZADO', 'FINALIZADA'], true)
                || in_array($code, ['EJECUTADO', 'DONE', 'REALIZADO', 'COMPLETADO'], true);
        });

        if (!$pendingStatus || !$doneStatus) {
            return response()->json([
                'success' => false,
                'message' => 'Configura dos estados activos de ejecución: uno pendiente y otro realizado.',
            ], 422);
        }

        $isCurrentlyDone = (int) $reportDetail->execution_status_id === (int) $doneStatus->id;

        if ($isCurrentlyDone) {
            $reportDetail->execution_status_id = $pendingStatus->id;
            $reportDetail->execution_date = null;
        } else {
            $reportDetail->execution_status_id = $doneStatus->id;
            $reportDetail->execution_date = now()->toDateString();
        }

        $reportDetail->save();

        return response()->json([
            'success' => true,
            'executed' => (int) $reportDetail->execution_status_id === (int) $doneStatus->id,
            'execution_date' => $reportDetail->execution_date
                ? \Carbon\Carbon::parse($reportDetail->execution_date)->format('Y-m-d')
                : '',
            'execution_status_id' => $reportDetail->execution_status_id,
            'execution_status_name' => $reportDetail->executionStatus?->name ?? '',
        ]);
    }


    private function baseScopedQuery(Client $client, ElementType $elementType, int $year, $user)
    {
        return ReportDetail::query()
            ->with([
                'user',
                'element.area.client',
                'element.elementType',
                'component',
                'diagnostic',
                'condition',
                'executionStatus',
            ])
            ->where('status', true)
            ->where('year', $year)
            ->whereHas('element', function ($query) use ($client, $elementType, $user) {
                $query->where('element_type_id', $elementType->id)
                    ->whereHas('area', function ($areaQuery) use ($client) {
                        $areaQuery->where('client_id', $client->id);
                    });

                if ($this->mustRestrictByElementTypes($user)) {
                    $allowedElementTypeIds = $this->getAllowedElementTypeIdsForClient($user, (int) $client->id);

                    if (empty($allowedElementTypeIds) || !in_array((int) $elementType->id, $allowedElementTypeIds, true)) {
                        $query->whereRaw('1 = 0');
                        return;
                    }
                }

                if ($this->mustRestrictByAreas($user)) {
                    $allowedAreaIds = $this->getAllowedAreaIdsForClientAndElementType(
                        $user,
                        (int) $client->id,
                        (int) $elementType->id
                    );

                    if (empty($allowedAreaIds)) {
                        $query->whereRaw('1 = 0');
                        return;
                    }

                    $query->whereIn('area_id', $allowedAreaIds);
                }
            });
    }


    private function applyFilters($query, Request $request): void
    {
        $elementTypeNames = array_filter((array) $request->input('element_type_names', []));
        if (!empty($elementTypeNames)) {
            $query->whereHas('element.elementType', function ($q) use ($elementTypeNames) {
                $q->whereIn('name', $elementTypeNames);
            });
        }

        $elementNames = array_filter((array) $request->input('element_names', []));
        if (!empty($elementNames)) {
            $query->whereHas('element', function ($q) use ($elementNames) {
                $q->whereIn('name', $elementNames);
            });
        }

        $areaNames = array_filter((array) $request->input('area_names', []));
        if (!empty($areaNames)) {
            $query->whereHas('element.area', function ($q) use ($areaNames) {
                $q->whereIn('name', $areaNames);
            });
        }


        $areaNames = array_filter((array) $request->input('area_names', []));
        if (!empty($areaNames)) {
            $query->whereHas('element.area', function ($q) use ($areaNames) {
                $q->whereIn('name', $areaNames);
            });
        }

        $diagnosticPairs = array_filter((array) $request->input('diagnostic_pairs', []));
        if (!empty($diagnosticPairs)) {
            $query->where(function ($outer) use ($diagnosticPairs) {
                foreach ($diagnosticPairs as $pair) {
                    [$componentId, $diagnosticId] = array_pad(explode('|', $pair), 2, null);

                    if ($componentId && $diagnosticId) {
                        $outer->orWhere(function ($inner) use ($componentId, $diagnosticId) {
                            $inner->where('component_id', (int) $componentId)
                                ->where('diagnostic_id', (int) $diagnosticId);
                        });
                    }
                }
            });
        }

        $recommendationValues = array_filter((array) $request->input('recommendation_values', []));
        if (!empty($recommendationValues)) {
            $normalizedValues = collect($recommendationValues)
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values()
                ->toArray();

            $query->where(function ($recommendationQuery) use ($normalizedValues) {
                foreach ($normalizedValues as $value) {
                    $recommendationQuery->orWhereRaw(
                        "EXISTS (
                            SELECT 1
                            FROM regexp_split_to_table(COALESCE(report_details.recommendation, ''), E'\\r?\\n') AS lines(line_text)
                            WHERE BTRIM(line_text) = ?
                        )",
                        [$value]
                    );
                }
            });
        }

        $conditionCodes = array_filter((array) $request->input('condition_codes', []));
        if (!empty($conditionCodes)) {
            $query->whereHas('condition', function ($q) use ($conditionCodes) {
                $q->whereIn('code', $conditionCodes);
            });
        }

        $ordenValues = array_filter((array) $request->input('orden_values', []));
        if (!empty($ordenValues)) {
            $query->whereIn('orden', $ordenValues);
        }

        $avisoValues = array_filter((array) $request->input('aviso_values', []));
        if (!empty($avisoValues)) {
            $query->whereIn('aviso', $avisoValues);
        }

        $inspectorNames = array_filter((array) $request->input('inspector_names', []));
        if (!empty($inspectorNames)) {
            $query->whereHas('user', function ($q) use ($inspectorNames) {
                $q->whereIn('name', $inspectorNames);
            });
        }

        $responsableNames = array_filter((array) $request->input('responsable_names', []));
        if ($request->filled('responsable_names')) {
            $responsableNames = array_filter((array) $request->input('responsable_names', []));

            $query->where(function ($outerQuery) use ($responsableNames) {
                foreach ($responsableNames as $responsableName) {
                    $outerQuery->orWhereExists(function ($subQuery) use ($responsableName) {
                        $subQuery->selectRaw('1')
                            ->from('users')
                            ->join('roles', 'roles.id', '=', 'users.role_id')
                            ->join('client_user', 'client_user.user_id', '=', 'users.id')
                            ->join('group_user', 'group_user.user_id', '=', 'users.id')
                            ->join('groups', 'groups.id', '=', 'group_user.group_id')
                            ->join('user_client_group_areas', function ($join) {
                                $join->on('user_client_group_areas.user_id', '=', 'users.id')
                                    ->on('user_client_group_areas.client_id', '=', 'client_user.client_id')
                                    ->on('user_client_group_areas.group_id', '=', 'group_user.group_id');
                            })
                            ->join('elements', 'elements.id', '=', 'report_details.element_id')
                            ->join('areas', 'areas.id', '=', 'elements.area_id')
                            ->whereColumn('client_user.client_id', 'areas.client_id')
                            ->whereColumn('groups.client_id', 'client_user.client_id')
                            ->whereColumn('group_user.group_id', 'elements.group_id')
                            ->whereColumn('user_client_group_areas.group_id', 'elements.group_id')
                            ->whereColumn('user_client_group_areas.area_id', 'elements.area_id')
                            ->where('roles.key', 'admin_cliente')
                            ->where('users.status', true)
                            ->where('users.name', $responsableName);
                    });
                }
            });
        }


        if ($request->filled('report_date_from')) {
            $query->whereDate('created_at', '>=', $request->input('report_date_from'));
        }

        if ($request->filled('report_date_to')) {
            $query->whereDate('created_at', '<=', $request->input('report_date_to'));
        }

        if ($request->filled('execution_date_from')) {
            $query->whereDate('execution_date', '>=', $request->input('execution_date_from'));
        }

        if ($request->filled('execution_date_to')) {
            $query->whereDate('execution_date', '<=', $request->input('execution_date_to'));
        }

        $conditionNames = array_filter((array) $request->input('condition_names', []));
        if (!empty($conditionNames)) {
            $query->whereHas('condition', function ($q) use ($conditionNames) {
                $q->whereIn('name', $conditionNames);
            });
        }

        $executionStatuses = array_filter((array) $request->input('execution_statuses', []));
        if (!empty($executionStatuses)) {
            $query->where(function ($statusQuery) use ($executionStatuses) {
                $normalized = collect($executionStatuses)
                    ->map(fn ($v) => strtoupper(trim((string) $v)))
                    ->values();

                if ($normalized->contains('REALIZADO')) {
                    $statusQuery->orWhereHas('executionStatus', function ($q) {
                        $q->where('name', 'REALIZADO');
                    });
                }

                if ($normalized->contains('PENDIENTE')) {
                    $statusQuery->orWhere(function ($q) {
                        $q->whereNull('execution_status_id')
                            ->orWhereHas('executionStatus', function ($statusQ) {
                                $statusQ->where('name', '!=', 'REALIZADO');
                            });
                    });
                }
            });
        }

        $weeks = array_filter((array) $request->input('weeks', []), fn ($value) => $value !== null && $value !== '');
        if (!empty($weeks)) {
            $query->whereIn('week', $weeks);
        }
    }

private function resolveAdminClienteResponsables(ReportDetail $report): string
{
    $clientId = $report->element?->area?->client_id;
    $groupId = $report->element?->group_id;
    $areaId = $report->element?->area_id;

    if (!$clientId || !$groupId || !$areaId) {
        return '—';
    }

    $names = User::query()
        ->select('users.name')
        ->join('roles', 'roles.id', '=', 'users.role_id')
        ->where('roles.key', 'admin_cliente')
        ->where('users.status', true)
        ->whereExists(function ($query) use ($clientId) {
            $query->selectRaw('1')
                ->from('client_user')
                ->whereColumn('client_user.user_id', 'users.id')
                ->where('client_user.client_id', $clientId);
        })
        ->whereExists(function ($query) use ($clientId, $groupId) {
            $query->selectRaw('1')
                ->from('group_user')
                ->join('groups', 'groups.id', '=', 'group_user.group_id')
                ->whereColumn('group_user.user_id', 'users.id')
                ->where('group_user.group_id', $groupId)
                ->where('groups.client_id', $clientId);
        })
        ->whereExists(function ($query) use ($clientId, $groupId, $areaId) {
            $query->selectRaw('1')
                ->from('user_client_group_areas')
                ->whereColumn('user_client_group_areas.user_id', 'users.id')
                ->where('user_client_group_areas.client_id', $clientId)
                ->where('user_client_group_areas.group_id', $groupId)
                ->where('user_client_group_areas.area_id', $areaId);
        })
        ->orderBy('users.name')
        ->pluck('users.name')
        ->unique()
        ->values()
        ->all();

    return !empty($names) ? implode(', ', $names) : '—';
}

public function showByGroup(\App\Models\Group $group, \Illuminate\Http\Request $request): \Illuminate\View\View
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
        'No tienes permisos para ver este reporte.'
    );

    $allowedClientIds = match ($roleKey) {
        'superadmin', 'admin_global', 'observador' => \App\Models\Client::query()
            ->where('status', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all(),

        default => $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->map(fn ($id) => (int) $id)
            ->all(),
    };

    abort_unless(
        in_array((int) $group->client_id, $allowedClientIds, true),
        403,
        'No tienes acceso a esta agrupación.'
    );

    if (in_array($roleKey, ['admin_cliente', 'observador_cliente'], true)) {
        abort_unless(
            $user->groups()
                ->where('groups.id', $group->id)
                ->where('groups.client_id', $group->client_id)
                ->exists(),
            403,
            'No tienes acceso a esta agrupación.'
        );
    }

    $dateFrom = $request->input('date_from', now()->startOfYear()->toDateString());
    $dateTo = $request->input('date_to', now()->toDateString());

    abort_unless($dateFrom && $dateTo, 422, 'Debes enviar date_from y date_to.');
    abort_unless($dateTo >= $dateFrom, 422, 'La fecha final no puede ser menor que la fecha inicial.');

    $baseQuery = \App\Models\ReportDetail::query()
        ->with([
            'user:id,name',
            'element:id,name,code,warehouse_code,area_id,element_type_id,group_id',
            'element.area:id,name,client_id',
            'component:id,name',
            'diagnostic:id,name',
            'condition:id,name,code,color',
            'files:id,report_detail_id',
            'executionStatus:id,code,name',
        ])
        ->where('status', true)
        ->whereHas('element', function ($elementQuery) use ($group) {
            $elementQuery->where('group_id', $group->id);
        })
        ->whereDate('created_at', '>=', $dateFrom)
        ->whereDate('created_at', '<=', $dateTo);

    /*
     * Nueva regla ManTec:
     * admin_cliente ve todos los reportes de sus agrupaciones asignadas,
     * independientemente de las áreas.
     *
     * Por eso aquí NO se filtra por area_id.
     * El acceso ya fue validado arriba por cliente + agrupación.
     */

    $query = clone $baseQuery;
    $this->applyGroupFilters($query, $request);

    $totalReportsGenerated = (clone $baseQuery)->count();
    $totalReportsFiltered = (clone $query)->count();

    $reports = $query
        ->orderByDesc('created_at')
        ->orderByDesc('id')
        ->paginate(100)
        ->withQueryString();

    $reports->setCollection(
        $reports->getCollection()->map(function ($report) {
            $element = $report->element;
            $area = $element?->area;
            $component = $report->component;
            $diagnostic = $report->diagnostic;
            $condition = $report->condition;
            $inspector = $report->user;
            $executionStatus = $report->executionStatus;

            $statusName = trim((string) ($executionStatus?->name ?? ''));
            $statusUpper = mb_strtoupper($statusName);

            $report->area_name = $area?->name ?: '—';
            $report->element_name = $element?->name ?: '—';
            $report->warehouse_code = $element?->warehouse_code ?: '—';

            $report->component_name = $component?->name ?: '—';
            $report->diagnostic_name = $diagnostic?->name ?: '—';

            $report->condition_code = $condition?->code ?: '—';
            $report->condition_name = $condition?->name ?: '—';
            $report->condition_color = $condition?->color ?: '#e2e8f0';

            $report->inspector_name = $inspector?->name ?: '—';
            $report->responsable_name = $this->resolveAdminClienteResponsables($report);

            $report->execution_status_name = $statusName !== '' ? $statusName : 'PENDIENTE';

            $report->executed = in_array($statusUpper, [
                'EJECUTADO',
                'EJECUTADA',
                'FINALIZADO',
                'FINALIZADA',
                'REALIZADO',
                'REALIZADA',
            ], true);

            if (!$report->executed) {
                $report->execution_date = null;
            }

            $report->report_date = optional($report->created_at)?->format('Y-m-d');
            $report->has_evidence = $report->files->isNotEmpty();

            return $report;
        })
    );

    $showWarehouseColumn = $reports->getCollection()
        ->pluck('warehouse_code')
        ->filter(fn ($value) => $value !== null && $value !== '' && $value !== '—')
        ->isNotEmpty();

    $optionsRows = (clone $baseQuery)
        ->orderByDesc('created_at')
        ->orderByDesc('id')
        ->get()
        ->map(function ($report) {
            $element = $report->element;
            $area = $element?->area;
            $component = $report->component;
            $diagnostic = $report->diagnostic;
            $condition = $report->condition;
            $inspector = $report->user;
            $executionStatus = $report->executionStatus;

            return (object) [
                'element_name' => $element?->name,
                'area_name' => $area?->name,
                'warehouse_code' => $element?->warehouse_code,
                'component_id' => $component?->id,
                'component_name' => $component?->name,
                'diagnostic_id' => $diagnostic?->id,
                'diagnostic_name' => $diagnostic?->name,
                'recommendation' => $report->recommendation,
                'condition_code' => $condition?->code,
                'condition_name' => $condition?->name,
                'orden' => $report->orden,
                'aviso' => $report->aviso,
                'inspector_name' => $inspector?->name,
                'responsable_name' => $this->resolveAdminClienteResponsables($report),
                'execution_status_name' => $executionStatus?->name,
                'week' => $report->week,
            ];
        });

    $filterOptions = [
        'element_names' => $optionsRows
            ->pluck('element_name')
            ->filter()
            ->unique()
            ->sort()
            ->values(),

        'area_names' => $optionsRows
            ->pluck('area_name')
            ->filter()
            ->unique()
            ->sort()
            ->values(),

        'warehouse_codes' => $optionsRows
            ->pluck('warehouse_code')
            ->filter()
            ->unique()
            ->sort()
            ->values(),

        'diagnostic_pairs' => $optionsRows
            ->filter(fn ($row) => $row->component_id && $row->diagnostic_id)
            ->map(fn ($row) => [
                'value' => $row->component_id . '|' . $row->diagnostic_id,
                'label' => $row->component_name . ' | ' . $row->diagnostic_name,
            ])
            ->unique('value')
            ->sortBy('label')
            ->values(),

        'recommendation_values' => $optionsRows
            ->pluck('recommendation')
            ->filter()
            ->unique()
            ->sort()
            ->values(),

        'condition_codes' => $optionsRows
            ->pluck('condition_code')
            ->filter()
            ->unique()
            ->sort()
            ->values(),

        'orden_values' => $optionsRows
            ->pluck('orden')
            ->filter()
            ->unique()
            ->sort()
            ->values(),

        'aviso_values' => $optionsRows
            ->pluck('aviso')
            ->filter()
            ->unique()
            ->sort()
            ->values(),

        'inspector_names' => $optionsRows
            ->pluck('inspector_name')
            ->filter()
            ->unique()
            ->sort()
            ->values(),

        'responsable_names' => $optionsRows
            ->pluck('responsable_name')
            ->filter(fn ($value) => $value !== null && $value !== '' && $value !== '—')
            ->flatMap(fn ($value) => collect(explode(', ', $value)))
            ->filter()
            ->unique()
            ->sort()
            ->values(),

        'condition_names' => $optionsRows
            ->pluck('condition_name')
            ->filter()
            ->unique()
            ->sort()
            ->values(),

        'execution_statuses' => $optionsRows
            ->pluck('execution_status_name')
            ->filter()
            ->unique()
            ->sort()
            ->values(),

        'weeks' => $optionsRows
            ->pluck('week')
            ->filter()
            ->unique()
            ->sort()
            ->values(),
    ];

    $activeFilters = [
        'element_names' => (array) $request->input('element_names', []),
        'area_names' => (array) $request->input('area_names', []),
        'warehouse_codes' => (array) $request->input('warehouse_codes', []),
        'diagnostic_pairs' => (array) $request->input('diagnostic_pairs', []),
        'recommendation_values' => (array) $request->input('recommendation_values', []),
        'condition_codes' => (array) $request->input('condition_codes', []),
        'orden_values' => (array) $request->input('orden_values', []),
        'aviso_values' => (array) $request->input('aviso_values', []),
        'inspector_names' => (array) $request->input('inspector_names', []),
        'responsable_names' => (array) $request->input('responsable_names', []),
        'report_date_range' => $request->input('report_date_range'),
        'execution_date_range' => $request->input('execution_date_range'),
        'condition_names' => (array) $request->input('condition_names', []),
        'execution_statuses' => (array) $request->input('execution_statuses', []),
        'weeks' => (array) $request->input('weeks', []),
    ];

    return view('admin.reports.preventive.show', [
        'group' => $group->loadMissing('client'),
        'reports' => $reports,
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo,
        'isReadOnly' => in_array($roleKey, ['observador', 'observador_cliente'], true),
        'roleKey' => $roleKey,
        'activeFilters' => $activeFilters,
        'filterOptions' => $filterOptions,
        'showWarehouseColumn' => $showWarehouseColumn,
        'totalGenerated' => $totalReportsGenerated,
        'totalFiltered' => $totalReportsFiltered,
        'canInlineEditOrderAviso' => $roleKey === 'admin_cliente',
        'canInlineEditExecutionDate' => in_array($roleKey, ['superadmin', 'admin_global', 'admin', 'admin_cliente'], true),
        'canEditReports' => in_array($roleKey, ['superadmin', 'admin_global', 'admin'], true),
    ]);
}


    public function editData(\App\Models\ReportDetail $reportDetail)
    {
        $user = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless(
            in_array($roleKey, ['superadmin', 'admin_global', 'admin'], true),
            403,
            'No tienes permisos para editar reportes.'
        );

        $report = \App\Models\ReportDetail::with([
            'element.area',
            'element.elementType',
            'component',
            'diagnostic',
            'condition',
            'user',
        ])
            ->where('status', true)
            ->findOrFail($reportDetail->id);

        $clientId = (int) ($report->element?->area?->client_id ?? 0);
        $elementTypeId = (int) ($report->element?->element_type_id ?? 0);
        $areaId = (int) ($report->element?->area_id ?? 0);

        abort_unless(
            in_array($clientId, $this->getAllowedClientIds($user), true),
            403,
            'No autorizado para editar este reporte.'
        );

        abort_unless(
            $this->canAccessElementType($user, $clientId, $elementTypeId),
            403,
            'No autorizado para editar este reporte.'
        );

        if ($this->mustRestrictByAreas($user)) {
            abort_unless(
                $this->canAccessArea($user, $clientId, $elementTypeId, $areaId),
                403,
                'No autorizado para editar este reporte.'
            );
        }

        $areas = \App\Models\Area::query()
            ->where('client_id', $clientId)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $elements = \App\Models\Element::query()
            ->where('area_id', $areaId)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $components = $report->element
            ? $report->element->components()
                ->where('components.status', true)
                ->orderBy('components.name')
                ->get(['components.id', 'components.name'])
            : collect();

        $diagnostics = \App\Models\Diagnostic::query()
            ->where('client_id', $clientId)
            ->where('element_type_id', $elementTypeId)
            ->where('status', true)
            ->whereHas('components', function ($q) use ($report) {
                $q->where('components.id', $report->component_id);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $conditions = \App\Models\Condition::query()
            ->where('client_id', $clientId)
            ->where('element_type_id', $elementTypeId)
            ->where('status', true)
            ->whereHas('components', function ($q) use ($report) {
                $q->where('components.id', $report->component_id);
            })
            ->orderBy('severity')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'color']);

        return response()->json([
            'success' => true,
            'report' => [
                'id' => $report->id,
                'area_id' => $report->element?->area?->id,
                'element_id' => $report->element_id,
                'element_type_id' => $report->element?->element_type_id,
                'component_id' => $report->component_id,
                'diagnostic_id' => $report->diagnostic_id,
                'condition_id' => $report->condition_id,
                'recommendation' => $report->recommendation ?? '',
                'report_date' => optional($report->created_at)?->format('Y-m-d'),
                'inspector_name' => $report->user?->name ?? '—',
            ],
            'areas' => $areas,
            'elements' => $elements,
            'components' => $components,
            'diagnostics' => $diagnostics,
            'conditions' => $conditions,
        ]);
    }

    public function evidence(\App\Models\ReportDetail $reportDetail): \Illuminate\View\View
    {
        $user = auth()->user();

        $report = \App\Models\ReportDetail::with([
            'element.area',
            'element.elementType',
            'component',
            'diagnostic',
            'condition',
            'files',
            'executionStatus',
            'user',
        ])
            ->where('status', true)
            ->findOrFail($reportDetail->id);

        abort_unless(
            $this->canAccessReportByCurrentScope($user, $report),
            403,
            'No autorizado para ver la evidencia de este reporte.'
        );

        return view('admin.preventive-reports.evidence', compact('report'));
    }

    public function getConditionsByComponent(\App\Models\Component $component)
    {
        $conditions = $component->conditions()
            ->where('conditions.status', true)
            ->orderBy('conditions.severity')
            ->orderBy('conditions.name')
            ->get(['conditions.id', 'conditions.code', 'conditions.name', 'conditions.color']);

        return response()->json($conditions);
    }

    public function updateExecutionDate(\App\Models\ReportDetail $reportDetail, \Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless(
            in_array($roleKey, ['superadmin', 'admin_global', 'admin', 'admin_cliente'], true),
            403,
            'No tienes permisos para editar la fecha de ejecución.'
        );

        $validated = $request->validate([
            'execution_date' => ['required', 'date'],
        ]);

        $report = \App\Models\ReportDetail::with([
            'element.area',
            'element.elementType',
            'executionStatus',
        ])
            ->where('status', true)
            ->findOrFail($reportDetail->id);

        abort_unless(
            $this->canAccessReportByCurrentScope($user, $report),
            403,
            'No autorizado para editar este reporte.'
        );

        $statusName = mb_strtoupper(trim((string) ($report->executionStatus?->name ?? '')));
        $isExecuted = in_array($statusName, [
            'EJECUTADO',
            'EJECUTADA',
            'FINALIZADO',
            'FINALIZADA',
            'REALIZADO',
            'REALIZADA',
        ], true);

        abort_unless(
            $isExecuted,
            422,
            'Solo se puede definir fecha de ejecución cuando la orden está en estado realizado.'
        );

        $report->execution_date = $validated['execution_date'];
        $report->save();

        return response()->json([
            'success' => true,
            'execution_date' => \Carbon\Carbon::parse($report->execution_date)->format('Y-m-d'),
            'message' => 'Fecha de ejecución actualizada correctamente.',
        ]);
    }

    public function inlineUpdate(\App\Models\ReportDetail $reportDetail, \Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless($roleKey === 'admin_cliente', 403, 'No tienes permisos para editar este campo.');

        $validated = $request->validate([
            'field' => ['required', 'string', 'in:orden,aviso'],
            'value' => ['nullable', 'string', 'max:255'],
        ]);

        $field = $validated['field'];
        $value = isset($validated['value']) ? trim((string) $validated['value']) : null;
        $value = $value === '' ? null : $value;

        $report = \App\Models\ReportDetail::with([
            'element.area',
            'element.elementType',
        ])
            ->where('status', true)
            ->findOrFail($reportDetail->id);

        abort_unless(
            $this->canAccessReportByCurrentScope($user, $report),
            403,
            'No autorizado para editar este reporte.'
        );

        $report->{$field} = $value;
        $report->save();

        return response()->json([
            'success' => true,
            'field' => $field,
            'value' => $report->{$field} ?? '',
            'message' => ucfirst($field) . ' actualizado correctamente.',
        ]);
    }

    private function applyGroupFilters($query, \Illuminate\Http\Request $request): void
    {
        if ($request->filled('element_names')) {
            $names = array_filter((array) $request->input('element_names', []));
            $query->whereHas('element', function ($elementQuery) use ($names) {
                $elementQuery->whereIn('name', $names);
            });
        }

        if ($request->filled('area_names')) {
            $areas = array_filter((array) $request->input('area_names', []));
            $query->whereHas('element.area', function ($areaQuery) use ($areas) {
                $areaQuery->whereIn('name', $areas);
            });
        }

        if ($request->filled('warehouse_codes')) {
            $codes = array_filter((array) $request->input('warehouse_codes', []));
            $query->whereHas('element', function ($elementQuery) use ($codes) {
                $elementQuery->whereIn('warehouse_code', $codes);
            });
        }

        if ($request->filled('diagnostic_pairs')) {
            $pairs = array_filter((array) $request->input('diagnostic_pairs', []));
            $query->where(function ($subQuery) use ($pairs) {
                foreach ($pairs as $pair) {
                    [$componentId, $diagnosticId] = array_pad(explode('|', $pair), 2, null);

                    if ($componentId && $diagnosticId) {
                        $subQuery->orWhere(function ($nested) use ($componentId, $diagnosticId) {
                            $nested->where('component_id', $componentId)
                                ->where('diagnostic_id', $diagnosticId);
                        });
                    }
                }
            });
        }

        if ($request->filled('recommendation_values')) {
            $values = array_filter((array) $request->input('recommendation_values', []));
            $query->whereIn('recommendation', $values);
        }

        if ($request->filled('condition_codes')) {
            $codes = array_filter((array) $request->input('condition_codes', []));
            $query->whereHas('condition', function ($conditionQuery) use ($codes) {
                $conditionQuery->whereIn('code', $codes);
            });
        }

        if ($request->filled('orden_values')) {
            $values = array_filter((array) $request->input('orden_values', []));
            $query->whereIn('orden', $values);
        }

        if ($request->filled('aviso_values')) {
            $values = array_filter((array) $request->input('aviso_values', []));
            $query->whereIn('aviso', $values);
        }

        if ($request->filled('inspector_names')) {
            $names = array_filter((array) $request->input('inspector_names', []));
            $query->whereHas('user', function ($userQuery) use ($names) {
                $userQuery->whereIn('name', $names);
            });
        }

if ($request->filled('responsable_names')) {
    $responsableNames = array_filter((array) $request->input('responsable_names', []));

    $query->where(function ($outerQuery) use ($responsableNames) {
        foreach ($responsableNames as $responsableName) {
            $outerQuery->orWhereExists(function ($subQuery) use ($responsableName) {
                $subQuery->selectRaw('1')
                    ->from('users')
                    ->join('roles', 'roles.id', '=', 'users.role_id')
                    ->join('client_user', 'client_user.user_id', '=', 'users.id')
                    ->join('group_user', 'group_user.user_id', '=', 'users.id')
                    ->join('groups', 'groups.id', '=', 'group_user.group_id')
                    ->join('elements', 'elements.id', '=', 'report_details.element_id')
                    ->join('areas', 'areas.id', '=', 'elements.area_id')
                    ->whereColumn('client_user.client_id', 'areas.client_id')
                    ->whereColumn('groups.client_id', 'client_user.client_id')
                    ->whereColumn('group_user.group_id', 'elements.group_id')
                    ->where('roles.key', 'admin_cliente')
                    ->where('users.status', true)
                    ->where('users.name', $responsableName);
            });
        }
    });
}

        if ($request->filled('condition_names')) {
            $names = array_filter((array) $request->input('condition_names', []));
            $query->whereHas('condition', function ($conditionQuery) use ($names) {
                $conditionQuery->whereIn('name', $names);
            });
        }

        if ($request->filled('execution_statuses')) {
            $statuses = array_filter((array) $request->input('execution_statuses', []));
            $query->whereHas('executionStatus', function ($statusQuery) use ($statuses) {
                $statusQuery->whereIn('name', $statuses);
            });
        }

        if ($request->filled('weeks')) {
            $weeks = array_filter((array) $request->input('weeks', []));
            $query->whereIn('week', $weeks);
        }

        $reportDateRange = $request->input('report_date_range');
        if (is_array($reportDateRange)) {
            if (!empty($reportDateRange['from'])) {
                $query->whereDate('created_at', '>=', $reportDateRange['from']);
            }
            if (!empty($reportDateRange['to'])) {
                $query->whereDate('created_at', '<=', $reportDateRange['to']);
            }
        }

        $executionDateRange = $request->input('execution_date_range');
        if (is_array($executionDateRange)) {
            if (!empty($executionDateRange['from'])) {
                $query->whereDate('execution_date', '>=', $executionDateRange['from']);
            }
            if (!empty($executionDateRange['to'])) {
                $query->whereDate('execution_date', '<=', $executionDateRange['to']);
            }
        }
    }

    private function getAllowedClientIds($user): array
    {
        $roleKey = $user->role?->key;

        if (in_array($roleKey, ['superadmin', 'admin_global', 'observador'], true)) {
            return Client::query()
                ->where('status', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function isReadOnlyRole($user): bool
    {
        return in_array($user->role?->key, ['observador', 'observador_cliente'], true);
    }

private function mustRestrictByElementTypes($user): bool
{
    return in_array($user->role?->key, ['observador_cliente'], true);
}

private function mustRestrictByAreas($user): bool
{
    return false;
}

    private function canAccessElementType($user, int $clientId, int $elementTypeId): bool
    {
        if (!$this->mustRestrictByElementTypes($user)) {
            return true;
        }

        return $user->allowedElementTypes()
            ->wherePivot('client_id', $clientId)
            ->where('element_types.id', $elementTypeId)
            ->exists();
    }

    private function canAccessArea($user, int $clientId, int $elementTypeId, int $areaId): bool
    {
        if (!$this->mustRestrictByAreas($user)) {
            return true;
        }

        return $user->allowedAreas()
            ->wherePivot('client_id', $clientId)
            ->wherePivot('element_type_id', $elementTypeId)
            ->where('areas.id', $areaId)
            ->exists();
    }

    private function getAllowedElementTypeIdsForClient($user, int $clientId): array
    {
        if (!$this->mustRestrictByElementTypes($user)) {
            return ElementType::query()
                ->where('client_id', $clientId)
                ->where('status', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return $user->allowedElementTypes()
            ->wherePivot('client_id', $clientId)
            ->where('element_types.status', true)
            ->pluck('element_types.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function getAllowedAreaIdsForClientAndElementType($user, int $clientId, int $elementTypeId): array
    {
        if (!$this->mustRestrictByAreas($user)) {
            return [];
        }

        return $user->allowedAreas()
            ->wherePivot('client_id', $clientId)
            ->wherePivot('element_type_id', $elementTypeId)
            ->pluck('areas.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function getAllowedAreaIdsGroupedByElementType($user, int $clientId): array
    {
        if (!$this->mustRestrictByAreas($user)) {
            return [];
        }

        return $user->allowedAreas()
            ->wherePivot('client_id', $clientId)
            ->get(['areas.id'])
            ->groupBy(fn ($area) => (int) $area->pivot->element_type_id)
            ->map(function ($group) {
                return $group->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();
            })
            ->toArray();
    }
    private function getAvailableYearsForClient($user, int $clientId): array
    {
            $query = ReportDetail::query()
                ->where('status', true)
                ->whereHas('element', function ($elementQuery) use ($user, $clientId) {
                $elementQuery->whereHas('area', function ($areaQuery) use ($clientId) {
                    $areaQuery->where('client_id', $clientId);
                });

                if ($this->mustRestrictByElementTypes($user)) {
                    $allowedElementTypeIds = $this->getAllowedElementTypeIdsForClient($user, $clientId);

                    if (empty($allowedElementTypeIds)) {
                        $elementQuery->whereRaw('1 = 0');
                    } else {
                        $elementQuery->whereIn('element_type_id', $allowedElementTypeIds);
                    }
                }

                if ($this->mustRestrictByAreas($user)) {
                    $allowedAreaMap = $this->getAllowedAreaIdsGroupedByElementType($user, $clientId);

                    if (empty($allowedAreaMap)) {
                        $elementQuery->whereRaw('1 = 0');
                    } else {
                        $elementQuery->where(function ($outer) use ($allowedAreaMap) {
                            foreach ($allowedAreaMap as $elementTypeId => $areaIds) {
                                $outer->orWhere(function ($inner) use ($elementTypeId, $areaIds) {
                                    $inner->where('element_type_id', (int) $elementTypeId)
                                        ->whereIn('area_id', $areaIds);
                                });
                            }
                        });
                    }
                }
            });

        $years = $query->pluck('year')
            ->filter()
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        $currentYear = (int) now()->year;

        if (!in_array($currentYear, $years, true)) {
            $years[] = $currentYear;
            rsort($years, SORT_NUMERIC);
        }

        return array_values(array_unique($years));
    }

    private function getAvailableYearsForClientAndElementType($user, int $clientId, int $elementTypeId): array
    {
        $query = ReportDetail::query()
            ->where('status', true)
            ->whereHas('element', function ($elementQuery) use ($user, $clientId, $elementTypeId) {
                $elementQuery->where('element_type_id', $elementTypeId)
                    ->whereHas('area', function ($areaQuery) use ($clientId) {
                        $areaQuery->where('client_id', $clientId);
                    });

                if ($this->mustRestrictByAreas($user)) {
                    $allowedAreaIds = $this->getAllowedAreaIdsForClientAndElementType(
                        $user,
                        $clientId,
                        $elementTypeId
                    );

                    if (empty($allowedAreaIds)) {
                        $elementQuery->whereRaw('1 = 0');
                    } else {
                        $elementQuery->whereIn('area_id', $allowedAreaIds);
                    }
                }
            });

        $years = $query->pluck('year')
            ->filter()
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        $currentYear = (int) now()->year;

        if (!in_array($currentYear, $years, true)) {
            $years[] = $currentYear;
            rsort($years, SORT_NUMERIC);
        }

        return array_values(array_unique($years));
    }

    private function normalizeYears(Collection|array $years): array
    {
        return collect($years)
            ->filter(fn ($year) => $year !== null && $year !== '')
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }
public function getElementsByArea(\App\Models\Area $area, Request $request)
{
    $query = $area->elements()
        ->where('status', true);

    if ($request->filled('group_id')) {
        $query->where('group_id', (int) $request->input('group_id'));
    }

    $elements = $query
        ->orderBy('name')
        ->get(['id', 'name']);

    return response()->json($elements);
}

    public function getComponentsByElement(\App\Models\Element $element)
    {
        $components = $element->components()
            ->where('components.status', true)
            ->orderBy('components.name')
            ->get(['components.id', 'components.name']);

        return response()->json($components);
    }    

    public function getDiagnosticsByComponent(\App\Models\Component $component)
    {
        $diagnostics = $component->diagnostics()
            ->where('diagnostics.status', true)
            ->orderBy('diagnostics.name')
            ->get(['diagnostics.id', 'diagnostics.name']);

        return response()->json($diagnostics);
    }

    public function adminUpdate(\App\Models\ReportDetail $reportDetail, \Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless(
            in_array($roleKey, ['superadmin', 'admin_global', 'admin'], true),
            403
        );

        $validated = $request->validate([
            'area_id' => ['required', 'integer'],
            'element_id' => ['required', 'integer'],
            'component_id' => ['required', 'integer'],
            'diagnostic_id' => ['required', 'integer'],
            'condition_id' => ['required', 'integer'],
            'recommendation' => ['nullable', 'string'],
            'date_mode' => ['required', 'in:keep,new'],
            'new_date' => ['nullable', 'date'],
        ]);

        $reportDetail->loadMissing([
            'element.area',
            'element.elementType',
        ]);

        $currentGroupId = (int) ($reportDetail->element?->group_id ?? 0);
        $currentClientId = (int) ($reportDetail->element?->area?->client_id ?? 0);

        $element = \App\Models\Element::query()
            ->with(['area', 'elementType'])
            ->findOrFail($validated['element_id']);

        abort_unless(
            $currentClientId > 0 && in_array($currentClientId, $this->getAllowedClientIds($user), true),
            403,
            'No autorizado para editar este reporte.'
        );

        abort_unless(
            (int) ($element->area?->client_id ?? 0) === $currentClientId,
            422,
            'El activo seleccionado no pertenece al mismo cliente del reporte.'
        );

        abort_unless(
            (int) ($element->group_id ?? 0) === $currentGroupId,
            422,
            'El activo seleccionado no pertenece a la misma agrupación del reporte.'
        );

        $componentAllowed = $element->components()
            ->where('components.id', $validated['component_id'])
            ->exists();

        abort_unless(
            $componentAllowed,
            422,
            'El componente seleccionado no pertenece al activo indicado.'
        );

        $diagnosticAllowed = \App\Models\Diagnostic::query()
            ->where('id', $validated['diagnostic_id'])
            ->where('client_id', $element->area?->client_id)
            ->where('element_type_id', $element->element_type_id)
            ->whereHas('components', function ($q) use ($validated) {
                $q->where('components.id', $validated['component_id']);
            })
            ->exists();

        abort_unless(
            $diagnosticAllowed,
            422,
            'El diagnóstico seleccionado no pertenece al componente indicado.'
        );

        $conditionAllowed = \App\Models\Condition::query()
            ->where('id', $validated['condition_id'])
            ->where('client_id', $element->area?->client_id)
            ->where('element_type_id', $element->element_type_id)
            ->whereHas('components', function ($q) use ($validated) {
                $q->where('components.id', $validated['component_id']);
            })
            ->exists();

        abort_unless(
            $conditionAllowed,
            422,
            'La condición seleccionada no pertenece al componente indicado.'
        );

        $reportDetail->element_id = $validated['element_id'];
        $reportDetail->component_id = $validated['component_id'];
        $reportDetail->diagnostic_id = $validated['diagnostic_id'];
        $reportDetail->condition_id = $validated['condition_id'];
        $reportDetail->recommendation = $validated['recommendation'];

        if ($validated['date_mode'] === 'new' && !empty($validated['new_date'])) {
            $newDate = \Carbon\Carbon::parse($validated['new_date'])
                ->setTimeFrom(now());

            $reportDetail->created_at = $newDate;
        }

        $reportDetail->save();

        return response()->json([
            'success' => true,
            'message' => 'Reporte actualizado correctamente.',
        ]);
    }

    public function toggleStatus(\App\Models\ReportDetail $reportDetail)
    {
        $user = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless(
            in_array($roleKey, ['superadmin', 'admin_global'], true),
            403,
            'No tienes permisos para modificar este reporte.'
        );

        $report = \App\Models\ReportDetail::with([
            'element.area',
            'element.elementType',
        ])->findOrFail($reportDetail->id);

        abort_unless(
            $this->canAccessReportByCurrentScope($user, $report),
            403,
            'No autorizado para modificar este reporte.'
        );

        $report->status = !$report->status;
        $report->save();

        return response()->json([
            'success' => true,
            'status' => $report->status,
            'message' => $report->status
                ? 'Reporte restaurado correctamente.'
                : 'Reporte ocultado correctamente.',
        ]);
    }

    private function getAllowedAreaIdsForClientAndGroup($user, int $clientId, int $groupId): array
    {
        if ($user->role?->key !== 'admin_cliente') {
            return [];
        }

        return $user->allowedGroupAreas()
            ->wherePivot('client_id', $clientId)
            ->wherePivot('group_id', $groupId)
            ->pluck('areas.id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function canAccessReportByCurrentScope($user, ReportDetail $report): bool
{
    $roleKey = $user->role?->key;

    $clientId = (int) ($report->element?->area?->client_id ?? 0);
    $groupId = (int) ($report->element?->group_id ?? 0);
    $areaId = (int) ($report->element?->area_id ?? 0);
    $elementTypeId = (int) ($report->element?->element_type_id ?? 0);

    if ($clientId <= 0 || !in_array($clientId, $this->getAllowedClientIds($user), true)) {
        return false;
    }

    if ($roleKey === 'admin_cliente') {
        if ($groupId <= 0) {
            return false;
        }

        return $user->groups()
            ->where('groups.id', $groupId)
            ->where('groups.client_id', $clientId)
            ->exists();
    }

    if ($roleKey === 'observador_cliente') {
        if ($groupId <= 0) {
            return false;
        }

        return $user->groups()
            ->where('groups.id', $groupId)
            ->where('groups.client_id', $clientId)
            ->exists();
    }

    return $this->canAccessElementType($user, $clientId, $elementTypeId);
}


}