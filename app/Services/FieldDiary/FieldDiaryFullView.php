<?php

namespace App\Services\FieldDiary;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Tabla del Diario de Campo (pedido 2026-09-28): una sola vista con dos
 * modos — "Todas las fechas" (lo mas reciente primero) o "Por dia" (un solo
 * dia, sin columna Fecha) —, paginada de a 100 y con filtro tipo Excel por
 * columna (mismo espiritu que los reportes preventivos por agrupacion,
 * AdminPreventiveReportController::showByGroup).
 *
 * Una fila = un grupo de horas de una actividad, exactamente como la vista
 * diaria (Activity::diaryHourGroups()) — se reutiliza ese metodo en vez de
 * replicar la regla en SQL, para que ambas vistas nunca muestren numeros
 * distintos. Por eso el filtrado y la paginacion se hacen en PHP sobre las
 * filas ya armadas: Horas/Personas/N° son valores por grupo, no columnas de
 * activities. Suficiente para miles de filas; si el volumen crece a
 * decenas de miles, el siguiente paso seria llevar diaryHourGroups() a SQL.
 *
 * Opciones de cada filtro al estilo Excel: se calculan sobre las filas que
 * pasan TODOS los demas filtros menos el de esa misma columna, asi se
 * pueden seguir agregando valores a un filtro ya activo.
 */
class FieldDiaryFullView
{
    public const PER_PAGE = 100;

    public const EMPTY_VALUE = '__EMPTY__';

    // clave de filtro (query string) => etiqueta. El orden es el de las
    // columnas en pantalla. 'fecha' es rango (fecha_desde/fecha_hasta), el
    // resto son listas de valores (clave[]).
    public const COLUMNS = [
        'fecha' => 'Fecha',
        'empresa' => 'Empresa',
        'equipo' => 'Equipo',
        'proceso' => 'Proceso',
        'actividad' => 'Actividad programada',
        'ejecutada' => 'Actividad ejecutada',
        'personas' => 'Personas',
        'n_personas' => 'N°',
        'horas' => 'Horas',
        'jornada' => 'Jornada',
        'comentarios' => 'Comentarios',
        'zcom' => 'ZCOM',
        'linea' => 'Línea',
        'ot_sap' => 'OT SAP',
        'acta' => 'Acta entrega',
        'we' => 'WE',
    ];

    // Columnas que se ordenan como numero en la lista de opciones.
    private const NUMERIC_COLUMNS = ['horas', 'n_personas'];

    /**
     * @return array{
     *     filas: LengthAwarePaginator,
     *     filtrosActivos: array<string, mixed>,
     *     opciones: array<string, array<int, array{value: string, label: string}>>,
     *     totalGenerado: int,
     *     totalFiltrado: int
     * }
     */
    public function build(Request $request, ?string $dia = null): array
    {
        // Modo "Por dia" (pedido 2026-09-28: una sola vista para las dos
        // formas de ver el Diario de Campo): solo se cargan las filas de ese
        // dia — Total/Filtrado y las opciones de filtro son de ese dia — y
        // el filtro de rango de Fecha no aplica (la columna ni se muestra).
        $filtros = $this->activeFilters($request, $dia !== null);
        $filas = $this->allRows($dia);

        $filtradas = $filas->filter(fn ($fila) => $this->matchesAll($fila, $filtros))->values();

        $opciones = [];
        foreach (array_keys(self::COLUMNS) as $clave) {
            if ($clave === 'fecha') {
                continue;
            }
            $base = $filas->filter(fn ($fila) => $this->matchesAll($fila, $filtros, $clave));
            $opciones[$clave] = $this->optionsFor($base, $clave);
        }

        $page = max(1, (int) $request->query('page', 1));
        $paginadas = new LengthAwarePaginator(
            $filtradas->forPage($page, self::PER_PAGE)->values(),
            $filtradas->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->except('page')]
        );

