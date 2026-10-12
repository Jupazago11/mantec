<?php

namespace Tests\Feature\Admin;

use App\Exports\ArrayExport;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * Reporte preventivo por agrupación con reportes fusionados de varios inspectores:
 * hallazgo multilínea filtrable, columna/filtro Inspector con todos los inspectores
 * y Excel con los mismos nombres.
 */
class PreventiveGroupReportMultiInspectorTest extends TestCase
{
    use RefreshDatabase;

    private const MERGED_FINDING = "Eder Gutiérrez — 05/10/2026 09:13:\npalo atascado entre la pantalla y la chell";

    private function buildScenario(): array
    {
        $role = Role::firstOrCreate(['key' => 'superadmin'], ['name' => 'superadmin', 'status' => true]);
        $inspectorRole = Role::firstOrCreate(['key' => 'inspector'], ['name' => 'inspector', 'status' => true]);

        $admin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin_test',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
            'status' => true,
        ]);

        $makeInspector = fn (string $name) => User::create([
            'name' => $name,
            'username' => 'user_' . uniqid(),
            'password' => bcrypt('secret'),
            'role_id' => $inspectorRole->id,
            'status' => true,
        ]);

        $client = Client::create(['name' => 'Cliente Test', 'status' => true]);
        $area = Area::create(['name' => 'MOLINO CEMENTO', 'code' => 'A1', 'client_id' => $client->id, 'status' => true]);
        $elementType = ElementType::create(['client_id' => $client->id, 'name' => 'Banda', 'status' => true]);
        $group = Group::create(['client_id' => $client->id, 'name' => 'Planta Test', 'status' => true]);

        $element = Element::create([
            'area_id' => $area->id,
            'element_type_id' => $elementType->id,
            'group_id' => $group->id,
            'name' => 'K91BT04',
            'status' => true,
        ]);

        $component = Component::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'name' => 'Banda',
            'status' => true,
        ]);

        $diagnostic = Diagnostic::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'name' => 'Estado',
            'status' => true,
        ]);

        $condition = Condition::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'code' => 'OBSERV',
            'name' => 'Observación',
            'severity' => 3,
            'status' => true,
        ]);

        $ana = $makeInspector('Ana Cecilia');
        $eder = $makeInspector('Eder Gutiérrez');
        $alan = $makeInspector('Alan Rojas');

        $newReport = fn (User $creator, ?string $recommendation) => ReportDetail::create([
            'user_id' => $creator->id,
            'element_id' => $element->id,
            'component_id' => $component->id,
            'diagnostic_id' => $diagnostic->id,
            'year' => 2026,
            'week' => 41,
            'condition_id' => $condition->id,
            'recommendation' => $recommendation,
            'status' => true,
        ]);

        // Ana creó el reporte sin hallazgo y Eder lo complementó (caso de la captura).
        $merged = $newReport($ana, self::MERGED_FINDING);
        $merged->additionalInspectors()->attach($eder->id);

        $single = $newReport($alan, 'equipo detenido');

        $this->actingAs($admin);

        return compact('admin', 'group', 'ana', 'eder', 'alan', 'merged', 'single');
    }

    private function groupUrl(Group $group, array $query = []): string
    {
        return route('admin.preventive-reports.group', ['group' => $group->id] + [
            'date_from' => now()->startOfYear()->toDateString(),
            'date_to' => now()->toDateString(),
        ] + $query);
    }

    public function test_hallazgo_filter_matches_multiline_text_resent_by_the_browser_with_crlf(): void
    {
        $scenario = $this->buildScenario();

        // Así llega desde el formulario: el navegador convierte \n en \r\n.
        $response = $this->get($this->groupUrl($scenario['group'], [
            'recommendation_values' => [str_replace("\n", "\r\n", self::MERGED_FINDING)],
        ]));

        $response->assertOk();
        $response->assertViewHas('totalFiltered', 1);
        $response->assertViewHas('reports', fn ($reports) => $reports->pluck('id')->all() === [$scenario['merged']->id]);
    }

    public function test_hallazgo_filter_ignores_stored_crlf_and_surrounding_whitespace(): void
    {
        $scenario = $this->buildScenario();
        $scenario['merged']->update(['recommendation' => "  línea uno\r\nlínea dos \r\n"]);

        $response = $this->get($this->groupUrl($scenario['group'], [
            'recommendation_values' => ["línea uno\nlínea dos"],
        ]));

        $response->assertViewHas('totalFiltered', 1);
        $response->assertViewHas('filterOptions', function ($options) {
            return collect($options['recommendation_values'])->pluck('value')->contains("línea uno\nlínea dos");
        });
        $response->assertViewHas('activeFilters', function ($filters) {
            return $filters['recommendation_values'] === ["línea uno\nlínea dos"];
        });
    }

    public function test_hallazgo_filter_still_excludes_other_reports(): void
    {
        $scenario = $this->buildScenario();

        $response = $this->get($this->groupUrl($scenario['group'], [
            'recommendation_values' => ['equipo detenido'],
        ]));

        $response->assertViewHas('totalFiltered', 1);
        $response->assertViewHas('reports', fn ($reports) => $reports->pluck('id')->all() === [$scenario['single']->id]);
    }

    public function test_inspector_column_lists_creator_and_merged_inspectors(): void
    {
        $scenario = $this->buildScenario();

        $response = $this->get($this->groupUrl($scenario['group']));

        $response->assertOk();
        $response->assertViewHas('reports', function ($reports) use ($scenario) {
            return $reports->firstWhere('id', $scenario['merged']->id)->inspector_names === ['Ana Cecilia', 'Eder Gutiérrez']
                && $reports->firstWhere('id', $scenario['single']->id)->inspector_names === ['Alan Rojas'];
        });
        $response->assertSeeInOrder(['Ana Cecilia</div>', 'Eder Gutiérrez</div>'], false);
    }

    public function test_inspector_filter_and_options_include_merged_inspectors(): void
    {
        $scenario = $this->buildScenario();

        $byEder = $this->get($this->groupUrl($scenario['group'], ['inspector_names' => ['Eder Gutiérrez']]));
        $byEder->assertViewHas('totalFiltered', 1);
        $byEder->assertViewHas('reports', fn ($reports) => $reports->pluck('id')->all() === [$scenario['merged']->id]);

        $byAna = $this->get($this->groupUrl($scenario['group'], ['inspector_names' => ['Ana Cecilia']]));
        $byAna->assertViewHas('totalFiltered', 1);

        $all = $this->get($this->groupUrl($scenario['group']));
        $all->assertViewHas('filterOptions', function ($options) {
            return collect($options['inspector_names'])->pluck('value')->sort()->values()->all()
                === ['Alan Rojas', 'Ana Cecilia', 'Eder Gutiérrez'];
        });
    }

    public function test_hallazgo_cells_preserve_line_breaks_and_use_multiline_editor(): void
    {
        $scenario = $this->buildScenario();

        $response = $this->get($this->groupUrl($scenario['group']));

        $response->assertSee('data-multiline="1"', false);
        $response->assertSee('<span class="inline-edit-trigger multiline-text">' . e(self::MERGED_FINDING) . '</span>', false);
        $response->assertSee('.preventive-table .multiline-text', false);
        $response->assertSee('inline-edit-textarea', false);
    }

    public function test_filter_popover_is_positioned_inside_the_viewport(): void
    {
        $scenario = $this->buildScenario();

        $response = $this->get($this->groupUrl($scenario['group']));

        // El popover es fixed: ya no se suma el scroll de la página ni se abre siempre hacia abajo.
        $response->assertDontSee('window.scrollY + triggerRect.bottom', false);
        $response->assertSee('function positionFilterPopover()', false);
        $response->assertSee('filter-popover-list', false);
    }

    public function test_excel_export_lists_every_inspector_one_per_line(): void
    {
        $scenario = $this->buildScenario();
        Excel::fake();

        $this->get(route('admin.preventive-reports.group.export', [
            'group' => $scenario['group']->id,
            'date_from' => now()->startOfYear()->toDateString(),
            'date_to' => now()->toDateString(),
            'inspector_names' => ['Eder Gutiérrez'],
        ]))->assertOk();

        Excel::matchByRegex();
        Excel::assertDownloaded('/reporte-preventivo-.*\.xlsx/', function (ArrayExport $export) {
            $inspectorColumn = array_search('Inspector', $export->headings(), true);
            $rows = $export->array();

            return $inspectorColumn !== false
                && count($rows) === 1
                && $rows[0][$inspectorColumn] === "Ana Cecilia\nEder Gutiérrez";
        });
    }
}
