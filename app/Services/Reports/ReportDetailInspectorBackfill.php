<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Reconstruye report_detail_inspectors para reportes fusionados antes de que
 * existiera esa tabla, leyendo los encabezados "Nombre — dd/mm/aaaa HH:mm:" del
 * hallazgo. Solo se asocia un nombre si coincide exactamente con un único
 * usuario; un nombre ambiguo o inexistente se omite. Idempotente.
 */
class ReportDetailInspectorBackfill
{
    public function __construct(private readonly ReportDetailMerger $merger)
    {
    }

    public function run(): int
    {
        $userIdsByName = DB::table('users')
            ->select('id', 'name')
            ->get()
            ->groupBy(fn ($user) => trim((string) $user->name))
            ->filter(fn ($users, $name) => $name !== '' && $users->count() === 1)
            ->map(fn ($users) => (int) $users->first()->id);

        $inserted = 0;

        DB::table('report_details')
            ->select('id', 'user_id', 'recommendation')
            ->where('recommendation', 'like', '% — %')
            ->orderBy('id')
            ->chunkById(500, function ($reports) use ($userIdsByName, &$inserted) {
                $rows = [];

                foreach ($reports as $report) {
                    foreach ($this->merger->findingAuthors($report->recommendation) as $author) {
                        $userId = $userIdsByName->get($author['name']);

                        if (!$userId || $userId === (int) $report->user_id) {
                            continue;
                        }

                        // La primera aparición en el texto es el primer aporte de ese inspector.
                        $rows[$report->id . '-' . $userId] ??= [
                            'report_detail_id' => $report->id,
                            'user_id' => $userId,
                            'created_at' => $author['at'],
                            'updated_at' => now(),
                        ];
                    }
                }

                if ($rows !== []) {
                    $inserted += DB::table('report_detail_inspectors')->insertOrIgnore(array_values($rows));
                }
            });

        return $inserted;
    }
}
