<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Component;
use App\Models\Condition;
use App\Models\Diagnostic;
use App\Models\Element;
use App\Models\ReportDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InspectorSyncController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'local_report_id' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'element_id' => ['required', 'integer', 'exists:elements,id'],
            'component_id' => ['required', 'integer', 'exists:components,id'],
            'diagnostic_id' => ['required', 'integer', 'exists:diagnostics,id'],
            'condition_id' => ['required', 'integer', 'exists:conditions,id'],
            'recommendation' => ['nullable', 'string'],
            'week' => ['required', 'integer', 'min:1', 'max:53'],
            'year' => ['required', 'integer', 'min:2020'],
            'execution_date' => ['required', 'date'],
            'is_belt_change' => ['nullable', 'boolean'],
        ]);

        $user = Auth::user();

        $area = Area::findOrFail($validated['area_id']);
        $element = Element::findOrFail($validated['element_id']);
        $component = Component::findOrFail($validated['component_id']);
        $diagnostic = Diagnostic::findOrFail($validated['diagnostic_id']);
        $condition = Condition::findOrFail($validated['condition_id']);

        if ((int) $area->client_id !== (int) $validated['client_id']) {
            return response()->json([
                'success' => false,
                'message' => 'El área no pertenece al cliente indicado.',
            ], 422);
        }

        if ((int) $element->area_id !== (int) $area->id) {
            return response()->json([
                'success' => false,
                'message' => 'El activo no pertenece al área indicada.',
            ], 422);
        }

        if (!$element->components()->where('components.id', $component->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'El componente no pertenece al activo.',
            ], 422);
        }

        if (!$component->diagnostics()->where('diagnostics.id', $diagnostic->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'El diagnóstico no pertenece al componente.',
            ], 422);
        }

        if ((int) $condition->client_id !== (int) $validated['client_id']) {
            return response()->json([
                'success' => false,
                'message' => 'La condición no pertenece al cliente.',
            ], 422);
        }

        $existing = ReportDetail::query()
            ->where('user_id', $user->id)
            ->where('element_id', $validated['element_id'])
            ->where('component_id', $validated['component_id'])
            ->where('diagnostic_id', $validated['diagnostic_id'])
            ->where('week', $validated['week'])
            ->where('year', $validated['year'])
            ->first();

        if ($existing) {
            $incomingRecommendation = trim((string) ($validated['recommendation'] ?? ''));
            $currentRecommendation = trim((string) ($existing->recommendation ?? ''));

            $finalRecommendation = $currentRecommendation;

            if ($incomingRecommendation !== '') {
                $existingLines = collect(preg_split("/\r\n|\n|\r/", $currentRecommendation))
                    ->map(fn ($line) => trim($line))
                    ->filter()
                    ->values();

                if (!$existingLines->contains($incomingRecommendation)) {
                    $finalRecommendation = $currentRecommendation === ''
                        ? $incomingRecommendation
                        : $currentRecommendation . PHP_EOL . $incomingRecommendation;
                }
            }

            $existing->update([
                'condition_id' => $validated['condition_id'],
                'recommendation' => $finalRecommendation !== '' ? $finalRecommendation : null,
                'is_belt_change' => $validated['is_belt_change'] ?? null,
                'execution_date' => $validated['execution_date'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'El reporte ya existía y fue actualizado correctamente.',
                'server_report_detail_id' => $existing->id,
                'duplicated' => true,
            ]);
        }


        $reportDetail = DB::transaction(function () use ($validated, $user) {
            return ReportDetail::create([
                'report_id' => null,
                'user_id' => $user->id,
                'element_id' => $validated['element_id'],
                'component_id' => $validated['component_id'],
                'diagnostic_id' => $validated['diagnostic_id'],
                'year' => $validated['year'],
                'week' => $validated['week'],
                'condition_id' => $validated['condition_id'],
                'observation' => null,
                'recommendation' => $validated['recommendation'] ?? null,
                'orden' => null,
                'aviso' => null,
                'is_belt_change' => $validated['is_belt_change'] ?? null,
                'execution_status_id' => null,
                'execution_date' => $validated['execution_date'],
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Reporte sincronizado correctamente.',
            'server_report_detail_id' => $reportDetail->id,
            'duplicated' => false,
        ]);
    }
}
