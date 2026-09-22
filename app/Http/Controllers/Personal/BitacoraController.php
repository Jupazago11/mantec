<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\ActivityEmployeeComment;
use App\Models\BitacoraEntry;
use App\Models\BitacoraQuota;
use App\Models\Employee;
use App\Services\Bitacora\BitacoraHoursCalculator;
use App\Support\PersonalGuard;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BitacoraController extends Controller
{
    // Seccion 8: "182 horas en el ejemplo visto" — se usa solo cuando el
    // mes todavia no tiene una cuota guardada en bitacora_quotas.
    private const CUOTA_DEFAULT = 182.0;

    public function index(Request $request, BitacoraHoursCalculator $horasCalculator): View
    {
        // Seccion 8: "Web, Administrativos Y Superadmin" — gobernado por
        // el permiso "ver_bitacora" (Roles y permisos), no por el nombre
        // del rol.
        abort_unless(PersonalGuard::can('ver_bitacora'), 403);

        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);
        $fechaMes = Carbon::createFromDate($year, $month, 1);
        $diasEnMes = $fechaMes->daysInMonth;

        $dias = [];
        for ($n = 1; $n <= $diasEnMes; $n++) {
            $fecha = $fechaMes->copy()->day($n);
            $dias[] = [
                'numero' => $n,
                'nombre' => $fecha->translatedFormat('l'),
                // Simplificacion ya usada en el mockup: no hay calendario
                // real de festivos colombianos modelado todavia.
                'festivo' => $fecha->isSunday(),
            ];
        }

        // Seccion 14.19: "programada"/"reportada" y la prioridad
        // corregida > reportada > programada ya viven en
        // BitacoraHoursCalculator (compartido con ActivityController,
        // seccion 14.16) — antes esta pantalla tenia su propio calculo
        // duplicado de "programada" y "reportada" quedaba hardcodeada en
        // null (comentario historico: "sin la app Android, reportada
        // siempre esta vacia" — ya no aplica, la app todavia no existe
        // pero el registro real del supervisor via "Ver como supervisor",
        // seccion 14.18, ya alimenta reportada).
        $programada = $horasCalculator->horasProgramadasPorDia($year, $month);
        $reportada = $horasCalculator->horasReportadasPorDia($year, $month);

        $entries = BitacoraEntry::whereYear('date', $year)->whereMonth('date', $month)->get();
        $entriesPorEmpleadoDia = [];
        foreach ($entries as $entry) {
            $entriesPorEmpleadoDia[$entry->employee_id][$entry->date->day] = $entry;
        }

        // Historial de comentarios (pedido 2026-09-22): activity_id no nulo
        // = comentario del responsable (app Android, uno por
        // actividad+trabajador); activity_id nulo = comentario de
        // administrativo a nivel de dia (lo que antes era
        // bitacora_entries.comment, ahora tambien historial — ver
        // saveEntry()). orderBy('created_at') para que se muestren en el
        // orden en que se escribieron.
        $comentarios = ActivityEmployeeComment::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('created_at')
            ->get();
        $comentariosPorEmpleadoDia = [];
        foreach ($comentarios as $comentario) {
            $comentariosPorEmpleadoDia[$comentario->employee_id][$comentario->date->day][] = [
                'autor' => $comentario->author_name,
                'texto' => $comentario->comment,
                'es_responsable' => $comentario->activity_id !== null,
            ];
        }

        $idsCalificados = array_unique(array_merge(
            array_keys($programada),
            array_keys($reportada),
            $entries->pluck('employee_id')->unique()->all(),
            $comentarios->pluck('employee_id')->unique()->all()
        ));

        $empleados = Employee::where('in_bitacora', true)
            ->whereIn('id', $idsCalificados)
            ->orderBy('nickname')
            ->get();

        $celdas = [];
        $totales = [];
        foreach ($empleados as $empleado) {
            $totalMes = 0;
            $tieneNumerico = false;
            for ($n = 1; $n <= $diasEnMes; $n++) {
                $prog = $programada[$empleado->id][$n] ?? null;
                $reportadaValor = $reportada[$empleado->id][$n] ?? null;
                $entry = $entriesPorEmpleadoDia[$empleado->id][$n] ?? null;
                $corregida = $entry?->corrected_value;

                $final = $horasCalculator->valorFinal($prog, $reportadaValor, $entry);

                $celdas[$empleado->id][$n] = [
                    'programada' => $prog,
                    'reportada' => $reportadaValor,
                    'corregida' => $corregida,
                    'comentarios' => $comentariosPorEmpleadoDia[$empleado->id][$n] ?? [],
                    'final' => $final,
                ];

                if ($final !== null && is_numeric($final)) {
                    $totalMes += (float) $final;
                    $tieneNumerico = true;
                }
            }
            $totales[$empleado->id] = $tieneNumerico ? $totalMes : null;
        }

        $quota = BitacoraQuota::where('year', $year)->where('month', $month)->first();
        $cuotaHoras = $quota ? (float) $quota->quota_hours : self::CUOTA_DEFAULT;

        return view('personal.bitacora.index', [
            'year' => $year,
            'month' => $month,
            'fechaMes' => $fechaMes,
            'dias' => $dias,
            'empleados' => $empleados,
            'celdas' => $celdas,
            'totales' => $totales,
            'cuotaHoras' => $cuotaHoras,
        ]);
    }

    public function saveEntry(Request $request): RedirectResponse
    {
        abort_unless(PersonalGuard::can('ver_bitacora'), 403);

        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'corrected_value' => ['nullable', 'string', 'max:20'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        BitacoraEntry::updateOrCreate(
            ['employee_id' => $validated['employee_id'], 'date' => $validated['date']],
            [
                'corrected_value' => $validated['corrected_value'] ?? null,
                'corrected_by_employee_id' => PersonalGuard::employee()?->id,
            ]
        );

        // A diferencia de corrected_value (un solo valor vigente), el
        // comentario de administrativo ahora es historial (pedido
        // 2026-09-22, igual que el del responsable en la app) — siempre
        // create(), nunca upsert, para no perder los comentarios previos.
        $comentario = trim((string) ($validated['comment'] ?? ''));
        if ($comentario !== '') {
            ActivityEmployeeComment::create([
                'employee_id' => $validated['employee_id'],
                'date' => $validated['date'],
                'activity_id' => null,
                'author_employee_id' => PersonalGuard::employee()?->id,
                'author_name' => PersonalGuard::displayName(),
                'comment' => $comentario,
            ]);
        }

        $fecha = Carbon::parse($validated['date']);

        return redirect()
            ->route('personal.bitacora.index', ['year' => $fecha->year, 'month' => $fecha->month])
            ->with('success', 'Corrección guardada.');
    }

    public function saveQuota(Request $request): RedirectResponse
    {
        abort_unless(PersonalGuard::can('ver_bitacora'), 403);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020'],
            'month' => ['required', 'integer', 'between:1,12'],
            'quota_hours' => ['required', 'numeric', 'min:0'],
        ]);

        BitacoraQuota::updateOrCreate(
            ['year' => $validated['year'], 'month' => $validated['month']],
            ['quota_hours' => $validated['quota_hours']]
        );

        return redirect()
            ->route('personal.bitacora.index', ['year' => $validated['year'], 'month' => $validated['month']])
            ->with('success', 'Cuota mensual actualizada.');
    }
}
