<?php

namespace App\Services\Reports;

use App\Models\ReportDetail;
use Carbon\Carbon;

class ReportDetailMerger
{
    private const FINDING_DATE_FORMAT = 'd/m/Y H:i';

    // Encabezado que appendFinding() escribe antes del hallazgo de otro inspector.
    private const FINDING_HEADER_PATTERN = '/^(?<name>.+?) — (?<at>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}):/mu';

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

        $entry = $inspectorName . ' — ' . $when->format(self::FINDING_DATE_FORMAT) . ':' . PHP_EOL . $finding;

        return $current === '' ? $entry : $current . PHP_EOL . PHP_EOL . $entry;
    }

    /**
     * Deja constancia del inspector que complementó el reporte. El creador ya
     * está en user_id, así que no se duplica.
     */
    public function recordInspector(ReportDetail $report, int $userId): void
    {
        if ((int) $report->user_id === $userId) {
            return;
        }

        $report->additionalInspectors()->syncWithoutDetaching([$userId]);
    }

    /**
     * Autores de los encabezados escritos por appendFinding(), en el orden en
     * que aparecen en el texto.
     *
     * @return array<int, array{name: string, at: Carbon}>
     */
    public function findingAuthors(?string $recommendation): array
    {
        if (!preg_match_all(self::FINDING_HEADER_PATTERN, (string) $recommendation, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $authors = [];

        foreach ($matches as $match) {
            try {
                $at = Carbon::createFromFormat(self::FINDING_DATE_FORMAT, $match['at']);
            } catch (\Throwable) {
                continue;
            }

            if (!$at) {
                continue;
            }

            $authors[] = ['name' => trim($match['name']), 'at' => $at];
        }

        return $authors;
    }
}
