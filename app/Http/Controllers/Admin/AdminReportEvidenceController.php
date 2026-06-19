<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ReportDetail;
use App\Models\ReportDetailFile;
use App\Support\ReportFilePathBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class AdminReportEvidenceController extends Controller
{
    public function store(Request $request, ReportDetail $reportDetail): RedirectResponse
    {
        $user = auth()->user();

        $report = ReportDetail::with([
            'element.area',
            'element.group',
            'element.elementType',
        ])
            ->where('status', true)
            ->findOrFail($reportDetail->id);

        abort_unless(
            $this->canAccessReportByCurrentScope($user, $report),
            403,
            'No autorizado para cargar evidencia en este reporte.'
        );

        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'max:102400'],
            'evidence_kind' => ['required', 'in:' . implode(',', [
                ReportDetailFile::KIND_HALLAZGO,
                ReportDetailFile::KIND_CORRECCION,
            ])],
        ]);

        $this->ensureCanManageEvidence($user, $validated['evidence_kind']);

        $existingCount = $report->files()
            ->where('evidence_kind', $validated['evidence_kind'])
            ->count();

        foreach ($validated['files'] as $index => $file) {
            $built = ReportFilePathBuilder::build($report->element, $file, $validated['evidence_kind']);

            $stream = fopen($file->getRealPath(), 'r');

            if ($stream === false) {
                return back()->withErrors([
                    'files' => 'No se pudo abrir uno de los archivos seleccionados.',
                ]);
            }

            try {
                Storage::disk('r2')->writeStream(
                    $built['path'],
                    $stream,
                    [
                        'ContentType' => $file->getMimeType(),
                    ]
                );
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $fileType = str_starts_with($mime, 'video/') ? 'video' : 'image';

            $report->files()->create([
                'uploaded_by' => $user->id,
                'disk' => 'r2',
                'path' => $built['path'],
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $built['stored_name'],
                'mime_type' => $mime,
                'extension' => $built['extension'],
                'file_type' => $fileType,
                'evidence_kind' => $validated['evidence_kind'],
                'size_bytes' => $file->getSize() ?: 0,
                'sort_order' => $existingCount + $index,
            ]);
        }

        $kindLabel = $validated['evidence_kind'] === ReportDetailFile::KIND_CORRECCION
            ? 'corrección'
            : 'hallazgo';

        return redirect()
            ->route('admin.preventive-reports.evidence', $report->id)
            ->with('success', 'Evidencia de ' . $kindLabel . ' cargada correctamente.');
    }

    public function open(ReportDetailFile $file): RedirectResponse
    {

        $disk = Storage::disk($file->disk);

        if (!$disk->exists($file->path)) {
            abort(404, 'El archivo no existe en el almacenamiento.');
        }

        $safeName = $file->original_name ?: $file->stored_name;

        try {
            $temporaryUrl = $disk->temporaryUrl(
                $file->path,
                now()->addMinutes(10),
                [
                    'ResponseContentDisposition' => 'inline; filename="' . addslashes($safeName) . '"',
                ]
            );

            return redirect()->away($temporaryUrl);
        } catch (\Throwable $e) {
            // Fallback por si el driver no soporta temporaryUrl en tu configuración
            $url = $disk->url($file->path);

            return redirect()->away($url);
        }
    }

    public function destroy(ReportDetailFile $file): RedirectResponse
    {
        $user = auth()->user();
        $this->ensureCanManageEvidence($user, $file->evidence_kind);

        $file->loadMissing([
            'reportDetail.element.area',
            'reportDetail.element.elementType',
        ]);

        $report = $file->reportDetail;

        abort_unless(
            $report
                && (bool) $report->status
                && $this->canAccessReportByCurrentScope($user, $report),
            403,
            'No autorizado para desasociar esta evidencia.'
        );

        $file->forceFill([
            'detached_by' => $user->id,
        ])->save();

        $file->delete();

        return redirect()
            ->route('admin.preventive-reports.evidence', $report->id)
            ->with('success', 'El material fue desasociado del reporte.');
    }

    private function ensureCanManageEvidence($user, ?string $evidenceKind = null): void
    {
        $roleKey = $user->role?->key;

        if ($roleKey === 'admin_cliente') {
            abort_unless(
                $evidenceKind === ReportDetailFile::KIND_CORRECCION,
                403,
                'No tienes permisos para gestionar evidencia de hallazgo.'
            );

            return;
        }

        abort_unless(
            in_array($roleKey, ['superadmin', 'admin_global', 'admin'], true),
            403,
            'No tienes permisos para gestionar esta evidencia.'
        );
    }

    private function canAccessReportByCurrentScope($user, ReportDetail $report): bool
    {
        $clientId = (int) ($report->element?->area?->client_id ?? 0);
        $elementTypeId = (int) ($report->element?->element_type_id ?? 0);

        if ($clientId <= 0 || !in_array($clientId, $this->getAllowedClientIds($user), true)) {
            return false;
        }

        return $this->canAccessElementType($user, $clientId, $elementTypeId);
    }

    private function getAllowedClientIds($user): array
    {
        $roleKey = $user->role?->key;

        if (in_array($roleKey, ['superadmin', 'admin_global', 'observador'], true)) {
            return Client::query()
                ->where('status', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return $user->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function canAccessElementType($user, int $clientId, int $elementTypeId): bool
    {
        if (!$this->mustRestrictByElementTypes($user)) {
            return true;
        }

        return $user->allowedElementTypes()
            ->wherePivot('client_id', $clientId)
            ->where('element_types.id', $elementTypeId)
            ->exists();
    }

    private function mustRestrictByElementTypes($user): bool
    {
        return in_array($user->role?->key, ['observador_cliente'], true);
    }
}
