<?php

namespace App\Http\Controllers\Inspector;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Component;
use App\Models\Condition;
use App\Models\Element;
use App\Models\ExecutionStatus;
use App\Models\ReportDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InspectorReportController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $inspectorClients = $user->clients()
            ->where('clients.status', true)
            ->orderBy('clients.name')
            ->get();

        $clientIds = $inspectorClients->pluck('id');

        $assignedClient = $inspectorClients->count() === 1
            ? $inspectorClients->first()
            : null;

        $areas = Area::with('client')
            ->whereIn('client_id', $clientIds)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $conditions = Condition::whereIn('client_id', $clientIds)
            ->where('status', true)
            ->orderBy('code')
            ->get();

        $executionStatuses = ExecutionStatus::where('status', true)
            ->orderBy('name')
            ->get();

        $recentReports = ReportDetail::with([
            'element.area.client',
            'component',
            'diagnostic',
            'condition',
            'executionStatus',
        ])
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(168))
            ->orderByDesc('created_at')
            ->get();

        return view('inspector.reports.index', compact(
            'inspectorClients',
            'areas',
            'conditions',
            'assignedClient',
            'executionStatuses',
            'recentReports'
        ));
    }

    public function getElementsByArea(Area $area): JsonResponse
    {
        $user = Auth::user();

        abort_unless(
            $user->clients()->where('clients.id', $area->client_id)->exists(),
            403
        );

        $elements = $area->elements()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'element_type_id']);

        return response()->json($elements);
    }

    public function getComponentsByElement(Element $element): JsonResponse
    {
        $user = Auth::user();

        abort_unless(
            $user->clients()->where('clients.id', $element->area->client_id)->exists(),
            403
        );

        $components = $element->components()
            ->where('components.status', true)
            ->orderBy('components.name')
            ->get([
                'components.id',
                'components.name',
                'components.code',
                'components.element_type_id',
            ]);

        return response()->json($components);
    }

    public function getDiagnosticsByComponent(Component $component): JsonResponse
    {
        $user = Auth::user();

        abort_unless(
            $user->clients()->where('clients.id', $component->client_id)->exists(),
            403
        );

        $diagnostics = $component->diagnostics()
            ->where('diagnostics.status', true)
            ->orderBy('diagnostics.name')
            ->get([
                'diagnostics.id',
                'diagnostics.name',
                'diagnostics.code',
            ]);

        return response()->json($diagnostics);
    }

    public function getPendingDiagnostics(Request $request, Element $element): JsonResponse
    {
        $user = Auth::user();

        abort_unless(
            $user->clients()->where('clients.id', $element->area->client_id)->exists(),
            403
        );

        $week = now()->weekOfYear;
        $year = now()->year;

        $expected = $element->components()
            ->with(['diagnostics' => function ($query) {
                $query->where('diagnostics.status', true)
                    ->orderBy('diagnostics.name');
            }])
            ->where('components.status', true)
            ->orderBy('components.name')
            ->get();

        $doneKeys = ReportDetail::where('element_id', $element->id)
            ->where('week', $week)
            ->where('year', $year)
            ->get(['component_id', 'diagnostic_id'])
            ->map(fn ($row) => $row->component_id . '-' . $row->diagnostic_id)
            ->toArray();

        $pending = [];

        foreach ($expected as $component) {
            foreach ($component->diagnostics as $diagnostic) {
                $key = $component->id . '-' . $diagnostic->id;

                if (!in_array($key, $doneKeys)) {
                    $pending[] = [
                        'component_id' => $component->id,
                        'component_name' => $component->name,
                        'component_code' => $component->code,
                        'diagnostic_id' => $diagnostic->id,
                        'diagnostic_name' => $diagnostic->name,
                        'diagnostic_code' => $diagnostic->code,
                    ];
                }
            }
        }

        return response()->json([
            'total_pending' => count($pending),
            'items' => $pending,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'area_id' => ['required', 'exists:areas,id'],
            'element_id' => ['required', 'exists:elements,id'],
            'component_id' => ['required', 'exists:components,id'],
            'diagnostic_id' => ['required', 'exists:diagnostics,id'],
            'condition_id' => ['required', 'exists:conditions,id'],
            'recommendation' => ['nullable', 'string'],
        ]);

        $user = Auth::user();

        $area = Area::findOrFail($validated['area_id']);
        $element = Element::findOrFail($validated['element_id']);
        $component = Component::findOrFail($validated['component_id']);
        $condition = Condition::findOrFail($validated['condition_id']);

        $now = now();
        $currentWeek = $now->weekOfYear;
        $currentYear = $now->year;

        abort_unless(
            $user->clients()->where('clients.id', $area->client_id)->exists(),
            403,
            'No tienes acceso a esta área.'
        );

        if ($element->area_id !== $area->id) {
            return back()
                ->withErrors(['element_id' => 'El elemento no pertenece al área seleccionada.'])
                ->withInput();
        }

        if ($component->client_id !== $area->client_id) {
            return back()
                ->withErrors(['component_id' => 'El componente no pertenece al cliente del área seleccionada.'])
                ->withInput();
        }

        if ($condition->client_id !== $area->client_id) {
            return back()
                ->withErrors(['condition_id' => 'La condición no pertenece al cliente del área seleccionada.'])
                ->withInput();
        }

        if (!$element->components()->where('components.id', $component->id)->exists()) {
            return back()
                ->withErrors(['component_id' => 'El componente no pertenece al elemento seleccionado.'])
                ->withInput();
        }

        if (!$component->diagnostics()->where('diagnostics.id', $validated['diagnostic_id'])->exists()) {
            return back()
                ->withErrors(['diagnostic_id' => 'El diagnóstico no pertenece al componente seleccionado.'])
                ->withInput();
        }

        $exists = ReportDetail::where('element_id', $element->id)
            ->where('component_id', $component->id)
            ->where('diagnostic_id', $validated['diagnostic_id'])
            ->where('week', $currentWeek)
            ->where('year', $currentYear)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors([
                    'diagnostic_id' => 'Ese diagnóstico ya fue diligenciado para este elemento, componente, semana y año.',
                ])
                ->withInput();
        }

        ReportDetail::create([
            'report_id' => null,
            'user_id' => $user->id,
            'element_id' => $element->id,
            'component_id' => $component->id,
            'diagnostic_id' => $validated['diagnostic_id'],
            'year' => $currentYear,
            'week' => $currentWeek,
            'condition_id' => $validated['condition_id'],
            'observation' => null,
            'recommendation' => $validated['recommendation'] ?? null,
            'orden' => null,
            'aviso' => null,
            'execution_status_id' => null,
            'execution_date' => now()->toDateString(),
        ]);

        return redirect()
            ->route('inspector.reports.index')
            ->with('success', 'Reporte registrado correctamente.');
    }
}