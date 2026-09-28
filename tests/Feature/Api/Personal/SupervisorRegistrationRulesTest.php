<?php

namespace Tests\Feature\Api\Personal;

use App\Models\Activity;
use App\Models\ActivityEmployeeComment;
use App\Models\ActivityEmployeeHour;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PersonalCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Reglas del registro del responsable desde la app (revision de seguridad
 * 2026-09-28, App\Services\Personal\RegistroSupervisor):
 * - solo actividades de hoy o de ayer (consultar, registrar, evidencias);
 * - solo personas asignadas a la actividad (antes se podia inyectar un
 *   comentario en la Bitacora de cualquiera, y un id inexistente daba 500);
 * - horas por persona 0-24; filas de horas viejas se limpian;
 * - un empleado inactivado o sin acceso ya no puede usar su token.
 */
class SupervisorRegistrationRulesTest extends TestCase
{
    use RefreshDatabase;

    private function company(): Company
    {
        return Company::firstOrCreate(['name' => 'Empresa Reglas API'], ['is_default' => true, 'archived' => false]);
    }

    private function empleado(string $nickname, bool $login = true): Employee
    {
        $category = PersonalCategory::firstOrCreate(['name' => 'Campo Reglas API'], ['activo' => true]);

        return Employee::create([
            'nombre' => 'Empleado '.$nickname, 'nickname' => $nickname,
            'personal_category_id' => $category->id, 'activo' => true, 'has_login' => $login,
            'username' => $login ? 'u_'.uniqid() : null, 'password' => $login ? bcrypt('secret') : null, 'in_bitacora' => true,
        ]);
    }

    private function actividad(Employee $responsable, string $fecha, array $personas = [], array $extra = []): Activity
    {
        $a = Activity::create(array_merge([
            'date' => $fecha, 'company_id' => $this->company()->id, 'description' => 'Actividad '.uniqid(),
            'activity_type' => 'S', 'shift' => 'Diurno', 'estimated_hours' => 8, 'responsible_employee_id' => $responsable->id,
        ], $extra));
        $a->personas()->sync(collect($personas)->pluck('id'));

        return $a;
    }

    public function test_cannot_query_dates_outside_today_and_yesterday(): void
    {
        $sup = $this->empleado('sup-consulta');
        Sanctum::actingAs($sup, ['*']);

        $this->getJson('/api/personal/actividades?date='.today()->toDateString())->assertOk();
        $this->getJson('/api/personal/actividades?date='.today()->subDay()->toDateString())->assertOk();
        $this->getJson('/api/personal/actividades?date='.today()->subDays(2)->toDateString())
            ->assertStatus(422)->assertJsonPath('success', false);
        $this->getJson('/api/personal/actividades?date=no-es-fecha')->assertStatus(422);
    }

    // Consultando "ayer", la nocturna de ANTEAYER ya queda fuera de la
    // ventana y no se lista (no se podria registrar).
    public function test_yesterday_view_does_not_include_night_shift_from_two_days_ago(): void
    {
        $sup = $this->empleado('sup-nocturna');
        $this->actividad($sup, today()->subDays(2)->toDateString(), [], ['shift' => 'Nocturno', 'description' => 'Nocturna anteayer']);
        $this->actividad($sup, today()->subDay()->toDateString(), [], ['description' => 'De ayer']);
        Sanctum::actingAs($sup, ['*']);

        $descripciones = collect($this->getJson('/api/personal/actividades?date='.today()->subDay()->toDateString())->json('actividades'))->pluck('description');

        $this->assertSame(['De ayer'], $descripciones->all());
    }

    public function test_cannot_register_an_activity_older_than_yesterday(): void
    {
        $sup = $this->empleado('sup-vieja');
        $vieja = $this->actividad($sup, today()->subDays(3)->toDateString());
        Sanctum::actingAs($sup, ['*']);

        $this->postJson("/api/personal/actividades/{$vieja->id}", ['all_worked_scheduled_hours' => true])
            ->assertStatus(403)->assertJsonPath('success', false);

        $this->assertNull($vieja->fresh()->hours_registered_at);
    }

    public function test_rejects_people_not_assigned_to_the_activity(): void
    {
        $sup = $this->empleado('sup-ajenos');
        $asignado = $this->empleado('asignado', false);
        $ajeno = $this->empleado('ajeno', false);
        $a = $this->actividad($sup, today()->toDateString(), [$asignado]);
        Sanctum::actingAs($sup, ['*']);

        $this->postJson("/api/personal/actividades/{$a->id}", [
            'all_worked_scheduled_hours' => false,
            'personas' => [['employee_id' => $ajeno->id, 'worked_hours' => 8, 'comment' => 'inyectado']],
        ])->assertStatus(422)->assertJsonValidationErrors('personas.0.employee_id');

        $this->postJson("/api/personal/actividades/{$a->id}", [
            'all_worked_scheduled_hours' => false,
            'personas' => [['employee_id' => 99999999, 'worked_hours' => 8]],
        ])->assertStatus(422);

        $this->assertDatabaseCount('activity_employee_hours', 0);
        $this->assertDatabaseMissing('activity_employee_comments', ['employee_id' => $ajeno->id]);
    }

