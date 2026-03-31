<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ElementType;
use App\Models\ExecutionStatus;
use App\Models\ReportDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminPreventiveReportController extends Controller
{
    public function show(Client $client, ElementType $elementType, Request $request): View
    {
        $user = Auth::user();

        $allowedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->toArray();

        abort_unless(
            in_array($client->id, $allowedClientIds),
            403,
            'No autorizado para ver reportes de este cliente.'
        );

        if ((int) $elementType->client_id !== (int) $client->id) {
            abort(404, 'El tipo de activo no pertenece al cliente indicado.');
        }

        $currentYear = now()->year;

        $query = ReportDetail::with([
            'user',
            'element.area.client',
            'element.elementType',
            'diagnostic',
            'condition',
            'executionStatus',
        ])
            ->where('year', $currentYear)
            ->whereHas('element', function ($q) use ($client, $elementType) {
                $q->where('element_type_id', $elementType->id)
                    ->whereHas('area', function ($areaQuery) use ($client) {
                        $areaQuery->where('client_id', $client->id);
                    });
            });

        if ($request->filled('element_name')) {
            $value = trim($request->element_name);
            $query->whereHas('element', function ($q) use ($value) {
                $q->where('name', 'ilike', '%' . $value . '%');
            });
        }

        if ($request->filled('diagnostic_name')) {
            $value = trim($request->diagnostic_name);
            $query->whereHas('diagnostic', function ($q) use ($value) {
                $q->where('name', 'ilike', '%' . $value . '%');
            });
        }

        if ($request->filled('recommendation')) {
            $value = trim($request->recommendation);
            $query->where('recommendation', 'ilike', '%' . $value . '%');
        }

        if ($request->filled('orden')) {
            $value = trim($request->orden);
            $query->where('orden', 'ilike', '%' . $value . '%');
        }

        if ($request->filled('aviso')) {
            $value = trim($request->aviso);
            $query->where('aviso', 'ilike', '%' . $value . '%');
        }

        if ($request->filled('responsable')) {
            $value = trim($request->responsable);
            $query->whereHas('user', function ($q) use ($value) {
                $q->where('name', 'ilike', '%' . $value . '%');
            });
        }

        if ($request->filled('report_date')) {
            $query->whereDate('created_at', $request->report_date);
        }

        if ($request->filled('execution_date')) {
            $query->whereDate('execution_date', $request->execution_date);
        }

        if ($request->filled('condition_name')) {
            $value = trim($request->condition_name);
            $query->whereHas('condition', function ($q) use ($value) {
                $q->where('name', 'ilike', '%' . $value . '%');
            });
        }

        if ($request->filled('execution_status')) {
            if ($request->execution_status === 'realizado') {
                $query->whereHas('executionStatus', function ($q) {
                    $q->where('name', 'Realizado');
                });
            }

            if ($request->execution_status === 'pendiente') {
                $query->where(function ($q) {
                    $q->whereNull('execution_status_id')
                        ->orWhereHas('executionStatus', function ($statusQ) {
                            $statusQ->where('name', '!=', 'Realizado');
                        });
                });
            }
        }

        if ($request->filled('week')) {
            $query->where('week', (int) $request->week);
        }

        $reports = $query
            ->orderByDesc('week')
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.reports.preventive.show', compact(
            'client',
            'elementType',
            'reports',
            'currentYear'
        ));
    }

    public function toggleExecution(Request $request, ReportDetail $reportDetail): JsonResponse
    {
        $user = Auth::user();

        $allowedClientIds = $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->toArray();

        $reportClientId = $reportDetail->element?->area?->client_id;

        abort_unless(
            in_array($reportClientId, $allowedClientIds),
            403,
            'No autorizado para modificar este reporte.'
        );

        $validated = $request->validate([
            'is_checked' => ['required', 'boolean'],
        ]);

        $realizadoStatus = ExecutionStatus::where('name', 'Realizado')->first();

        if ($validated['is_checked']) {
            if (!$realizadoStatus) {
                return response()->json([
                    'message' => 'No existe el estado de ejecución "Realizado".'
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

        $reportDetail->load('executionStatus');

        return response()->json([
            'success' => true,
            'execution_status' => $reportDetail->executionStatus?->name,
            'execution_date' => $reportDetail->execution_date,
        ]);
    }
}