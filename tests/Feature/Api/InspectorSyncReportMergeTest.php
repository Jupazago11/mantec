<?php

namespace Tests\Feature\Api;

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
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InspectorSyncReportMergeTest extends TestCase
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

    private function makeInspector(string $name, Client $client, ?Group $group = null): User
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

        if ($group) {
            $user->groups()->attach($group->id);
        }

        return $user;
    }

    private function payload(array $scenario, Condition $condition, string $recommendation): array
    {
        return [
            'local_report_id' => 'local-' . uniqid(),
            'client_id' => $scenario['client']->id,
            'area_id' => $scenario['area']->id,
            'element_id' => $scenario['element']->id,
            'component_id' => $scenario['component']->id,
            'diagnostic_id' => $scenario['diagnostic']->id,
            'condition_id' => $condition->id,
            'recommendation' => $recommendation,
            'week' => 38,
            'year' => 2026,
            'execution_date' => '2026-09-16',
        ];
    }

    public function test_two_different_inspectors_within_24h_merge_into_one_report_with_traceability(): void
    {
        $scenario = $this->buildScenario();
        $ana = $this->makeInspector('Ana Cecilia', $scenario['client'], $scenario['group']);
        $norman = $this->makeInspector('Norman Berrío', $scenario['client'], $scenario['group']);

        Sanctum::actingAs($ana, ['*']);

        $first = $this->postJson(
            '/api/inspector/reports/sync',
            $this->payload($scenario, $scenario['conditionAlta'], 'cambiar rodillo #21-D')
        );
        $first->assertOk();
        $first->assertJsonPath('duplicated', false);
        $reportId = $first->json('server_report_detail_id');

        Sanctum::actingAs($norman, ['*']);

        $second = $this->postJson(
            '/api/inspector/reports/sync',
            $this->payload($scenario, $scenario['conditionMedia'], 'Realizar limpieza general en las canastillas')
        );
        $second->assertOk();
        $second->assertJsonPath('duplicated', true);
        $second->assertJsonPath('server_report_detail_id', $reportId);

        $this->assertSame(1, ReportDetail::count());

        $report = ReportDetail::find($reportId);
        $this->assertSame((int) $scenario['conditionMedia']->id, $report->condition_id);
        $this->assertSame((int) $ana->id, $report->user_id);
        $this->assertStringContainsString('cambiar rodillo #21-D', $report->recommendation);
        $this->assertStringContainsString('Norman Berrío', $report->recommendation);
        $this->assertStringContainsString('Realizar limpieza general en las canastillas', $report->recommendation);
    }

    public function test_same_inspector_merge_does_not_add_name_prefix(): void
    {
        $scenario = $this->buildScenario();
        $ana = $this->makeInspector('Ana Cecilia', $scenario['client'], $scenario['group']);

        Sanctum::actingAs($ana, ['*']);

        $first = $this->postJson(
            '/api/inspector/reports/sync',
            $this->payload($scenario, $scenario['conditionAlta'], 'hallazgo inicial')
        );
        $first->assertOk();

        $second = $this->postJson(
            '/api/inspector/reports/sync',
            $this->payload($scenario, $scenario['conditionAlta'], 'complemento del mismo inspector')
        );
        $second->assertOk();
        $second->assertJsonPath('duplicated', true);

        $this->assertSame(1, ReportDetail::count());

        $report = ReportDetail::first();
        $this->assertStringNotContainsString('Ana Cecilia', (string) $report->recommendation);
        $this->assertStringContainsString('hallazgo inicial', $report->recommendation);
        $this->assertStringContainsString('complemento del mismo inspector', $report->recommendation);
    }

    public function test_merge_does_not_happen_after_24_hours(): void
    {
        $scenario = $this->buildScenario();
        $ana = $this->makeInspector('Ana Cecilia', $scenario['client'], $scenario['group']);
        $norman = $this->makeInspector('Norman Berrío', $scenario['client'], $scenario['group']);

        Sanctum::actingAs($ana, ['*']);

        $first = $this->postJson(
            '/api/inspector/reports/sync',
            $this->payload($scenario, $scenario['conditionAlta'], 'hallazgo viejo')
        );
        $first->assertOk();
        $firstId = $first->json('server_report_detail_id');

        DB::table('report_details')->where('id', $firstId)->update([
            'created_at' => now()->subHours(25),
        ]);

        Sanctum::actingAs($norman, ['*']);

        $second = $this->postJson(
            '/api/inspector/reports/sync',
            $this->payload($scenario, $scenario['conditionMedia'], 'hallazgo nuevo, mas de 24h despues')
        );
        $second->assertOk();
        $second->assertJsonPath('duplicated', false);

        $this->assertSame(2, ReportDetail::count());
    }

    public function test_inspector_without_access_to_element_is_rejected(): void
    {
        $scenario = $this->buildScenario();
        $outsider = $this->makeInspector('Inspector Sin Acceso', $scenario['client']);

        Sanctum::actingAs($outsider, ['*']);

        $response = $this->postJson(
            '/api/inspector/reports/sync',
            $this->payload($scenario, $scenario['conditionAlta'], 'no deberia poder reportar')
        );

        $response->assertStatus(403);
        $this->assertSame(0, ReportDetail::count());
    }
}
