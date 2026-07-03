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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicatorControllerLatestByComponentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Reproduce el caso reportado: un activo revisado por partes durante el año
     * (un componente en la semana 10, otro componente 5 semanas después). El
     * "estado actual" del activo debe reflejar el último dato conocido de CADA
     * componente, no solo el de la visita más reciente del activo completo.
     */
    private function buildScenario(User $user): array
    {
        $client = Client::create(['name' => 'Cliente Test', 'status' => true]);
        $area = Area::create(['name' => 'Area Test', 'code' => 'A1', 'client_id' => $client->id, 'status' => true]);
        $elementType = ElementType::create(['client_id' => $client->id, 'name' => 'Tipo Test', 'status' => true]);
        $group = Group::create(['client_id' => $client->id, 'name' => 'Grupo Test', 'status' => true]);

        $element = Element::create([
            'area_id' => $area->id,
            'element_type_id' => $elementType->id,
            'group_id' => $group->id,
            'name' => 'Activo Test',
            'status' => true,
        ]);

        $componentA = Component::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'name' => 'Componente A',
            'status' => true,
        ]);

        $componentB = Component::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'name' => 'Componente B',
            'status' => true,
        ]);

        $element->components()->attach([$componentA->id, $componentB->id]);

        $diagnostic = Diagnostic::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'name' => 'Estado',
            'status' => true,
        ]);

        $conditionOk = Condition::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'code' => 'OK',
            'name' => 'OK',
            'severity' => 0,
            'status' => true,
        ]);

        $conditionAlta = Condition::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'code' => 'ALTA-01',
            'name' => 'Desgaste severo',
            'severity' => 1,
            'status' => true,
        ]);

        // Componente A revisado en la semana 10/2026: hallazgo de atención (Alta).
        ReportDetail::create([
            'user_id' => $user->id,
            'element_id' => $element->id,
            'component_id' => $componentA->id,
            'diagnostic_id' => $diagnostic->id,
            'year' => 2026,
            'week' => 10,
            'condition_id' => $conditionAlta->id,
            'status' => true,
        ]);

        // Componente B revisado 5 semanas después, en otra visita: sin novedad.
        ReportDetail::create([
            'user_id' => $user->id,
            'element_id' => $element->id,
            'component_id' => $componentB->id,
            'diagnostic_id' => $diagnostic->id,
            'year' => 2026,
            'week' => 15,
            'condition_id' => $conditionOk->id,
            'status' => true,
        ]);

        return compact(
            'client',
            'group',
            'area',
            'element',
            'componentA',
            'componentB',
            'diagnostic',
            'conditionOk',
            'conditionAlta'
        );
    }

    private function actingAsSuperadmin(): User
    {
        $role = Role::create(['name' => 'Superadmin', 'key' => 'superadmin', 'status' => true]);

        $user = User::create([
            'name' => 'Admin Test',
            'username' => 'admin_test',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
            'status' => true,
        ]);

        $this->actingAs($user);

        return $user;
    }

    public function test_ranking_fallback_keeps_latest_state_of_every_component_not_just_the_elements_last_visit(): void
    {
        $user = $this->actingAsSuperadmin();
        $scenario = $this->buildScenario($user);

        $response = $this->getJson(route('admin.indicators.data', [
            'client_id' => $scenario['client']->id,
            'group_id' => $scenario['group']->id,
            'mode' => 'latest',
        ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $topElements = collect($response->json('charts.top_elements'));
        $row = $topElements->firstWhere('name', 'Activo Test');

        $this->assertNotNull($row, 'El activo debería aparecer en el ranking porque tiene un hallazgo de atención.');
        $this->assertSame(1, $row['attention'], 'Debe contar 1 hallazgo con atención (Componente A, semana 10).');
        $this->assertSame(
            2,
            $row['total'],
            'Debe contar 2 componentes con dato conocido (A y B); con el bug anterior solo sobrevivía el de la semana 15.'
        );

        $severity = collect($response->json('charts.severity_distribution'));
        $alta = $severity->firstWhere('severity', 1);
        $ok = $severity->firstWhere('severity', 0);

        $this->assertNotNull($alta, 'La distribución por criticidad debe incluir el hallazgo Alta del Componente A.');
        $this->assertSame(1, $alta['total']);
        $this->assertNotNull($ok);
        $this->assertSame(1, $ok['total']);

        // Desglose por condición del propio activo (para el hover del gráfico).
        $conditions = collect($row['conditions']);
        $this->assertCount(2, $conditions, 'Debe listar ambas condiciones (Desgaste severo y OK) por separado.');
        $this->assertNotNull($conditions->firstWhere('name', 'Desgaste severo'));
        $this->assertNotNull($conditions->firstWhere('name', 'OK'));

        // Detalle por reporte (para el modal al hacer clic).
        $reports = collect($row['reports']);
        $this->assertCount(2, $reports, 'Debe listar los 2 reportes considerados (uno por componente).');

        $reportA = $reports->firstWhere('component', 'Componente A');
        $this->assertNotNull($reportA);
        $this->assertSame('Estado', $reportA['diagnostic']);
        $this->assertSame('Desgaste severo', $reportA['condition_name']);
        $this->assertSame('S10 / 2026', $reportA['week_label']);

        $reportB = $reports->firstWhere('component', 'Componente B');
        $this->assertNotNull($reportB);
        $this->assertSame('OK', $reportB['condition_name']);
        $this->assertSame('S15 / 2026', $reportB['week_label']);
    }

    public function test_range_summary_keeps_latest_state_of_every_component_within_the_selected_weeks(): void
    {
        $user = $this->actingAsSuperadmin();
        $scenario = $this->buildScenario($user);

        $response = $this->getJson(route('admin.indicators.data', [
            'client_id' => $scenario['client']->id,
            'group_id' => $scenario['group']->id,
            'year_from' => 2026,
            'week_from' => 9,
            'year_to' => 2026,
            'week_to' => 16,
            'strict_summary' => 1,
        ]));

        $response->assertOk();

        $severityBreakdown = collect($response->json('summary.severity_breakdown'));
        $alta = $severityBreakdown->firstWhere('severity', 1);
        $sinNovedad = $severityBreakdown->firstWhere('severity', 0);

        $this->assertNotNull(
            $alta,
            'El hallazgo Alta del Componente A (semana 10) no debe perderse aunque el Componente B se haya revisado después (semana 15), ambas dentro del rango.'
        );
        $this->assertSame(1, $alta['total']);
        $this->assertSame('Alta', $alta['label']);

        $this->assertNotNull($sinNovedad);
        $this->assertSame(1, $sinNovedad['total']);
        $this->assertSame('Sin novedad', $sinNovedad['label']);
    }

    public function test_severity_kpi_breakdown_is_dynamic_not_hardcoded_to_four_buckets(): void
    {
        $user = $this->actingAsSuperadmin();
        $scenario = $this->buildScenario($user);

        // Agrega una tercera condición con una severidad fuera del set clásico 0/1/2/3,
        // en otro componente, misma semana que el Componente B (15/2026).
        $componentC = Component::create([
            'client_id' => $scenario['client']->id,
            'element_type_id' => $scenario['element']->element_type_id,
            'name' => 'Componente C',
            'status' => true,
        ]);

        $conditionExtrema = Condition::create([
            'client_id' => $scenario['client']->id,
            'element_type_id' => $componentC->element_type_id,
            'code' => 'EXTREMA-01',
            'name' => 'Criticidad extrema',
            'severity' => 5,
            'status' => true,
        ]);

        ReportDetail::create([
            'user_id' => $user->id,
            'element_id' => $scenario['element']->id,
            'component_id' => $componentC->id,
            'diagnostic_id' => $scenario['diagnostic']->id,
            'year' => 2026,
            'week' => 15,
            'condition_id' => $conditionExtrema->id,
            'status' => true,
        ]);

        $response = $this->getJson(route('admin.indicators.data', [
            'client_id' => $scenario['client']->id,
            'group_id' => $scenario['group']->id,
            'mode' => 'latest',
        ]));

        $response->assertOk();

        $severityBreakdown = collect($response->json('summary.severity_breakdown'));

        // Deben existir 3 tarjetas: Alta(1), Sin novedad(0) y la severidad 5, que no
        // tiene una etiqueta fija predefinida — no debe perderse ni quedar en 0.
        $this->assertCount(3, $severityBreakdown);

        $extrema = $severityBreakdown->firstWhere('severity', 5);
        $this->assertNotNull($extrema, 'Una severidad fuera de 0/1/2/3 debe seguir apareciendo como su propia tarjeta.');
        $this->assertSame(1, $extrema['total']);
        $this->assertSame('Criticidad 5', $extrema['label']);
    }

    public function test_chart_detail_endpoint_filters_by_each_dimension(): void
    {
        $user = $this->actingAsSuperadmin();
        $scenario = $this->buildScenario($user);

        $baseParams = [
            'client_id' => $scenario['client']->id,
            'group_id' => $scenario['group']->id,
            'mode' => 'latest',
        ];

        // severity=1 (Alta) -> solo el reporte del Componente A.
        $response = $this->getJson(route('admin.indicators.chart-detail', $baseParams + [
            'dimension' => 'severity',
            'severity' => 1,
        ]));
        $response->assertOk();
        $response->assertJsonCount(1, 'reports');
        $this->assertSame('Componente A', $response->json('reports.0.component'));

        // condition_id de "OK" -> solo el reporte del Componente B.
        $response = $this->getJson(route('admin.indicators.chart-detail', $baseParams + [
            'dimension' => 'condition',
            'condition_id' => $scenario['conditionOk']->id,
        ]));
        $response->assertOk();
        $response->assertJsonCount(1, 'reports');
        $this->assertSame('Componente B', $response->json('reports.0.component'));

        // element_id del activo -> ambos reportes (A y B).
        $response = $this->getJson(route('admin.indicators.chart-detail', $baseParams + [
            'dimension' => 'element',
            'element_id' => $scenario['element']->id,
        ]));
        $response->assertOk();
        $response->assertJsonCount(2, 'reports');

        // component_id de Componente A -> solo su reporte.
        $response = $this->getJson(route('admin.indicators.chart-detail', $baseParams + [
            'dimension' => 'component',
            'component_id' => $scenario['componentA']->id,
        ]));
        $response->assertOk();
        $response->assertJsonCount(1, 'reports');
        $this->assertSame('Desgaste severo', $response->json('reports.0.condition_name'));

        // area_id del área del activo -> ambos reportes (los dos componentes son del mismo activo/área).
        $response = $this->getJson(route('admin.indicators.chart-detail', $baseParams + [
            'dimension' => 'area',
            'area_id' => $scenario['area']->id,
        ]));
        $response->assertOk();
        $response->assertJsonCount(2, 'reports');

        // week=10/2026 -> el reporte literal de esa semana (no la reconstrucción de "último estado").
        $response = $this->getJson(route('admin.indicators.chart-detail', $baseParams + [
            'dimension' => 'week',
            'year' => 2026,
            'week' => 10,
        ]));
        $response->assertOk();
        $response->assertJsonCount(1, 'reports');
        $this->assertSame('Componente A', $response->json('reports.0.component'));
    }

    public function test_chart_detail_endpoint_requires_authorization_for_foreign_client(): void
    {
        $user = $this->actingAsSuperadmin();
        $this->buildScenario($user);

        // Cliente inexistente (no autorizado / no existe) -> falla de validación, no expone datos ajenos.
        $response = $this->getJson(route('admin.indicators.chart-detail', [
            'client_id' => 999999,
            'dimension' => 'severity',
            'severity' => 1,
        ]));

        $response->assertStatus(422);
    }
}
