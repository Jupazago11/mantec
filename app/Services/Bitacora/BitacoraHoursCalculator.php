<?php

namespace App\Services\Bitacora;

use App\Models\BitacoraEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Calculo de "horas finales" por empleado/dia (seccion 8 de
 * NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md, seccion 14.19):
 * prioridad corregida > reportada > programada. Desde 2026-09-28 la base
 * es el valor del Diario de Campo por actividad (horasDiarioPorDia()), para
 * que Diario y Bitacora muestren siempre el mismo numero; la correccion
 * por dia de la Bitacora sigue mandando encima. "Reportada" viene del
 * registro real del supervisor (seccion 7/14.18, "Ver como supervisor")
 * — antes de eso no existia fuente real y siempre quedaba en null.
 * Usado tanto por BitacoraController (consolidado mensual, detalle por
 * dia) como por ActivityController (horas acumuladas en el selector de
 * "Personas de la actividad", seccion 14.16) — una sola implementacion
 * para no divergir entre pantallas.
 */
class BitacoraHoursCalculator
{
    /**
     * @return array<int, array<int, float>> [employee_id][dia] => horas programadas ese dia
     */
    public function horasProgramadasPorDia(int $year, int $month): array
    {
        $rows = DB::table('activity_employee')
            ->join('activities', 'activities.id', '=', 'activity_employee.activity_id')
            ->whereYear('activities.date', $year)
            ->whereMonth('activities.date', $month)
            ->selectRaw('activity_employee.employee_id as employee_id, activities.date as date, SUM(activities.estimated_hours) as total')
            ->groupBy('activity_employee.employee_id', 'activities.date')
            ->get();

        $porDia = [];
        foreach ($rows as $row) {
            $dia = Carbon::parse($row->date)->day;
            $porDia[$row->employee_id][$dia] = (float) $row->total;
        }

        return $porDia;
    }

    /**
     * Horas reportadas por el supervisor (seccion 7/14.18) — solo cuenta
     * actividades ya registradas (activities.hours_registered_at no
     * nulo); una actividad nunca registrada no aporta nada aqui, se sigue
     * viendo "programada" nada mas hasta que alguien la registre. Por
     * actividad registrada: si el supervisor marco que NO todos
     * trabajaron las horas programadas, usa el detalle por persona
     * (activity_employee_hours.worked_hours); si confirmo que SI todos
     * las trabajaron (sin fila de detalle), usa estimated_hours — mismo
     * valor que "programada", pero ya CONFIRMADO por el supervisor en
     * campo, no solo agendado.
     *
     * @return array<int, array<int, float>> [employee_id][dia] => horas reportadas ese dia
     */
    public function horasReportadasPorDia(int $year, int $month): array
    {
        $rows = DB::table('activity_employee')
            ->join('activities', 'activities.id', '=', 'activity_employee.activity_id')
            ->leftJoin('activity_employee_hours', function ($join) {
                $join->on('activity_employee_hours.activity_id', '=', 'activity_employee.activity_id')
                    ->on('activity_employee_hours.employee_id', '=', 'activity_employee.employee_id');
            })
            ->whereYear('activities.date', $year)
            ->whereMonth('activities.date', $month)
            ->whereNotNull('activities.hours_registered_at')
            ->select([
                'activity_employee.employee_id as employee_id',
                'activities.date as date',
                'activities.estimated_hours as estimated_hours',
                'activity_employee_hours.worked_hours as worked_hours',
            ])
            ->get();

        $porDia = [];
        foreach ($rows as $row) {
            $dia = Carbon::parse($row->date)->day;
            $valor = $row->worked_hours !== null ? (float) $row->worked_hours : (float) ($row->estimated_hours ?? 0);
            $porDia[$row->employee_id][$dia] = ($porDia[$row->employee_id][$dia] ?? 0.0) + $valor;
        }

        return $porDia;
    }

    /**
     * @return array<int, array<int, BitacoraEntry>> [employee_id][dia] => entry
     */
    public function correccionesPorDia(int $year, int $month): array
    {
        $porDia = [];
        foreach (BitacoraEntry::whereYear('date', $year)->whereMonth('date', $month)->get() as $entry) {
            $porDia[$entry->employee_id][$entry->date->day] = $entry;
        }

        return $porDia;
    }

