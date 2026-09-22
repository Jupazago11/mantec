<?php

namespace Tests\Feature\Api\Personal;

use App\Models\Activity;
use App\Models\ActivityEmployeeHour;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PersonalCategory;
use App\Models\PersonalRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API de actividades del supervisor (sección 14.24) — equivalente a
 * SupervisorViewController (web, sección 14.18/14.20) pero autenticado
 * por Sanctum: el empleado es $request->user(), nunca un parámetro de
 * ruta. Cobertura de negocio (turno nocturno, horas por persona, etc.)
 * ya está en SupervisorViewControllerTest — aquí se enfoca en que la
 * autenticación/autorización vía token funcione igual de estricta.
 */
class ActivityControllerTest extends TestCase
{
    use RefreshDatabase;

    private function company(): Company
    {
        return Company::create(['name' => 'Empresa Test '.uniqid(), 'is_default' => true, 'archived' => false]);
    }

    private function empleado(string $nickname): Employee
    {
        $category = PersonalCategory::firstOrCreate(['name' => 'Campo Activity API Test'], ['activo' => true]);

        return Employee::create([
            'nombre' => 'Empleado '.$nickname, 'nickname' => $nickname,
            'personal_category_id' => $category->id, 'activo' => true, 'has_login' => true,
            'username' => 'emp_'.uniqid(), 'password' => bcrypt('secret'), 'in_bitacora' => true,
        ]);
    }

