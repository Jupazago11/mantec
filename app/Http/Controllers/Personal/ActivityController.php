<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PersonalCategory;
use App\Models\PersonalRole;
use App\Services\Bitacora\BitacoraHoursCalculator;
use App\Support\PersonalGuard;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request, BitacoraHoursCalculator $horasCalculator): View
    {
        abort_unless(PersonalGuard::can('ver_programacion'), 403);

        $dateCarbon = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : today();
        $date = $dateCarbon->toDateString();

        $actividades = Activity::with(['company', 'responsible', 'personas'])
            ->where('date', $date)
            ->orderByRaw('group_number IS NULL, group_number')
            ->orderBy('id')
            ->get();

        // Autocompletar Responsable por Grupo (pedido 2026-09-18): al
        // escribir un numero de Grupo que ya tiene actividades ese mismo
        // dia con Responsable asignado, se sugiere el mismo Responsable en
        // vez de tener que volver a buscarlo. Se arma sobre $actividades,
        // que ya viene filtrada por 'date' => $date arriba, asi que nunca
        // mezcla grupos de otros dias. unique('group_number') sobre la
        // coleccion ya ordenada por group_number/id se queda con el primer
        // responsable asignado a ese grupo ese dia (si hubiera mas de uno
        // por inconsistencia de datos, gana el mas antiguo).
        $gruposResponsables = $actividades
            ->whereNotNull('group_number')
            ->whereNotNull('responsible_employee_id')
            ->unique('group_number')
            ->pluck('responsible_employee_id', 'group_number');

        // Horas acumuladas este mes (pedido 2026-09-19, seccion 14.16): ya
        // no es el Excel de ejemplo (ese solo lo sigue usando el mockup en
        // preview-personal/) — se calcula con el mismo criterio real que
        // usa Bitacora (BitacoraHoursCalculator: correccion administrativa
        // si existe, si no lo programado), sumando SOLO los dias del mes
        // anteriores a la fecha que se esta viendo/programando. El dia que
        // se esta programando queda fuera a proposito: el selector lo
        // muestra aparte como "+ N horas hoy" con lo que el supervisor esta
        // escribiendo en el formulario en ese momento (ver
        // horasResumenTexto() en la vista), asi no se cuenta doble.
        $horasAcumuladas = $horasCalculator->totalPorEmpleado(
            $dateCarbon->year,
            $dateCarbon->month,
            1,
            $dateCarbon->day - 1
        );

        // Filtro de elegibilidad por subrol (pedido por el usuario
        // 2026-09-17): un empleado con subrol asignado solo aparece si ese
        // subrol tiene disponible_en_programacion=true — configurable desde
        // "Roles y permisos", no hardcodeado. Ya no depende de que
        // categoria/Rol tenga el empleado (antes solo aplicaba a
        // "Administrativos") — con la jerarquia Rol->Subrol cualquier
        // categoria puede tener subroles con o sin este flag. Un empleado
        // sin subrol asignado se muestra por defecto, para no desaparecer
        // gente silenciosamente por falta de configuracion.
        $empleados = Employee::where('activo', true)
            ->with(['personalRole:id,disponible_en_programacion', 'personalCategory:id,name'])
            ->orderBy('nombre')
            ->get()
            ->filter(fn ($e) => $e->personalRole === null || $e->personalRole->disponible_en_programacion)
            ->map(fn ($e) => [
                'id' => $e->id,
                'nombre' => $e->nickname,
                'categoria' => $e->personalCategory->name,
                'horasAcumuladas' => $horasAcumuladas[$e->id] ?? null,
            ])->values();

        $empresas = Company::where('archived', false)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        // Categorias activas (nivel "Rol") — alimentan el agrupado dinamico
        // del selector de "Personas de la actividad" en la vista (antes un
        // array PHP hardcodeado ['Campo', 'Administrativos']).
        $categorias = PersonalCategory::where('activo', true)->orderBy('name')->pluck('name');

        // Responsable (seccion 6, pedido 2026-09-18, corregido el mismo
        // dia): ya no se decide por el nombre exacto del subrol
        // ("Supervisor" hardcodeado). Jerarquia real: un Subrol solo cuenta
        // como Responsable si SU Rol (PersonalCategory) tambien tiene
        // "responsable_actividad" activo — el Rol es un requisito
        // (gatekeeper), no basta con marcar solo el Subrol. Esto permite
        // activar el Rol una vez y despues elegir uno o varios subroles
        // puntuales dentro de el (ej. "Supervisor" si, "Administrativo"/
        // "SISO" no, los 3 dentro del mismo Rol "Administrativo") — ver
        // responsableEligibleRoleIds(). La columna "Resp." de la tabla
        // muestra el nickname.
        $supervisores = Employee::where('activo', true)
            ->whereIn('personal_role_id', $this->responsableEligibleRoleIds())
            ->orderBy('nombre')
            ->get()
            ->map(fn ($e) => ['id' => $e->id, 'nombre' => $e->nickname])
            ->values();

        // Tabla reactiva Alpine (pedido 2026-09-19, mismo patron que
        // Empleados/Roles): la vista ya no recibe $actividades ni
        // $modificables para un @foreach de Blade — recibe el array ya
        // serializado (serialize() incluye "modificable" por fila, ver
        // canModify()) para hidratar el estado inicial y para que
        // store/update devuelvan exactamente la misma forma tras guardar
        // por AJAX, sin recargar la pagina.
        $actividadesJs = $actividades->map(fn ($a) => $this->serialize($a))->values();

        return view('personal.programacion.index', [
            'actividadesJs' => $actividadesJs,
            'empresas' => $empresas,
            'empleados' => $empleados,
            'categorias' => $categorias,
            'supervisores' => $supervisores,
            'fecha' => $date,
            'isEditable' => $this->isEditable($date),
            // Equipo/Proceso (pedido por el usuario 2026-09-18): siguen
            // siendo texto libre, no un catalogo con FK — esto solo
            // alimenta el combobox de sugerencias en la vista para evitar
            // variantes tipo "Cemento"/"CEMENTO"/"Semento" que ensucian el
            // filtrado futuro (ej. Bitacora). Nada bloquea escribir un
            // valor nuevo.
            'equipos' => $this->distinctFreeTextValues('team'),
            'procesos' => $this->distinctFreeTextValues('process'),
            'gruposResponsables' => $gruposResponsables,
            // Mapa id => horas para la tarjeta de hover sobre el nombre en
            // la tabla (seccion 14.17) — a proposito el array crudo de
            // totalPorEmpleado(), sin filtrar por elegibilidad/activo como
            // $empleados: alguien que ya aparece en una actividad de este
            // dia (persona o responsable) debe poder mostrar sus horas aqui
            // aunque ya no sea seleccionable para actividades nuevas.
            'horasAcumuladasPorId' => $horasAcumuladas,
        ]);
    }

    // Deduplica sin distinguir mayusculas/minusculas (unique() con closure
    // agrupa por la clave devuelta) para que el combobox no muestre
    // "Cemento" y "CEMENTO" como dos sugerencias distintas.
    private function distinctFreeTextValues(string $column): \Illuminate\Support\Collection
    {
        return Activity::whereNotNull($column)
            ->where($column, '!=', '')
            ->orderBy($column)
            ->pluck($column)
            ->map(fn ($v) => trim($v))
            ->filter()
            ->unique(fn ($v) => mb_strtolower($v))
            ->values();
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless(PersonalGuard::can('ver_programacion'), 403);

        $validated = $this->validated($request);

        $activity = Activity::create([
            'date' => $validated['date'],
            'company_id' => $validated['company_id'],
            'group_number' => $validated['group_number'] ?? null,
            'team' => $validated['team'] ?? null,
            'process' => $validated['process'] ?? null,
            'description' => $validated['description'],
            'scheduling_comment' => $validated['scheduling_comment'] ?? null,
            'activity_type' => $validated['activity_type'],
            'estimated_hours' => $validated['estimated_hours'] ?? null,
            'shift' => $validated['shift'],
            'responsible_employee_id' => $validated['responsible_employee_id'] ?? null,
            'created_by_employee_id' => PersonalGuard::employee()?->id,
        ]);

        $activity->personas()->sync($validated['personas'] ?? []);

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Actividad creada correctamente.',
                'activity' => $this->serialize($activity),
            ]);
        }

        return redirect()
            ->route('personal.programacion.index', ['date' => $validated['date']])
            ->with('success', 'Actividad creada correctamente.');
    }

    public function update(Request $request, Activity $activity): RedirectResponse|JsonResponse
    {
        abort_if(! $this->canModify($activity), 403);

        $validated = $this->validated($request, $activity);

        $activity->update([
            'company_id' => $validated['company_id'],
            'group_number' => $validated['group_number'] ?? null,
            'team' => $validated['team'] ?? null,
            'process' => $validated['process'] ?? null,
            'description' => $validated['description'],
            'scheduling_comment' => $validated['scheduling_comment'] ?? null,
            'activity_type' => $validated['activity_type'],
            'estimated_hours' => $validated['estimated_hours'] ?? null,
            'shift' => $validated['shift'],
            'responsible_employee_id' => $validated['responsible_employee_id'] ?? null,
        ]);

        $activity->personas()->sync($validated['personas'] ?? []);

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Actividad actualizada correctamente.',
                'activity' => $this->serialize($activity),
            ]);
        }

        return redirect()
            ->route('personal.programacion.index', ['date' => $validated['date']])
            ->with('success', 'Actividad actualizada correctamente.');
    }

    public function destroy(Request $request, Activity $activity): RedirectResponse|JsonResponse
    {
        abort_if(! $this->canModify($activity), 403);

        $date = $activity->date->toDateString();
        $id = $activity->id;
        $activity->delete();

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Actividad eliminada correctamente.',
                'id' => $id,
            ]);
        }

        return redirect()
            ->route('personal.programacion.index', ['date' => $date])
            ->with('success', 'Actividad eliminada correctamente.');
    }

    // Mismo criterio que EmployeeController::isAjaxRequest — el fetch() del
    // frontend manda Accept: application/json.
    private function isAjaxRequest(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }

    // Forma que consume la tabla reactiva Alpine de la vista (ver
    // gruposOrdenados() en programacion/index.blade.php) — misma forma en
    // la carga inicial (index()) y en cada respuesta de store/update, para
    // que el frontend pueda reemplazar/insertar la fila sin recargar.
    private function serialize(Activity $activity): array
    {
        $activity->loadMissing(['company:id,name', 'responsible:id,nickname,nombre', 'personas:id,nickname,nombre']);

        return [
            'id' => $activity->id,
            'company_id' => $activity->company_id,
            'company_name' => $activity->company->name,
            // Cast explicito a int (bug 2026-09-19): $activity->group_number
            // no esta en $casts del modelo, asi que tras store()/update()
            // (sin refresh() desde BD) conserva el tipo tal como llego en el
            // JSON del request — si el frontend mando "1" (string), este
            // metodo devolveria "1" en vez de 1, y el agrupado en JS
            // (gruposOrdenados(), que usa group_number como key de un Map)
            // trataria "1" y 1 como grupos distintos.
            'group_number' => $activity->group_number !== null ? (int) $activity->group_number : null,
            'team' => $activity->team,
            'process' => $activity->process,
            'description' => $activity->description,
            'scheduling_comment' => $activity->scheduling_comment,
            'activity_type' => $activity->activity_type,
            'estimated_hours' => $activity->estimated_hours !== null ? (float) $activity->estimated_hours : null,
            'shift' => $activity->shift,
            'responsible_employee_id' => $activity->responsible_employee_id,
            'responsible_nickname' => $activity->responsible?->nickname,
            // nombre completo (pedido 2026-09-19, seccion 14.17): alimenta
            // la tarjeta de hover sobre el nombre en la tabla — no depende
            // de que el empleado siga en la lista $empleados filtrada de
            // index() (ej. alguien inactivado despues sigue mostrandose
            // bien en actividades ya creadas).
            'responsible_nombre' => $activity->responsible?->nombre,
            'personas' => $activity->personas->map(fn ($p) => ['id' => $p->id, 'nickname' => $p->nickname, 'nombre' => $p->nombre])->values(),
            'closed' => $activity->isClosed(),
            'modificable' => $this->canModify($activity),
        ];
    }

    private function validated(Request $request, ?Activity $activity = null): array
    {
        return Validator::make($request->all(), [
            'date' => ['required', 'date'],
            // Nota: Rule::exists()->where('col', false) rompe contra Postgres
            // (bindea el booleano PHP como cadena vacia, "invalid input
            // syntax for type boolean") — se usa la forma de closure, que
            // si arma un where() normal de query builder.
            'company_id' => ['required', Rule::exists('companies', 'id')->where(fn ($q) => $q->where('archived', false))],
            'group_number' => ['nullable', 'integer', 'min:1'],
            'team' => ['nullable', 'string', 'max:150'],
            'process' => ['nullable', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:255'],
            // Contexto opcional (pedido 2026-09-24), distinto de
            // "description" (la actividad programada en si) — ver
            // migracion add_scheduling_comment_to_activities_table.
            'scheduling_comment' => ['nullable', 'string', 'max:1000'],
            'activity_type' => ['required', Rule::in(['P', 'S'])],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'shift' => ['required', Rule::in(['Diurno', 'Nocturno'])],
            // Responsable = opcional, pero si se manda uno tiene que ser un
            // empleado activo de verdad, elegible por
            // responsableEligibleRoleIds() (Subrol marcado como
            // "responsable_actividad" Y su Rol tambien — ver seccion 6 y el
            // mismo criterio que $supervisores en index() — deben coincidir
            // siempre, si no el formulario ofreceria opciones que el
            // backend rechazaria).
            'responsible_employee_id' => [
                'nullable',
                Rule::exists('employees', 'id')->where(function ($q) {
                    $q->where('activo', true)
                        ->whereIn('personal_role_id', $this->responsableEligibleRoleIds());
                }),
            ],
            'personas' => ['array'],
            'personas.*' => [Rule::exists('employees', 'id')->where(fn ($q) => $q->where('activo', true))],
        ])->after(function ($validator) use ($request, $activity) {
            $date = $request->input('date');

            // Ventana de edicion (seccion 6) — gobernada por el permiso
            // "editar_programacion_sin_limite" (Roles y permisos), ya no
            // por el nombre del rol.
            if (! PersonalGuard::can('editar_programacion_sin_limite')) {
                $permitidas = [today()->toDateString(), today()->subDay()->toDateString()];
                if (! in_array($date, $permitidas, true)) {
                    $validator->errors()->add('date', 'Solo puedes programar el día actual o el día anterior.');
                }
            }

            // Primaria unica por persona/dia (seccion 6) — el mockup no
            // validaba esto ("no validado en este mockup"), aqui si. Al
            // editar, se excluye la propia actividad del chequeo.
            if ($request->input('activity_type') === 'P' && $date) {
                $personaIds = collect($request->input('personas', []))
                    ->filter()
                    ->map(fn ($id) => (int) $id);

                if ($personaIds->isNotEmpty()) {
                    $conflictos = Activity::where('date', $date)
                        ->where('activity_type', 'P')
                        ->when($activity, fn ($q) => $q->where('id', '!=', $activity->id))
                        ->whereHas('personas', fn ($q) => $q->whereIn('employees.id', $personaIds))
                        ->with(['personas' => fn ($q) => $q->whereIn('employees.id', $personaIds)])
                        ->get();

                    $nombresConflicto = $conflictos->flatMap(fn ($a) => $a->personas->pluck('nickname'))->unique();

                    if ($nombresConflicto->isNotEmpty()) {
                        $validator->errors()->add(
                            'personas',
                            'Ya tiene otra actividad primaria ese día: ' . $nombresConflicto->implode(', ') . '.'
                        );
                    }
                }
            }
        })->validate();
    }

    public function diasConDatos(Request $request): JsonResponse
    {
        // Compartido con el calendario de Diario de Campo (misma tabla
        // activities) — basta con cualquiera de los dos permisos, no solo
        // ver_programacion, para no romper el calendario de esa pantalla.
        abort_unless(PersonalGuard::can('ver_programacion') || PersonalGuard::can('ver_diario_campo'), 403);

        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);
        $inicio = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
        $fin = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        // pluck('date') aplica el cast del modelo ('date' => Carbon) aunque
        // sea una columna sola — sin normalizar, el JSON sale como
        // timestamp completo ("2026-09-16T05:00:00.000000Z") en vez de
        // "2026-09-16", y el Set del calendario en el navegador nunca
        // matchea contra eso.
        $dias = Activity::whereBetween('date', [$inicio, $fin])
            ->distinct()
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->values();

        return response()->json(['dias' => $dias]);
    }

    // Subroles (PersonalRole) que efectivamente otorgan elegibilidad de
    // Responsable: el Subrol debe tener "responsable_actividad" activo Y
    // pertenecer a un Rol (PersonalCategory) que TAMBIEN lo tenga activo —
    // el Rol es un requisito, no una alternativa (un Subrol no puede ser
    // Responsable si su Rol no lo es). Centralizado aqui porque
    // $supervisores (index()) y la regla Rule::exists() de
    // responsible_employee_id (validated()) deben usar siempre el mismo
    // criterio — si divergieran, el formulario ofreceria opciones que el
    // backend terminaria rechazando. Un empleado sin subrol asignado
    // (personal_role_id null) nunca es elegible.
    private function responsableEligibleRoleIds()
    {
        // Centralizado en PersonalRole (seccion 14.18) porque
        // EmployeeController tambien lo necesita ahora (boton "Ver como
        // supervisor") — mismo criterio en los dos lugares.
        return PersonalRole::eligibleAsResponsableIds();
    }

    private function isEditable(string $date): bool
    {
        // "editar_programacion_sin_limite" (seccion 6: antes atado a
        // role==='supervisor', ahora a un permiso configurable desde
        // Roles y permisos) — quien no lo tiene solo puede tocar hoy/ayer.
        if (! PersonalGuard::can('editar_programacion_sin_limite')) {
            return in_array($date, [today()->toDateString(), today()->subDay()->toDateString()], true);
        }

        return true;
    }

    // Una actividad ya cerrada en Diario de Campo (seccion 2: "el
    // ADMINISTRATIVO revisa y TERMINA de llenar la actividad") deja de
    // ser modificable para quien no tiene "editar_programacion_sin_limite"
    // — mismo permiso que gobierna la ventana de edicion, ver plan de
    // Roles y permisos dinamicos.
    private function canModify(Activity $activity): bool
    {
        if ($activity->isClosed() && ! PersonalGuard::can('editar_programacion_sin_limite')) {
            return false;
        }

        return $this->isEditable($activity->date->toDateString());
    }
}
