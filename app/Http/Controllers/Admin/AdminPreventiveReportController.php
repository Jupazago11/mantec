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

        $reports = $query
            ->orderByDesc('week')
            ->orderByDesc('created_at')
            ->paginate(30)
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

        $reports = $query
            ->orderByDesc('week')
            ->orderByDesc('created_at')
            ->paginate(30)
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
            'totalReportsFiltered'
        ))->with([
            'currentYear' => $year,
            'selectedYear' => $year,
            'isReadOnly' => $this->isReadOnlyRole($user),
            'roleKey' => $user->role?->key,
        ]);
    }

    public function toggleExecution(Request $request, ReportDetail $reportDetail): JsonResponse
    {
        $user = Auth::user();

        if ($this->isReadOnlyRole($user)) {
            return response()->json([
                'message' => 'No autorizado para modificar este reporte.',
            ], 403);
        }

        $allowedClientIds = $this->getAllowedClientIds($user);

        $reportDetail->loadMissing([
            'element.area',
            'element.elementType',
            'executionStatus',
        ]);

        $reportClientId = (int) ($reportDetail->element?->area?->client_id ?? 0);
        $reportElementTypeId = (int) ($reportDetail->element?->element_type_id ?? 0);
        $reportAreaId = (int) ($reportDetail->element?->area_id ?? 0);

        abort_unless(
            $reportClientId && in_array($reportClientId, $allowedClientIds, true),
            403,
            'No autorizado para modificar este reporte.'
        );

        abort_unless(
            $this->canAccessElementType($user, $reportClientId, $reportElementTypeId),
            403,
            'No autorizado para modificar este reporte.'
        );

        if ($this->mustRestrictByAreas($user)) {
            abort_unless(
                $this->canAccessArea($user, $reportClientId, $reportElementTypeId, $reportAreaId),
                403,
                'No autorizado para modificar este reporte.'
            );
        }

        $validated = $request->validate([
            'is_checked' => ['required', 'boolean'],
        ]);

        $realizadoStatus = ExecutionStatus::where('name', 'REALIZADO')->first();

        if ($validated['is_checked']) {
            if (!$realizadoStatus) {
                return response()->json([
                    'message' => 'No existe el estado de ejecución "REALIZADO".',
                ], 422);
            }

            $reportDetail->update([
                'execution_status_id' => $realizadoStatus->id,
                'execution_date' => now()->toDateString(),
            ]);
        } else {
            $reportDetail->update([
                'execution_status_id' => null,
                'execution_date' => null,
            ]);
        }

        $reportDetail->refresh();
        $reportDetail->load('executionStatus');

        return response()->json([
            'success' => true,
            'execution_status' => $reportDetail->executionStatus?->name,
            'execution_date' => $reportDetail->execution_date,
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


    public function evidence($id)
    {
        $user = Auth::user();

        $report = ReportDetail::with([
            'element.area',
            'element.elementType',
            'component',
            'diagnostic',
            'condition',
            'files',
        ])->findOrFail($id);

        $clientId = (int) ($report->element?->area?->client_id ?? 0);
        $elementTypeId = (int) ($report->element?->element_type_id ?? 0);
        $areaId = (int) ($report->element?->area_id ?? 0);

        abort_unless(
            in_array($clientId, $this->getAllowedClientIds($user), true),
            403,
            'No autorizado para ver la evidencia de este reporte.'
        );

        abort_unless(
            $this->canAccessElementType($user, $clientId, $elementTypeId),
            403,
            'No autorizado para ver la evidencia de este reporte.'
        );

        if ($this->mustRestrictByAreas($user)) {
            abort_unless(
                $this->canAccessArea($user, $clientId, $elementTypeId, $areaId),
                403,
                'No autorizado para ver la evidencia de este reporte.'
            );
        }

        return view('admin.preventive-reports.evidence', compact('report'));
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