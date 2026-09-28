<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\FieldDiary\FieldDiaryFullView;
use App\Support\PersonalGuard;
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
    // para executed_description en esta pantalla). 2026-09-28: se suman
    // "team" (Equipo) y "description" (Actividad programada) — el usuario
    // pidio poder corregirlos tambien desde aqui; description no puede
    // quedar vacia (mismo "required" que en Programacion).
    private const EDITABLE_TEXT_FIELDS = [
        'team' => 150,
        'process' => 150,
        'description' => 255,
        'comments' => 2000,
        'zcom' => 50,
        'line_code' => 50,
        'ot_sap' => 50,
        'acta_entrega' => 50,
        'we_code' => 50,
    ];

    // Una sola vista para el Diario de Campo (pedido 2026-09-28): "Por dia"
    // (por defecto hoy, sin columna Fecha, navegacion dia a dia) o "Todas
    // las fechas" (?vista=todas). Misma tabla, mismos filtros tipo Excel,
    // misma paginacion de 100 y misma edicion en linea en ambos modos —
    // logica en FieldDiaryFullView. Reemplaza la vista diaria anterior y la
    // "Vista completa" separada (/completo) del mismo dia.
    public function index(Request $request, FieldDiaryFullView $vista): View
    {
        abort_unless(PersonalGuard::can('ver_diario_campo'), 403);

        $modoTodas = $request->query('vista') === 'todas';
        $dia = $modoTodas
            ? null
            : (FieldDiaryFullView::normalizeDate($request->query('date')) ?? today()->toDateString());

        return view('personal.diario-campo.index', $vista->build($request, $dia) + [
            'columnas' => $modoTodas
                ? FieldDiaryFullView::COLUMNS
                : array_diff_key(FieldDiaryFullView::COLUMNS, ['fecha' => true]),
            'modoTodas' => $modoTodas,
            'dia' => $dia,
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
            // Horas por persona en un dia (revision 2026-09-28): sin tope, un
            // valor >= 1000 desbordaba decimal(5,2) con un error 500.
            if ($value !== null && (float) $value > 24) {
                throw ValidationException::withMessages(['value' => 'Las horas corregidas no pueden superar 24.']);
            }
        } else {
            if ($field === 'description' && $value === null) {
                throw ValidationException::withMessages(['value' => 'La actividad programada no puede quedar vacía.']);
            }
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
