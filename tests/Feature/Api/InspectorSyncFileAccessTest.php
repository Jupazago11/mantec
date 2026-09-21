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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InspectorSyncFileAccessTest extends TestCase
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

        $condition = Condition::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'code' => 'ALTA',
            'name' => 'Alta',
            'severity' => 1,
            'status' => true,
        ]);

        return compact('client', 'area', 'elementType', 'group', 'element', 'component', 'diagnostic', 'condition');
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

    public function test_second_inspector_with_access_can_attach_evidence_to_a_merged_report(): void
    {
        Storage::fake('r2');

        $scenario = $this->buildScenario();
        $ana = $this->makeInspector('Ana Cecilia', $scenario['client'], $scenario['group']);
        $norman = $this->makeInspector('Norman Berrío', $scenario['client'], $scenario['group']);

        $report = ReportDetail::create([
            'user_id' => $ana->id,
            'element_id' => $scenario['element']->id,
            'component_id' => $scenario['component']->id,
            'diagnostic_id' => $scenario['diagnostic']->id,
            'year' => 2026,
            'week' => 38,
            'condition_id' => $scenario['condition']->id,
            'recommendation' => 'hallazgo original',
            'status' => true,
        ]);

        Sanctum::actingAs($norman, ['*']);

        $response = $this->postJson(
            "/api/inspector/report-details/{$report->id}/files",
            ['file' => UploadedFile::fake()->image('foto.jpg')]
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('report_detail_files', [
            'report_detail_id' => $report->id,
            'uploaded_by' => $norman->id,
        ]);
    }

    public function test_inspector_without_access_cannot_attach_evidence(): void
    {
        Storage::fake('r2');

        $scenario = $this->buildScenario();
        $ana = $this->makeInspector('Ana Cecilia', $scenario['client'], $scenario['group']);
        $outsider = $this->makeInspector('Inspector Sin Acceso', $scenario['client']);

        $report = ReportDetail::create([
            'user_id' => $ana->id,
            'element_id' => $scenario['element']->id,
            'component_id' => $scenario['component']->id,
            'diagnostic_id' => $scenario['diagnostic']->id,
            'year' => 2026,
            'week' => 38,
            'condition_id' => $scenario['condition']->id,
            'recommendation' => 'hallazgo original',
            'status' => true,
        ]);

        Sanctum::actingAs($outsider, ['*']);

        $response = $this->postJson(
            "/api/inspector/report-details/{$report->id}/files",
            ['file' => UploadedFile::fake()->image('foto.jpg')]
        );

        $response->assertStatus(403);

        $this->assertDatabaseCount('report_detail_files', 0);
    }
}