    public function test_index_lists_only_activities_where_the_authenticated_employee_is_responsible(): void
    {
        $company = $this->company();
        $supervisor = $this->empleado('sup-index-api');
        $otro = $this->empleado('otro-index-api');

        Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'Suya',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $supervisor->id,
        ]);
        Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'De otro',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $otro->id,
        ]);

        Sanctum::actingAs($supervisor, ['*']);
        $response = $this->getJson('/api/personal/actividades');

        $response->assertOk();
        $response->assertJsonCount(1, 'actividades');
        $response->assertJsonPath('actividades.0.description', 'Suya');
    }

    public function test_index_includes_yesterdays_night_shift_activity(): void
    {
        $company = $this->company();
        $supervisor = $this->empleado('sup-nocturno-api');

        Activity::create([
            'date' => today()->subDay()->toDateString(), 'company_id' => $company->id, 'description' => 'Turno nocturno de ayer',
            'activity_type' => 'P', 'shift' => 'Nocturno', 'responsible_employee_id' => $supervisor->id,
        ]);

        Sanctum::actingAs($supervisor, ['*']);
        $response = $this->getJson('/api/personal/actividades');

        $response->assertOk();
        $response->assertJsonCount(1, 'actividades');
    }

    public function test_index_rejects_requests_without_a_token(): void
    {
        $response = $this->getJson('/api/personal/actividades');

        $response->assertStatus(401);
    }

    // Sanctum es polimorfico: un token de User (Inspector) no debe poder
    // usar las rutas de Employee (personal.api.employee).
    public function test_index_rejects_a_user_inspector_token(): void
    {
        $role = Role::firstOrCreate(['key' => 'inspector'], ['name' => 'inspector', 'status' => true]);
        $inspector = User::create([
            'name' => 'Inspector Test', 'username' => 'insp_'.uniqid(),
            'password' => bcrypt('secret'), 'role_id' => $role->id, 'status' => true,
        ]);

        Sanctum::actingAs($inspector, ['*']);
        $response = $this->getJson('/api/personal/actividades');

        $response->assertStatus(403);
    }

    public function test_store_with_all_worked_scheduled_hours_sets_reported_hours_to_estimated(): void
    {
        $company = $this->company();
        $supervisor = $this->empleado('sup-store-api');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $supervisor->id,
            'estimated_hours' => 10,
        ]);

        Sanctum::actingAs($supervisor, ['*']);
        $response = $this->postJson("/api/personal/actividades/{$activity->id}", [
            'comments' => 'Todo normal', 'all_worked_scheduled_hours' => true, 'personas' => [],
        ]);

        $response->assertOk();
        $response->assertJsonPath('activity.reported_hours', 10);
        $this->assertDatabaseHas('activities', [
            'id' => $activity->id, 'hours_registered_by_employee_id' => $supervisor->id,
        ]);
    }

    public function test_store_with_per_person_hours_sums_reported_hours(): void
    {
        $company = $this->company();
        $supervisor = $this->empleado('sup-detalle-api');
        $persona = $this->empleado('persona-detalle-api');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $supervisor->id,
            'estimated_hours' => 8,
        ]);
        $activity->personas()->sync([$persona->id]);

        Sanctum::actingAs($supervisor, ['*']);
        $response = $this->postJson("/api/personal/actividades/{$activity->id}", [
            'all_worked_scheduled_hours' => false,
            'personas' => [['employee_id' => $persona->id, 'worked_hours' => 5]],
        ]);

        $response->assertOk();
        $response->assertJsonPath('activity.reported_hours', 5);
        $this->assertDatabaseHas('activity_employee_hours', [
            'activity_id' => $activity->id, 'employee_id' => $persona->id, 'worked_hours' => 5,
        ]);
    }

    public function test_store_rejects_activity_that_does_not_belong_to_the_authenticated_employee(): void
    {
        $company = $this->company();
        $supervisor = $this->empleado('sup-scope-api');
        $otro = $this->empleado('otro-scope-api');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $otro->id,
        ]);

        Sanctum::actingAs($supervisor, ['*']);
        $response = $this->postJson("/api/personal/actividades/{$activity->id}", [
            'all_worked_scheduled_hours' => true, 'personas' => [],
        ]);

        $response->assertStatus(404);
    }

    public function test_store_rejects_closed_activity(): void
    {
        $company = $this->company();
        $supervisor = $this->empleado('sup-cerrada-api');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $supervisor->id,
            'closed_at' => now(),
        ]);

        Sanctum::actingAs($supervisor, ['*']);
        $response = $this->postJson("/api/personal/actividades/{$activity->id}", [
            'all_worked_scheduled_hours' => true, 'personas' => [],
        ]);

        $response->assertStatus(403);
    }

    public function test_store_rejects_negative_worked_hours(): void
    {
        $company = $this->company();
        $supervisor = $this->empleado('sup-negativo-api');
        $persona = $this->empleado('persona-negativo-api');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $supervisor->id,
        ]);

        Sanctum::actingAs($supervisor, ['*']);
        $response = $this->postJson("/api/personal/actividades/{$activity->id}", [
            'all_worked_scheduled_hours' => false,
            'personas' => [['employee_id' => $persona->id, 'worked_hours' => -1]],
        ]);

        $response->assertStatus(422);
    }

    // Pedido 2026-09-22: el responsable puede comentar sobre un trabajador
    // puntual de esta actividad, ademas de las horas.
    public function test_store_saves_a_comment_for_a_person(): void
    {
        $company = $this->company();
        $supervisor = $this->empleado('sup-comentario-api');
        $persona = $this->empleado('persona-comentario-api');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $supervisor->id,
        ]);
        $activity->personas()->sync([$persona->id]);

        Sanctum::actingAs($supervisor, ['*']);
        $response = $this->postJson("/api/personal/actividades/{$activity->id}", [
            'all_worked_scheduled_hours' => false,
            'personas' => [['employee_id' => $persona->id, 'worked_hours' => 5, 'comment' => 'Se fue temprano']],
        ]);

        $response->assertOk();
        $response->assertJsonPath('activity.personas.0.comment', 'Se fue temprano');
        $this->assertDatabaseHas('activity_employee_comments', [
            'activity_id' => $activity->id, 'employee_id' => $persona->id,
            'author_employee_id' => $supervisor->id, 'author_name' => $supervisor->nombre,
            'comment' => 'Se fue temprano',
        ]);
    }

    // "Solo 1 del responsable" (confirmado con el usuario): un segundo
    // guardado con texto distinto reemplaza el comentario anterior, no lo
    // acumula — a diferencia del historial de administrativo en Bitacora.
    public function test_store_editing_a_comment_replaces_it_instead_of_accumulating(): void
    {
        $company = $this->company();
        $supervisor = $this->empleado('sup-editar-comentario-api');
        $persona = $this->empleado('persona-editar-comentario-api');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $supervisor->id,
        ]);
        $activity->personas()->sync([$persona->id]);

        Sanctum::actingAs($supervisor, ['*']);
        $this->postJson("/api/personal/actividades/{$activity->id}", [
            'all_worked_scheduled_hours' => false,
            'personas' => [['employee_id' => $persona->id, 'worked_hours' => 5, 'comment' => 'Primer comentario']],
        ])->assertOk();

        $response = $this->postJson("/api/personal/actividades/{$activity->id}", [
            'all_worked_scheduled_hours' => false,
            'personas' => [['employee_id' => $persona->id, 'worked_hours' => 5, 'comment' => 'Comentario editado']],
        ]);

        $response->assertOk();
        $response->assertJsonPath('activity.personas.0.comment', 'Comentario editado');
        $this->assertDatabaseCount('activity_employee_comments', 1);
    }

    // "Puede... borrarlo desde el modal" — mandar comment vacio borra la
    // fila (no la deja como cadena vacia).
    public function test_store_with_empty_comment_deletes_it(): void
    {
        $company = $this->company();
        $supervisor = $this->empleado('sup-borrar-comentario-api');
        $persona = $this->empleado('persona-borrar-comentario-api');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $supervisor->id,
        ]);
        $activity->personas()->sync([$persona->id]);

        Sanctum::actingAs($supervisor, ['*']);
        $this->postJson("/api/personal/actividades/{$activity->id}", [
            'all_worked_scheduled_hours' => false,
            'personas' => [['employee_id' => $persona->id, 'worked_hours' => 5, 'comment' => 'Se fue temprano']],
        ])->assertOk();

        $response = $this->postJson("/api/personal/actividades/{$activity->id}", [
            'all_worked_scheduled_hours' => false,
            'personas' => [['employee_id' => $persona->id, 'worked_hours' => 5, 'comment' => '']],
        ]);

        $response->assertOk();
        $response->assertJsonPath('activity.personas.0.comment', null);
        $this->assertDatabaseCount('activity_employee_comments', 0);
    }

    // Volver a "Si, todos trabajaron" no borra un comentario ya escrito
    // (decision explicita, ver ActivityController::store).
    public function test_switching_to_all_worked_does_not_delete_existing_comment(): void
    {
        $company = $this->company();
        $supervisor = $this->empleado('sup-preserva-comentario-api');
        $persona = $this->empleado('persona-preserva-comentario-api');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $supervisor->id,
            'estimated_hours' => 8,
        ]);
        $activity->personas()->sync([$persona->id]);

        Sanctum::actingAs($supervisor, ['*']);
        $this->postJson("/api/personal/actividades/{$activity->id}", [
            'all_worked_scheduled_hours' => false,
            'personas' => [['employee_id' => $persona->id, 'worked_hours' => 5, 'comment' => 'Se fue temprano']],
        ])->assertOk();

        $this->postJson("/api/personal/actividades/{$activity->id}", [
            'all_worked_scheduled_hours' => true, 'personas' => [],
        ])->assertOk();

        $this->assertDatabaseHas('activity_employee_comments', [
            'activity_id' => $activity->id, 'employee_id' => $persona->id, 'comment' => 'Se fue temprano',
        ]);
    }
}
