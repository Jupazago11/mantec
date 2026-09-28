<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Employee;
use App\Services\Personal\RegistroSupervisor;
use App\Support\PersonalGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Ver como supervisor" (pedido 2026-09-19, seccion 14.18): pantalla
 * minima, pensada como celular, para prototipar la logica del API que
 * consumira la app Android real (seccion 7 del documento — repo aparte,
 * ese repo no existe todavia). Solo superadmin puede entrar, eligiendo un
 * empleado desde Empleados ("Ver como"); NO se reemplaza la sesion
 * (Auth::guard('personal')) — la sesion de superadmin sigue intacta en
 * todas las pestañas, esta pantalla solo consulta/opera sobre los datos
 * del empleado elegido, pasado por la URL y verificado en cada request.
 * Lo que se guarda queda atribuido a ese empleado
 * (hours_registered_by_employee_id), no al superadmin.
 */
class SupervisorViewController extends Controller
{
    public function __construct(private RegistroSupervisor $registro)
    {
    }

    public function index(Request $request, Employee $employee): View
    {
        abort_unless(PersonalGuard::isSuperadmin(), 403);

        // Misma ventana que la app (revision 2026-09-28): hoy o ayer; una
        // fecha fuera de la ventana o invalida cae en hoy.
        $date = RegistroSupervisor::fechaConsulta($request->query('date')) ?? today()->toDateString();

        // Seccion 7: "cada supervisor hace este registro para las
        // distintas actividades asignadas a su nombre ese dia" — el
        // "nombre" es responsible_employee_id, no ser parte de personas().
        // Incluye las nocturnas del dia anterior (seccion 14.20), dentro de
        // la ventana — ver RegistroSupervisor::actividadesDe().
        $actividades = $this->registro->actividadesDe($employee, $date, ['company', 'personas', 'employeeHours', 'evidences']);

        return view('personal.ver-como.index', [
            'empleado' => $employee,
            'fecha' => $date,
            'actividadesJs' => $actividades->map(fn ($a) => $this->serialize($a))->values(),
            'saveUrlTemplate' => route('personal.ver-como.store', ['employee' => $employee->id, 'activity' => '__ID__']),
            'evidenciasUrlTemplate' => route('personal.ver-como.evidencias.store', ['employee' => $employee->id, 'activity' => '__ID__']),
        ]);
    }

    public function store(Request $request, Employee $employee, Activity $activity): JsonResponse
    {
        abort_unless(PersonalGuard::isSuperadmin(), 403);
        // La actividad tiene que ser realmente de este empleado como
        // responsable — nunca confiar solo en el ID de la URL (AGENTS.md
        // seccion 6).
        abort_unless((int) $activity->responsible_employee_id === $employee->id, 404);
        // Una vez cerrada en Diario de Campo, el registro del supervisor ya
        // no deberia poder cambiar los datos que el administrativo ya
        // revisó — mismo criterio que canModify() en ActivityController.
        abort_if($activity->isClosed(), 403);

        if (! RegistroSupervisor::dentroDeVentana($activity)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puedes registrar actividades de hoy o de ayer.',
            ], 403);
        }

        // Misma validacion y guardado que la app (RegistroSupervisor): solo
        // personas de la actividad, horas 0-24, en una transaccion. Esta
        // pantalla no maneja comentario por persona.
        $validated = $this->registro->validar($request, $activity, conComentarios: false);
        $this->registro->registrar($activity, $employee, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Registro guardado correctamente.',
            'activity' => $this->serialize($activity->fresh(['personas', 'employeeHours', 'evidences'])),
        ]);
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
                'open_url' => route('personal.ver-como.evidencias.open', [
                    'employee' => $activity->responsible_employee_id,
                    'activity' => $activity->id,
                    'evidence' => $e->id,
                ]),
                'delete_url' => route('personal.ver-como.evidencias.destroy', [
                    'employee' => $activity->responsible_employee_id,
                    'activity' => $activity->id,
                    'evidence' => $e->id,
                ]),
            ])->values(),
        ];
    }
}