        return [
            'filas' => $paginadas,
            'filtrosActivos' => $filtros,
            'opciones' => $opciones,
            'totalGenerado' => $filas->count(),
            'totalFiltrado' => $filtradas->count(),
        ];
    }

    /**
     * Filtros activos normalizados desde el query string. Fechas invalidas
     * se ignoran en silencio (es un GET de navegacion, no un formulario).
     *
     * @return array<string, mixed>
     */
    public function activeFilters(Request $request, bool $ignorarFecha = false): array
    {
        $filtros = [
            'fecha' => [
                'from' => $ignorarFecha ? null : self::normalizeDate($request->query('fecha_desde')),
                'to' => $ignorarFecha ? null : self::normalizeDate($request->query('fecha_hasta')),
            ],
        ];

        foreach (array_keys(self::COLUMNS) as $clave) {
            if ($clave === 'fecha') {
                continue;
            }
            $filtros[$clave] = collect((array) $request->query($clave, []))
                ->filter(fn ($v) => is_scalar($v))
                ->map(fn ($v) => trim((string) $v))
                ->filter(fn ($v) => $v !== '')
                ->unique()
                ->values()
                ->all();
        }

        return $filtros;
    }

    /**
     * Todas las filas (grupo de horas) de todas las actividades, en el
     * orden final: fecha mas reciente primero y, dentro del mismo dia, el
     * mismo orden que la vista diaria (grupo ascendente, sin grupo al
     * final, luego id).
     */
    private function allRows(?string $dia = null): Collection
    {
        $actividades = Activity::query()
            ->when($dia !== null, fn ($q) => $q->where('date', $dia))
            ->select([
                'id', 'date', 'company_id', 'group_number', 'team', 'process', 'description',
                'shift', 'comments', 'zcom', 'line_code', 'ot_sap', 'acta_entrega', 'we_code',
                'estimated_hours', 'reported_hours', 'corrected_hours', 'all_worked_scheduled_hours',
            ])
            ->with([
                'company:id,name',
                'personas:id,nickname',
                'employeeHours:id,activity_id,employee_id,worked_hours',
            ])
            ->orderByDesc('date')
            ->orderByRaw('group_number IS NULL, group_number')
            ->orderBy('id')
            ->get();

        return $actividades->flatMap(function (Activity $a) {
            return collect($a->diaryHourGroups())->map(function ($grupo) use ($a) {
                $personas = $grupo['personas']->pluck('nickname')->filter()->values()->all();

                return [
                    'activity_id' => $a->id,
                    'fecha' => $a->date->toDateString(),
                    'empresa' => $a->company?->name,
                    'equipo' => $a->team,
                    'proceso' => $a->process,
                    'actividad' => $a->description,
                    // Seccion 14.23: "Actividad ejecutada" y "Comentarios"
                    // son el mismo campo (comments), a proposito.
                    'ejecutada' => $a->comments,
                    'personas' => $personas,
                    'n_personas' => count($personas),
                    'horas' => $grupo['horas'],
                    'jornada' => $a->shift,
                    'comentarios' => $a->comments,
                    'zcom' => $a->zcom,
                    'linea' => $a->line_code,
                    'ot_sap' => $a->ot_sap,
                    'acta' => $a->acta_entrega,
                    'we' => $a->we_code,
                ];
            });
        })->values();
    }

    private function matchesAll(array $fila, array $filtros, ?string $excepto = null): bool
    {
        $desde = $filtros['fecha']['from'];
        $hasta = $filtros['fecha']['to'];
        if ($excepto !== 'fecha') {
            if ($desde !== null && $fila['fecha'] < $desde) {
                return false;
            }
            if ($hasta !== null && $fila['fecha'] > $hasta) {
                return false;
            }
        }

        foreach ($filtros as $clave => $seleccion) {
            if ($clave === 'fecha' || $clave === $excepto || $seleccion === []) {
                continue;
            }

            $buscados = array_map(fn ($v) => $v === self::EMPTY_VALUE ? '' : $v, $seleccion);
            if (array_intersect($this->cellValues($fila, $clave), $buscados) === []) {
                return false;
            }
        }

        return true;
    }

    /**
     * Valores de una celda tal como se comparan contra el filtro y se
     * ofrecen como opcion ('' = vacio / "Sin valor"). Personas es la unica
     * columna con varios valores por celda: una fila coincide si incluye
     * a CUALQUIERA de las personas seleccionadas.
     *
     * @return array<int, string>
     */
    public function cellValues(array $fila, string $clave): array
    {
        return match ($clave) {
            'personas' => $fila['personas'] === [] ? [''] : $fila['personas'],
            'n_personas' => [$fila['n_personas'] > 0 ? (string) $fila['n_personas'] : ''],
            'horas' => [self::formatHours($fila['horas'])],
            default => [trim((string) ($fila[$clave] ?? ''))],
        };
    }

    /**
     * Mismo formato que la vista diaria ($grupo['horas'] + 0): 8, 8.5, 0.5.
     */
    public static function formatHours(?float $horas): string
    {
        if ($horas === null) {
            return '';
        }

        return rtrim(rtrim(number_format($horas, 2, '.', ''), '0'), '.');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function optionsFor(Collection $filas, string $clave): array
    {
        $valores = $filas->flatMap(fn ($fila) => $this->cellValues($fila, $clave))->unique();
        $hayVacio = $valores->contains('');

        $valores = $valores->reject(fn ($v) => $v === '');
        $valores = in_array($clave, self::NUMERIC_COLUMNS, true)
            ? $valores->sortBy(fn ($v) => (float) $v)
            : $valores->sortBy(fn ($v) => mb_strtolower($v));

        $opciones = $valores->map(fn ($v) => ['value' => $v, 'label' => $v])->values();

        if ($hayVacio) {
            $opciones->prepend(['value' => self::EMPTY_VALUE, 'label' => 'Sin valor']);
        }

        return $opciones->values()->all();
    }

    /**
     * 'YYYY-MM-DD' valida o null (tambien la usa el controlador para el
     * parametro "date" del modo Por dia).
     */
    public static function normalizeDate(mixed $valor): ?string
    {
        if (! is_string($valor) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $m)) {
            return null;
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $valor : null;
    }
}
