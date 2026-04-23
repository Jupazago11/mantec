<?php

namespace App\Http\Controllers\Admin\SystemModules;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BandEvent;
use App\Models\BandEventDraft;

class BandEventDraftController extends Controller
{
    // =========================
    // CREAR / OBTENER DRAFT
    // =========================
    public function create(Request $request, $elementId)
    {
        $type = $request->validate([
            'type' => 'required|in:band,vulcanization,section_change'
        ])['type'];

        $draft = BandEventDraft::firstOrCreate(
            [
                'element_id' => $elementId,
                'type' => $type
            ],
            [
                'created_by' => auth()->id()
            ]
        );

        return response()->json([
            'success' => true,
            'draft' => $draft
        ]);
    }

    // =========================
    // ACTUALIZAR DRAFT
    // =========================
    public function update(Request $request, $elementId)
    {
        $type = $request->input('type');

        $draft = BandEventDraft::where('element_id', $elementId)
            ->where('type', $type)
            ->firstOrFail();

        $data = $this->validateDraftByType($request);

        $draft->update(array_merge($data, [
            'updated_by' => auth()->id()
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Borrador guardado correctamente.',
            'draft' => $draft->fresh()
        ]);
    }

    // =========================
    // PUBLICAR
    // =========================
    public function publish(Request $request, $elementId)
    {
        $type = $request->input('type');

        $draft = BandEventDraft::where('element_id', $elementId)
            ->where('type', $type)
            ->firstOrFail();

        $request->validate([
            'report_date' => 'required|date',
        ]);

        $this->validatePublishDraftData($draft, $type);

        // determinar banda activa si es hijo
        $parentId = null;

        if ($type !== 'band') {
            $parentId = $draft->parent_id;

            if (!$parentId) {
                $parentId = BandEvent::where('element_id', $elementId)
                    ->where('type', 'band')
                    ->where('status', true)
                    ->orderByDesc('report_date')
                    ->value('id');
            }
        }

        $event = BandEvent::create([
            'element_id' => $elementId,
            'type' => $type,
            'parent_id' => $parentId,
            'report_date' => $request->report_date,

            // REFERENCIA BANDA
            'brand' => $draft->brand,
            'total_thickness' => $draft->total_thickness,
            'top_cover_thickness' => $draft->top_cover_thickness,
            'bottom_cover_thickness' => $draft->bottom_cover_thickness,
            'plies' => $draft->plies,
            'width' => $draft->width,
            'length' => $draft->length,
            'roll_count' => $draft->roll_count,

            // VULCANIZADO
            'temperature' => $draft->temperature,
            'pressure' => $draft->pressure,
            'time' => $draft->time,
            'cooling_time' => $draft->cooling_time,

            // ENTREGA EQUIPO
            'motor_current' => $draft->motor_current,
            'alignment' => $draft->alignment,
            'material_accumulation' => $draft->material_accumulation,
            'guard' => $draft->guard,
            'idler_condition' => $draft->idler_condition,

            // CAMBIO TRAMO
            'section_brand' => $draft->section_brand,
            'section_thickness' => $draft->section_thickness,
            'section_plies' => $draft->section_plies,
            'section_length' => $draft->section_length,
            'section_width' => $draft->section_width,

            // LÓGICA
            'same_reference' => $draft->same_reference,

            // COMUNES
            'observation' => $draft->observation,

            // CONTROL
            'created_by' => auth()->id(),
            'published_at' => now()
        ]);

        // eliminar draft
        $draft->delete();

        return response()->json([
            'success' => true,
            'report' => $event
        ]);
    }


    private function validateDraftByType(Request $request): array
    {
        return $request->validate([
            'parent_id' => 'nullable|integer|exists:band_events,id',
            'report_date' => 'nullable|date',
            'observation' => 'nullable|string',

            // REFERENCIA BANDA
            'brand' => 'nullable|string|max:255',
            'total_thickness' => 'nullable|numeric|min:0',
            'top_cover_thickness' => 'nullable|numeric|min:0',
            'bottom_cover_thickness' => 'nullable|numeric|min:0',
            'plies' => 'nullable|integer|min:1',
            'width' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'roll_count' => 'nullable|integer|min:1',

            // VULCANIZADO
            'temperature' => 'nullable|numeric|min:0',
            'pressure' => 'nullable|numeric|min:0',
            'time' => 'nullable|numeric|min:0',
            'cooling_time' => 'nullable|numeric|min:0',

            // ENTREGA EQUIPO
            'motor_current' => 'nullable|numeric|min:0',
            'alignment' => 'nullable|string|max:255',
            'material_accumulation' => 'nullable|string|max:255',
            'guard' => 'nullable|string|max:255',
            'idler_condition' => 'nullable|string|max:255',

            // CAMBIO TRAMO
            'section_brand' => 'nullable|string|max:255',
            'section_thickness' => 'nullable|numeric|min:0',
            'section_plies' => 'nullable|integer|min:1',
            'section_length' => 'nullable|numeric|min:0',
            'section_width' => 'nullable|numeric|min:0',

            // LÓGICA
            'same_reference' => 'nullable|boolean',
        ]);
    }
    // =========================
    // VALIDACIONES POR TIPO
    // =========================
    private function validateByType(Request $request, $type)
    {
        $base = [
            'parent_id' => 'nullable|integer|exists:band_events,id',
            'report_date' => 'nullable|date',
            'observation' => 'nullable|string',

            // VULCANIZADO
            'temperature' => 'nullable|numeric',
            'pressure' => 'nullable|numeric',
            'time' => 'nullable|numeric',
            'cooling_time' => 'nullable|numeric',

            // ENTREGA EQUIPO
            'motor_current' => 'nullable|numeric',
            'alignment' => 'nullable|string|max:255',
            'material_accumulation' => 'nullable|string|max:255',
            'guard' => 'nullable|string|max:255',
            'idler_condition' => 'nullable|string|max:255',

            // LÓGICA
            'same_reference' => 'nullable|boolean',
        ];

        switch ($type) {

            case 'band':
                return $request->validate(array_merge($base, [
                    'brand' => 'required|string|max:255',
                    'total_thickness' => 'required|numeric|min:0',
                    'top_cover_thickness' => 'required|numeric|min:0',
                    'bottom_cover_thickness' => 'required|numeric|min:0',
                    'plies' => 'required|integer|min:1',
                    'width' => 'required|numeric|min:0',
                    'length' => 'required|numeric|min:0',
                    'roll_count' => 'required|integer|min:1',
                ]));

            case 'vulcanization':
                return $request->validate(array_merge($base, [
                    'temperature' => 'required|numeric|min:0',
                    'pressure' => 'required|numeric|min:0',
                    'time' => 'required|numeric|min:0',
                    'cooling_time' => 'required|numeric|min:0',
                ]));

            case 'section_change':
                return $request->validate(array_merge($base, [
                    'temperature' => 'required|numeric|min:0',
                    'pressure' => 'required|numeric|min:0',
                    'time' => 'required|numeric|min:0',
                    'cooling_time' => 'required|numeric|min:0',

                    'motor_current' => 'required|numeric|min:0',
                    'alignment' => 'required|string|max:255',
                    'material_accumulation' => 'required|string|max:255',
                    'guard' => 'required|string|max:255',
                    'idler_condition' => 'required|string|max:255',

                    'section_brand' => 'required|string|max:255',
                    'section_thickness' => 'required|numeric|min:0',
                    'section_plies' => 'required|integer|min:1',
                    'section_length' => 'required|numeric|min:0',
                    'section_width' => 'required|numeric|min:0',
                ]));
        }

        abort(422, 'Tipo de evento inválido.');
    }

    private function validatePublishDraftData(BandEventDraft $draft, string $type): void
    {
        $errors = [];

        if (!$draft->report_date) {
            $errors['report_date'][] = 'La fecha del reporte es obligatoria.';
        }

        if ($type === 'band') {
            if (!$draft->brand) $errors['brand'][] = 'El campo marca es obligatorio.';
            if ($draft->total_thickness === null || $draft->total_thickness === '') $errors['total_thickness'][] = 'El campo espesor total es obligatorio.';
            if ($draft->top_cover_thickness === null || $draft->top_cover_thickness === '') $errors['top_cover_thickness'][] = 'El campo espesor cubierta superior es obligatorio.';
            if ($draft->bottom_cover_thickness === null || $draft->bottom_cover_thickness === '') $errors['bottom_cover_thickness'][] = 'El campo espesor cubierta inferior es obligatorio.';
            if ($draft->plies === null || $draft->plies === '') $errors['plies'][] = 'El campo lonas es obligatorio.';
            if ($draft->width === null || $draft->width === '') $errors['width'][] = 'El campo ancho es obligatorio.';
            if ($draft->length === null || $draft->length === '') $errors['length'][] = 'El campo longitud es obligatorio.';
            if ($draft->roll_count === null || $draft->roll_count === '') $errors['roll_count'][] = 'El campo cantidad de rollos es obligatorio.';
            if ($draft->temperature === null || $draft->temperature === '') $errors['temperature'][] = 'El campo temperatura es obligatorio.';
            if ($draft->pressure === null || $draft->pressure === '') $errors['pressure'][] = 'El campo presión es obligatorio.';
            if ($draft->time === null || $draft->time === '') $errors['time'][] = 'El campo tiempo de vulcanizado es obligatorio.';
            if ($draft->cooling_time === null || $draft->cooling_time === '') $errors['cooling_time'][] = 'El campo tiempo de enfriamiento es obligatorio.';
            if ($draft->motor_current === null || $draft->motor_current === '') $errors['motor_current'][] = 'El campo corriente motor es obligatorio.';
            if (!$draft->alignment) $errors['alignment'][] = 'El campo alineación es obligatorio.';
            if (!$draft->material_accumulation) $errors['material_accumulation'][] = 'El campo material acumulado es obligatorio.';
            if (!$draft->guard) $errors['guard'][] = 'El campo guardilña es obligatorio.';
            if (!$draft->idler_condition) $errors['idler_condition'][] = 'El campo rodillería es obligatorio.';
        }

        if ($type === 'vulcanization') {
            if ($draft->temperature === null || $draft->temperature === '') $errors['temperature'][] = 'El campo temperatura es obligatorio.';
            if ($draft->pressure === null || $draft->pressure === '') $errors['pressure'][] = 'El campo presión es obligatorio.';
            if ($draft->time === null || $draft->time === '') $errors['time'][] = 'El campo tiempo de vulcanizado es obligatorio.';
            if ($draft->cooling_time === null || $draft->cooling_time === '') $errors['cooling_time'][] = 'El campo tiempo de enfriamiento es obligatorio.';
        }

        if ($type === 'section_change') {
            if ($draft->temperature === null || $draft->temperature === '') $errors['temperature'][] = 'El campo temperatura es obligatorio.';
            if ($draft->pressure === null || $draft->pressure === '') $errors['pressure'][] = 'El campo presión es obligatorio.';
            if ($draft->time === null || $draft->time === '') $errors['time'][] = 'El campo tiempo de vulcanizado es obligatorio.';
            if ($draft->cooling_time === null || $draft->cooling_time === '') $errors['cooling_time'][] = 'El campo tiempo de enfriamiento es obligatorio.';
            if ($draft->motor_current === null || $draft->motor_current === '') $errors['motor_current'][] = 'El campo corriente motor es obligatorio.';
            if (!$draft->alignment) $errors['alignment'][] = 'El campo alineación es obligatorio.';
            if (!$draft->material_accumulation) $errors['material_accumulation'][] = 'El campo material acumulado es obligatorio.';
            if (!$draft->guard) $errors['guard'][] = 'El campo guardilña es obligatorio.';
            if (!$draft->idler_condition) $errors['idler_condition'][] = 'El campo rodillería es obligatorio.';
            if (!$draft->section_brand) $errors['section_brand'][] = 'El campo marca del tramo es obligatorio.';
            if ($draft->section_thickness === null || $draft->section_thickness === '') $errors['section_thickness'][] = 'El campo espesor del tramo es obligatorio.';
            if ($draft->section_plies === null || $draft->section_plies === '') $errors['section_plies'][] = 'El campo lonas del tramo es obligatorio.';
            if ($draft->section_length === null || $draft->section_length === '') $errors['section_length'][] = 'El campo longitud del tramo es obligatorio.';
            if ($draft->section_width === null || $draft->section_width === '') $errors['section_width'][] = 'El campo ancho del tramo es obligatorio.';
        }

        if (!empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }
}