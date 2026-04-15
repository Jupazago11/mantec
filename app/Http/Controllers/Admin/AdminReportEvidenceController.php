<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportDetailFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class AdminReportEvidenceController extends Controller
{
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
}
