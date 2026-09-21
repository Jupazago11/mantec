<?php

namespace Tests\Feature\Personal;

use App\Models\Activity;
use App\Models\ActivityEvidence;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PersonalCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cobertura de evidencias (foto/video) del registro del supervisor
 * (seccion 14.20). Mismo criterio de autorizacion que
 * SupervisorViewControllerTest: solo superadmin, actividad debe ser
 * realmente del empleado elegido, bloqueado si la actividad ya esta
 * cerrada.
 */
class ActivityEvidenceControllerTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        $role = Role::firstOrCreate(['key' => 'superadmin'], ['name' => 'superadmin', 'status' => true]);

        return User::create([
            'name' => 'Admin Test',
            'username' => 'admin_test_'.uniqid(),
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
            'status' => true,
        ]);
    }

    private function company(): Company
    {
        return Company::create(['name' => 'Empresa Test '.uniqid(), 'is_default' => true, 'archived' => false]);
    }

    private function empleado(string $nickname): Employee
    {
        $category = PersonalCategory::firstOrCreate(['name' => 'Campo Evidencias Test'], ['activo' => true]);

        return Employee::create([
            'nombre' => 'Empleado '.$nickname,
            'nickname' => $nickname,
            'personal_category_id' => $category->id,
            'activo' => true,
            'has_login' => false,
            'in_bitacora' => true,
        ]);
    }

    private function actividad(Employee $supervisor, array $overrides = []): Activity
    {
        return Activity::create(array_merge([
            'date' => today()->toDateString(),
            'company_id' => $this->company()->id,
            'description' => 'x',
            'activity_type' => 'P',
            'shift' => 'Diurno',
            'responsible_employee_id' => $supervisor->id,
        ], $overrides));
    }

    public function test_store_uploads_file_to_r2_and_creates_evidence(): void
    {
        Storage::fake('r2');
        $admin = $this->superadmin();
        $supervisor = $this->empleado('supervisor-evidencia-test');
        $activity = $this->actividad($supervisor);

        $file = UploadedFile::fake()->image('foto.jpg', 100, 100)->size(500);

        $response = $this->actingAs($admin)->post(
            route('personal.ver-como.evidencias.store', ['employee' => $supervisor->id, 'activity' => $activity->id]),
            ['files' => [$file]],
            ['Accept' => 'application/json']
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'evidencias');

        $this->assertDatabaseCount('activity_evidences', 1);
        $evidence = ActivityEvidence::first();
        $this->assertEquals($activity->id, $evidence->activity_id);
        $this->assertEquals($supervisor->id, $evidence->uploaded_by_employee_id);
        $this->assertEquals('image', $evidence->file_type);
        $this->assertStringStartsWith('personal-actividades/', $evidence->path);
        Storage::disk('r2')->assertExists($evidence->path);
    }

    public function test_store_rejects_non_superadmin(): void
    {
        Storage::fake('r2');
        $category = PersonalCategory::firstOrCreate(['name' => 'Campo Evidencias Test'], ['activo' => true]);
        $actor = Employee::create([
            'nombre' => 'Empleado Sin Superadmin', 'nickname' => 'sin-superadmin-evidencia-test',
            'personal_category_id' => $category->id, 'activo' => true, 'has_login' => true,
            'username' => 'sin_superadmin_evidencia_'.uniqid(), 'password' => bcrypt('secret'), 'in_bitacora' => true,
        ]);
        $supervisor = $this->empleado('supervisor-evidencia-autz-test');
        $activity = $this->actividad($supervisor);

        $response = $this->actingAs($actor, 'personal')->post(
            route('personal.ver-como.evidencias.store', ['employee' => $supervisor->id, 'activity' => $activity->id]),
            ['files' => [UploadedFile::fake()->image('foto.jpg')]],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(403);
    }

    public function test_store_rejects_activity_that_does_not_belong_to_the_chosen_employee(): void
    {
        Storage::fake('r2');
        $admin = $this->superadmin();
        $supervisor = $this->empleado('supervisor-evidencia-scope-test');
        $otro = $this->empleado('otro-evidencia-scope-test');
        $activity = $this->actividad($otro);

        $response = $this->actingAs($admin)->post(
            route('personal.ver-como.evidencias.store', ['employee' => $supervisor->id, 'activity' => $activity->id]),
            ['files' => [UploadedFile::fake()->image('foto.jpg')]],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(404);
    }

    public function test_store_rejects_closed_activity(): void
    {
        Storage::fake('r2');
        $admin = $this->superadmin();
        $supervisor = $this->empleado('supervisor-evidencia-cerrada-test');
        $activity = $this->actividad($supervisor, ['closed_at' => now()]);

        $response = $this->actingAs($admin)->post(
            route('personal.ver-como.evidencias.store', ['employee' => $supervisor->id, 'activity' => $activity->id]),
            ['files' => [UploadedFile::fake()->image('foto.jpg')]],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(403);
    }

    public function test_store_rejects_invalid_mime_type(): void
    {
        Storage::fake('r2');
        $admin = $this->superadmin();
        $supervisor = $this->empleado('supervisor-evidencia-mime-test');
        $activity = $this->actividad($supervisor);

        $response = $this->actingAs($admin)->post(
            route('personal.ver-como.evidencias.store', ['employee' => $supervisor->id, 'activity' => $activity->id]),
            ['files' => [UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf')]],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['files.0']);
    }

    public function test_store_rejects_file_larger_than_limit(): void
    {
        Storage::fake('r2');
        $admin = $this->superadmin();
        $supervisor = $this->empleado('supervisor-evidencia-tamano-test');
        $activity = $this->actividad($supervisor);

        $response = $this->actingAs($admin)->post(
            route('personal.ver-como.evidencias.store', ['employee' => $supervisor->id, 'activity' => $activity->id]),
            ['files' => [UploadedFile::fake()->image('foto.jpg')->size(51201)]],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['files.0']);
    }

    public function test_destroy_removes_file_from_disk_and_database_row(): void
    {
        Storage::fake('r2');
        $admin = $this->superadmin();
        $supervisor = $this->empleado('supervisor-evidencia-borrar-test');
        $activity = $this->actividad($supervisor);

        Storage::disk('r2')->put('personal-actividades/empresa-1/2026/actividad-1/archivo.jpg', 'contenido');
        $evidence = ActivityEvidence::create([
            'activity_id' => $activity->id, 'uploaded_by_employee_id' => $supervisor->id,
            'disk' => 'r2', 'path' => 'personal-actividades/empresa-1/2026/actividad-1/archivo.jpg',
            'original_name' => 'archivo.jpg', 'stored_name' => 'archivo.jpg',
            'mime_type' => 'image/jpeg', 'extension' => 'jpg', 'file_type' => 'image', 'size_bytes' => 9,
        ]);

        $response = $this->actingAs($admin)->delete(
            route('personal.ver-como.evidencias.destroy', ['employee' => $supervisor->id, 'activity' => $activity->id, 'evidence' => $evidence->id]),
            [],
            ['Accept' => 'application/json']
        );

        $response->assertOk();
        $this->assertDatabaseMissing('activity_evidences', ['id' => $evidence->id]);
        Storage::disk('r2')->assertMissing($evidence->path);
    }

    public function test_destroy_rejects_closed_activity(): void
    {
        Storage::fake('r2');
        $admin = $this->superadmin();
        $supervisor = $this->empleado('supervisor-evidencia-borrar-cerrada-test');
        $activity = $this->actividad($supervisor, ['closed_at' => now()]);

        $evidence = ActivityEvidence::create([
            'activity_id' => $activity->id, 'uploaded_by_employee_id' => $supervisor->id,
            'disk' => 'r2', 'path' => 'personal-actividades/x/2026/actividad-1/archivo.jpg',
            'original_name' => 'archivo.jpg', 'stored_name' => 'archivo.jpg',
            'mime_type' => 'image/jpeg', 'extension' => 'jpg', 'file_type' => 'image', 'size_bytes' => 9,
        ]);

        $response = $this->actingAs($admin)->delete(
            route('personal.ver-como.evidencias.destroy', ['employee' => $supervisor->id, 'activity' => $activity->id, 'evidence' => $evidence->id]),
            [],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('activity_evidences', ['id' => $evidence->id]);
    }

    public function test_open_redirects_to_a_url(): void
    {
        Storage::fake('r2');
        $admin = $this->superadmin();
        $supervisor = $this->empleado('supervisor-evidencia-abrir-test');
        $activity = $this->actividad($supervisor);

        Storage::disk('r2')->put('personal-actividades/x/2026/actividad-1/archivo.jpg', 'contenido');
        $evidence = ActivityEvidence::create([
            'activity_id' => $activity->id, 'uploaded_by_employee_id' => $supervisor->id,
            'disk' => 'r2', 'path' => 'personal-actividades/x/2026/actividad-1/archivo.jpg',
            'original_name' => 'archivo.jpg', 'stored_name' => 'archivo.jpg',
            'mime_type' => 'image/jpeg', 'extension' => 'jpg', 'file_type' => 'image', 'size_bytes' => 9,
        ]);

        $response = $this->actingAs($admin)->get(
            route('personal.ver-como.evidencias.open', ['employee' => $supervisor->id, 'activity' => $activity->id, 'evidence' => $evidence->id])
        );

        $response->assertRedirect();
    }
}
