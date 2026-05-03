<?php
namespace App\Support;

use App\Models\Element;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ReportFilePathBuilder
{
    public static function build(Element $element, UploadedFile $file): array
    {
        $element->loadMissing('area.client', 'group');

        $now = now();

        $year = $now->format('Y');
        $week = $now->weekOfYear;

        $client = $element->area?->client;
        $group = $element->group;

        $clientSegment = self::segment('cliente', $client?->name, $client?->id);
        $groupSegment = self::segment('agrupacion', $group?->name, $group?->id);
        $elementSegment = self::segment('activo', $element->name, $element->id);

        $extension = strtolower(
            $file->getClientOriginalExtension()
            ?: $file->extension()
            ?: 'bin'
        );

        $storedName = $now->format('Y-m-d') . '_' . Str::uuid() . '.' . $extension;

        $path = "clientes/{$clientSegment}/agrupaciones/{$groupSegment}/{$year}/semana-{$week}/{$elementSegment}/{$storedName}";

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
