<?php

namespace Tests\Feature\Admin;

use App\Models\Area;
use App\Models\Client;
use App\Models\Component;
use App\Models\Condition;
use App\Models\Diagnostic;
use App\Models\Element;
use App\Models\ElementType;
use App\Models\ExecutionStatus;
use App\Models\Group;
use App\Models\ReportDetail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicatorAnnualExecutionStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cuando el hallazgo que dispara "Sí requiere" / "Con novedad" ya fue marcado
     * como ejecutado (orden REALIZADA), el indicador anual debe reflejar que la
     * necesidad quedó suplida en lugar de seguir contando el diagnóstico original.
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

        $componentBanda = Component::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'name' => 'Banda',
            'status' => true,
        ]);

        $componentGuardas = Component::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'name' => 'Guardas de seguridad',
            'status' => true,
        ]);

        $element->components()->attach([$componentBanda->id, $componentGuardas->id]);

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
            'name' => 'ALTA',
            'severity' => 1,
            'status' => true,
        ]);

        $pendingStatus = ExecutionStatus::create(['code' => 'pending', 'name' => 'PENDIENTE', 'status' => true]);
        $doneStatus = ExecutionStatus::create(['code' => 'done', 'name' => 'REALIZADO', 'status' => true]);

        return compact(
            'client',
            'group',
            'area',
            'element',
            'componentBanda',
            'componentGuardas',
            'diagnostic',
            'conditionAlta',
            'pendingStatus',
            'doneStatus'
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

    public function test_belt_change_still_pending_counts_as_yes(): void
    {
        $user = $this->actingAsSuperadmin();
        $scenario = $this->buildScenario($user);
        $year = now()->isoWeekYear();

        ReportDetail::create([
            'user_id' => $user->id,
            'element_id' => $scenario['element']->id,
            'component_id' => $scenario['componentBanda']->id,
            'diagnostic_id' => $scenario['diagnostic']->id,
            'year' => $year,
            'week' => 10,
            'condition_id' => $scenario['conditionAlta']->id,
            'is_belt_change' => true,
            'execution_status_id' => $scenario['pendingStatus']->id,
            'status' => true,
        ]);

        $response = $this->getJson(route('admin.indicators.data', [
            'client_id' => $scenario['client']->id,
            'group_id' => $scenario['group']->id,
            'mode' => 'latest',
        ]));

        $response->assertOk();
        $response->assertJsonPath('annual.belt_change.yes', 1);
        $response->assertJsonPath('annual.belt_change.no', 0);
    }

    public function test_belt_change_marked_as_done_no_longer_requires_change(): void
    {
        $user = $this->actingAsSuperadmin();
        $scenario = $this->buildScenario($user);
        $year = now()->isoWeekYear();

        ReportDetail::create([
            'user_id' => $user->id,
            'element_id' => $scenario['element']->id,
            'component_id' => $scenario['componentBanda']->id,
            'diagnostic_id' => $scenario['diagnostic']->id,
            'year' => $year,
            'week' => 10,
            'condition_id' => $scenario['conditionAlta']->id,
            'is_belt_change' => true,
            'execution_status_id' => $scenario['doneStatus']->id,
            'status' => true,
        ]);

        $response = $this->getJson(route('admin.indicators.data', [
            'client_id' => $scenario['client']->id,
            'group_id' => $scenario['group']->id,
            'mode' => 'latest',
        ]));

        $response->assertOk();
        $response->assertJsonPath('annual.belt_change.yes', 0);
        $response->assertJsonPath('annual.belt_change.no', 1);
        $this->assertSame([], $response->json('annual.belt_change.yes_names'));
    }

    public function test_security_novelty_still_pending_counts_as_novedad(): void
    {
        $user = $this->actingAsSuperadmin();
        $scenario = $this->buildScenario($user);
        $year = now()->isoWeekYear();

        ReportDetail::create([
            'user_id' => $user->id,
            'element_id' => $scenario['element']->id,
            'component_id' => $scenario['componentGuardas']->id,
            'diagnostic_id' => $scenario['diagnostic']->id,
            'year' => $year,
            'week' => 10,
            'condition_id' => $scenario['conditionAlta']->id,
            'execution_status_id' => $scenario['pendingStatus']->id,
            'status' => true,
        ]);

        $response = $this->getJson(route('admin.indicators.data', [
            'client_id' => $scenario['client']->id,
            'group_id' => $scenario['group']->id,
            'mode' => 'latest',
        ]));

        $response->assertOk();
        $response->assertJsonPath('annual.security.novedad', 1);
        $response->assertJsonPath('annual.security.ok', 0);
    }

    public function test_security_novelty_marked_as_done_counts_as_ok(): void
    {
        $user = $this->actingAsSuperadmin();
        $scenario = $this->buildScenario($user);
        $year = now()->isoWeekYear();

        ReportDetail::create([
            'user_id' => $user->id,
            'element_id' => $scenario['element']->id,
            'component_id' => $scenario['componentGuardas']->id,
            'diagnostic_id' => $scenario['diagnostic']->id,
            'year' => $year,
            'week' => 10,
            'condition_id' => $scenario['conditionAlta']->id,
            'execution_status_id' => $scenario['doneStatus']->id,
            'status' => true,
        ]);

        $response = $this->getJson(route('admin.indicators.data', [
            'client_id' => $scenario['client']->id,
            'group_id' => $scenario['group']->id,
            'mode' => 'latest',
        ]));

        $response->assertOk();
        $response->assertJsonPath('annual.security.novedad', 0);
        $response->assertJsonPath('annual.security.ok', 1);
    }
}
