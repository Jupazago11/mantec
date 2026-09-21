<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityEvidence;
use App\Models\Employee;
use App\Support\ActivityEvidencePathBuilder;
use App\Support\PersonalGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Evidencias (foto/video) del registro del supervisor en "Ver como"
 * (seccion 7 / 14.18, resuelto en 14.20). Mismo patron de autorizacion que
 * SupervisorViewController::store(): solo superadmin, la actividad debe ser
 * realmente del empleado elegido, y no se puede tocar si ya esta cerrada en
 * Diario de Campo.
 */
class ActivityEvidenceController extends Controller
{
    public function store(Request $request, Employee $employee, Activity $activity): JsonResponse
    {
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

            $creadas[] = $this->serialize($evidence, $employee, $activity);
        }

        return response()->json([
            'success' => true,
            'message' => 'Evidencia cargada correctamente.',
            'evidencias' => $creadas,
        ]);
    }

    public function open(Employee $employee, Activity $activity, ActivityEvidence $evidence): RedirectResponse
    {
        $this->authorizeAccess($employee, $activity, allowClosed: true);
        abort_unless((int) $evidence->activity_id === $activity->id, 404);

        $disk = Storage::disk($evidence->disk);

        if (! $disk->exists($evidence->path)) {
            abort(404, 'El archivo no existe en el almacenamiento.');
        }

        $safeName = $evidence->original_name ?: $evidence->stored_name;

        try {
            $temporaryUrl = $disk->temporaryUrl(
                $evidence->path,
                now()->addMinutes(10),
                ['ResponseContentDisposition' => 'inline; filename="'.addslashes($safeName).'"']
            );

            return redirect()->away($temporaryUrl);
        } catch (\Throwable $e) {
            return redirect()->away($disk->url($evidence->path));
        }
    }

    public function destroy(Employee $employee, Activity $activity, ActivityEvidence $evidence): JsonResponse
    {
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
        abort_unless(PersonalGuard::isSuperadmin(), 403);
        abort_unless((int) $activity->responsible_employee_id === $employee->id, 404);

        if (! $allowClosed) {
            abort_if($activity->isClosed(), 403);
        }
    }

    private function serialize(ActivityEvidence $evidence, Employee $employee, Activity $activity): array
    {
        return [
            'id' => $evidence->id,
            'original_name' => $evidence->original_name,
            'file_type' => $evidence->file_type,
            'open_url' => route('personal.ver-como.evidencias.open', [
                'employee' => $employee->id,
                'activity' => $activity->id,
                'evidence' => $evidence->id,
            ]),
            'delete_url' => route('personal.ver-como.evidencias.destroy', [
                'employee' => $employee->id,
                'activity' => $activity->id,
                'evidence' => $evidence->id,
            ]),
        ];
    }
}
