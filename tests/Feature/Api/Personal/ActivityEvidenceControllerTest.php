<?php

namespace Tests\Feature\Api\Personal;

use App\Models\Activity;
use App\Models\ActivityEvidence;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PersonalCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API de evidencias del supervisor (sección 14.24) — mismo patrón de
 * subida a R2 que App\Http\Controllers\Personal\ActivityEvidenceController
 * (web, sección 14.20), autenticado por Sanctum. show() devuelve JSON con
 * la URL firmada en vez de redirigir.
 */
class ActivityEvidenceControllerTest extends TestCase
{
    use RefreshDatabase;

    private function company(): Company
    {
        return Company::create(['name' => 'Empresa Test '.uniqid(), 'is_default' => true, 'archived' => false]);
    }

    private function empleado(string $nickname): Employee
    {
        $category = PersonalCategory::firstOrCreate(['name' => 'Campo Evidence API Test'], ['activo' => true]);

        return Employee::create([
            'nombre' => 'Empleado '.$nickname, 'nickname' => $nickname,
            'personal_category_id' => $category->id, 'activo' => true, 'has_login' => true,
            'username' => 'emp_'.uniqid(), 'password' => bcrypt('secret'), 'in_bitacora' => true,
        ]);
    }

    private function actividad(Employee $supervisor, array $overrides = []): Activity
    {
        return Activity::create(array_merge([
            'date' => today()->toDateString(), 'company_id' => $this->company()->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $supervisor->id,
        ], $overrides));
    }

    public function test_store_uploads_file_to_r2_and_creates_evidence(): void
    {
        Storage::fake('r2');
        $supervisor = $this->empleado('sup-evidencia-api');
        $activity = $this->actividad($supervisor);

        Sanctum::actingAs($supervisor, ['*']);
        $response = $this->postJson("/api/personal/actividades/{$activity->id}/evidencias", [
            'files' => [UploadedFile::fake()->image('foto.jpg', 100, 100)->size(500)],
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'evidencias');

        $evidence = ActivityEvidence::first();
        $this->assertEquals($activity->id, $evidence->activity_id);
        $this->assertEquals($supervisor->id, $evidence->uploaded_by_employee_id);
        $this->assertStringStartsWith('personal-actividades/', $evidence->path);
        Storage::disk('r2')->assertExists($evidence->path);
    }

    public function test_store_rejects_invalid_mime_type(): void
    {
        Storage::fake('r2');
        $supervisor = $this->empleado('sup-evidencia-mime-api');
        $activity = $this->actividad($supervisor);

        Sanctum::actingAs($supervisor, ['*']);
        $response = $this->postJson("/api/personal/actividades/{$activity->id}/evidencias", [
            'files' => [UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf')],
        ]);

        $response->assertStatus(422);
    }

    public function test_store_rejects_activity_that_does_not_belong_to_the_authenticated_employee(): void
    {
        Storage::fake('r2');
        $supervisor = $this->empleado('sup-evidencia-scope-api');
        $otro = $this->empleado('otro-evidencia-scope-api');
        $activity = $this->actividad($otro);

        Sanctum::actingAs($supervisor, ['*']);
        $response = $this->postJson("/api/personal/actividades/{$activity->id}/evidencias", [
            'files' => [UploadedFile::fake()->image('foto.jpg')],
        ]);

        $response->assertStatus(404);
    }

    public function test_store_rejects_closed_activity(): void
    {
        Storage::fake('r2');
        $supervisor = $this->empleado('sup-evidencia-cerrada-api');
        $activity = $this->actividad($supervisor, ['closed_at' => now()]);

        Sanctum::actingAs($supervisor, ['*']);
        $response = $this->postJson("/api/personal/actividades/{$activity->id}/evidencias", [
            'files' => [UploadedFile::fake()->image('foto.jpg')],
        ]);

        $response->assertStatus(403);
    }

    public function test_show_returns_json_with_a_signed_url_not_a_redirect(): void
    {
        Storage::fake('r2');
        $supervisor = $this->empleado('sup-evidencia-abrir-api');
        $activity = $this->actividad($supervisor);

        Storage::disk('r2')->put('personal-actividades/x/2026/actividad-1/archivo.jpg', 'contenido');
        $evidence = ActivityEvidence::create([
            'activity_id' => $activity->id, 'uploaded_by_employee_id' => $supervisor->id,
            'disk' => 'r2', 'path' => 'personal-actividades/x/2026/actividad-1/archivo.jpg',
            'original_name' => 'archivo.jpg', 'stored_name' => 'archivo.jpg',
            'mime_type' => 'image/jpeg', 'extension' => 'jpg', 'file_type' => 'image', 'size_bytes' => 9,
        ]);

        Sanctum::actingAs($supervisor, ['*']);
        $response = $this->getJson("/api/personal/actividades/{$activity->id}/evidencias/{$evidence->id}");

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure(['url', 'expires_at', 'file_type', 'original_name']);
    }

    public function test_destroy_removes_file_from_disk_and_database_row(): void
    {
        Storage::fake('r2');
        $supervisor = $this->empleado('sup-evidencia-borrar-api');
        $activity = $this->actividad($supervisor);

        Storage::disk('r2')->put('personal-actividades/x/2026/actividad-1/archivo.jpg', 'contenido');
        $evidence = ActivityEvidence::create([
            'activity_id' => $activity->id, 'uploaded_by_employee_id' => $supervisor->id,
            'disk' => 'r2', 'path' => 'personal-actividades/x/2026/actividad-1/archivo.jpg',
            'original_name' => 'archivo.jpg', 'stored_name' => 'archivo.jpg',
            'mime_type' => 'image/jpeg', 'extension' => 'jpg', 'file_type' => 'image', 'size_bytes' => 9,
        ]);

        Sanctum::actingAs($supervisor, ['*']);
        $response = $this->deleteJson("/api/personal/actividades/{$activity->id}/evidencias/{$evidence->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('activity_evidences', ['id' => $evidence->id]);
        Storage::disk('r2')->assertMissing($evidence->path);
    }
}
