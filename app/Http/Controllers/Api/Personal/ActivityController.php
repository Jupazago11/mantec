<?php

namespace App\Http\Controllers\Api\Personal;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityEmployeeHour;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

// Seccion 14.24: API real para la app Android del supervisor — mismo
// modelo de datos y misma logica que
// App\Http\Controllers\Personal\SupervisorViewController (pantalla web
// "Ver como", seccion 14.18/14.20), pero autenticado por Sanctum en vez
// de superadmin+empleado-por-URL: el empleado siempre es
// $request->user() (garantizado Employee por el middleware
// EnsureTokenableIsEmployee), nunca un parametro de ruta.
class ActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();

        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))->toDateString()
            : today()->toDateString();

        // Mismo criterio que SupervisorViewController::index() (seccion
        // 14.20): ademas de hoy, se incluyen las de AYER con turno
        // Nocturno (cruzan medianoche), siempre visibles.
        $ayer = Carbon::parse($date)->subDay()->toDateString();

        $actividades = Activity::with(['company', 'personas', 'employeeHours', 'evidences'])
            ->where('responsible_employee_id', $employee->id)
            ->where(function ($query) use ($date, $ayer) {
                $query->where('date', $date)
                    ->orWhere(function ($nocturnaAyer) use ($ayer) {
                        $nocturnaAyer->where('date', $ayer)->where('shift', 'Nocturno');
                    });
            })
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'date' => $date,
            'actividades' => $actividades->map(fn ($a) => $this->serialize($a))->values(),
        ]);
    }

    public function store(Request $request, Activity $activity): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();

        // La actividad tiene que ser realmente de este empleado como
        // responsable — nunca confiar solo en el ID de la URL (AGENTS.md
        // seccion 6). Ya no hace falta comparar contra un empleado de la
        // URL: el empleado siempre es el del token.
        abort_unless((int) $activity->responsible_employee_id === $employee->id, 404);
        abort_if($activity->isClosed(), 403);

        $validated = $this->validated($request);

        if ($validated['all_worked_scheduled_hours']) {
            $activity->employeeHours()->delete();
            $reportedHours = $activity->estimated_hours;
        } else {
            $reportedHours = 0.0;
            foreach ($validated['personas'] as $persona) {
                $workedHours = round((float) $persona['worked_hours'], 2);
                $reportedHours += $workedHours;

                ActivityEmployeeHour::updateOrCreate(
                    ['activity_id' => $activity->id, 'employee_id' => $persona['employee_id']],
                    [
                        'worked' => $workedHours > 0,
                        'worked_hours' => $workedHours,
                    ]
                );
            }
        }

        $activity->update([
            'comments' => $validated['comments'] ?? null,
            'all_worked_scheduled_hours' => $validated['all_worked_scheduled_hours'],
            'reported_hours' => round($reportedHours, 2),
            'hours_registered_by_employee_id' => $employee->id,
            'hours_registered_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registro guardado correctamente.',
            'activity' => $this->serialize($activity->fresh(['personas', 'employeeHours', 'evidences'])),
        ]);
    }

    private function validated(Request $request): array
    {
        return Validator::make($request->all(), [
            'comments' => ['nullable', 'string', 'max:2000'],
            'all_worked_scheduled_hours' => ['required', 'boolean'],
            'personas' => ['array'],
            'personas.*.employee_id' => ['required', 'integer'],
            'personas.*.worked_hours' => ['required', 'numeric', 'min:0'],
        ])->after(function ($validator) use ($request) {
            if ($request->boolean('all_worked_scheduled_hours')) {
                return;
            }

            if (collect($request->input('personas', []))->isEmpty()) {
                $validator->errors()->add('personas', 'Indica el detalle por persona si no todos trabajaron las horas programadas.');
            }
        })->validate();
    }

    private function serialize(Activity $activity): array
    {
        $horasPorEmpleado = $activity->employeeHours->keyBy('employee_id');

        return [
            'id' => $activity->id,
            'date' => $activity->date->toDateString(),
            'company_name' => $activity->company->name,
            'team' => $activity->team,
            'process' => $activity->process,
            'description' => $activity->description,
            'activity_type' => $activity->activity_type,
            'shift' => $activity->shift,
            'estimated_hours' => $activity->estimated_hours !== null ? (float) $activity->estimated_hours : null,
            'closed' => $activity->isClosed(),
            'comments' => $activity->comments,
            'all_worked_scheduled_hours' => $activity->all_worked_scheduled_hours,
            'reported_hours' => $activity->reported_hours !== null ? (float) $activity->reported_hours : null,
            'registrado' => $activity->hours_registered_at !== null,
            'personas' => $activity->personas->map(function ($p) use ($horasPorEmpleado) {
                $h = $horasPorEmpleado->get($p->id);

                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'nickname' => $p->nickname,
                    'worked_hours' => $h?->worked_hours !== null ? (float) $h->worked_hours : null,
                ];
            })->values(),
            'evidencias' => $activity->evidences->map(fn ($e) => [
                'id' => $e->id,
                'original_name' => $e->original_name,
                'file_type' => $e->file_type,
                'show_url' => route('api.personal.actividades.evidencias.show', [
                    'activity' => $activity->id,
                    'evidence' => $e->id,
                ]),
                'delete_url' => route('api.personal.actividades.evidencias.destroy', [
                    'activity' => $activity->id,
                    'evidence' => $e->id,
                ]),
            ])->values(),
        ];
    }
}
