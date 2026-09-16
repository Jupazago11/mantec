<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\BitacoraEntry;
use App\Models\BitacoraQuota;
use App\Models\Employee;
use App\Support\PersonalGuard;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BitacoraController extends Controller
{
    // Seccion 8: "182 horas en el ejemplo visto" — se usa solo cuando el
    // mes todavia no tiene una cuota guardada en bitacora_quotas.
    private const CUOTA_DEFAULT = 182.0;

    public function index(Request $request): View
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

        // "Programada" (seccion 8: "la hora que el supervisor puso al
        // crear la actividad en la Programacion") se calcula en vivo, no
        // se guarda — suma de estimated_hours por empleado/dia.
        $programadaRows = DB::table('activity_employee')
            ->join('activities', 'activities.id', '=', 'activity_employee.activity_id')
            ->whereYear('activities.date', $year)
            ->whereMonth('activities.date', $month)
            ->selectRaw('activity_employee.employee_id as employee_id, activities.date as date, SUM(activities.estimated_hours) as total')
            ->groupBy('activity_employee.employee_id', 'activities.date')
            ->get();

        $programada = [];
        foreach ($programadaRows as $row) {
            $dia = Carbon::parse($row->date)->day;
            $programada[$row->employee_id][$dia] = (float) $row->total;
        }

        $entries = BitacoraEntry::whereYear('date', $year)->whereMonth('date', $month)->get();
        $entriesPorEmpleadoDia = [];
        foreach ($entries as $entry) {
            $entriesPorEmpleadoDia[$entry->employee_id][$entry->date->day] = $entry;
        }

        $idsConProgramada = array_keys($programada);
        $idsConEntries = $entries->pluck('employee_id')->unique()->all();
        $idsCalificados = array_unique(array_merge($idsConProgramada, $idsConEntries));

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
                $entry = $entriesPorEmpleadoDia[$empleado->id][$n] ?? null;
                $corregida = $entry?->corrected_value;
                $comentario = $entry?->comment;

                // Decision confirmada con el usuario (plan Fase 2c): sin la
                // app Android, "reportada" siempre esta vacia — la celda
                // usa corregida si existe, si no la programada calculada.
                $final = ($corregida !== null && $corregida !== '') ? $corregida : $prog;

                $celdas[$empleado->id][$n] = [
                    'programada' => $prog,
                    'reportada' => null,
                    'corregida' => $corregida,
                    'comentario' => $comentario,
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
                'comment' => $validated['comment'] ?? null,
                'corrected_by_employee_id' => PersonalGuard::employee()?->id,
            ]
        );

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
