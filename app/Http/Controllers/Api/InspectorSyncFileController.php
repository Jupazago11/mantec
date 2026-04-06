<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReportDetail;
use App\Support\ReportFilePathBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class InspectorSyncFileController extends Controller
{
    public function store(Request $request, ReportDetail $reportDetail): JsonResponse
    {
        $user = Auth::user();

        abort_unless((int) $reportDetail->user_id === (int) $user->id, 403);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:102400'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $file = $validated['file'];
        $sortOrder = $validated['sort_order'] ?? 0;

        $reportDetail->loadMissing('element');

        $built = ReportFilePathBuilder::build($reportDetail->element, $file);

        $stream = fopen($file->getRealPath(), 'r');

        if ($stream === false) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo abrir el archivo temporal.',
            ], 500);
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

        if (!Storage::disk('r2')->exists($built['path'])) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo no quedó almacenado en R2.',
            ], 500);
        }

        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $fileType = str_starts_with($mime, 'video/') ? 'video' : 'image';

        $saved = $reportDetail->files()->create([
            'uploaded_by' => $user->id,
            'disk' => 'r2',
            'path' => $built['path'],
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $built['stored_name'],
            'mime_type' => $mime,
            'extension' => $built['extension'],
            'file_type' => $fileType,
            'size_bytes' => $file->getSize() ?: 0,
            'sort_order' => $sortOrder,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Archivo sincronizado correctamente.',
            'file_id' => $saved->id,
            'path' => $saved->path,
            'file_type' => $saved->file_type,
        ]);
    }
}
