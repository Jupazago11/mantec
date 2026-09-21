<?php

namespace App\Services\Reports;

use App\Models\ReportDetail;
use Carbon\Carbon;

class ReportDetailMerger
{
    public function findMergeCandidate(int $elementId, int $componentId, int $diagnosticId): ?ReportDetail
    {
        return ReportDetail::query()
            ->where('element_id', $elementId)
            ->where('component_id', $componentId)
            ->where('diagnostic_id', $diagnosticId)
            ->where('status', true)
            ->where('created_at', '>=', now()->subHours(24))
            ->latest('created_at')
            ->lockForUpdate()
            ->first();
    }

    public function appendFinding(?string $currentRecommendation, string $inspectorName, Carbon $when, string $newFinding): string
    {
        $current = trim((string) $currentRecommendation);
        $finding = trim($newFinding);

        if ($finding === '') {
            return $current;
        }

        $entry = $inspectorName . ' — ' . $when->format('d/m/Y H:i') . ':' . PHP_EOL . $finding;

        return $current === '' ? $entry : $current . PHP_EOL . PHP_EOL . $entry;
    }
}
