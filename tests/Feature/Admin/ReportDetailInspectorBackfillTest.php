<?php

namespace Tests\Feature\Admin;

use App\Models\Area;
use App\Models\Client;
use App\Models\Component;
use App\Models\Condition;
use App\Models\Diagnostic;
use App\Models\Element;
use App\Models\ElementType;
use App\Models\Group;
use App\Models\ReportDetail;
use App\Models\Role;
use App\Models\User;
use App\Services\Reports\ReportDetailInspectorBackfill;
use App\Services\Reports\ReportDetailMerger;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportDetailInspectorBackfillTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $name): User
    {
        $role = Role::firstOrCreate(['key' => 'inspector'], ['name' => 'inspector', 'status' => true]);

        return User::create([
            'name' => $name,
            'username' => 'user_' . uniqid(),
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
            'status' => true,
        ]);
    }

    private function makeReport(User $creator, string $recommendation): ReportDetail
    {
        $client = Client::create(['name' => 'Cliente ' . uniqid(), 'status' => true]);
        $area = Area::create(['name' => 'Area', 'code' => 'A1', 'client_id' => $client->id, 'status' => true]);
        $elementType = ElementType::create(['client_id' => $client->id, 'name' => 'Tipo', 'status' => true]);
        $group = Group::create(['client_id' => $client->id, 'name' => 'Grupo', 'status' => true]);
        $element = Element::create([
            'area_id' => $area->id,
            'element_type_id' => $elementType->id,
            'group_id' => $group->id,
            'name' => 'K91BT04',
            'status' => true,
        ]);
        $component = Component::create(['client_id' => $client->id, 'element_type_id' => $elementType->id, 'name' => 'Banda', 'status' => true]);
        $diagnostic = Diagnostic::create(['client_id' => $client->id, 'element_type_id' => $elementType->id, 'name' => 'Estado', 'status' => true]);
        $condition = Condition::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'code' => 'ALTA',
            'name' => 'Alta',
            'severity' => 1,
            'status' => true,
        ]);

        return ReportDetail::create([
            'user_id' => $creator->id,
            'element_id' => $element->id,
            'component_id' => $component->id,
            'diagnostic_id' => $diagnostic->id,
            'year' => 2026,
            'week' => 38,
            'condition_id' => $condition->id,
            'recommendation' => $recommendation,
            'status' => true,
        ]);
    }

    public function test_finding_authors_reads_headers_written_by_append_finding(): void
    {
        $merger = app(ReportDetailMerger::class);

        $text = $merger->appendFinding('hallazgo inicial', 'Norman Berrío', Carbon::create(2026, 9, 16, 10, 15), 'limpieza');
        $text = $merger->appendFinding($text, 'Eder Gutiérrez', Carbon::create(2026, 9, 16, 11, 0), 'otro');

        $authors = $merger->findingAuthors($text);

        $this->assertSame(['Norman Berrío', 'Eder Gutiérrez'], array_column($authors, 'name'));
        $this->assertSame('2026-09-16 10:15', $authors[0]['at']->format('Y-m-d H:i'));
        $this->assertSame([], $merger->findingAuthors('hallazgo sin encabezados — normal'));
    }

    public function test_backfill_links_unique_names_and_skips_creator_ambiguous_and_unknown(): void
    {
        $ana = $this->makeUser('Ana Cecilia');
        $norman = $this->makeUser('Norman Berrío');
        $this->makeUser('Juan Pérez');
        $this->makeUser('Juan Pérez');

        $report = $this->makeReport($ana, implode("\n", [
            'hallazgo de Ana',
            '',
            'Norman Berrío — 16/09/2026 10:15:',
            'limpieza',
            '',
            'Ana Cecilia — 16/09/2026 10:30:',
            'complemento de la creadora',
            '',
            'Juan Pérez — 16/09/2026 10:40:',
            'nombre repetido entre usuarios',
            '',
            'Persona Inexistente — 16/09/2026 10:50:',
            'sin usuario',
            '',
            'Norman Berrío — 16/09/2026 11:00:',
            'segundo aporte',
        ]));

        $inserted = app(ReportDetailInspectorBackfill::class)->run();

        $this->assertSame(1, $inserted);
        $rows = DB::table('report_detail_inspectors')->get();
        $this->assertCount(1, $rows);
        $this->assertSame($report->id, (int) $rows[0]->report_detail_id);
        $this->assertSame($norman->id, (int) $rows[0]->user_id);
        $this->assertSame('2026-09-16 10:15', Carbon::parse($rows[0]->created_at)->format('Y-m-d H:i'));

        // Idempotente: una segunda corrida no duplica.
        $this->assertSame(0, app(ReportDetailInspectorBackfill::class)->run());
        $this->assertSame(1, DB::table('report_detail_inspectors')->count());
    }
}
