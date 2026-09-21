<?php

namespace Tests\Feature\Personal;

use App\Models\Activity;
use App\Models\ActivityEmployeeHour;
use App\Models\BitacoraEntry;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PersonalCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobertura de Bitácora (2026-09-19, sección 14.19) — hasta hoy no tenía
 * ningún test. Se agrega al conectar "reportada" (antes hardcodeada en
 * null) con el registro real del supervisor (sección 7/14.18): prioridad
 * corregida > reportada > programada.
 *
 * Nota: estas aserciones verifican los valores de programada/reportada/
 * corregida que el backend efectivamente sirve por celda (cada uno es su
 * propio "campo: @js(...)," dentro del x-data de la celda, ver
 * personal/bitacora/index.blade.php). El getter Alpine "final" que
 * combina esos tres valores en pantalla es JS puro (get final() {...}) y
 * no se puede asegurar con un test de contenido HTML estático — no hay
 * herramienta de navegador disponible en esta sesión para probarlo en
 * runtime, así que esa parte queda pendiente de verificación manual.
 */
class BitacoraControllerTest extends TestCase
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
        $category = PersonalCategory::firstOrCreate(['name' => 'Campo Bitacora Test'], ['activo' => true]);

        return Employee::create([
            'nombre' => 'Empleado '.$nickname,
            'nickname' => $nickname,
            'personal_category_id' => $category->id,
            'activo' => true,
            'has_login' => false,
            'in_bitacora' => true,
        ]);
    }

    public function test_unregistered_activity_shows_programada_and_no_reportada(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $persona = $this->empleado('sin-registrar-test');

        $activity = Activity::create([
            'date' => '2026-03-05', 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 8,
        ]);
        $activity->personas()->sync([$persona->id]);

        $response = $this->actingAs($admin)->get(route('personal.bitacora.index', ['year' => 2026, 'month' => 3]));

        $response->assertOk();
        // Todavía no se registró nada -> reportada sigue en null, mismo
        // comportamiento que antes de conectar "Ver como supervisor".
        $response->assertSee('programada: 8,', false);
        $response->assertSee('reportada: null,', false);
    }

    public function test_registered_activity_with_per_person_hours_overrides_programada_as_reportada(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $persona = $this->empleado('registrado-test');

        $activity = Activity::create([
            'date' => '2026-03-06', 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 10,
            'all_worked_scheduled_hours' => false,
            'hours_registered_by_employee_id' => $persona->id,
            'hours_registered_at' => now(),
        ]);
        $activity->personas()->sync([$persona->id]);
        ActivityEmployeeHour::create([
            'activity_id' => $activity->id, 'employee_id' => $persona->id,
            'worked' => true, 'worked_hours' => 6,
        ]);

        $response = $this->actingAs($admin)->get(route('personal.bitacora.index', ['year' => 2026, 'month' => 3]));

        $response->assertOk();
        // programada sigue siendo 10 (lo agendado), reportada (lo que
        // realmente registró el supervisor) es 6 — distinto valor, ambos
        // servidos.
        $response->assertSee('programada: 10,', false);
        $response->assertSee('reportada: 6,', false);
    }

    public function test_registered_activity_with_all_worked_scheduled_hours_reports_estimated_hours(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $persona = $this->empleado('todos-trabajaron-test');

        $activity = Activity::create([
            'date' => '2026-03-07', 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 8,
            'all_worked_scheduled_hours' => true,
            'hours_registered_by_employee_id' => $persona->id,
            'hours_registered_at' => now(),
        ]);
        $activity->personas()->sync([$persona->id]);

        $response = $this->actingAs($admin)->get(route('personal.bitacora.index', ['year' => 2026, 'month' => 3]));

        $response->assertOk();
        // Confirmado por el supervisor sin override -> reportada = estimated_hours.
        $response->assertSee('reportada: 8,', false);
    }

    public function test_admin_correction_is_served_alongside_reportada(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $persona = $this->empleado('correccion-gana-test');

        $activity = Activity::create([
            'date' => '2026-03-08', 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 10,
            'all_worked_scheduled_hours' => false,
            'hours_registered_by_employee_id' => $persona->id,
            'hours_registered_at' => now(),
        ]);
        $activity->personas()->sync([$persona->id]);
        ActivityEmployeeHour::create([
            'activity_id' => $activity->id, 'employee_id' => $persona->id,
            'worked' => true, 'worked_hours' => 6,
        ]);
        BitacoraEntry::create(['employee_id' => $persona->id, 'date' => '2026-03-08', 'corrected_value' => '9']);

        $response = $this->actingAs($admin)->get(route('personal.bitacora.index', ['year' => 2026, 'month' => 3]));

        $response->assertOk();
        // Los tres valores llegan servidos — el getter Alpine "final" (JS,
        // sin cobertura automatizada aquí) es el que debe elegir corregida
        // sobre reportada/programada en pantalla.
        $response->assertSee('programada: 10,', false);
        $response->assertSee('reportada: 6,', false);
        $response->assertSee("corregida: '9',", false);
    }

    public function test_unauthorized_employee_cannot_view_bitacora(): void
    {
        $persona = $this->empleado('sin-permiso-bitacora-test');

        $response = $this->actingAs($persona, 'personal')->get(route('personal.bitacora.index'));

        $response->assertStatus(403);
    }
}
