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

class IndicatorChartDetailKpiBasisTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Reproduce el caso reportado: la tarjeta KPI de severidad ("Alta") cuenta solo los
     * hallazgos del rango de semana seleccionado, pero el modal de detalle (chartDetail)
     * usaba siempre el "estado actual" (último reporte conocido por componente, sin límite
     * de fecha) — mostrando un total distinto al de la tarjeta que originó el clic.
     *
     * Escenario: Componente A tiene un hallazgo Alta DENTRO del rango consultado.
     * Componente B tiene un hallazgo Alta fuera del rango (más antiguo, sin reporte
     * más reciente), por lo que solo aparece en el "estado actual" del parque.
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

        $conditionAlta = Condition::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'code' => 'ALTA-01',
            'name' => 'Desgaste severo',
            'severity' => 1,
            'status' => true,
        ]);

        // Componente A: hallazgo Alta DENTRO del rango consultado (semana 20/2026).
        ReportDetail::create([
            'user_id' => $user->id,
            'element_id' => $element->id,
            'component_id' => $componentA->id,
            'diagnostic_id' => $diagnostic->id,
            'year' => 2026,
            'week' => 20,
            'condition_id' => $conditionAlta->id,
            'status' => true,
        ]);

        // Componente B: hallazgo Alta FUERA del rango (semana 5/2026), sigue siendo su
        // último dato conocido (no se volvió a inspeccionar).
        ReportDetail::create([
            'user_id' => $user->id,
            'element_id' => $element->id,
            'component_id' => $componentB->id,
            'diagnostic_id' => $diagnostic->id,
            'year' => 2026,
            'week' => 5,
            'condition_id' => $conditionAlta->id,
            'status' => true,
        ]);

        return compact('client', 'group', 'element', 'componentA', 'componentB', 'diagnostic', 'conditionAlta');
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

    public function test_severity_kpi_card_and_its_detail_modal_report_the_same_total(): void
    {
        $user = $this->actingAsSuperadmin();
        $scenario = $this->buildScenario($user);

        $baseParams = [
            'client_id' => $scenario['client']->id,
            'group_id' => $scenario['group']->id,
            'mode' => 'latest',
            'strict_summary' => 1,
            'year_from' => 2026,
            'week_from' => 20,
            'year_to' => 2026,
            'week_to' => 20,
        ];

        // La tarjeta KPI "Alta" del dashboard cuenta 1 (solo Componente A, dentro del rango).
        $dataResponse = $this->getJson(route('admin.indicators.data', $baseParams));
        $dataResponse->assertOk();

        $severityBreakdown = collect($dataResponse->json('summary.severity_breakdown'));
        $alta = $severityBreakdown->firstWhere('severity', 1);
        $this->assertNotNull($alta);
        $this->assertSame(1, $alta['total'], 'La tarjeta KPI debe contar solo el hallazgo Alta dentro del rango (Componente A).');

        // Sin basis=kpi (p.ej. clic desde la gráfica de "estado actual"): sigue siendo el
        // estado actual del parque completo -> 2 (Componente A y Componente B).
        $legacyDetail = $this->getJson(route('admin.indicators.chart-detail', $baseParams + [
            'dimension' => 'severity',
            'severity' => 1,
        ]));
        $legacyDetail->assertOk();
        $this->assertSame(2, $legacyDetail->json('total'), 'Sin basis=kpi, el detalle sigue midiendo el estado actual del parque (ambos componentes).');

        // Con basis=kpi (clic desde la tarjeta): el modal debe coincidir con la tarjeta -> 1.
        $kpiDetail = $this->getJson(route('admin.indicators.chart-detail', $baseParams + [
            'dimension' => 'severity',
            'severity' => 1,
            'basis' => 'kpi',
        ]));
        $kpiDetail->assertOk();
        $this->assertSame(1, $kpiDetail->json('total'), 'Con basis=kpi, el detalle debe coincidir exactamente con el total mostrado en la tarjeta.');
        $this->assertSame('Componente A', $kpiDetail->json('reports.0.component'));
    }
}
