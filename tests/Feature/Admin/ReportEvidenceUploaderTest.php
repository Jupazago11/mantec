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

class ReportEvidenceUploaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_evidence_view_shows_who_uploaded_each_file_when_more_than_one_inspector_contributed(): void
    {
        $role = Role::firstOrCreate(['key' => 'superadmin'], ['name' => 'superadmin', 'status' => true]);
        $admin = User::create([
            'name' => 'Admin Test',
            'username' => 'admin_test',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
            'status' => true,
        ]);

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

        $diagnostic = Diagnostic::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'name' => 'Estado',
            'status' => true,
        ]);

        $condition = Condition::create([
            'client_id' => $client->id,
            'element_type_id' => $elementType->id,
            'code' => 'ALTA',
            'name' => 'Alta',
            'severity' => 1,
            'status' => true,
        ]);

        $inspectorRole = Role::firstOrCreate(['key' => 'inspector'], ['name' => 'inspector', 'status' => true]);
        $ana = User::create([
            'name' => 'Ana Cecilia',
            'username' => 'ana_test',
            'password' => bcrypt('secret'),
            'role_id' => $inspectorRole->id,
            'status' => true,
        ]);
        $norman = User::create([
            'name' => 'Norman Berrío',
            'username' => 'norman_test',
            'password' => bcrypt('secret'),
            'role_id' => $inspectorRole->id,
            'status' => true,
        ]);

        $report = ReportDetail::create([
            'user_id' => $ana->id,
            'element_id' => $element->id,
            'component_id' => $component->id,
            'diagnostic_id' => $diagnostic->id,
            'year' => 2026,
            'week' => 38,
            'condition_id' => $condition->id,
            'recommendation' => 'hallazgo original',
            'status' => true,
        ]);

        $report->files()->create([
            'uploaded_by' => $ana->id,
            'disk' => 'r2',
            'path' => 'fake/ana.jpg',
            'original_name' => 'ana.jpg',
            'stored_name' => 'ana-stored.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'file_type' => 'image',
            'evidence_kind' => 'hallazgo',
            'size_bytes' => 1000,
            'sort_order' => 0,
        ]);

        $report->files()->create([
            'uploaded_by' => $norman->id,
            'disk' => 'r2',
            'path' => 'fake/norman.jpg',
            'original_name' => 'norman.jpg',
            'stored_name' => 'norman-stored.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'file_type' => 'image',
            'evidence_kind' => 'hallazgo',
            'size_bytes' => 1000,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.preventive-reports.evidence', $report)
        );

        $response->assertOk();
        $response->assertSee('Subido por Ana Cecilia');
        $response->assertSee('Subido por Norman Berrío');
    }
}
