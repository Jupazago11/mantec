<?php

namespace Tests\Feature\Personal;

use App\Models\Activity;
use App\Models\ActivityEmployeeComment;
use App\Models\ActivityEmployeeHour;
use App\Models\BitacoraEntry;
use App\Models\BitacoraHoliday;
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

    // Pedido 2026-09-22: el comentario de administrativo deja de
    // sobrescribirse y pasa a ser historial (igual que el del responsable
    // desde la app) — dos guardados con texto distinto deben acumularse,
    // no reemplazarse.
    public function test_admin_comments_accumulate_as_history_instead_of_overwriting(): void
    {
        $admin = $this->superadmin();
        $persona = $this->empleado('historial-admin-test');

        $this->actingAs($admin)->post(route('personal.bitacora.entries.store'), [
            'employee_id' => $persona->id, 'date' => '2026-03-09', 'comment' => 'Primer comentario admin',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('personal.bitacora.entries.store'), [
            'employee_id' => $persona->id, 'date' => '2026-03-09', 'comment' => 'Segundo comentario admin',
        ])->assertRedirect();

        $this->assertDatabaseCount('activity_employee_comments', 2);
        $this->assertDatabaseHas('activity_employee_comments', [
            'employee_id' => $persona->id, 'date' => '2026-03-09', 'activity_id' => null,
            'author_name' => 'Admin Test (superadmin)', 'comment' => 'Primer comentario admin',
        ]);
        $this->assertDatabaseHas('activity_employee_comments', [
            'employee_id' => $persona->id, 'date' => '2026-03-09', 'activity_id' => null,
            'author_name' => 'Admin Test (superadmin)', 'comment' => 'Segundo comentario admin',
        ]);

        $response = $this->actingAs($admin)->get(route('personal.bitacora.index', ['year' => 2026, 'month' => 3]));
        $response->assertOk();
        $response->assertSee('Primer comentario admin', false);
        $response->assertSee('Segundo comentario admin', false);
    }

    // Enviar el formulario sin texto no debe crear una fila vacia en el
    // historial.
    public function test_saving_entry_without_comment_does_not_create_empty_history_row(): void
    {
        $admin = $this->superadmin();
        $persona = $this->empleado('sin-comentario-test');

        $this->actingAs($admin)->post(route('personal.bitacora.entries.store'), [
            'employee_id' => $persona->id, 'date' => '2026-03-10', 'corrected_value' => '9',
        ])->assertRedirect();

        $this->assertDatabaseCount('activity_employee_comments', 0);
    }

    // El comentario del responsable (activity_id no nulo, escrito desde la
    // app Android) debe aparecer en Bitacora mezclado con el del admin,
    // marcado como tal.
    public function test_responsible_comment_from_the_app_shows_up_in_bitacora_history(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $persona = $this->empleado('comentario-responsable-test');
        $responsable = $this->empleado('responsable-comentario-test');

        $activity = Activity::create([
            'date' => '2026-03-11', 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 8,
            'responsible_employee_id' => $responsable->id,
        ]);
        $activity->personas()->sync([$persona->id]);

        ActivityEmployeeComment::create([
            'employee_id' => $persona->id, 'date' => '2026-03-11', 'activity_id' => $activity->id,
            'author_employee_id' => $responsable->id, 'author_name' => $responsable->nombre,
            'comment' => 'Se fue temprano',
        ]);

        $response = $this->actingAs($admin)->get(route('personal.bitacora.index', ['year' => 2026, 'month' => 3]));

        $response->assertOk();
        $response->assertSee('Se fue temprano', false);
        // @js() escapa comillas como " (JSON_HEX_QUOT) — el HTML
        // crudo trae la clave asi, no con comillas literales (chr(92) =
        // backslash literal, para evitar que el propio editor interprete
        // " como una comilla real al escribir este archivo).
        $response->assertSee('es_responsable'.chr(92).'u0022:true', false);
    }

    public function test_unauthorized_employee_cannot_view_bitacora(): void
    {
        $persona = $this->empleado('sin-permiso-bitacora-test');

        $response = $this->actingAs($persona, 'personal')->get(route('personal.bitacora.index'));

        $response->assertStatus(403);
    }

    // Pedido 2026-09-24: el sistema no tiene calendario de festivos
    // colombianos, asi que un administrativo puede marcar/quitar una fecha
    // puntual a mano — primer clic crea el registro, segundo clic (misma
    // fecha) lo quita, nunca acumula duplicados.
    public function test_toggle_holiday_creates_then_removes_on_second_toggle(): void
    {
        $admin = $this->superadmin();

        $this->actingAs($admin)
            ->post(route('personal.bitacora.holidays.toggle'), ['date' => '2026-07-20'])
            ->assertRedirect(route('personal.bitacora.index', ['year' => 2026, 'month' => 7]));
        $this->assertDatabaseCount('bitacora_holidays', 1);
        $this->assertDatabaseHas('bitacora_holidays', ['date' => '2026-07-20']);

        $this->actingAs($admin)->post(route('personal.bitacora.holidays.toggle'), ['date' => '2026-07-20']);
        $this->assertDatabaseCount('bitacora_holidays', 0);
    }

    // El dia marcado a mano debe servirse como festivo=true en la celda de
    // valor (fuerza texto rojo en pantalla, ver :class en la vista) y el
    // dia debe mostrar el marcador de festivo manual junto al numero.
    public function test_manually_marked_holiday_is_served_as_festivo_true_for_that_day(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $persona = $this->empleado('festivo-manual-test');

        $activity = Activity::create([
            'date' => '2026-07-20', 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 8,
        ]);
        $activity->personas()->sync([$persona->id]);
        BitacoraHoliday::create(['date' => '2026-07-20']);

        $response = $this->actingAs($admin)->get(route('personal.bitacora.index', ['year' => 2026, 'month' => 7]));

        $response->assertOk();
        $response->assertSee('festivo: true,', false);
        $response->assertSee('Festivo marcado manualmente', false);
    }

    // Un domingo sigue siendo festivo aunque nunca se haya marcado a mano
    // (comportamiento previo, no debe romperse con el cambio).
    public function test_sunday_is_still_served_as_festivo_true_without_manual_mark(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $persona = $this->empleado('domingo-test');

        // 2026-07-05 es domingo.
        $activity = Activity::create([
            'date' => '2026-07-05', 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 8,
        ]);
        $activity->personas()->sync([$persona->id]);

        $response = $this->actingAs($admin)->get(route('personal.bitacora.index', ['year' => 2026, 'month' => 7]));

        $response->assertOk();
        $response->assertSee('festivo: true,', false);
    }

    public function test_unauthorized_employee_cannot_toggle_holiday(): void
    {
        $persona = $this->empleado('sin-permiso-festivo-test');

        $response = $this->actingAs($persona, 'personal')
            ->post(route('personal.bitacora.holidays.toggle'), ['date' => '2026-07-20']);

        $response->assertStatus(403);
        $this->assertDatabaseCount('bitacora_holidays', 0);
    }

    // Pedido 2026-09-24: comentario puesto en Programacion (una fila por
    // actividad, no por empleado) debe verse en el historial de Bitacora de
    // CADA persona asignada a esa actividad ese dia, sin duplicarse en
    // activity_employee_comments (fuente unica: activities.scheduling_comment).
    public function test_scheduling_comment_from_programacion_shows_up_in_bitacora_history_for_every_persona(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $p1 = $this->empleado('prog-comentario-uno-test');
        $p2 = $this->empleado('prog-comentario-dos-test');

        $activity = Activity::create([
            'date' => '2026-04-10', 'company_id' => $company->id, 'description' => 'x',
            'scheduling_comment' => 'Llevar equipo de proteccion adicional',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 8,
        ]);
        $activity->personas()->sync([$p1->id, $p2->id]);

        $response = $this->actingAs($admin)->get(route('personal.bitacora.index', ['year' => 2026, 'month' => 4]));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Llevar equipo de proteccion adicional',
            'Llevar equipo de proteccion adicional',
        ], false);
        $this->assertDatabaseCount('activity_employee_comments', 0);
    }

    // Sin scheduling_comment (caso normal, es opcional) no debe agregar
    // nada al historial ni fallar.
    public function test_activity_without_scheduling_comment_does_not_affect_bitacora_history(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $persona = $this->empleado('sin-comentario-prog-test');

        $activity = Activity::create([
            'date' => '2026-04-11', 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 8,
        ]);
        $activity->personas()->sync([$persona->id]);

        $response = $this->actingAs($admin)->get(route('personal.bitacora.index', ['year' => 2026, 'month' => 4]));

        $response->assertOk();
        $response->assertSee('comentarios: [],', false);
    }
}
