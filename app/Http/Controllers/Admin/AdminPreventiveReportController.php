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
        if (!empty($responsableNames)) {
            $query->where(function ($outerQuery) use ($responsableNames) {
                foreach ($responsableNames as $responsableName) {
                    $outerQuery->orWhere(function ($innerQuery) use ($responsableName) {
                        $innerQuery->whereExists(function ($subQuery) use ($responsableName) {
                            $subQuery->selectRaw('1')
                                ->from('users')
                                ->join('roles', 'roles.id', '=', 'users.role_id')
                                ->join('client_user', 'client_user.user_id', '=', 'users.id')
                                ->join('user_client_element_type', function ($join) {
                                    $join->on('user_client_element_type.user_id', '=', 'users.id')
                                        ->on('user_client_element_type.client_id', '=', 'client_user.client_id');
                                })
                                ->join('user_client_element_type_areas', function ($join) {
                                    $join->on('user_client_element_type_areas.user_id', '=', 'users.id')
                                        ->on('user_client_element_type_areas.client_id', '=', 'user_client_element_type.client_id')
                                        ->on('user_client_element_type_areas.element_type_id', '=', 'user_client_element_type.element_type_id');
                                })
                                ->join('elements', 'elements.id', '=', 'report_details.element_id')
                                ->join('areas', 'areas.id', '=', 'elements.area_id')
                                ->whereColumn('client_user.client_id', 'areas.client_id')
                                ->whereColumn('user_client_element_type.element_type_id', 'elements.element_type_id')
                                ->whereColumn('user_client_element_type_areas.area_id', 'areas.id')
                                ->where('roles.key', 'admin_cliente')
                                ->where('users.status', true)
                                ->where('users.name', $responsableName);
                        });
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
        $elementTypeId = $report->element?->element_type_id;
        $areaId = $report->element?->area_id;

        if (!$clientId || !$elementTypeId || !$areaId) {
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
            ->whereExists(function ($query) use ($clientId, $elementTypeId) {
                $query->selectRaw('1')
                    ->from('user_client_element_type')
                    ->whereColumn('user_client_element_type.user_id', 'users.id')
                    ->where('user_client_element_type.client_id', $clientId)
                    ->where('user_client_element_type.element_type_id', $elementTypeId);
            })
            ->whereExists(function ($query) use ($clientId, $elementTypeId, $areaId) {
                $query->selectRaw('1')
                    ->from('user_client_element_type_areas')
                    ->whereColumn('user_client_element_type_areas.user_id', 'users.id')
                    ->where('user_client_element_type_areas.client_id', $clientId)
                    ->where('user_client_element_type_areas.element_type_id', $elementTypeId)
                    ->where('user_client_element_type_areas.area_id', $areaId);
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
            ->whereHas('element', function ($elementQuery) use ($group) {
                $elementQuery->where('group_id', $group->id);
            })
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        if (in_array($roleKey, ['admin_cliente', 'observador_cliente'], true)) {
            $allowedAreaIds = $user->areas()->pluck('areas.id')->map(fn ($id) => (int) $id)->all();

            if (!empty($allowedAreaIds)) {
                $baseQuery->whereHas('element', function ($elementQuery) use ($allowedAreaIds) {
                    $elementQuery->whereIn('area_id', $allowedAreaIds);
                });
            }
        }

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
                $report->responsable_name = '—';

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

            'responsable_names' => collect(),

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
        ]);
    }

    public function evidence(\App\Models\ReportDetail $reportDetail)
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
            'No tienes permisos para ver evidencia.'
        );

        $file = \App\Models\ReportDetailFile::query()
            ->where('report_detail_id', $reportDetail->id)
            ->orderBy('id')
            ->first();

        if (!$file) {
            abort(404, 'Este detalle de reporte no tiene evidencia asociada.');
        }

        $disk = \Illuminate\Support\Facades\Storage::disk($file->disk);

        if (!$disk->exists($file->path)) {
            abort(404, 'El archivo no existe en el almacenamiento.');
        }

        $safeName = $file->original_name ?: $file->stored_name;

        try {
            $temporaryUrl = $disk->temporaryUrl(
                $file->path,
                now()->addMinutes(10),
                [
                    'ResponseContentDisposition' => 'inline; filename="' . addslashes($safeName) . '"',
                ]
            );

            return redirect()->away($temporaryUrl);
        } catch (\Throwable $e) {
            $url = $disk->url($file->path);

            return redirect()->away($url);
        }
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
        return in_array($user->role?->key, ['admin_cliente', 'observador_cliente'], true);
    }

    private function mustRestrictByAreas($user): bool
    {
        return $user->role?->key === 'admin_cliente';
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
}