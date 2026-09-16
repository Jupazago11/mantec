<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Employee;
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
    // Catalogo ilustrativo de areas (seccion 6 del documento — sin captura
    // real que lo respalde todavia). Constante simple, no tabla, mismo
    // criterio que ya usaba el mockup.
    private const AREAS = ['Trituración', 'Molienda', 'Empaque', 'Talleres', 'Confiabilidad', 'Administrativa'];

    public function index(Request $request): View
    {
        abort_unless(PersonalGuard::can('ver_programacion'), 403);

        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))->toDateString()
            : today()->toDateString();

        $actividades = Activity::with(['company', 'responsible', 'personas'])
            ->where('date', $date)
            ->orderByRaw('group_number IS NULL, group_number')
            ->orderBy('id')
            ->get();

        // "Horas acumuladas este mes" sigue viniendo de datos de ejemplo
        // (misma fuente que ya usa Bitacora) — la Bitacora real, que seria
        // la fuente correcta, todavia no existe (Fase 2). Para empleados
        // reales que no estaban en el Excel de muestra, esto sale null y
        // simplemente no se muestra nada (ver horasMesTexto en la vista).
        $horasMes = include resource_path('views/preview-personal/_horas-mes-data.php');
        $horasMes = $horasMes['totales'] ?? [];

        $empleados = Employee::where('activo', true)->orderBy('nombre')->get()->map(fn ($e) => [
            'id' => $e->id,
            'nombre' => $e->nickname,
            'categoria' => $e->categoria,
            'abreviatura' => $e->abreviatura,
            'horasMes' => $horasMes[$e->nickname] ?? null,
        ])->values();

        $empresas = Company::where('archived', false)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        // Por actividad: si el actor puede editarla/eliminarla (ver
        // canModify) — una actividad ya cerrada en Diario de Campo deja de
        // ser modificable para supervisor, sin importar la ventana de
        // edicion.
        $modificables = $actividades->mapWithKeys(fn ($a) => [$a->id => $this->canModify($a)]);

        return view('personal.programacion.index', [
            'actividades' => $actividades,
            'empresas' => $empresas,
            'empleados' => $empleados,
            'areas' => self::AREAS,
            'fecha' => $date,
            'isEditable' => $this->isEditable($date),
            'modificables' => $modificables,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(PersonalGuard::can('ver_programacion'), 403);

        $validated = $this->validated($request);

        $activity = Activity::create([
            'date' => $validated['date'],
            'company_id' => $validated['company_id'],
            'group_number' => $validated['group_number'] ?? null,
            'area' => $validated['area'] ?? null,
            'team' => $validated['team'] ?? null,
            'description' => $validated['description'],
            'activity_type' => $validated['activity_type'],
            'estimated_hours' => $validated['estimated_hours'] ?? null,
            'shift' => $validated['shift'],
            'responsible_employee_id' => $validated['responsible_employee_id'] ?? null,
            'created_by_employee_id' => PersonalGuard::employee()?->id,
        ]);

        $activity->personas()->sync($validated['personas'] ?? []);

        return redirect()
            ->route('personal.programacion.index', ['date' => $validated['date']])
            ->with('success', 'Actividad creada correctamente.');
    }

    public function update(Request $request, Activity $activity): RedirectResponse
    {
        abort_if(! $this->canModify($activity), 403);

        $validated = $this->validated($request, $activity);

        $activity->update([
            'company_id' => $validated['company_id'],
            'group_number' => $validated['group_number'] ?? null,
            'area' => $validated['area'] ?? null,
            'team' => $validated['team'] ?? null,
            'description' => $validated['description'],
            'activity_type' => $validated['activity_type'],
            'estimated_hours' => $validated['estimated_hours'] ?? null,
            'shift' => $validated['shift'],
            'responsible_employee_id' => $validated['responsible_employee_id'] ?? null,
        ]);

        $activity->personas()->sync($validated['personas'] ?? []);

        return redirect()
            ->route('personal.programacion.index', ['date' => $validated['date']])
            ->with('success', 'Actividad actualizada correctamente.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        abort_if(! $this->canModify($activity), 403);

        $date = $activity->date->toDateString();
        $activity->delete();

        return redirect()
            ->route('personal.programacion.index', ['date' => $date])
            ->with('success', 'Actividad eliminada correctamente.');
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
            'area' => ['nullable', Rule::in(self::AREAS)],
            'group_number' => ['nullable', 'integer', 'min:1'],
            'team' => ['nullable', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:255'],
            'activity_type' => ['required', Rule::in(['P', 'S'])],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'shift' => ['required', Rule::in(['Diurno', 'Nocturno'])],
            'responsible_employee_id' => ['nullable', Rule::exists('employees', 'id')->where('categoria', 'Administrativos')],
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
