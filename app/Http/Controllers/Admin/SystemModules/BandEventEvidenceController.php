<?php

namespace App\Http\Controllers\Admin\SystemModules;

use App\Http\Controllers\Controller;
use App\Models\BandEvent;
use App\Models\BandEventDraft;
use App\Models\BandEventDraftEvidence;
use App\Models\BandEventEvidence;
use App\Models\ClientElementTypeModule;
use App\Models\Element;
use App\Models\SystemModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BandEventEvidenceController extends Controller
{
    public function uploadDraft(Request $request, int $element): JsonResponse
    {
        $measurementElement = Element::query()->findOrFail($element);
        $this->ensureMeasurementCreationEnabled($measurementElement);

        $validated = $request->validate([
            'type' => ['required', 'in:band,vulcanization,section_change'],
            'attachments' => ['required', 'array', 'max:6'],
            'attachments.*' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm',
                'max:51200',
            ],
        ]);

        $draft = BandEventDraft::query()->firstOrCreate(
            [
                'element_id' => $measurementElement->id,
                'type' => $validated['type'],
            ],
            [
                'created_by' => auth()->id(),
            ]
        );

        $existingCount = $draft->evidences()->count();
        $incomingFiles = $request->file('attachments', []);

        if (($existingCount + count($incomingFiles)) > 12) {
            return response()->json([
                'success' => false,
                'message' => 'Puedes adjuntar máximo 12 fotos o videos por reporte.',
            ], 422);
        }

        foreach ($incomingFiles as $index => $file) {
            $this->storeDraftEvidence($measurementElement, $draft, $file, $existingCount + $index);
        }

        $draft->load('evidences');

        return response()->json([
            'success' => true,
            'message' => 'Evidencia cargada correctamente.',
            'evidences' => $draft->evidences
                ->map(fn (BandEventDraftEvidence $evidence) => $this->serializeDraftEvidence($evidence))
                ->values(),
        ]);
    }

    public function destroyDraft(int $element, BandEventDraftEvidence $evidence): JsonResponse
    {
        $measurementElement = Element::query()->findOrFail($element);
        $this->ensureMeasurementCreationEnabled($measurementElement);

        $evidence->loadMissing('draft');

        abort_unless(
            $evidence->draft && (int) $evidence->draft->element_id === $measurementElement->id,
            404
        );

        $disk = Storage::disk($evidence->disk ?: 'r2');

        if ($disk->exists($evidence->file_path)) {
            $disk->delete($evidence->file_path);
        }

        $draft = $evidence->draft;
        $evidence->delete();
        $draft->load('evidences');

        return response()->json([
            'success' => true,
            'message' => 'Evidencia eliminada correctamente.',
            'evidences' => $draft->evidences
                ->map(fn (BandEventDraftEvidence $item) => $this->serializeDraftEvidence($item))
                ->values(),
        ]);
    }

    public function uploadReport(Request $request, int $element, int $event): JsonResponse
    {
        $this->ensureCanManageOfficialEvidence();

        $measurementElement = Element::query()->findOrFail($element);
        $bandEvent = BandEvent::query()
            ->where('element_id', $measurementElement->id)
            ->where('id', $event)
            ->where('status', true)
            ->firstOrFail();

        $request->validate([
            'attachments' => ['required', 'array', 'max:6'],
            'attachments.*' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm',
                'max:51200',
            ],
        ]);

        $existingCount = $bandEvent->evidences()->count();
        $incomingFiles = $request->file('attachments', []);

        if (($existingCount + count($incomingFiles)) > 12) {
            return response()->json([
                'success' => false,
                'message' => 'Puedes adjuntar máximo 12 fotos o videos por reporte.',
            ], 422);
        }

        foreach ($incomingFiles as $index => $file) {
            $this->storeReportEvidence($measurementElement, $bandEvent, $file, $existingCount + $index);
        }

        $bandEvent->load('evidences');

        return response()->json([
            'success' => true,
            'message' => 'Evidencia cargada correctamente.',
            'event_id' => $bandEvent->id,
            'evidences' => $bandEvent->evidences
                ->map(fn (BandEventEvidence $evidence) => $this->serializeBandEvidence($evidence))
                ->values(),
        ]);
    }

    public function destroyReport(int $element, BandEventEvidence $evidence): JsonResponse
    {
        $this->ensureCanManageOfficialEvidence();

        $measurementElement = Element::query()->findOrFail($element);
        $evidence->loadMissing('bandEvent');

        abort_unless(
            $evidence->bandEvent
                && (int) $evidence->bandEvent->element_id === $measurementElement->id
                && (bool) $evidence->bandEvent->status,
            404
        );

        $bandEvent = $evidence->bandEvent;
        $disk = Storage::disk($evidence->disk ?: 'r2');

        if ($disk->exists($evidence->file_path)) {
            $disk->delete($evidence->file_path);
        }

        $evidence->delete();
        $bandEvent->load('evidences');

        return response()->json([
            'success' => true,
            'message' => 'Evidencia eliminada correctamente.',
            'event_id' => $bandEvent->id,
            'evidences' => $bandEvent->evidences
                ->map(fn (BandEventEvidence $item) => $this->serializeBandEvidence($item))
                ->values(),
        ]);
    }

    public function openDraft(BandEventDraftEvidence $evidence): RedirectResponse
    {
        return $this->openStoredEvidence(
            $evidence->disk ?: 'r2',
            $evidence->file_path,
            $evidence->file_name ?: 'evidencia'
        );
    }

    public function open(BandEventEvidence $evidence): RedirectResponse
    {
        return $this->openStoredEvidence(
            $evidence->disk ?: 'r2',
            $evidence->file_path,
            $evidence->file_name ?: 'evidencia'
        );
    }

    private function storeDraftEvidence(
        Element $element,
        BandEventDraft $draft,
        UploadedFile $file,
        int $sortOrder
    ): void {
        $built = $this->buildEvidencePath($element, $draft, $file);
        $stream = fopen($file->getRealPath(), 'r');

        if ($stream === false) {
            throw new \RuntimeException('No se pudo abrir el archivo temporal para subirlo.');
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

        if (!Storage::disk('r2')->exists($built['path'])) {
            throw new \RuntimeException('El archivo no quedó almacenado en R2 después de la subida.');
        }

        $mime = $file->getMimeType() ?: 'application/octet-stream';

        $draft->evidences()->create([
            'disk' => 'r2',
            'file_path' => $built['path'],
            'file_type' => str_starts_with($mime, 'video/') ? 'video' : 'image',
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'size_bytes' => $file->getSize() ?: 0,
            'sort_order' => $sortOrder,
            'created_by' => auth()->id(),
        ]);
    }

    private function storeReportEvidence(
        Element $element,
        BandEvent $event,
        UploadedFile $file,
        int $sortOrder
    ): void {
        $built = $this->buildReportEvidencePath($element, $event, $file);
        $stream = fopen($file->getRealPath(), 'r');

        if ($stream === false) {
            throw new \RuntimeException('No se pudo abrir el archivo temporal para subirlo.');
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

        if (!Storage::disk('r2')->exists($built['path'])) {
            throw new \RuntimeException('El archivo no quedó almacenado en R2 después de la subida.');
        }

        $mime = $file->getMimeType() ?: 'application/octet-stream';

        $event->evidences()->create([
            'disk' => 'r2',
            'file_path' => $built['path'],
            'file_type' => str_starts_with($mime, 'video/') ? 'video' : 'image',
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'size_bytes' => $file->getSize() ?: 0,
            'sort_order' => $sortOrder,
            'created_by' => auth()->id(),
        ]);
    }

    private function buildEvidencePath(Element $element, BandEventDraft $draft, UploadedFile $file): array
    {
        $element->loadMissing('area.client', 'group');

        $client = $element->area?->client;
        $group = $element->group;
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $storedName = now()->format('Y-m-d') . '_' . Str::uuid() . '.' . $extension;

        $clientSegment = $this->segment('cliente', $client?->name, $client?->id);
        $groupSegment = $this->segment('agrupacion', $group?->name, $group?->id);
        $elementSegment = $this->segment('activo', $element->name, $element->id);
        $typeSegment = Str::slug($draft->type ?: 'evento') ?: 'evento';

        return [
            'path' => "clientes/{$clientSegment}/agrupaciones/{$groupSegment}/mediciones/cambio-banda/{$elementSegment}/borradores/{$typeSegment}/{$storedName}",
            'stored_name' => $storedName,
        ];
    }

    private function buildReportEvidencePath(Element $element, BandEvent $event, UploadedFile $file): array
    {
        $element->loadMissing('area.client', 'group');

        $client = $element->area?->client;
        $group = $element->group;
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $storedName = now()->format('Y-m-d') . '_' . Str::uuid() . '.' . $extension;

        $clientSegment = $this->segment('cliente', $client?->name, $client?->id);
        $groupSegment = $this->segment('agrupacion', $group?->name, $group?->id);
        $elementSegment = $this->segment('activo', $element->name, $element->id);
        $typeSegment = Str::slug($event->type ?: 'evento') ?: 'evento';

        return [
            'path' => "clientes/{$clientSegment}/agrupaciones/{$groupSegment}/mediciones/cambio-banda/{$elementSegment}/reportes/{$typeSegment}/evento-{$event->id}/{$storedName}",
            'stored_name' => $storedName,
        ];
    }

    private function openStoredEvidence(string $diskName, string $path, string $safeName): RedirectResponse
    {
        $disk = Storage::disk($diskName);

        if (!$disk->exists($path)) {
            abort(404, 'El archivo no existe en el almacenamiento.');
        }

        try {
            $temporaryUrl = $disk->temporaryUrl(
                $path,
                now()->addMinutes(10),
                [
                    'ResponseContentDisposition' => 'inline; filename="' . addslashes($safeName) . '"',
                ]
            );

            return redirect()->away($temporaryUrl);
        } catch (\Throwable $e) {
            return redirect()->away($disk->url($path));
        }
    }

    private function serializeDraftEvidence(BandEventDraftEvidence $evidence): array
    {
        return [
            'id' => $evidence->id,
            'file_type' => $evidence->file_type,
            'file_name' => $evidence->file_name,
            'mime_type' => $evidence->mime_type,
            'size_bytes' => $evidence->size_bytes,
            'url' => route('band-events.draft-evidence.open', $evidence),
        ];
    }

    private function serializeBandEvidence(BandEventEvidence $evidence): array
    {
        return [
            'id' => $evidence->id,
            'file_type' => $evidence->file_type,
            'file_name' => $evidence->file_name,
            'mime_type' => $evidence->mime_type,
            'size_bytes' => $evidence->size_bytes,
            'url' => route('band-events.evidence.open', $evidence),
        ];
    }

    private function ensureCanManageOfficialEvidence(): void
    {
        $user = auth()->user();

        abort_unless(
            in_array($user?->role?->key, ['superadmin', 'admin_global'], true),
            403,
            'No tienes permisos para modificar evidencias de reportes oficiales.'
        );
    }

    private function segment(string $fallback, ?string $name, ?int $id): string
    {
        $slug = Str::slug($name ?: $fallback) ?: $fallback;

        return $id ? "{$slug}-{$id}" : $slug;
    }

    private function canCreateMeasurementRecords(Element $element): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if (method_exists($user, 'canCreateSystemModule') && !$user->canCreateSystemModule('mediciones')) {
            return false;
        }

        $config = $this->measurementModuleConfigForElement($element);

        return (bool) ($config?->creation_enabled);
    }

    private function ensureMeasurementCreationEnabled(Element $element): void
    {
        abort_unless(
            $this->canCreateMeasurementRecords($element),
            403,
            'La creación de registros está deshabilitada para este cliente y tipo de activo.'
        );
    }

    private function measurementModuleConfigForElement(Element $element): ?ClientElementTypeModule
    {
        $element->loadMissing([
            'area:id,client_id,name,status',
            'elementType:id,client_id,name,status',
        ]);

        $module = SystemModule::query()
            ->where('key', 'mediciones')
            ->where('status', true)
            ->first();

        if (!$module || !$element->area) {
            return null;
        }

        return ClientElementTypeModule::query()
            ->where('client_id', $element->area->client_id)
            ->where('element_type_id', $element->element_type_id)
            ->where('system_module_id', $module->id)
            ->where('status', true)
            ->where('module_enabled', true)
            ->first();
    }
}
