<?php
namespace App\Support;

use App\Models\Element;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ReportFilePathBuilder
{
    public static function build(Element $element, UploadedFile $file): array
    {
        $now = now();

        $year = $now->format('Y');
        $week = $now->weekOfYear;

        $safeElement = Str::slug($element->name ?: 'activo');

        $extension = strtolower(
            $file->getClientOriginalExtension()
            ?: $file->extension()
            ?: 'bin'
        );

        $storedName = $now->format('Y-m-d') . '_' . Str::uuid() . '.' . $extension;

        $path = "{$year}/semana-{$week}/{$safeElement}/{$storedName}";

        return [
            'path' => $path,
            'stored_name' => $storedName,
            'extension' => $extension,
        ];
    }
}