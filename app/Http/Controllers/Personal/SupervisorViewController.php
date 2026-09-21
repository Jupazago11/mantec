<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityEmployeeHour;
use App\Models\Employee;
use App\Support\PersonalGuard;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
    public function index(Request $request, Employee $employee): View
    {
        abort_unless(PersonalGuard::isSuperadmin(), 403);

        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))->toDateString()
            : today()->toDateString();

        // Seccion 7: "cada supervisor hace este registro para las
        // distintas actividades asignadas a su nombre ese dia" — el
        // "nombre" es responsible_employee_id, no ser parte de personas().
        // Ademas de las de hoy, se incluyen las de AYER con turno Nocturno:
        // un turno nocturno cruza medianoche, asi que al momento en que el
        // responsable "hace login" para diligenciarla puede seguir estando
        // en curso o recien terminada ya del lado de "hoy" en el reloj.
        // Se muestran siempre (Registrado o Pendiente), igual que las de
        // hoy — pedido explicito del usuario, sin ocultarlas al registrarse.
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
            // Horas nunca negativas (mismo criterio que "Horas estimadas"
            // en Programación) — sin captura de hora inicio/hora final por
            // ahora (pedido 2026-09-19: "no implementemos ahora lo de la
            // hora de ingreso y final", queda para una iteración futura).
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
