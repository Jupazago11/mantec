<?php

namespace App\Services\Personal;

use App\Models\Activity;
use App\Models\ActivityEmployeeComment;
use App\Models\ActivityEmployeeHour;
use App\Models\Employee;
use App\Services\FieldDiary\FieldDiaryFullView;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Registro del responsable (supervisor) sobre sus actividades — logica
 * unica para la app Android (Api\Personal\ActivityController) y para
 * "Ver como supervisor" (SupervisorViewController), que antes la tenian
 * duplicada (seccion 14.24). Revision 2026-09-28:
 *
 * - Ventana: el responsable solo consulta y registra actividades de HOY o
 *   de AYER (pedido del usuario: "el supervisor solamente va a ver ayer y
 *   hoy"). Antes la API aceptaba registrar cualquier actividad pasada.
 * - Solo se aceptan personas asignadas a la actividad (antes se podian
 *   mandar horas/comentarios de cualquier empleado — el comentario
 *   terminaba en la Bitacora de esa persona — y un id inexistente daba 500).
 * - Horas por persona entre 0 y 24.
 * - Todo en una transaccion, y se borran las filas de horas de personas
 *   que ya no vienen en el envio (quedaban con el valor viejo).
 */
class RegistroSupervisor
{
    public const MAX_HORAS = 24;

    /** @return array<int, string> hoy y ayer, 'Y-m-d' (hora de Colombia) */
    public static function fechasPermitidas(): array
    {
        return [today()->toDateString(), today()->subDay()->toDateString()];
    }

    public static function dentroDeVentana(Activity $activity): bool
    {
        return in_array($activity->date->toDateString(), self::fechasPermitidas(), true);
    }

    /**
     * Fecha pedida para consultar (?date=): hoy si no viene; null si es
     * invalida o esta fuera de la ventana hoy/ayer.
     */
    public static function fechaConsulta(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return today()->toDateString();
        }

        $fecha = FieldDiaryFullView::normalizeDate($valor);

        return $fecha !== null && in_array($fecha, self::fechasPermitidas(), true) ? $fecha : null;
    }

    /**
     * Actividades de las que el empleado es responsable en esa fecha, mas
     * las nocturnas del dia anterior (un turno nocturno cruza medianoche,
     * seccion 14.20) — solo si ese dia anterior sigue dentro de la ventana.
     */
    public function actividadesDe(Employee $responsable, string $fecha, array $with = []): Collection
    {
        $anterior = Carbon::parse($fecha)->subDay()->toDateString();
        $incluirNocturnaAnterior = in_array($anterior, self::fechasPermitidas(), true);

        return Activity::with($with)
            ->where('responsible_employee_id', $responsable->id)
            ->where(function ($query) use ($fecha, $anterior, $incluirNocturnaAnterior) {
                $query->where('date', $fecha);

                if ($incluirNocturnaAnterior) {
                    $query->orWhere(fn ($nocturna) => $nocturna->where('date', $anterior)->where('shift', 'Nocturno'));
                }
            })
            ->orderBy('date')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  bool  $conComentarios  la app manda comentario por persona;
     *                                "Ver como" (web) no.
     */
    public function validar(Request $request, Activity $activity, bool $conComentarios): array
    {
        $idsPersonas = $activity->personas()->pluck('employees.id')->all();

        $rules = [
            'comments' => ['nullable', 'string', 'max:2000'],
            'all_worked_scheduled_hours' => ['required', 'boolean'],
            'personas' => ['array'],
            'personas.*.employee_id' => ['required', 'integer', 'distinct', Rule::in($idsPersonas)],
            'personas.*.worked_hours' => ['required', 'numeric', 'min:0', 'max:'.self::MAX_HORAS],
        ];

        if ($conComentarios) {
            $rules['personas.*.comment'] = ['nullable', 'string', 'max:1000'];
        }

        return Validator::make($request->all(), $rules, [
            'personas.*.employee_id.in' => 'Una de las personas enviadas no está asignada a esta actividad.',
            'personas.*.employee_id.distinct' => 'Una persona viene repetida en el registro.',
            'personas.*.worked_hours.max' => 'Las horas por persona no pueden superar '.self::MAX_HORAS.'.',
        ])->after(function ($validator) use ($request) {
            if ($request->boolean('all_worked_scheduled_hours')) {
                return;
            }

            if (collect($request->input('personas', []))->isEmpty()) {
                $validator->errors()->add('personas', 'Indica el detalle por persona si no todos trabajaron las horas programadas.');
            }
        })->validate();
    }

    public function registrar(Activity $activity, Employee $autor, array $validated): void
    {
        DB::transaction(function () use ($activity, $autor, $validated) {
            if ($validated['all_worked_scheduled_hours']) {
                // "Todos trabajaron lo programado": sin detalle por persona.
                // Los comentarios por persona NO se borran aqui a proposito
                // (seccion 14.31: texto escrito a mano, mas caro de perder).
                $activity->employeeHours()->delete();
                $reportadas = $activity->estimated_hours;
            } else {
                $reportadas = 0.0;
                $ids = [];

                foreach ($validated['personas'] as $persona) {
                    $horas = round((float) $persona['worked_hours'], 2);
                    $reportadas += $horas;
                    $ids[] = (int) $persona['employee_id'];

                    ActivityEmployeeHour::updateOrCreate(
                        ['activity_id' => $activity->id, 'employee_id' => $persona['employee_id']],
                        ['worked' => $horas > 0, 'worked_hours' => $horas]
                    );

                    if (array_key_exists('comment', $persona)) {
                        $this->guardarComentario($activity, $autor, (int) $persona['employee_id'], $persona['comment']);
                    }
                }

                $activity->employeeHours()->whereNotIn('employee_id', $ids)->delete();
            }

            $activity->update([
                'comments' => $validated['comments'] ?? null,
                'all_worked_scheduled_hours' => $validated['all_worked_scheduled_hours'],
                'reported_hours' => $reportadas !== null ? round((float) $reportadas, 2) : null,
                'hours_registered_by_employee_id' => $autor->id,
                'hours_registered_at' => now(),
            ]);
        });
    }

    // Un solo comentario del responsable por actividad+trabajador (seccion
    // 14.31): vacio = se borra.
    private function guardarComentario(Activity $activity, Employee $autor, int $employeeId, mixed $texto): void
    {
        $comentario = trim((string) ($texto ?? ''));

        if ($comentario === '') {
            ActivityEmployeeComment::where('activity_id', $activity->id)->where('employee_id', $employeeId)->delete();

            return;
        }

        ActivityEmployeeComment::updateOrCreate(
            ['activity_id' => $activity->id, 'employee_id' => $employeeId],
            [
                'date' => $activity->date,
                'author_employee_id' => $autor->id,
                'author_name' => $autor->nombre,
                'comment' => $comentario,
            ]
        );
    }
}
