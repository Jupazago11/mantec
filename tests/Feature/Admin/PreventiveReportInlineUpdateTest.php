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

class PreventiveReportInlineUpdateTest extends TestCase
{
    use RefreshDatabase;

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

        $component = Component::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'name' => 'Componente A',
            'status' => true,
        ]);

        $element->components()->attach([$component->id]);

        $diagnostic = Diagnostic::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'name' => 'Estado',
            'status' => true,
        ]);

        $condition = Condition::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'code' => 'ALTA-01',
            'name' => 'Desgaste severo',
            'severity' => 1,
            'status' => true,
        ]);

        $report = ReportDetail::create([
            'user_id' => $user->id,
            'element_id' => $element->id,
            'component_id' => $component->id,
            'diagnostic_id' => $diagnostic->id,
            'year' => 2026,
            'week' => 10,
            'condition_id' => $condition->id,
            'recommendation' => 'Hallazgo original',
            'recommendation_2' => 'Recomendación original',
            'status' => true,
        ]);

        return compact('client', 'area', 'elementType', 'group', 'element', 'component', 'diagnostic', 'condition', 'report');
    }

    private function actingAsRole(string $roleKey): User
    {
        $role = Role::firstOrCreate(['key' => $roleKey], ['name' => $roleKey, 'status' => true]);

        $user = User::create([
            'name' => 'Test User ' . $roleKey,
            'username' => 'user_' . $roleKey,
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
            'status' => true,
        ]);

        $this->actingAs($user);

        return $user;
    }

    public function test_superadmin_can_inline_edit_recommendation_without_group_config(): void
    {
        $user = $this->actingAsRole('superadmin');
        $scenario = $this->buildScenario($user);

        $response = $this->patchJson(
            route('admin.preventive-reports.inline-update', ['reportDetail' => $scenario['report']->id]),
            ['field' => 'recommendation', 'value' => 'Hallazgo editado por superadmin']
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('value', 'Hallazgo editado por superadmin');

        $this->assertSame('Hallazgo editado por superadmin', $scenario['report']->fresh()->recommendation);
    }

    public function test_admin_global_can_inline_edit_recommendation_2_without_group_config(): void
    {
        $user = $this->actingAsRole('admin_global');
        $scenario = $this->buildScenario($user);

        $response = $this->patchJson(
            route('admin.preventive-reports.inline-update', ['reportDetail' => $scenario['report']->id]),
            ['field' => 'recommendation_2', 'value' => 'Recomendación editada por admin_global']
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertSame('Recomendación editada por admin_global', $scenario['report']->fresh()->recommendation_2);
    }

    public function test_superadmin_still_cannot_inline_edit_orden_or_aviso(): void
    {
        $user = $this->actingAsRole('superadmin');
        $scenario = $this->buildScenario($user);

        $response = $this->patchJson(
            route('admin.preventive-reports.inline-update', ['reportDetail' => $scenario['report']->id]),
            ['field' => 'orden', 'value' => 'OT-123']
        );

        $response->assertStatus(403);
    }

    public function test_admin_role_is_still_blocked_from_inline_update(): void
    {
        $user = $this->actingAsRole('admin');
        $scenario = $this->buildScenario($user);
        $user->clients()->attach($scenario['client']->id);

        $response = $this->patchJson(
            route('admin.preventive-reports.inline-update', ['reportDetail' => $scenario['report']->id]),
            ['field' => 'recommendation', 'value' => 'Intento de admin']
        );

        $response->assertStatus(403);
    }

    public function test_admin_cliente_without_group_permission_is_still_blocked_by_default(): void
    {
        $adminGlobal = $this->actingAsRole('admin_global');
        $scenario = $this->buildScenario($adminGlobal);

        $adminCliente = $this->actingAsRole('admin_cliente');
        $adminCliente->clients()->attach($scenario['client']->id);
        $adminCliente->groups()->attach($scenario['group']->id);

        $response = $this->patchJson(
            route('admin.preventive-reports.inline-update', ['reportDetail' => $scenario['report']->id]),
            ['field' => 'recommendation', 'value' => 'Intento de admin_cliente sin permiso']
        );

        $response->assertStatus(403);
    }
}
