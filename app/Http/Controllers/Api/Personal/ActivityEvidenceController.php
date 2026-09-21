<?php

namespace App\Http\Controllers\Api\Personal;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityEvidence;
use App\Models\Employee;
use App\Support\ActivityEvidencePathBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// Seccion 14.24: API real de evidencias para la app Android del
// supervisor — mismo patron de subida a R2 que
// App\Http\Controllers\Personal\ActivityEvidenceController (web, seccion
// 14.20), pero el empleado siempre es $request->user() (Sanctum), nunca
// un parametro de ruta, y show() devuelve JSON con la URL firmada en vez
// de un redirect HTTP (un cliente API pide la URL y la usa directo en su
// visor de imagen/video, no sigue redirects de servidor).
class ActivityEvidenceController extends Controller
{
    public function store(Request $request, Activity $activity): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();
        $this->authorizeAccess($employee, $activity);

        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:6'],
            'files.*' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm',
                'max:51200',
            ],
        ]);

        $existingCount = $activity->evidences()->count();
        $creadas = [];

        foreach ($validated['files'] as $index => $file) {
            $built = ActivityEvidencePathBuilder::build($activity, $file);

            $stream = fopen($file->getRealPath(), 'r');

            if ($stream === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo abrir uno de los archivos seleccionados.',
                ], 422);
            }

            try {
                Storage::disk('r2')->writeStream($built['path'], $stream, [
                    'ContentType' => $file->getMimeType(),
                ]);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if (! Storage::disk('r2')->exists($built['path'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'La subida a almacenamiento no se pudo confirmar.',
                ], 422);
            }

            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $fileType = str_starts_with($mime, 'video/') ? 'video' : 'image';

            $evidence = $activity->evidences()->create([
                'uploaded_by_employee_id' => $employee->id,
                'disk' => 'r2',
                'path' => $built['path'],
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $built['stored_name'],
                'mime_type' => $mime,
                'extension' => $built['extension'],
                'file_type' => $fileType,
                'size_bytes' => $file->getSize() ?: 0,
                'sort_order' => $existingCount + $index,
            ]);

            $creadas[] = $this->serialize($evidence, $activity);
        }

        return response()->json([
            'success' => true,
            'message' => 'Evidencia cargada correctamente.',
            'evidencias' => $creadas,
        ]);
    }

    // JSON con la URL firmada, no redirect — ver comentario de clase.
    public function show(Request $request, Activity $activity, ActivityEvidence $evidence): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();
        $this->authorizeAccess($employee, $activity, allowClosed: true);
        abort_unless((int) $evidence->activity_id === $activity->id, 404);

        $disk = Storage::disk($evidence->disk);

        if (! $disk->exists($evidence->path)) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo no existe en el almacenamiento.',
            ], 404);
        }

        $safeName = $evidence->original_name ?: $evidence->stored_name;
        $expiresAt = now()->addMinutes(10);

        try {
            $url = $disk->temporaryUrl(
                $evidence->path,
                $expiresAt,
                ['ResponseContentDisposition' => 'inline; filename="'.addslashes($safeName).'"']
            );
        } catch (\Throwable $e) {
            $url = $disk->url($evidence->path);
        }

        return response()->json([
            'success' => true,
            'url' => $url,
            'expires_at' => $expiresAt->toIso8601String(),
            'file_type' => $evidence->file_type,
            'original_name' => $evidence->original_name,
        ]);
    }

    public function destroy(Request $request, Activity $activity, ActivityEvidence $evidence): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();
        $this->authorizeAccess($employee, $activity);
        abort_unless((int) $evidence->activity_id === $activity->id, 404);

        Storage::disk($evidence->disk)->delete($evidence->path);
        $evidence->delete();

        return response()->json([
            'success' => true,
            'message' => 'Evidencia eliminada.',
        ]);
    }

    private function authorizeAccess(Employee $employee, Activity $activity, bool $allowClosed = false): void
    {
        abort_unless((int) $activity->responsible_employee_id === $employee->id, 404);

        if (! $allowClosed) {
            abort_if($activity->isClosed(), 403);
        }
    }

    private function serialize(ActivityEvidence $evidence, Activity $activity): array
    {
        return [
            'id' => $evidence->id,
            'original_name' => $evidence->original_name,
            'file_type' => $evidence->file_type,
            'show_url' => route('api.personal.actividades.evidencias.show', [
                'activity' => $activity->id,
                'evidence' => $evidence->id,
            ]),
            'delete_url' => route('api.personal.actividades.evidencias.destroy', [
                'activity' => $activity->id,
                'evidence' => $evidence->id,
            ]),
        ];
    }
}