    public function test_hours_per_person_cannot_exceed_24(): void
    {
        $sup = $this->empleado('sup-horas');
        $p = $this->empleado('persona-horas', false);
        $a = $this->actividad($sup, today()->toDateString(), [$p]);
        Sanctum::actingAs($sup, ['*']);

        $this->postJson("/api/personal/actividades/{$a->id}", [
            'all_worked_scheduled_hours' => false,
            'personas' => [['employee_id' => $p->id, 'worked_hours' => 25]],
        ])->assertStatus(422)->assertJsonValidationErrors('personas.0.worked_hours');
    }

    // Una cuadrilla grande con detalle por persona: la suma (reported_hours)
    // ya no desborda la columna (decimal(8,2)).
    public function test_large_crew_total_does_not_overflow_reported_hours(): void
    {
        $sup = $this->empleado('sup-cuadrilla');
        $personas = collect(range(1, 45))->map(fn ($i) => $this->empleado("cuadrilla-$i", false));
        $a = $this->actividad($sup, today()->toDateString(), $personas->all());
        Sanctum::actingAs($sup, ['*']);

        $this->postJson("/api/personal/actividades/{$a->id}", [
            'all_worked_scheduled_hours' => false,
            'personas' => $personas->map(fn ($p) => ['employee_id' => $p->id, 'worked_hours' => 24])->values()->all(),
        ])->assertOk();

        $this->assertSame('1080.00', $a->fresh()->reported_hours);
    }

    // Si en un segundo envio falta una persona, su fila de horas vieja se
    // borra (antes quedaba con el valor anterior).
    public function test_stale_hour_rows_are_removed_on_resubmit(): void
    {
        $sup = $this->empleado('sup-stale');
        $ana = $this->empleado('ana-stale', false);
        $beto = $this->empleado('beto-stale', false);
        $a = $this->actividad($sup, today()->toDateString(), [$ana, $beto]);
        Sanctum::actingAs($sup, ['*']);

        $this->postJson("/api/personal/actividades/{$a->id}", [
            'all_worked_scheduled_hours' => false,
            'personas' => [['employee_id' => $ana->id, 'worked_hours' => 8], ['employee_id' => $beto->id, 'worked_hours' => 6]],
        ])->assertOk();
        $this->postJson("/api/personal/actividades/{$a->id}", [
            'all_worked_scheduled_hours' => false,
            'personas' => [['employee_id' => $ana->id, 'worked_hours' => 7]],
        ])->assertOk();

        $this->assertSame([$ana->id], ActivityEmployeeHour::where('activity_id', $a->id)->pluck('employee_id')->all());
        $this->assertSame('7.00', $a->fresh()->reported_hours);
    }

    public function test_deactivated_employee_token_stops_working(): void
    {
        $sup = $this->empleado('sup-inactivo');
        $token = $sup->createToken('supervisor-app')->plainTextToken;

        $this->withToken($token)->getJson('/api/personal/actividades')->assertOk();

        $sup->update(['activo' => false]);
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/personal/actividades')->assertStatus(401)->assertJsonPath('success', false);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_employee_without_login_token_stops_working(): void
    {
        $sup = $this->empleado('sup-sin-login');
        $token = $sup->createToken('supervisor-app')->plainTextToken;
        $sup->update(['has_login' => false]);

        $this->withToken($token)->getJson('/api/personal/actividades')->assertStatus(401);
    }

    public function test_evidence_upload_limited_to_today_and_yesterday(): void
    {
        Storage::fake('r2');
        $sup = $this->empleado('sup-evidencia');
        $vieja = $this->actividad($sup, today()->subDays(2)->toDateString());
        $hoy = $this->actividad($sup, today()->toDateString());
        Sanctum::actingAs($sup, ['*']);

        $this->post("/api/personal/actividades/{$vieja->id}/evidencias", [
            'files' => [UploadedFile::fake()->image('foto.jpg')],
        ], ['Accept' => 'application/json'])->assertStatus(403);

        $this->post("/api/personal/actividades/{$hoy->id}/evidencias", [
            'files' => [UploadedFile::fake()->image('foto.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();
    }

    // El comentario por persona se sigue guardando y borrando igual que
    // antes (seccion 14.31) a traves del servicio compartido.
    public function test_person_comment_is_saved_and_cleared(): void
    {
        $sup = $this->empleado('sup-comentario');
        $p = $this->empleado('persona-comentario', false);
        $a = $this->actividad($sup, today()->toDateString(), [$p]);
        Sanctum::actingAs($sup, ['*']);

        $this->postJson("/api/personal/actividades/{$a->id}", [
            'all_worked_scheduled_hours' => false,
            'personas' => [['employee_id' => $p->id, 'worked_hours' => 8, 'comment' => 'Se fue temprano']],
        ])->assertOk();
        $this->assertSame('Se fue temprano', ActivityEmployeeComment::where('employee_id', $p->id)->value('comment'));

        $this->postJson("/api/personal/actividades/{$a->id}", [
            'all_worked_scheduled_hours' => false,
            'personas' => [['employee_id' => $p->id, 'worked_hours' => 8, 'comment' => '']],
        ])->assertOk();
        $this->assertDatabaseMissing('activity_employee_comments', ['employee_id' => $p->id]);
    }
}
