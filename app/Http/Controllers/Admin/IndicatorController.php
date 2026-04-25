<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Element;
use App\Models\Group;
use App\Models\ReportDetail;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class IndicatorController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless($this->canAccessIndicators($roleKey), 403, 'Rol no autorizado para consultar indicadores.');

        $clients = $this->getScopedClients($user);
        $groups = $this->getScopedGroups($user, $clients->pluck('id')->all());

        return view('admin.indicators.index', [
            'clients' => $clients,
            'groups' => $groups,
            'roleKey' => $roleKey,
            'isReadOnly' => in_array($roleKey, ['observador', 'observador_cliente'], true),
            'defaultDateFrom' => now()->startOfYear()->toDateString(),
            'defaultDateTo' => now()->toDateString(),
            'dataRoute' => route('admin.indicators.data'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $user = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless($this->canAccessIndicators($roleKey), 403, 'Rol no autorizado para consultar indicadores.');

        $validated = $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $dateFrom = Carbon::parse($validated['date_from'] ?? now()->startOfYear()->toDateString())->startOfDay();
        $dateTo = Carbon::parse($validated['date_to'] ?? now()->toDateString())->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha inicial no puede ser mayor que la fecha final.',
            ], 422);
        }

        $clients = $this->getScopedClients($user);
        $clientIds = $clients->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (!empty($validated['client_id'])) {
            $requestedClientId = (int) $validated['client_id'];

            if (!in_array($requestedClientId, $clientIds, true)) {
                abort(403, 'No tienes acceso a este cliente.');
            }

            $clientIds = [$requestedClientId];
        }

        $groups = $this->getScopedGroups($user, $clientIds);

        if (!empty($validated['group_id'])) {
            $requestedGroupId = (int) $validated['group_id'];

            if (!$groups->pluck('id')->map(fn ($id) => (int) $id)->contains($requestedGroupId)) {
                abort(403, 'No tienes acceso a esta agrupación.');
            }

            $groups = $groups->where('id', $requestedGroupId)->values();
        }

        $groupIds = $groups->pluck('id')->map(fn ($id) => (int) $id)->all();

        $elements = Element::query()
            ->with(['area:id,name', 'elementType:id,name', 'group:id,name,client_id'])
            ->where('status', true)
            ->whereIn('group_id', $groupIds)
            ->get(['id', 'area_id', 'element_type_id', 'group_id', 'name', 'code', 'status']);

        $elementIds = $elements->pluck('id')->map(fn ($id) => (int) $id)->all();

        $weekPairs = $this->buildWeekPairs($dateFrom, $dateTo);

        $details = ReportDetail::query()
            ->with([
                'element:id,name,group_id,element_type_id,area_id',
                'component:id,name',
                'diagnostic:id,name',
                'condition:id,name',
                'executionStatus:id,name',
            ])
            ->where('status', true)
            ->whereIn('element_id', $elementIds)
            ->where(function ($query) use ($weekPairs) {
                foreach ($weekPairs as $pair) {
                    $query->orWhere(function ($subQuery) use ($pair) {
                        $subQuery
                            ->where('year', $pair['year'])
                            ->where('week', $pair['week']);
                    });
                }
            })
            ->get();

        $inspectedElementIds = $details->pluck('element_id')->unique()->values();

        $totalElements = $elements->count();
        $inspectedElements = $inspectedElementIds->count();
        $notInspectedElements = max($totalElements - $inspectedElements, 0);

        $coverage = $totalElements > 0
            ? round(($inspectedElements / $totalElements) * 100, 1)
            : 0;

        $preventiveReports = $this->countPreventiveReports($details);

        $conditionDistribution = $details
            ->groupBy(fn ($detail) => $detail->condition?->name ?: 'Sin condición')
            ->map(fn ($items, $name) => [
                'label' => $name,
                'total' => $items->count(),
            ])
            ->sortByDesc('total')
            ->values();

        $reportsByWeek = $details
            ->groupBy(fn ($detail) => "{$detail->year}-" . str_pad((string) $detail->week, 2, '0', STR_PAD_LEFT))
            ->map(fn ($items, $key) => [
                'label' => 'S' . substr($key, -2) . ' / ' . substr($key, 0, 4),
                'total' => $this->countPreventiveReports($items),
            ])
            ->sortBy('label')
            ->values();

        $topElements = $details
            ->groupBy('element_id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'name' => $first?->element?->name ?: 'Sin activo',
                    'total' => $items->count(),
                    'critical' => $this->countCriticalLike($items),
                ];
            })
            ->sortByDesc('total')
            ->take(10)
            ->values();

        $topComponents = $details
            ->groupBy('component_id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'name' => $first?->component?->name ?: 'Sin componente',
                    'total' => $items->count(),
                ];
            })
            ->sortByDesc('total')
            ->take(10)
            ->values();

        $topDiagnostics = $details
            ->groupBy('diagnostic_id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'name' => $first?->diagnostic?->name ?: 'Sin diagnóstico',
                    'total' => $items->count(),
                ];
            })
            ->sortByDesc('total')
            ->take(10)
            ->values();

        $pendingExecution = $details
            ->filter(function ($detail) {
                $statusName = mb_strtolower((string) ($detail->executionStatus?->name ?? ''));

                if ($detail->execution_date) {
                    return false;
                }

                if ($statusName === '') {
                    return true;
                }

                return !str_contains($statusName, 'ejecut');
            })
            ->count();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_elements' => $totalElements,
                'inspected_elements' => $inspectedElements,
                'not_inspected_elements' => $notInspectedElements,
                'coverage' => $coverage,
                'preventive_reports' => $preventiveReports,
                'evaluated_components' => $details->count(),
                'diagnostics' => $details->whereNotNull('diagnostic_id')->count(),
                'pending_execution' => $pendingExecution,
            ],
            'charts' => [
                'condition_distribution' => $conditionDistribution,
                'reports_by_week' => $reportsByWeek,
            ],
            'tables' => [
                'top_elements' => $topElements,
                'top_components' => $topComponents,
                'top_diagnostics' => $topDiagnostics,
            ],
            'meta' => [
                'client_ids' => $clientIds,
                'group_ids' => $groupIds,
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
        ]);
    }

    private function canAccessIndicators(?string $roleKey): bool
    {
        return in_array($roleKey, [
            'superadmin',
            'admin_global',
            'admin',
            'admin_cliente',
            'observador',
            'observador_cliente',
        ], true);
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

    private function getScopedGroups($user, array $clientIds): Collection
    {
        $roleKey = $user->role?->key;

        $query = Group::query()
            ->with(['client:id,name'])
            ->where('status', true)
            ->whereIn('client_id', $clientIds)
            ->orderBy('client_id')
            ->orderBy('name');

        if (in_array($roleKey, ['admin_cliente', 'observador_cliente'], true)) {
            $allowedGroupIds = $user->groups()
                ->pluck('groups.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $query->whereIn('id', $allowedGroupIds);
        }

        return $query->get(['id', 'client_id', 'name', 'description', 'status']);
    }

    private function buildWeekPairs(Carbon $dateFrom, Carbon $dateTo): array
    {
        $period = CarbonPeriod::create($dateFrom->copy()->startOfWeek(), '1 week', $dateTo->copy()->endOfWeek());

        $weeks = [];

        foreach ($period as $date) {
            $weeks[] = [
                'year' => (int) $date->isoWeekYear(),
                'week' => (int) $date->isoWeek(),
            ];
        }

        return collect($weeks)
            ->unique(fn ($item) => $item['year'] . '-' . $item['week'])
            ->values()
            ->all();
    }

    private function countPreventiveReports(Collection $details): int
    {
        return $details
            ->map(function ($detail) {
                if ($detail->report_id) {
                    return 'report-' . $detail->report_id;
                }

                return 'fallback-' . $detail->element_id . '-' . $detail->year . '-' . $detail->week;
            })
            ->unique()
            ->count();
    }

    private function countCriticalLike(Collection $details): int
    {
        return $details->filter(function ($detail) {
            $conditionName = mb_strtolower((string) ($detail->condition?->name ?? ''));

            return str_contains($conditionName, 'crít')
                || str_contains($conditionName, 'crit')
                || str_contains($conditionName, 'malo')
                || str_contains($conditionName, 'grave');
        })->count();
    }
}