<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Parada;
use App\Models\ReportDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PendientesController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user    = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless(
            in_array($roleKey, ['superadmin', 'admin_global', 'admin'], true),
            403
        );

        $clients  = $this->getScopedClients($user, $roleKey);
        $clientId = $request->input('client_id')
            ?? ($clients->count() === 1 ? $clients->first()->id : null);

        $paradas = collect();
        $selectedParada = null;

        if ($clientId) {
            abort_unless($this->clientAllowed($user, $roleKey, (int) $clientId), 403);

            $paradas = Parada::query()
                ->withCount('areas')
                ->where('client_id', $clientId)
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get(['id', 'name', 'start_date', 'end_date']);

            $paradaId = $request->input('parada_id');

            if ($paradaId) {
                $selectedParada = $paradas->firstWhere('id', $paradaId);
            } elseif ($paradas->count() === 1) {
                $selectedParada = $paradas->first();
            }
        }

        $tree             = $selectedParada ? $this->buildTree($selectedParada) : null;
        $paradasProgress  = $paradas->isNotEmpty() ? $this->computeParadasProgress($paradas) : [];

        $viewData = compact('clients', 'clientId', 'paradas', 'selectedParada', 'tree', 'roleKey', 'paradasProgress');

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('admin.pendientes.partials.content', $viewData)->render(),
            ]);
        }

        return view('admin.pendientes.index', compact(
            'clients',
            'clientId',
            'paradas',
            'selectedParada',
            'tree',
            'roleKey',
            'paradasProgress',
        ));
    }

    /**
     * Calcula el progreso (elementos inspeccionados / total) para cada parada
     * usando el criterio: ¿tiene al menos un ReportDetail en el período de la parada?
     */
    private function computeParadasProgress(Collection $paradas): array
    {
        $progress = [];

        // Query 1 batch: total de elementos activos por parada (via parada_areas)
        $totals = DB::table('parada_areas')
            ->join('elements', 'elements.area_id', '=', 'parada_areas.area_id')
            ->whereIn('parada_areas.parada_id', $paradas->pluck('id')->all())
            ->where('elements.status', true)
            ->groupBy('parada_areas.parada_id')
            ->selectRaw('parada_areas.parada_id, COUNT(DISTINCT elements.id) as total')
            ->pluck('total', 'parada_id');

        // Query por parada: elementos con al menos 1 reporte en el período
        foreach ($paradas as $parada) {
            $total = (int) ($totals[$parada->id] ?? 0);

            $done = $total > 0
                ? DB::table('elements')
                    ->join('parada_areas', 'parada_areas.area_id', '=', 'elements.area_id')
                    ->where('parada_areas.parada_id', $parada->id)
                    ->where('elements.status', true)
                    ->whereExists(fn ($q) => $q
                        ->from('report_details')
                        ->whereColumn('report_details.element_id', 'elements.id')
                        ->whereBetween('report_details.created_at', [
                            $parada->start_date->copy()->startOfDay(),
                            $parada->end_date->copy()->endOfDay(),
                        ])
                        ->where('report_details.status', true)
                    )
                    ->distinct()
                    ->count('elements.id')
                : 0;

            $progress[$parada->id] = [
                'total' => $total,
                'done'  => $done,
                'pct'   => $total > 0 ? (int) round(($done / $total) * 100) : 0,
            ];
        }

        return $progress;
    }

    private function buildTree(Parada $parada): array
    {
        $parada->load('areas.elements.elementType');

        $start = $parada->start_date->startOfDay();
        $end   = $parada->end_date->endOfDay();

        // Obtener IDs de elementos de las áreas afectadas
        $elementIds = $parada->areas
            ->flatMap(fn ($area) => $area->elements->where('status', true)->pluck('id'))
            ->unique()
            ->values()
            ->all();

        if (empty($elementIds)) {
            return [];
        }

        // Cargar componentes y diagnósticos de esos elementos
        $elements = \App\Models\Element::query()
            ->with([
                'area:id,name,code',
                'elementType:id,name',
                'components:id,name',
                'components.diagnostics:id,name',
            ])
            ->whereIn('id', $elementIds)
            ->where('status', true)
            ->get();

        // Cargar reportes del período de la parada para esos elementos
        $reports = ReportDetail::query()
            ->whereIn('element_id', $elementIds)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', true)
            ->get(['id', 'element_id', 'component_id', 'diagnostic_id']);

        // Indexar reportes: "element_id-component_id-diagnostic_id" → report_detail_id
        $reportedKeys = $reports
            ->keyBy(fn ($r) => "{$r->element_id}-{$r->component_id}-{$r->diagnostic_id}")
            ->map(fn ($r) => $r->id)
            ->all();

        // Construir árbol por área
        $tree = [];

        foreach ($parada->areas->sortBy('name') as $area) {
            $areaElements = $elements->where('area_id', $area->id)->sortBy('name');

            $elementData = [];
            foreach ($areaElements as $element) {
                $componentData = [];
                $allElementDone = true;

                foreach ($element->components->sortBy('name') as $component) {
                    $diagnosticData = [];
                    $allComponentDone = true;

                    foreach ($component->diagnostics->sortBy('name') as $diagnostic) {
                        $key            = "{$element->id}-{$component->id}-{$diagnostic->id}";
                        $reportDetailId = $reportedKeys[$key] ?? null;
                        $revisado       = $reportDetailId !== null;

                        if (! $revisado) {
                            $allComponentDone = false;
                        }

                        $diagnosticData[] = [
                            'id'               => $diagnostic->id,
                            'name'             => $diagnostic->name,
                            'revisado'         => $revisado,
                            'report_detail_id' => $reportDetailId,
                        ];
                    }

                    if (! $allComponentDone) {
                        $allElementDone = false;
                    }

                    $componentData[] = [
                        'id'          => $component->id,
                        'name'        => $component->name,
                        'revisado'    => $allComponentDone,
                        'diagnostics' => $diagnosticData,
                    ];
                }

                $elementData[] = [
                    'id'         => $element->id,
                    'name'       => $element->name,
                    'code'       => $element->code,
                    'type'       => $element->elementType?->name,
                    'revisado'   => $allElementDone,
                    'components' => $componentData,
                    'total'      => count($componentData),
                    'done'       => collect($componentData)->where('revisado', true)->count(),
                ];
            }

            $totalElements = count($elementData);
            $doneElements  = collect($elementData)->where('revisado', true)->count();

            $tree[] = [
                'id'       => $area->id,
                'name'     => $area->name,
                'code'     => $area->code,
                'revisado' => $totalElements > 0 && $doneElements === $totalElements,
                'total'    => $totalElements,
                'done'     => $doneElements,
                'elements' => $elementData,
            ];
        }

        return $tree;
    }

    private function getScopedClients($user, string $roleKey)
    {
        if (in_array($roleKey, ['superadmin', 'admin_global', 'observador'], true)) {
            return Client::query()
                ->withCount('paradas')
                ->where('status', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return $user->clients()
            ->withCount('paradas')
            ->where('clients.status', true)
            ->orderBy('clients.name')
            ->get(['clients.id', 'clients.name']);
    }

    private function clientAllowed($user, string $roleKey, int $clientId): bool
    {
        if (in_array($roleKey, ['superadmin', 'admin_global', 'observador'], true)) {
            return true;
        }

        return $user->clients()->where('clients.id', $clientId)->exists();
    }
}