    /**
     * Horas del dia por empleado SEGUN EL DIARIO DE CAMPO (revision
     * 2026-09-28: "Diario y Bitacora no pueden mostrar numeros distintos").
     * Suma, actividad por actividad, el mismo valor por persona que muestra
     * el Diario (Activity::diaryHourGroups()):
     *   1. correccion de "Horas" del Diario (corrected_hours, toda la actividad);
     *   2. si el responsable reporto detalle por persona, las horas de esa persona;
     *   3. si no, reported_hours (= estimadas confirmadas) o las estimadas.
     * Antes la Bitacora ignoraba la correccion del Diario y, si en un dia
     * habia una actividad registrada y otra no, solo sumaba la registrada.
     *
     * @return array{valor: array<int, array<int, float|null>>, corregido: array<int, array<int, bool>>}
     */
    public function horasDiarioPorDia(int $year, int $month): array
    {
        $rows = DB::table('activity_employee')
            ->join('activities', 'activities.id', '=', 'activity_employee.activity_id')
            ->leftJoin('activity_employee_hours', function ($join) {
                $join->on('activity_employee_hours.activity_id', '=', 'activity_employee.activity_id')
                    ->on('activity_employee_hours.employee_id', '=', 'activity_employee.employee_id');
            })
            ->whereYear('activities.date', $year)
            ->whereMonth('activities.date', $month)
            ->select([
                'activity_employee.employee_id as employee_id',
                'activities.date as date',
                'activities.estimated_hours',
                'activities.reported_hours',
                'activities.corrected_hours',
                'activities.all_worked_scheduled_hours',
                'activity_employee_hours.worked_hours',
            ])
            ->selectRaw('EXISTS (SELECT 1 FROM activity_employee_hours d WHERE d.activity_id = activities.id) as tiene_detalle')
            ->get();

        $valor = [];
        $corregido = [];
        foreach ($rows as $row) {
            $dia = Carbon::parse($row->date)->day;

            if ($row->corrected_hours !== null) {
                $horas = (float) $row->corrected_hours;
                $corregido[$row->employee_id][$dia] = true;
            } elseif ($row->all_worked_scheduled_hours === false && $row->tiene_detalle) {
                // Detalle por persona: sin fila propia = sin dato (igual que
                // el grupo "sin registro" del Diario).
                $horas = $row->worked_hours !== null ? (float) $row->worked_hours : null;
            } else {
                $base = $row->reported_hours ?? $row->estimated_hours;
                $horas = $base !== null ? (float) $base : null;
            }

            if ($horas !== null) {
                $valor[$row->employee_id][$dia] = ($valor[$row->employee_id][$dia] ?? 0.0) + $horas;
            } else {
                $valor[$row->employee_id][$dia] ??= null;
            }
        }

        return ['valor' => $valor, 'corregido' => $corregido];
    }

    // "Final" de un dia: la correccion administrativa de la Bitacora manda
    // si tiene texto (aunque no sea numerica, ej. "L" de licencia — no suma
    // horas, pero is_numeric() la descarta correctamente donde se sume); si
    // no hay, manda el valor del Diario de Campo (horasDiarioPorDia(), que
    // ya aplica corregida del Diario > reportada > programada por
    // actividad).
    public function valorFinal(?float $diario, ?BitacoraEntry $entry): int|float|string|null
    {
        $corregida = $entry?->corrected_value;

        if ($corregida !== null && $corregida !== '') {
            return $corregida;
        }

        return $diario;
    }

    /**
     * Suma de valores finales NUMERICOS por empleado, para los dias del
     * rango [$desde, $hasta] (ambos inclusive, 1-31). null si el empleado
     * no tuvo ningun dia numerico calificado en el rango — se distingue
     * "0 horas" de "sin datos", mismo criterio que BitacoraController.
     *
     * @return array<int, float|null>
     */
    public function totalPorEmpleado(int $year, int $month, int $desde, int $hasta): array
    {
        if ($hasta < $desde) {
            return [];
        }

        $diario = $this->horasDiarioPorDia($year, $month)['valor'];
        $entries = $this->correccionesPorDia($year, $month);

        $employeeIds = array_unique(array_merge(
            array_keys($diario),
            array_keys($entries)
        ));

        $totales = [];
        foreach ($employeeIds as $employeeId) {
            $total = 0.0;
            $tieneNumerico = false;

            for ($dia = $desde; $dia <= $hasta; $dia++) {
                $final = $this->valorFinal(
                    $diario[$employeeId][$dia] ?? null,
                    $entries[$employeeId][$dia] ?? null
                );

                if ($final !== null && is_numeric($final)) {
                    $total += (float) $final;
                    $tieneNumerico = true;
                }
            }

            // round() evita artefactos de suma de floats (ej. 132.30000000000001).
            $totales[$employeeId] = $tieneNumerico ? round($total, 2) : null;
        }

        return $totales;
    }
}
