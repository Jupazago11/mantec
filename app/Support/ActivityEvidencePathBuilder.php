<?php

namespace App\Support;

use App\Models\Activity;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

// Analogo a ReportFilePathBuilder, pero para evidencias del modulo Personal
// (seccion 14.20): Activity no tiene cliente/elemento, asi que no puede
// reutilizar ese builder. Prefijo de primer nivel propio
// ("personal-actividades/") para no mezclarse con "clientes/" (reportes) ni
// con nada mas del bucket.
class ActivityEvidencePathBuilder
{
    public static function build(Activity $activity, UploadedFile $file): array
    {
        $activity->loadMissing('company');

        $now = now();
        $year = $now->format('Y');

        $companySegment = self::segment('empresa', $activity->company?->name, $activity->company_id);

        $extension = strtolower(
            $file->getClientOriginalExtension()
            ?: $file->extension()
            ?: 'bin'
        );

        $storedName = $now->format('Y-m-d').'_'.Str::uuid().'.'.$extension;

        $path = "personal-actividades/{$companySegment}/{$year}/actividad-{$activity->id}/{$storedName}";

        return [
            'path' => $path,
            'stored_name' => $storedName,
            'extension' => $extension,
        ];
    }

    private static function segment(string $fallback, ?string $name, ?int $id): string
    {
        $slug = Str::slug($name ?: $fallback) ?: $fallback;

        return $id ? "{$slug}-{$id}" : $slug;
    }
}
