<?php

namespace Tests\Feature\Personal;

use App\Models\Activity;
use App\Models\ActivityEmployeeHour;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PersonalCategory;
use App\Models\PersonalRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobertura de "Ver como supervisor" (2026-09-19, sección 14.18): pantalla
 * de prototipo, solo superadmin, que no reemplaza la sesión — la
 * autorización real vive en cada request (SupervisorViewController).
 */
class SupervisorViewControllerTest extends TestCase
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
        $category = PersonalCategory::firstOrCreate(['name' => 'Campo Ver Como Test'], ['activo' => true]);

        return Employee::create([
            'nombre' => 'Empleado '.$nickname,
            'nickname' => $nickname,
            'personal_category_id' => $category->id,
            'activo' => true,
            'has_login' => false,
            'in_bitacora' => true,
        ]);
    }

    // "Ver como supervisor" exige isSuperadmin() especificamente — ni
    // siquiera un Employee con todos los permisos del modulo Personal
    // marcados (ver_empleados, etc.) puede entrar, porque esos permisos no
    // implican ser superadmin.
    public function test_employee_with_full_personal_permissions_still_cannot_access_ver_como(): void
    {
        $category = PersonalCategory::firstOrCreate(['name' => 'Campo Ver Como Test'], ['activo' => true]);
        $rolCompleto = PersonalRole::create([
            'name' => 'Rol Completo Ver Como Test',
            'personal_category_id' => $category->id,
            'activo' => true, 'disponible_en_programacion' => true, 'responsable_actividad' => false,
            'ver_empleados' => true, 'ver_programacion' => true, 'editar_programacion_sin_limite' => true,
            'ver_diario_campo' => true, 'cerrar_diario_campo' => true, 'ver_bitacora' => true,
        ]);
        $actor = Employee::create([
            'nombre' => 'Empleado Con Todos Los Permisos', 'nickname' => 'con-todos-los-permisos-test',
            'personal_category_id' => $category->id, 'personal_role_id' => $rolCompleto->id,
            'activo' => true, 'has_login' => true, 'username' => 'con_todos_permisos_'.uniqid(),
            'password' => bcrypt('secret'), 'in_bitacora' => true,
        ]);
        $supervisor = $this->empleado('supervisor-auth-test');

        $response = $this->actingAs($actor, 'personal')->get(route('personal.ver-como.index', $supervisor));

        $response->assertStatus(403);
    }

    public function test_index_only_shows_activities_where_employee_is_the_responsible(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $supervisor = $this->empleado('supervisor-index-test');
        $otro = $this->empleado('otro-supervisor-index-test');

        $suya = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'Suya',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $supervisor->id,
        ]);
        Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'De otro',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $otro->id,
        ]);

        $response = $this->actingAs($admin)->get(route('personal.ver-como.index', $supervisor));

        $response->assertOk();
        $response->assertSee('Suya');
        $response->assertDontSee('De otro');
    }

    // Un turno nocturno cruza medianoche: si quedo programado "ayer", el
    // responsable debe seguir viendola hoy al momento de diligenciarla.
    public function test_index_includes_yesterdays_night_shift_activity(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $supervisor = $this->empleado('supervisor-nocturno-test');

        Activity::create([
            'date' => today()->subDay()->toDateString(), 'company_id' => $company->id, 'description' => 'Turno nocturno de ayer',
            'activity_type' => 'P', 'shift' => 'Nocturno', 'responsible_employee_id' => $supervisor->id,
        ]);

        $response = $this->actingAs($admin)->get(route('personal.ver-como.index', $supervisor));

        $response->assertOk();
        $response->assertSee('Turno nocturno de ayer');
    }

    // Solo el turno Nocturno cruza medianoche — uno Diurno de ayer no
    // deberia arrastrarse a la lista de hoy.
    public function test_index_excludes_yesterdays_day_shift_activity(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $supervisor = $this->empleado('supervisor-diurno-ayer-test');

        Activity::create([
            'date' => today()->subDay()->toDateString(), 'company_id' => $company->id, 'description' => 'Turno diurno de ayer',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $supervisor->id,
        ]);

        $response = $this->actingAs($admin)->get(route('personal.ver-como.index', $supervisor));

        $response->assertOk();
        $response->assertDontSee('Turno diurno de ayer');
    }

    // Pedido explicito del usuario: la nocturna de ayer se sigue mostrando
    // aunque ya este "Registrada" — no desaparece de la lista al guardarse.
    public function test_index_still_shows_yesterdays_night_shift_activity_once_registered(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $supervisor = $this->empleado('supervisor-nocturno-registrado-test');

        Activity::create([
            'date' => today()->subDay()->toDateString(), 'company_id' => $company->id, 'description' => 'Nocturna ya registrada',
            'activity_type' => 'P', 'shift' => 'Nocturno', 'responsible_employee_id' => $supervisor->id,
            'hours_registered_by_employee_id' => $supervisor->id, 'hours_registered_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('personal.ver-como.index', $supervisor));

        $response->assertOk();
        $response->assertSee('Nocturna ya registrada');
    }

    public function test_store_with_all_worked_scheduled_hours_sets_reported_hours_to_estimated(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $supervisor = $this->empleado('supervisor-store-test');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $supervisor->id,
            'estimated_hours' => 10,
        ]);

        $response = $this->actingAs($admin)->postJson(
            route('personal.ver-como.store', ['employee' => $supervisor->id, 'activity' => $activity->id]),
            ['comments' => 'Todo normal', 'all_worked_scheduled_hours' => true, 'personas' => []]
        );

        $response->assertOk();
        $response->assertJsonPath('activity.reported_hours', 10);
        $response->assertJsonPath('activity.registrado', true);
        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'comments' => 'Todo normal',
            'all_worked_scheduled_hours' => true,
            'hours_registered_by_employee_id' => $supervisor->id,
        ]);
    }

    // Simplificado 2026-09-19: sin hora inicio/hora final por ahora — el
    // supervisor captura la cantidad de horas directamente por persona.
    public function test_store_with_per_person_hours_sums_reported_hours(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $supervisor = $this->empleado('supervisor-detalle-test');
        $trabajoMenos = $this->empleado('trabajo-menos-test');
        $noTrabajo = $this->empleado('no-trabajo-test');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Nocturno', 'responsible_employee_id' => $supervisor->id,
            'estimated_hours' => 8,
        ]);
        $activity->personas()->sync([$trabajoMenos->id, $noTrabajo->id]);

        $response = $this->actingAs($admin)->postJson(
            route('personal.ver-como.store', ['employee' => $supervisor->id, 'activity' => $activity->id]),
            [
                'comments' => null,
                'all_worked_scheduled_hours' => false,
                'personas' => [
                    ['employee_id' => $trabajoMenos->id, 'worked_hours' => 5],
                    ['employee_id' => $noTrabajo->id, 'worked_hours' => 0],
                ],
            ]
        );

        $response->assertOk();
        $response->assertJsonPath('activity.reported_hours', 5);

        $this->assertDatabaseHas('activity_employee_hours', [
            'activity_id' => $activity->id, 'employee_id' => $trabajoMenos->id, 'worked' => true, 'worked_hours' => 5,
        ]);
        $this->assertDatabaseHas('activity_employee_hours', [
            'activity_id' => $activity->id, 'employee_id' => $noTrabajo->id, 'worked' => false, 'worked_hours' => 0,
        ]);
    }

    // "Horas jamás deben ser negativas" (mismo criterio ya pedido para
    // "Horas estimadas" en Programación) aplica también aquí.
    public function test_store_rejects_negative_worked_hours(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $supervisor = $this->empleado('supervisor-negativo-test');
        $persona = $this->empleado('persona-negativo-test');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $supervisor->id,
        ]);

        $response = $this->actingAs($admin)->postJson(
            route('personal.ver-como.store', ['employee' => $supervisor->id, 'activity' => $activity->id]),
            [
                'all_worked_scheduled_hours' => false,
                'personas' => [
                    ['employee_id' => $persona->id, 'worked_hours' => -1],
                ],
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['personas.0.worked_hours']);
    }

    public function test_store_rejects_activity_that_does_not_belong_to_the_chosen_employee(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $supervisor = $this->empleado('supervisor-scope-test');
        $otro = $this->empleado('otro-scope-test');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $otro->id,
        ]);

        $response = $this->actingAs($admin)->postJson(
            route('personal.ver-como.store', ['employee' => $supervisor->id, 'activity' => $activity->id]),
            ['all_worked_scheduled_hours' => true, 'personas' => []]
        );

        $response->assertStatus(404);
    }

    public function test_store_rejects_closed_activity(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $supervisor = $this->empleado('supervisor-cerrada-test');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $supervisor->id,
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->postJson(
            route('personal.ver-como.store', ['employee' => $supervisor->id, 'activity' => $activity->id]),
            ['all_worked_scheduled_hours' => true, 'personas' => []]
        );

        $response->assertStatus(403);
    }

    public function test_marking_all_worked_scheduled_hours_true_clears_previous_per_person_rows(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $supervisor = $this->empleado('supervisor-limpieza-test');
        $persona = $this->empleado('persona-limpieza-test');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'responsible_employee_id' => $supervisor->id,
            'estimated_hours' => 6,
        ]);
        ActivityEmployeeHour::create([
            'activity_id' => $activity->id, 'employee_id' => $persona->id,
            'worked' => true, 'worked_hours' => 6,
        ]);

        $response = $this->actingAs($admin)->postJson(
            route('personal.ver-como.store', ['employee' => $supervisor->id, 'activity' => $activity->id]),
            ['all_worked_scheduled_hours' => true, 'personas' => []]
        );

        $response->assertOk();
        $this->assertDatabaseMissing('activity_employee_hours', ['activity_id' => $activity->id, 'employee_id' => $persona->id]);
    }
}
