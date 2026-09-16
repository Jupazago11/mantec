<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Support\PersonalGuard;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FieldDiaryController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(PersonalGuard::can('ver_diario_campo'), 403);

        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))->toDateString()
            : today()->toDateString();

        $actividades = Activity::with(['company', 'responsible', 'personas', 'closedBy'])
            ->where('date', $date)
            ->orderByRaw('group_number IS NULL, group_number')
            ->orderBy('id')
            ->get();

        return view('personal.diario-campo.index', [
            'actividades' => $actividades,
            'fecha' => $date,
            // Seccion 2 del documento: el ADMINISTRATIVO cierra el Diario
            // de Campo, no el supervisor — ahora gobernado por el permiso
            // "cerrar_diario_campo" (Roles y permisos), no por el nombre
            // del rol.
            'puedeEditar' => PersonalGuard::can('cerrar_diario_campo'),
        ]);
    }

    public function close(Request $request, Activity $activity): RedirectResponse
    {
        // Autorizacion real en servidor, no solo ocultar el boton en la
        // vista (AGENTS.md seccion 6).
        abort_unless(PersonalGuard::can('cerrar_diario_campo'), 403);

        $validated = $request->validate([
            'process' => ['nullable', 'string', 'max:150'],
            'executed_description' => ['nullable', 'string', 'max:2000'],
            'corrected_hours' => ['nullable', 'numeric', 'min:0'],
            'comments' => ['nullable', 'string', 'max:2000'],
            'zcom' => ['nullable', 'string', 'max:50'],
            'line_code' => ['nullable', 'string', 'max:50'],
            'ot_sap' => ['nullable', 'string', 'max:50'],
            'acta_entrega' => ['nullable', 'string', 'max:50'],
            'we_code' => ['nullable', 'string', 'max:50'],
        ]);

        $activity->update([
            ...$validated,
            'closed_by_employee_id' => PersonalGuard::employee()?->id,
            'closed_at' => now(),
        ]);

        return redirect()
            ->route('personal.diario-campo.index', ['date' => $activity->date->toDateString()])
            ->with('success', 'Actividad cerrada correctamente.');
    }
}
