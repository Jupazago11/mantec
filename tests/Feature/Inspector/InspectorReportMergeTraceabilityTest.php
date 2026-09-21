<?php

namespace Tests\Feature\Inspector;

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

class InspectorReportMergeTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    private function buildScenario(): array
    {
        $client = Client::create(['name' => 'Cliente Test', 'status' => true]);
        $area = Area::create(['name' => 'Area Test', 'code' => 'A1', 'client_id' => $client->id, 'status' => true]);
        $elementType = ElementType::create(['client_id' => $client->id, 'name' => 'Tipo Test', 'status' => true]);
        $group = Group::create(['client_id' => $client->id, 'name' => 'Grupo Test', 'status' => true]);

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
            'name' => 'Rodillos de retorno',
            'status' => true,
        ]);
        $element->components()->attach([$component->id]);

        $diagnostic = Diagnostic::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'name' => 'Estado',
            'status' => true,
        ]);
        $component->diagnostics()->attach([$diagnostic->id]);

        $conditionAlta = Condition::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'code' => 'ALTA',
            'name' => 'Alta',
            'severity' => 1,
            'status' => true,
        ]);
        $conditionMedia = Condition::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'code' => 'MEDIA',
            'name' => 'Media',
            'severity' => 2,
            'status' => true,
        ]);
        $component->conditions()->attach([$conditionAlta->id, $conditionMedia->id]);

        return compact(
            'client',
            'area',
            'elementType',
            'group',
            'element',
            'component',
            'diagnostic',
            'conditionAlta',
            'conditionMedia'
        );
    }

    private function makeInspector(string $name, Client $client, Group $group): User
    {
        $role = Role::firstOrCreate(['key' => 'inspector'], ['name' => 'inspector', 'status' => true]);

        $user = User::create([
            'name' => $name,
            'username' => 'user_' . uniqid(),
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
            'status' => true,
        ]);

        $user->clients()->attach($client->id);
        $user->groups()->attach($group->id);

        return $user;
    }

    private function payload(array $scenario, Condition $condition, string $recommendation): array
    {
        return [
            'client_id' => $scenario['client']->id,
            'group_id' => $scenario['group']->id,
            'area_id' => $scenario['area']->id,
            'element_id' => $scenario['element']->id,
            'component_id' => $scenario['component']->id,
            'diagnostic_id' => $scenario['diagnostic']->id,
            'condition_id' => $condition->id,
            'recommendation' => $recommendation,
        ];
    }

    public function test_second_inspector_report_within_24h_merges_and_adds_name_and_date_below(): void
    {
        $scenario = $this->buildScenario();
        $ana = $this->makeInspector('Ana Cecilia', $scenario['client'], $scenario['group']);
        $norman = $this->makeInspector('Norman Berrío', $scenario['client'], $scenario['group']);

        $this->actingAs($ana)->post(
            route('inspector.reports.store'),
            $this->payload($scenario, $scenario['conditionAlta'], 'cambiar rodillo #21-D')
        )->assertRedirect(route('inspector.reports.index'));

        $this->assertSame(1, ReportDetail::count());
        $report = ReportDetail::first();

        $this->actingAs($norman)->post(
            route('inspector.reports.store'),
            $this->payload($scenario, $scenario['conditionMedia'], 'Realizar limpieza general en las canastillas')
        )->assertRedirect(route('inspector.reports.index'));

        $this->assertSame(1, ReportDetail::count());

        $report->refresh();
        $this->assertSame((int) $scenario['conditionMedia']->id, $report->condition_id);
        $this->assertSame((int) $ana->id, $report->user_id);
        $this->assertStringContainsString('cambiar rodillo #21-D', $report->recommendation);
        $this->assertStringContainsString('Norman Berrío', $report->recommendation);
        $this->assertMatchesRegularExpression(
            '/Norman Berrío — \d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:/',
            $report->recommendation
        );
        $this->assertStringContainsString('Realizar limpieza general en las canastillas', $report->recommendation);
    }
}
