<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Support\PersonalGuard;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class FieldDiaryController extends Controller
{
    // Campos editables en linea (seccion 14.22, pedido 2026-09-19: "que
    // las filas sean editables como en reportes preventivos") — mismo
    // conjunto que antes vivia en el modal de "cierre", menos
    // corrected_hours (validacion numerica separada abajo) y
    // executed_description (seccion 14.23: el comentario del supervisor
    // -"comments"- ES la actividad ejecutada, ya no hay celda separada
    // para executed_description en esta pantalla).
    private const EDITABLE_TEXT_FIELDS = [
        'process' => 150,
        'comments' => 2000,
        'zcom' => 50,
        'line_code' => 50,
        'ot_sap' => 50,
        'acta_entrega' => 50,
        'we_code' => 50,
    ];

    public function index(Request $request): View
    {
        abort_unless(PersonalGuard::can('ver_diario_campo'), 403);

        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))->toDateString()
            : today()->toDateString();

        $actividades = Activity::with(['company', 'responsible', 'personas', 'closedBy', 'employeeHours'])
            ->where('date', $date)
            ->orderByRaw('group_number IS NULL, group_number')
            ->orderBy('id')
            ->get();

        return view('personal.diario-campo.index', [
            'actividades' => $actividades,
            'fecha' => $date,
            // Seccion 2 del documento: el ADMINISTRATIVO edita el Diario
            // de Campo, no el supervisor — gobernado por el permiso
            // "cerrar_diario_campo" (Roles y permisos), no por el nombre
            // del rol. El nombre del permiso quedo de cuando esto
            // "cerraba" la actividad (seccion 14.18); desde 14.21/14.22
            // ya no existe ese concepto aqui, solo edicion de campos.
            'puedeEditar' => PersonalGuard::can('cerrar_diario_campo'),
        ]);
    }

    // Edicion en linea, celda por celda (seccion 14.22) — reemplaza el
    // modal de "cierre" (seccion 14.21 ya le habia quitado el badge de
    // estado). Ya NO marca la actividad como cerrada (closed_at):
    // confirmado con el usuario que editar estos campos no debe bloquear
    // Programacion ni "Ver como supervisor" — si en el futuro se necesita
    // un cierre administrativo real, sera una accion aparte, no ligada a
    // editar estos campos. Mismo patron que
    // AdminPreventiveReportController::inlineUpdate().
    public function inlineUpdate(Request $request, Activity $activity): JsonResponse
    {
        abort_unless(PersonalGuard::can('cerrar_diario_campo'), 403);

        $fields = array_merge(array_keys(self::EDITABLE_TEXT_FIELDS), ['corrected_hours']);

        $validated = $request->validate([
            'field' => ['required', 'string', 'in:'.implode(',', $fields)],
            'value' => ['nullable', 'string', 'max:2000'],
        ]);

        $field = $validated['field'];
        $value = isset($validated['value']) ? trim((string) $validated['value']) : null;
        $value = $value === '' ? null : $value;

        if ($field === 'corrected_hours') {
            if ($value !== null && ! is_numeric($value)) {
                throw ValidationException::withMessages(['value' => 'Las horas corregidas deben ser un número.']);
            }
            if ($value !== null && (float) $value < 0) {
                throw ValidationException::withMessages(['value' => 'Las horas corregidas no pueden ser negativas.']);
            }
        } else {
            $maxLength = self::EDITABLE_TEXT_FIELDS[$field];
            if ($value !== null && mb_strlen($value) > $maxLength) {
                throw ValidationException::withMessages(['value' => 'Este campo es demasiado largo.']);
            }
        }

        $activity->update([$field => $value]);

        return response()->json([
            'success' => true,
            'field' => $field,
            'value' => $activity->{$field} ?? '',
            'message' => 'Campo actualizado correctamente.',
        ]);
    }
}
