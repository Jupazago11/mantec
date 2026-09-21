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
 * Cobertura de Diario de Campo. Sección 14.21 (2026-09-19): ya no existe
 * el concepto de "Estado" (badge Pendiente/Cerrado), cada actividad
 * genera una fila por cada valor de horas distinto entre sus personas
 * (Activity::diaryHourGroups(), ver ActivityDiaryHourGroupsTest). Sección
 * 14.22 (misma sesión): el modal de edición se reemplazó por celdas
 * editables en línea (mismo patrón que
 * AdminPreventiveReportController::inlineUpdate()) — y, a diferencia del
 * modal anterior, editar un campo YA NO marca la actividad como cerrada
 * (closed_at), confirmado con el usuario.
 */
class FieldDiaryControllerTest extends TestCase
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
        $category = PersonalCategory::firstOrCreate(['name' => 'Campo Field Diary Test'], ['activo' => true]);

        return Employee::create([
            'nombre' => 'Empleado '.$nickname,
            'nickname' => $nickname,
            'personal_category_id' => $category->id,
            'activo' => true,
            'has_login' => false,
            'in_bitacora' => true,
        ]);
    }

    // Empleado con permiso para VER pero no para editar (cerrar_diario_campo).
    private function empleadoSoloVer(): Employee
    {
        $category = PersonalCategory::firstOrCreate(['name' => 'Campo Field Diary Test'], ['activo' => true]);
        $rol = PersonalRole::create([
            'name' => 'Solo Ver Diario Test '.uniqid(),
            'personal_category_id' => $category->id,
            'activo' => true, 'disponible_en_programacion' => false, 'responsable_actividad' => false,
            'ver_diario_campo' => true, 'cerrar_diario_campo' => false,
        ]);

        return Employee::create([
            'nombre' => 'Solo Ver', 'nickname' => 'solo-ver-diario-'.uniqid(),
            'personal_category_id' => $category->id, 'personal_role_id' => $rol->id,
            'activo' => true, 'has_login' => true, 'username' => 'solo_ver_diario_'.uniqid(),
            'password' => bcrypt('secret'), 'in_bitacora' => true,
        ]);
    }

    private function actividad(array $overrides = []): Activity
    {
        return Activity::create(array_merge([
            'date' => today()->toDateString(), 'company_id' => $this->company()->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 8,
        ], $overrides));
    }

    public function test_estado_badge_no_longer_exists(): void
    {
        $admin = $this->superadmin();
        $this->actividad();

        $response = $this->actingAs($admin)->get(route('personal.diario-campo.index'));

        $response->assertOk();
        $response->assertDontSee('Pendiente');
        $response->assertDontSee('Cerrado');
        $response->assertDontSee('Estado');
    }

    public function test_no_modal_or_abrir_modal_button_exists(): void
    {
        $admin = $this->superadmin();
        $this->actividad();

        $response = $this->actingAs($admin)->get(route('personal.diario-campo.index'));

        $response->assertOk();
        $response->assertDontSee('Abrir modal');
        $response->assertDontSee('Guardar cierre');
    }

    // Seccion 14.23: "Actividad ejecutada" muestra `comments` (lo que el
    // supervisor escribe en "Ver como supervisor" ES la actividad
    // ejecutada, confirmado con el usuario) — `executed_description` ya
    // no tiene celda visible en esta pantalla.
    public function test_shows_programada_and_ejecutada_as_separate_fields(): void
    {
        $admin = $this->superadmin();
        $this->actividad([
            'description' => 'Actividad Programada X',
            'comments' => 'Lo que realmente se hizo',
            'executed_description' => 'Este campo ya no se muestra aqui',
        ]);

        $response = $this->actingAs($admin)->get(route('personal.diario-campo.index'));

        $response->assertOk();
        $response->assertSee('Actividad Programada X');
        $response->assertSee('Lo que realmente se hizo');
        $response->assertDontSee('Este campo ya no se muestra aqui');
    }

    public function test_employee_without_permission_sees_plain_text_not_editable_cells(): void
    {
        $actor = $this->empleadoSoloVer();
        $this->actividad(['process' => 'Proceso visible']);

        $response = $this->actingAs($actor, 'personal')->get(route('personal.diario-campo.index'));

        $response->assertOk();
        $response->assertSee('Proceso visible');
        // "inline-editable" como tal aparece igual en el <script> (JS que
        // siempre se carga); lo que no debe existir es el atributo que
        // marca una celda como editable.
        $this->assertStringNotContainsString('class="inline-editable"', $response->getContent());
    }

    public function test_employee_with_permission_sees_editable_cells(): void
    {
        $admin = $this->superadmin();
        $this->actividad();

        $response = $this->actingAs($admin)->get(route('personal.diario-campo.index'));

        $response->assertOk();
        $this->assertStringContainsString('class="inline-editable"', $response->getContent());
    }

    public function test_single_hour_group_activity_renders_one_row(): void
    {
        $admin = $this->superadmin();
        $p1 = $this->empleado('fila-unica-1');
        $p2 = $this->empleado('fila-unica-2');

        $activity = $this->actividad([
            'description' => 'Actividad fila unica', 'estimated_hours' => 12,
            'all_worked_scheduled_hours' => true, 'reported_hours' => 12,
        ]);
        $activity->personas()->sync([$p1->id, $p2->id]);

        $response = $this->actingAs($admin)->get(route('personal.diario-campo.index'));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'align-top hover:bg-slate-50'));
    }

    public function test_activity_with_two_distinct_hour_values_renders_two_rows(): void
    {
        $admin = $this->superadmin();
        $ocho1 = $this->empleado('dos-filas-8-1');
        $ocho2 = $this->empleado('dos-filas-8-2');
        $diez1 = $this->empleado('dos-filas-10-1');
        $diez2 = $this->empleado('dos-filas-10-2');

        $activity = $this->actividad(['description' => 'Actividad dos filas', 'estimated_hours' => 9, 'all_worked_scheduled_hours' => false]);
        $activity->personas()->sync([$ocho1->id, $ocho2->id, $diez1->id, $diez2->id]);
        foreach ([$ocho1->id, $ocho2->id] as $id) {
            ActivityEmployeeHour::create(['activity_id' => $activity->id, 'employee_id' => $id, 'worked' => true, 'worked_hours' => 8]);
        }
        foreach ([$diez1->id, $diez2->id] as $id) {
            ActivityEmployeeHour::create(['activity_id' => $activity->id, 'employee_id' => $id, 'worked' => true, 'worked_hours' => 10]);
        }

        $response = $this->actingAs($admin)->get(route('personal.diario-campo.index'));

        $response->assertOk();
        $this->assertSame(2, substr_count($response->getContent(), 'align-top hover:bg-slate-50'));
        $response->assertSee('dos-filas-8-1');
        $response->assertSee('dos-filas-10-1');
    }

    // El "hoy" resaltado en el mini-calendario debe calcularse en el
    // servidor (zona America/Bogota, config/app.php), no con new Date()
    // del navegador (toISOString() siempre da UTC y marca mal el dia
    // durante ~5 horas cada noche hora Colombia). Ver
    // NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md.
    public function test_diario_campo_index_injects_hoyReal_in_colombia_timezone(): void
    {
        $admin = $this->superadmin();

        $response = $this->actingAs($admin)->get(route('personal.diario-campo.index'));

        $response->assertOk();
        $response->assertSee("hoyReal: '".today()->toDateString()."',", false);
        $response->assertDontSee('new Date().toISOString()', false);
    }

    // --- inlineUpdate() ---

    public function test_inline_update_saves_a_text_field(): void
    {
        $admin = $this->superadmin();
        $activity = $this->actividad();

        $response = $this->actingAs($admin)->patch(
            route('personal.diario-campo.inline-update', $activity),
            ['field' => 'process', 'value' => 'Cambio de banda'],
            ['Accept' => 'application/json']
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('value', 'Cambio de banda');
        $this->assertDatabaseHas('activities', ['id' => $activity->id, 'process' => 'Cambio de banda']);
    }

    // Confirmado con el usuario: editar un campo en linea ya NO marca la
    // actividad como cerrada — antes el modal siempre seteaba closed_at.
    public function test_inline_update_does_not_set_closed_at(): void
    {
        $admin = $this->superadmin();
        $activity = $this->actividad();

        $this->actingAs($admin)->patch(
            route('personal.diario-campo.inline-update', $activity),
            ['field' => 'corrected_hours', 'value' => '9.5'],
            ['Accept' => 'application/json']
        );

        $this->assertDatabaseHas('activities', ['id' => $activity->id, 'closed_at' => null, 'closed_by_employee_id' => null]);
        $this->assertSame('9.50', $activity->fresh()->corrected_hours);
    }

    public function test_inline_update_rejects_non_numeric_corrected_hours(): void
    {
        $admin = $this->superadmin();
        $activity = $this->actividad();

        $response = $this->actingAs($admin)->patch(
            route('personal.diario-campo.inline-update', $activity),
            ['field' => 'corrected_hours', 'value' => 'abc'],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422);
    }

    public function test_inline_update_rejects_negative_corrected_hours(): void
    {
        $admin = $this->superadmin();
        $activity = $this->actividad();

        $response = $this->actingAs($admin)->patch(
            route('personal.diario-campo.inline-update', $activity),
            ['field' => 'corrected_hours', 'value' => '-1'],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422);
    }

    public function test_inline_update_rejects_unknown_field(): void
    {
        $admin = $this->superadmin();
        $activity = $this->actividad();

        $response = $this->actingAs($admin)->patch(
            route('personal.diario-campo.inline-update', $activity),
            ['field' => 'closed_at', 'value' => 'x'],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422);
    }

    // Seccion 14.23: executed_description salio de la whitelist al dejar
    // de tener celda propia en la vista (ahora "Actividad ejecutada"
    // edita "comments").
    public function test_inline_update_rejects_executed_description(): void
    {
        $admin = $this->superadmin();
        $activity = $this->actividad();

        $response = $this->actingAs($admin)->patch(
            route('personal.diario-campo.inline-update', $activity),
            ['field' => 'executed_description', 'value' => 'x'],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422);
    }

    public function test_inline_update_rejects_employee_without_permission(): void
    {
        $actor = $this->empleadoSoloVer();
        $activity = $this->actividad();

        $response = $this->actingAs($actor, 'personal')->patch(
            route('personal.diario-campo.inline-update', $activity),
            ['field' => 'process', 'value' => 'x'],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(403);
    }

    // Guardar un valor vacio limpia el campo (mismo patron que
    // AdminPreventiveReportController::inlineUpdate: '' -> null).
    public function test_inline_update_empty_value_clears_the_field(): void
    {
        $admin = $this->superadmin();
        $activity = $this->actividad(['zcom' => 'ABC123']);

        $response = $this->actingAs($admin)->patch(
            route('personal.diario-campo.inline-update', $activity),
            ['field' => 'zcom', 'value' => ''],
            ['Accept' => 'application/json']
        );

        $response->assertOk();
        $this->assertDatabaseHas('activities', ['id' => $activity->id, 'zcom' => null]);
    }
}
