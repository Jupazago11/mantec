<?php

namespace App\Http\Controllers\Api\Personal;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Employee;
use App\Services\Personal\RegistroSupervisor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Seccion 14.24: API real para la app Android del supervisor — mismo
// modelo de datos y misma logica que
// App\Http\Controllers\Personal\SupervisorViewController (pantalla web
// "Ver como", seccion 14.18/14.20), pero autenticado por Sanctum en vez
// de superadmin+empleado-por-URL: el empleado siempre es
// $request->user() (garantizado Employee por el middleware
// EnsureTokenableIsEmployee), nunca un parametro de ruta.
class ActivityController extends Controller
{
    public function __construct(private RegistroSupervisor $registro)
    {
    }

    public function index(Request $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();

        // Solo hoy o ayer (revision 2026-09-28) — ver RegistroSupervisor.
        $date = RegistroSupervisor::fechaConsulta($request->query('date'));
        if ($date === null) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puedes consultar actividades de hoy o de ayer.',
            ], 422);
        }

        $actividades = $this->registro->actividadesDe($employee, $date, ['company', 'personas', 'employeeHours', 'evidences', 'employeeComments']);

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

        if (! RegistroSupervisor::dentroDeVentana($activity)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puedes registrar actividades de hoy o de ayer.',
            ], 403);
        }

        // Validacion (solo personas de la actividad, horas 0-24) y guardado
        // en una transaccion: RegistroSupervisor (compartido con "Ver
        // como"). Los comentarios por persona NO se borran al marcar "todos
        // trabajaron" (seccion 14.31).
        $validated = $this->registro->validar($request, $activity, conComentarios: true);
        $this->registro->registrar($activity, $employee, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Registro guardado correctamente.',
            'activity' => $this->serialize($activity->fresh(['personas', 'employeeHours', 'evidences', 'employeeComments'])),
        ]);
    }

    private function serialize(Activity $activity): array
    {
        $horasPorEmpleado = $activity->employeeHours->keyBy('employee_id');
        $comentariosPorEmpleado = $activity->employeeComments->keyBy('employee_id');

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
            'personas' => $activity->personas->map(function ($p) use ($horasPorEmpleado, $comentariosPorEmpleado) {
                $h = $horasPorEmpleado->get($p->id);

                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'nickname' => $p->nickname,
                    'worked_hours' => $h?->worked_hours !== null ? (float) $h->worked_hours : null,
                    'comment' => $comentariosPorEmpleado->get($p->id)?->comment,
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
