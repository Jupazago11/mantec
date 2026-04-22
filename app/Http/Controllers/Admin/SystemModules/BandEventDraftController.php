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

        $data = $this->validateByType($request, $type);

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
}