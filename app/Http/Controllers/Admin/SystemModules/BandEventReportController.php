<?php

namespace App\Http\Controllers\Admin\SystemModules;

use App\Http\Controllers\Controller;
use App\Models\BandEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BandEventReportController extends Controller
{
    public function update(Request $request, int $element, int $event): JsonResponse
    {
        $user = auth()->user();

        abort_unless(
            in_array($user?->role?->key, ['superadmin', 'admin_global'], true),
            403,
            'No tienes permisos para editar reportes oficiales.'
        );

        $bandEvent = BandEvent::query()
            ->where('element_id', $element)
            ->where('id', $event)
            ->where('status', true)
            ->firstOrFail();

        $validated = $this->validateEventByType($request, $bandEvent->type, $bandEvent);

        DB::transaction(function () use ($bandEvent, $validated) {
            $bandEvent->update(array_merge($validated, [
                'updated_by' => auth()->id(),
            ]));
        });

        return response()->json(array_merge([
            'success' => true,
            'message' => 'Evento actualizado correctamente.',
            'report' => $this->serializeBandEvent($bandEvent->fresh()),
        ], $this->bandEventPayload($element)));
    }

    public function destroy(int $element, int $event): JsonResponse
    {
        $user = auth()->user();

        abort_unless(
            in_array($user?->role?->key, ['superadmin', 'admin_global'], true),
            403,
            'No tienes permisos para eliminar reportes oficiales.'
        );

        $bandEvent = BandEvent::query()
            ->where('element_id', $element)
            ->where('id', $event)
            ->where('status', true)
            ->firstOrFail();

        if ($bandEvent->type === 'band') {
            $childrenCount = BandEvent::query()
                ->where('element_id', $element)
                ->where('parent_id', $bandEvent->id)
                ->where('status', true)
                ->count();

            if ($childrenCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar una banda padre que tiene vulcanizados o cambios de tramo asociados.',
                ], 422);
            }
        }

        DB::transaction(function () use ($bandEvent) {
            $bandEvent->update([
                'status' => false,
                'updated_by' => auth()->id(),
            ]);
        });

        return response()->json(array_merge([
            'success' => true,
            'message' => 'Evento eliminado correctamente.',
            'deleted_id' => $event,
        ], $this->bandEventPayload($element)));
    }

    private function validateEventByType(Request $request, string $type, BandEvent $event): array
    {
        $base = [
            'parent_id' => ['nullable', 'integer', 'exists:band_events,id'],
            'report_date' => ['required', 'date'],
            'observation' => ['nullable', 'string'],

            'temperature' => ['nullable', 'numeric', 'min:0'],
            'pressure' => ['nullable', 'numeric', 'min:0'],
            'time' => ['nullable', 'numeric', 'min:0'],
            'cooling_time' => ['nullable', 'numeric', 'min:0'],

            'motor_current' => ['nullable', 'numeric', 'min:0'],
            'alignment' => ['nullable', 'string', 'max:255'],
            'material_accumulation' => ['nullable', 'string', 'max:255'],
            'guard' => ['nullable', 'string', 'max:255'],
            'idler_condition' => ['nullable', 'string', 'max:255'],

            'same_reference' => ['nullable', 'boolean'],
        ];

        if ($type !== 'band') {
            $base['parent_id'] = ['required', 'integer', 'exists:band_events,id'];
        }

        if ($type === 'band') {
            return $request->validate(array_merge($base, [
                'parent_id' => ['nullable'],

                'brand' => ['required', 'string', 'max:255'],
                'total_thickness' => ['required', 'numeric', 'min:0'],
                'top_cover_thickness' => ['required', 'numeric', 'min:0'],
                'bottom_cover_thickness' => ['required', 'numeric', 'min:0'],
                'plies' => ['required', 'integer', 'min:1'],
                'width' => ['required', 'numeric', 'min:0'],
                'length' => ['required', 'numeric', 'min:0'],
                'roll_count' => ['required', 'integer', 'min:1'],

                'temperature' => ['required', 'numeric', 'min:0'],
                'pressure' => ['required', 'numeric', 'min:0'],
                'time' => ['required', 'numeric', 'min:0'],
                'cooling_time' => ['required', 'numeric', 'min:0'],

                'motor_current' => ['required', 'numeric', 'min:0'],
                'alignment' => ['required', 'string', 'max:255'],
                'material_accumulation' => ['required', 'string', 'max:255'],
                'guard' => ['required', 'string', 'max:255'],
                'idler_condition' => ['required', 'string', 'max:255'],
            ]));
        }

        if ($type === 'vulcanization') {
            return $request->validate(array_merge($base, [
                'brand' => ['nullable'],
                'total_thickness' => ['nullable'],
                'top_cover_thickness' => ['nullable'],
                'bottom_cover_thickness' => ['nullable'],
                'plies' => ['nullable'],
                'width' => ['nullable'],
                'length' => ['nullable'],
                'roll_count' => ['nullable'],

                'temperature' => ['required', 'numeric', 'min:0'],
                'pressure' => ['required', 'numeric', 'min:0'],
                'time' => ['required', 'numeric', 'min:0'],
                'cooling_time' => ['required', 'numeric', 'min:0'],
            ]));
        }

        if ($type === 'section_change') {
            return $request->validate(array_merge($base, [
                'section_brand' => ['required', 'string', 'max:255'],
                'section_thickness' => ['required', 'numeric', 'min:0'],
                'section_plies' => ['required', 'integer', 'min:1'],
                'section_length' => ['required', 'numeric', 'min:0'],
                'section_width' => ['required', 'numeric', 'min:0'],

                'temperature' => ['required', 'numeric', 'min:0'],
                'pressure' => ['required', 'numeric', 'min:0'],
                'time' => ['required', 'numeric', 'min:0'],
                'cooling_time' => ['required', 'numeric', 'min:0'],

                'motor_current' => ['required', 'numeric', 'min:0'],
                'alignment' => ['required', 'string', 'max:255'],
                'material_accumulation' => ['required', 'string', 'max:255'],
                'guard' => ['required', 'string', 'max:255'],
                'idler_condition' => ['required', 'string', 'max:255'],
            ]));
        }

        abort(422, 'Tipo de evento inválido.');
    }

    private function bandEventPayload(int $elementId): array
    {
        $latestReport = BandEvent::query()
            ->where('element_id', $elementId)
            ->where('status', true)
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->first();

        $activeBand = BandEvent::query()
            ->where('element_id', $elementId)
            ->where('type', 'band')
            ->where('status', true)
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->first();

        $bands = BandEvent::query()
            ->where('element_id', $elementId)
            ->where('type', 'band')
            ->where('status', true)
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->get();

        $children = BandEvent::query()
            ->where('element_id', $elementId)
            ->whereIn('type', ['vulcanization', 'section_change'])
            ->where('status', true)
            ->orderBy('report_date')
            ->orderBy('id')
            ->get()
            ->groupBy('parent_id');

        $historicalTree = $bands->map(function (BandEvent $band) use ($children) {
            $data = $this->serializeBandEvent($band);
            $data['children'] = ($children->get($band->id) ?? collect())
                ->map(fn (BandEvent $child) => $this->serializeBandEvent($child))
                ->values()
                ->all();

            return $data;
        })->values()->all();

        return [
            'latest_report' => $this->serializeBandEvent($latestReport),
            'active_band' => $this->serializeBandEvent($activeBand),
            'bands' => $bands->map(fn (BandEvent $band) => $this->serializeBandEvent($band))->values()->all(),
            'historical_tree' => $historicalTree,
        ];
    }

    private function serializeBandEvent(?BandEvent $event): ?array
    {
        if (!$event) {
            return null;
        }

        return [
            'id' => $event->id,
            'element_id' => $event->element_id,
            'parent_id' => $event->parent_id,
            'type' => $event->type,

            'brand' => $event->brand,
            'total_thickness' => $event->total_thickness,
            'top_cover_thickness' => $event->top_cover_thickness,
            'bottom_cover_thickness' => $event->bottom_cover_thickness,
            'plies' => $event->plies,
            'width' => $event->width,
            'length' => $event->length,
            'roll_count' => $event->roll_count,

            'temperature' => $event->temperature,
            'pressure' => $event->pressure,
            'time' => $event->time,
            'cooling_time' => $event->cooling_time,

            'motor_current' => $event->motor_current,
            'alignment' => $event->alignment,
            'material_accumulation' => $event->material_accumulation,
            'guard' => $event->guard,
            'idler_condition' => $event->idler_condition,

            'section_brand' => $event->section_brand,
            'section_thickness' => $event->section_thickness,
            'section_plies' => $event->section_plies,
            'section_length' => $event->section_length,
            'section_width' => $event->section_width,

            'same_reference' => (bool) $event->same_reference,
            'observation' => $event->observation,
            'report_date' => optional($event->report_date)?->format('Y-m-d'),
            'published_at' => optional($event->published_at)?->format('Y-m-d H:i:s'),
            'status' => (bool) $event->status,
        ];
    }
}