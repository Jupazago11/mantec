<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityEmployeeComment;
use App\Models\BitacoraEntry;
use App\Models\BitacoraHoliday;
use App\Models\BitacoraQuota;
use App\Models\Employee;
use App\Services\Bitacora\BitacoraHoursCalculator;
use App\Support\PersonalGuard;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
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

        // Festivos marcados a mano (pedido 2026-09-24): el sistema no tiene
        // un calendario real de festivos colombianos — un administrativo
        // marca/quita la fecha puntual desde el clic en el numero del dia
        // (ver toggleHoliday()). La vista combina domingo + manual en
        // esFestivo() (Alpine, reactivo) para pintar en rojo sin recargar;
        // "esDomingo" se mantiene aparte para no ofrecer el toggle sobre un
        // domingo (ya es festivo siempre, togglearlo no tendria efecto
        // visible).
        $festivosManuales = BitacoraHoliday::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->map(fn ($h) => $h->date->day)
            ->all();

        $dias = [];
        for ($n = 1; $n <= $diasEnMes; $n++) {
            $fecha = $fechaMes->copy()->day($n);
            $esDomingo = $fecha->isSunday();
            $festivoManual = in_array($n, $festivosManuales, true);
            $dias[] = [
                'numero' => $n,
                'nombre' => $fecha->translatedFormat('l'),
                'esDomingo' => $esDomingo,
                'festivoManual' => $festivoManual,
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
        // Base del valor final desde 2026-09-28: lo mismo que muestra el
        // Diario de Campo (incluye su correccion de Horas). "programada" y
        // "reportada" quedan como informacion en el tooltip.
        $diario = $horasCalculator->horasDiarioPorDia($year, $month);

        $entries = BitacoraEntry::whereYear('date', $year)->whereMonth('date', $month)->get();
        $entriesPorEmpleadoDia = [];
        foreach ($entries as $entry) {
            $entriesPorEmpleadoDia[$entry->employee_id][$entry->date->day] = $entry;
        }

        $comentariosPorEmpleadoDia = $this->comentariosDelMes($year, $month);

        $idsCalificados = array_unique(array_merge(
            array_keys($programada),
            array_keys($reportada),
            array_keys($diario['valor']),
            $entries->pluck('employee_id')->unique()->all(),
            array_keys($comentariosPorEmpleadoDia)
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
                $diarioValor = $diario['valor'][$empleado->id][$n] ?? null;
                $entry = $entriesPorEmpleadoDia[$empleado->id][$n] ?? null;
                $corregida = $entry?->corrected_value;

                $final = $horasCalculator->valorFinal($diarioValor, $entry);

                $celdas[$empleado->id][$n] = [
                    'programada' => $prog,
                    'reportada' => $reportadaValor,
                    'corregida' => $corregida,
                    // Valor del Diario de Campo y si incluye una correccion
                    // de "Horas" hecha alla (se muestra en el tooltip).
                    'diario' => $diarioValor,
                    'diario_corregido' => (bool) ($diario['corregido'][$empleado->id][$n] ?? false),
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

    /**
     * Historial de comentarios del mes por empleado/dia, en el orden en que
     * se escribieron. Lo usan index() (tooltip/modal de cada celda) y
     * saveEntry() (respuesta AJAX con el historial actualizado de la celda).
     *
     * @return array<int, array<int, array<int, array{autor: string, texto: string, es_responsable: bool}>>>
     */
    private function comentariosDelMes(int $year, int $month): array
    {
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
        $porEmpleadoDia = [];
        foreach ($comentarios as $comentario) {
            $porEmpleadoDia[$comentario->employee_id][$comentario->date->day][] = [
                'autor' => $comentario->author_name,
                'texto' => $comentario->comment,
                'es_responsable' => $comentario->activity_id !== null,
            ];
        }

        // Comentario de Programacion (pedido 2026-09-24, seccion
        // add_scheduling_comment_to_activities_table): se lee directo de
        // activities.scheduling_comment y se fusiona (solo en memoria, no
        // se duplica en activity_employee_comments) en el mismo historial
        // por empleado/dia que ya usa el tooltip/modal — uno por cada
        // persona de la actividad, no una fila por empleado (una actividad
        // puede tener varias personas y una sola descripcion/contexto).
        $actividadesConComentario = Activity::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNotNull('scheduling_comment')
            ->where('scheduling_comment', '!=', '')
            ->with('personas:id')
            ->get();
        foreach ($actividadesConComentario as $actividad) {
            $dia = $actividad->date->day;
            foreach ($actividad->personas as $persona) {
                $porEmpleadoDia[$persona->id][$dia][] = [
                    'autor' => 'Programación',
                    'texto' => $actividad->scheduling_comment,
                    'es_responsable' => false,
                ];
            }
        }

        return $porEmpleadoDia;
    }

    // Correccion + comentario de una celda (modal de la celda). Desde
    // 2026-09-28 la vista la guarda por AJAX, sin recargar (pedido: "todos
    // los CRUD dinamicos, con toast"): la respuesta JSON trae lo que la
    // celda necesita para actualizarse sola — valor corregido, historial de
    // comentarios y el total del mes del empleado (pie de la tabla). El
    // redirect se conserva como respaldo para un POST no-AJAX.
    public function saveEntry(Request $request, BitacoraHoursCalculator $horasCalculator): JsonResponse|RedirectResponse
    {
        abort_unless(PersonalGuard::can('ver_bitacora'), 403);

        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'date' => ['required', 'date'],
            // Texto libre a proposito ("L" = licencia, etc.); si es un
            // numero, son horas de un dia: entre 0 y 24 (revision
            // 2026-09-28, mismo tope que el resto del modulo).
            'corrected_value' => ['nullable', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (is_numeric($value) && ((float) $value < 0 || (float) $value > 24)) {
                    $fail('Las horas corregidas deben estar entre 0 y 24.');
                }
            }],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $entry = BitacoraEntry::updateOrCreate(
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

        if ($this->isAjaxRequest($request)) {
            $empleadoId = (int) $validated['employee_id'];
            $totales = $horasCalculator->totalPorEmpleado($fecha->year, $fecha->month, 1, $fecha->daysInMonth);

            return response()->json([
                'success' => true,
                'message' => 'Corrección guardada.',
                'corregida' => $entry->corrected_value,
                'comentarios' => $this->comentariosDelMes($fecha->year, $fecha->month)[$empleadoId][$fecha->day] ?? [],
                'total_mes' => $totales[$empleadoId] ?? null,
            ]);
        }

        return redirect()
            ->route('personal.bitacora.index', ['year' => $fecha->year, 'month' => $fecha->month])
            ->with('success', 'Corrección guardada.');
    }

    // Cuota mensual — tambien por AJAX desde 2026-09-28 (el pie de la
    // tabla, "Horas a laborar" y "Extras", se recalcula en la vista con la
    // cuota que devuelve esta respuesta).
    public function saveQuota(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless(PersonalGuard::can('ver_bitacora'), 403);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020'],
            'month' => ['required', 'integer', 'between:1,12'],
            // max:744 = 31 dias x 24 h (revision 2026-09-28: sin tope, un
            // valor >= 10000 desbordaba decimal(6,2) con un error 500).
            'quota_hours' => ['required', 'numeric', 'min:0', 'max:744'],
        ]);

        $quota = BitacoraQuota::updateOrCreate(
            ['year' => $validated['year'], 'month' => $validated['month']],
            ['quota_hours' => $validated['quota_hours']]
        );

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Cuota mensual actualizada.',
                'cuota' => (float) $quota->quota_hours,
            ]);
        }

        return redirect()
            ->route('personal.bitacora.index', ['year' => $validated['year'], 'month' => $validated['month']])
            ->with('success', 'Cuota mensual actualizada.');
    }

    // Marcar/quitar festivo manual sobre una fecha puntual (pedido
    // 2026-09-24) — clic en el numero del dia en la vista. Mismo permiso
    // que el resto de escritura de Bitacora (saveEntry/saveQuota), no hay
    // uno separado para esto. No aplica sobre domingos (ver "esDomingo" en
    // index() y el @if de la vista que oculta el toggle ahi) — si de
    // cualquier forma llega una fecha que cae domingo, no pasa nada malo,
    // solo queda un registro sin efecto visual (isSunday() ya lo pinta
    // rojo igual).
    //
    // La vista lo llama por AJAX (pedido 2026-09-28: pintar la fila no debe
    // recargar la pagina) y aplica el estado "festivo" que devuelve esta
    // respuesta, no el que asumia antes del clic — asi, si otra pestaña ya
    // habia cambiado ese dia, la pantalla queda igual a la BD. El redirect
    // se conserva como respaldo para un POST no-AJAX.
    public function toggleHoliday(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless(PersonalGuard::can('ver_bitacora'), 403);

        $validated = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $fecha = Carbon::parse($validated['date']);

        $existente = BitacoraHoliday::whereDate('date', $fecha->toDateString())->first();
        if ($existente) {
            $existente->delete();
            $mensaje = 'Festivo quitado.';
        } else {
            BitacoraHoliday::create([
                'date' => $fecha->toDateString(),
                'created_by_employee_id' => PersonalGuard::employee()?->id,
            ]);
            $mensaje = 'Día marcado como festivo.';
        }

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'date' => $fecha->toDateString(),
                'festivo' => ! $existente,
            ]);
        }

        return redirect()
            ->route('personal.bitacora.index', ['year' => $fecha->year, 'month' => $fecha->month])
            ->with('success', $mensaje);
    }

    private function isAjaxRequest(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }
}
