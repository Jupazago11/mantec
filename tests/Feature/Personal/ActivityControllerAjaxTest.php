<?php

namespace Tests\Feature\Personal;

use App\Models\Activity;
use App\Models\BitacoraEntry;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PersonalCategory;
use App\Models\PersonalRole;
use App\Models\Role;
use App\Models\User;
use App\Services\Bitacora\BitacoraHoursCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobertura del cambio 2026-09-19: Programacion paso de un <form> nativo
 * (recarga completa de pagina) a AJAX — store/update/destroy ahora
 * responden JSON cuando el request pide application/json (ver
 * ActivityController::isAjaxRequest), mismo patron que EmployeeController.
 */
class ActivityControllerAjaxTest extends TestCase
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

    public function test_superadmin_can_create_activity_via_ajax_without_page_reload(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();

        $response = $this->actingAs($admin)->postJson(route('personal.programacion.store'), [
            'date' => today()->toDateString(),
            'company_id' => $company->id,
            'description' => 'Cambiar banda transportadora',
            'activity_type' => 'P',
            'shift' => 'Diurno',
            'personas' => [],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Actividad creada correctamente.',
        ]);
        $response->assertJsonPath('activity.description', 'Cambiar banda transportadora');
        $response->assertJsonPath('activity.company_name', $company->name);
        $response->assertJsonPath('activity.modificable', true);
        $this->assertDatabaseHas('activities', [
            'description' => 'Cambiar banda transportadora',
            'company_id' => $company->id,
        ]);
    }

    // Pedido 2026-09-24: comentario opcional de contexto, distinto de
    // "description" — se guarda en store/update y se sirve en el payload
    // que alimenta la columna "Comentario" de la tabla (a.scheduling_comment
    // en la vista).
    public function test_scheduling_comment_is_saved_and_returned_when_creating_activity(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();

        $response = $this->actingAs($admin)->postJson(route('personal.programacion.store'), [
            'date' => today()->toDateString(),
            'company_id' => $company->id,
            'description' => 'Cambiar banda transportadora',
            'scheduling_comment' => 'Llevar repuesto de reserva',
            'activity_type' => 'P',
            'shift' => 'Diurno',
            'personas' => [],
        ]);

        $response->assertOk();
        $response->assertJsonPath('activity.scheduling_comment', 'Llevar repuesto de reserva');
        $this->assertDatabaseHas('activities', [
            'description' => 'Cambiar banda transportadora',
            'scheduling_comment' => 'Llevar repuesto de reserva',
        ]);
    }

    // scheduling_comment es opcional (a diferencia de description) — no
    // mandarlo no debe fallar la validacion.
    public function test_scheduling_comment_is_optional_when_creating_activity(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();

        $response = $this->actingAs($admin)->postJson(route('personal.programacion.store'), [
            'date' => today()->toDateString(),
            'company_id' => $company->id,
            'description' => 'Sin comentario',
            'activity_type' => 'P',
            'shift' => 'Diurno',
            'personas' => [],
        ]);

        $response->assertOk();
        $response->assertJsonPath('activity.scheduling_comment', null);
    }

    // El comentario se puede editar despues de programado (pedido
    // 2026-09-24: "obviamente se puede editar despues").
    public function test_scheduling_comment_can_be_edited_after_creation(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();

        $activity = Activity::create([
            'date' => today()->toDateString(),
            'company_id' => $company->id,
            'description' => 'Actividad existente',
            'scheduling_comment' => 'Comentario original',
            'activity_type' => 'P',
            'shift' => 'Diurno',
        ]);

        $response = $this->actingAs($admin)->putJson(route('personal.programacion.update', $activity), [
            'date' => today()->toDateString(),
            'company_id' => $company->id,
            'description' => 'Actividad existente',
            'scheduling_comment' => 'Comentario editado',
            'activity_type' => 'P',
            'shift' => 'Diurno',
            'personas' => [],
        ]);

        $response->assertOk();
        $response->assertJsonPath('activity.scheduling_comment', 'Comentario editado');
        $this->assertDatabaseHas('activities', ['id' => $activity->id, 'scheduling_comment' => 'Comentario editado']);
    }

    // Bug 2026-09-19: el <input type="number"> de Grupo mandaba
    // group_number como string (sin x-model.number en Alpine); como el
    // modelo no castea esa columna, serialize() devolvia el mismo tipo que
    // llego en el request. El agrupado en JS (gruposOrdenados(), Map por
    // group_number) trataba "1" y 1 como grupos distintos, partiendo la
    // vista en dos "Grupo 1". Simula el request tal como lo mandaba el
    // frontend con el bug (group_number como string) para fijar que la
    // respuesta siempre serializa como numero.
    public function test_activity_response_serializes_group_number_as_integer_even_if_sent_as_string(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();

        $response = $this->actingAs($admin)->postJson(route('personal.programacion.store'), [
            'date' => today()->toDateString(),
            'company_id' => $company->id,
            'group_number' => '1',
            'description' => 'Actividad con grupo como string',
            'activity_type' => 'P',
            'shift' => 'Diurno',
            'personas' => [],
        ]);

        $response->assertOk();
        $payload = $response->json();
        $this->assertSame(1, $payload['activity']['group_number']);
    }

    public function test_create_activity_via_ajax_returns_422_with_validation_errors(): void
    {
        $admin = $this->superadmin();

        $response = $this->actingAs($admin)->postJson(route('personal.programacion.store'), [
            'date' => today()->toDateString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['company_id', 'description', 'activity_type', 'shift']);
    }

    public function test_employee_without_ver_programacion_permission_cannot_create_activity(): void
    {
        $category = PersonalCategory::create(['name' => 'Campo Test', 'activo' => true]);
        $role = PersonalRole::create([
            'name' => 'Sin permisos Test',
            'personal_category_id' => $category->id,
            'activo' => true,
            'disponible_en_programacion' => true,
            'ver_empleados' => false,
            'ver_programacion' => false,
            'editar_programacion_sin_limite' => false,
            'ver_diario_campo' => false,
            'cerrar_diario_campo' => false,
            'ver_bitacora' => false,
            'responsable_actividad' => false,
        ]);
        $employee = Employee::create([
            'nombre' => 'Empleado Test',
            'nickname' => 'Empleado',
            'personal_category_id' => $category->id,
            'personal_role_id' => $role->id,
            'activo' => true,
            'has_login' => false,
            'in_bitacora' => true,
        ]);

        $company = $this->company();

        $response = $this->actingAs($employee, 'personal')->postJson(route('personal.programacion.store'), [
            'date' => today()->toDateString(),
            'company_id' => $company->id,
            'description' => 'No deberia crearse',
            'activity_type' => 'P',
            'shift' => 'Diurno',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('activities', ['description' => 'No deberia crearse']);
    }

    // Fija el formato exacto del mensaje ("Ya tiene otra actividad primaria
    // ese día: <nicknames>.") — extraerPersonasConflicto() en el JS del
    // modal (programacion/index.blade.php) parsea este texto para pintar
    // de rojo el chip de la persona en conflicto, asi que si el texto
    // cambia hay que actualizar los dos lados.
    public function test_primary_activity_conflict_error_message_includes_employee_nickname(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $category = PersonalCategory::create(['name' => 'Campo Test 2', 'activo' => true]);
        $employee = Employee::create([
            'nombre' => 'Camellador Cero Tres',
            'nickname' => 'camellador 0 3',
            'personal_category_id' => $category->id,
            'activo' => true,
            'has_login' => false,
            'in_bitacora' => true,
        ]);

        $existente = Activity::create([
            'date' => today()->toDateString(),
            'company_id' => $company->id,
            'description' => 'Actividad primaria existente',
            'activity_type' => 'P',
            'shift' => 'Diurno',
        ]);
        $existente->personas()->sync([$employee->id]);

        $response = $this->actingAs($admin)->postJson(route('personal.programacion.store'), [
            'date' => today()->toDateString(),
            'company_id' => $company->id,
            'description' => 'Actividad nueva en conflicto',
            'activity_type' => 'P',
            'shift' => 'Diurno',
            'personas' => [$employee->id],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.personas.0', 'Ya tiene otra actividad primaria ese día: camellador 0 3.');
    }

    public function test_superadmin_can_update_activity_via_ajax(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();

        $activity = Activity::create([
            'date' => today()->toDateString(),
            'company_id' => $company->id,
            'description' => 'Original',
            'activity_type' => 'P',
            'shift' => 'Diurno',
        ]);

        $response = $this->actingAs($admin)->putJson(route('personal.programacion.update', $activity), [
            'date' => today()->toDateString(),
            'company_id' => $company->id,
            'description' => 'Actualizada',
            'activity_type' => 'S',
            'shift' => 'Nocturno',
            'personas' => [],
        ]);

        $response->assertOk();
        $response->assertJsonPath('activity.description', 'Actualizada');
        $response->assertJsonPath('activity.activity_type', 'S');
        $this->assertDatabaseHas('activities', ['id' => $activity->id, 'description' => 'Actualizada']);
    }

    // Cobertura 2026-09-19 (sección 14.17): la tarjeta de hover sobre el
    // nombre de una persona/responsable en la tabla necesita el nombre
    // completo (no solo el nickname) — serialize() debe incluirlo.
    public function test_store_response_includes_full_names_for_personas_and_responsible(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $persona = $this->empleadoParaHoras('persona-nombre-test');

        $categoriaResp = PersonalCategory::create(['name' => 'Rol Responsable Test', 'activo' => true, 'responsable_actividad' => true]);
        $rolResp = PersonalRole::create([
            'name' => 'Subrol Responsable Test',
            'personal_category_id' => $categoriaResp->id,
            'activo' => true,
            'disponible_en_programacion' => true,
            'responsable_actividad' => true,
            'ver_empleados' => false, 'ver_programacion' => false, 'editar_programacion_sin_limite' => false,
            'ver_diario_campo' => false, 'cerrar_diario_campo' => false, 'ver_bitacora' => false,
        ]);
        $responsable = Employee::create([
            'nombre' => 'Empleado Responsable Nombre Test',
            'nickname' => 'responsable-nombre-test',
            'personal_category_id' => $categoriaResp->id,
            'personal_role_id' => $rolResp->id,
            'activo' => true, 'has_login' => false, 'in_bitacora' => true,
        ]);

        $response = $this->actingAs($admin)->postJson(route('personal.programacion.store'), [
            'date' => today()->toDateString(),
            'company_id' => $company->id,
            'description' => 'Con nombres completos',
            'activity_type' => 'P',
            'shift' => 'Diurno',
            'responsible_employee_id' => $responsable->id,
            'personas' => [$persona->id],
        ]);

        $response->assertOk();
        $response->assertJsonPath('activity.personas.0.nombre', $persona->nombre);
        $response->assertJsonPath('activity.responsible_nombre', $responsable->nombre);
    }

    // Regresion 2026-09-19: sin empresa marcada is_default, el <select>
    // nativo igual muestra la primera empresa del listado (comportamiento
    // del navegador) — el estado inicial de Alpine (defaultCompanyId) tiene
    // que arrancar en ese mismo id, si no el primer guardado por AJAX manda
    // company_id vacio aunque en pantalla se vea una empresa seleccionada.
    public function test_new_activity_form_defaults_to_first_company_when_none_is_marked_default(): void
    {
        $admin = $this->superadmin();
        Company::create(['name' => 'ARGOS Test', 'is_default' => false, 'archived' => false]);
        Company::create(['name' => 'CORONA Test', 'is_default' => false, 'archived' => false]);
        $primeraEnListado = Company::where('archived', false)->orderByDesc('is_default')->orderBy('name')->first();

        $response = $this->actingAs($admin)->get(route('personal.programacion.index'));

        $response->assertOk();
        $response->assertSee('defaultCompanyId: '.$primeraEnListado->id.',', false);
    }

    public function test_programacion_index_renders_with_actividades_and_without_it(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();

        Activity::create([
            'date' => today()->toDateString(),
            'company_id' => $company->id,
            'group_number' => 1,
            'description' => 'Actividad existente',
            'activity_type' => 'P',
            'shift' => 'Diurno',
        ]);

        // Con actividades (ejercita gruposOrdenados()/tabla) y sin ellas
        // (ejercita el estado vacio) — ambos ramales del template x-if.
        $this->actingAs($admin)->get(route('personal.programacion.index'))->assertOk();
        $this->actingAs($admin)
            ->get(route('personal.programacion.index', ['date' => today()->addDays(5)->toDateString()]))
            ->assertOk();
    }

    // El "hoy" resaltado en el mini-calendario debe calcularse en el
    // servidor (zona America/Bogota, config/app.php), no con new Date()
    // del navegador (toISOString() siempre da UTC y marca mal el dia
    // durante ~5 horas cada noche hora Colombia). Ver
    // NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md.
    public function test_programacion_index_injects_hoyReal_in_colombia_timezone(): void
    {
        $admin = $this->superadmin();

        $response = $this->actingAs($admin)->get(route('personal.programacion.index'));

        $response->assertOk();
        $response->assertSee("hoyReal: '".today()->toDateString()."',", false);
        $response->assertDontSee('new Date().toISOString()', false);
    }

    // Pedido 2026-09-21: despues de "Actividad" va una columna con la
    // CANTIDAD de personas ("Personas"), luego Horas, luego Jornada — la
    // lista de nombres ("Nombre de las personas") se corrio para despues
    // de Jornada. Los dos encabezados comparten la palabra "Personas"
    // (pedido 2026-09-21), asi que la columna de cantidad se ancla con el
    // texto exacto ">Personas<" (nada mas alrededor) y la de nombres con
    // su texto completo, para no confundir una con la otra.
    public function test_programacion_index_shows_cantidad_column_between_actividad_and_horas(): void
    {
        $admin = $this->superadmin();

        $response = $this->actingAs($admin)->get(route('personal.programacion.index'));
        $response->assertOk();

        $html = $response->getContent();
        $posActividad = strpos($html, '>Actividad<');
        $posCantidad = strpos($html, '>Personas<');
        $posHoras = strpos($html, '>Horas<');
        $posJornada = strpos($html, 'title="Jornada"');
        $posNombres = strpos($html, '>Nombre de las personas<');

        $this->assertNotFalse($posActividad);
        $this->assertNotFalse($posCantidad);
        $this->assertNotFalse($posHoras);
        $this->assertNotFalse($posJornada);
        $this->assertNotFalse($posNombres);
        $this->assertTrue($posActividad < $posCantidad, 'Personas (cantidad) debe ir despues de Actividad');
        $this->assertTrue($posCantidad < $posHoras, 'Horas debe ir despues de Personas (cantidad)');
        $this->assertTrue($posHoras < $posJornada, 'Jornada debe ir despues de Horas');
        $this->assertTrue($posJornada < $posNombres, 'Nombre de las personas debe ir despues de Jornada');
    }

    // El numero que se ve en la columna "Personas" (cantidad) lo calcula Alpine en el
    // navegador (a.personas.length, ver x-text del <td>) a partir de este
    // mismo arreglo — no hay forma de ejecutar ese JS desde un test
    // PHPUnit, asi que lo que se puede verificar del lado servidor es que
    // el payload que alimenta esa cuenta trae realmente a las 2 personas
    // asignadas (serialize() en ActivityController, campo "personas").
    public function test_programacion_index_payload_includes_all_personas_for_cantidad_column(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $p1 = $this->empleadoParaHoras('cantuno');
        $p2 = $this->empleadoParaHoras('cantdos');

        $activity = Activity::create([
            'date' => today()->toDateString(),
            'company_id' => $company->id,
            'description' => 'Actividad con dos personas',
            'activity_type' => 'P',
            'shift' => 'Diurno',
        ]);
        $activity->personas()->sync([$p1->id, $p2->id]);

        $response = $this->actingAs($admin)->get(route('personal.programacion.index'));

        $response->assertOk();
        $response->assertSee('cantuno', false);
        $response->assertSee('cantdos', false);
    }

    public function test_superadmin_can_delete_activity_via_ajax(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();

        $activity = Activity::create([
            'date' => today()->toDateString(),
            'company_id' => $company->id,
            'description' => 'A eliminar',
            'activity_type' => 'P',
            'shift' => 'Diurno',
        ]);

        $response = $this->actingAs($admin)->deleteJson(route('personal.programacion.destroy', $activity));

        $response->assertOk();
        $response->assertJson(['success' => true, 'id' => $activity->id]);
        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
    }

    private function empleadoParaHoras(string $nickname): Employee
    {
        $category = PersonalCategory::firstOrCreate(['name' => 'Campo Horas Test'], ['activo' => true]);

        return Employee::create([
            'nombre' => 'Empleado '.$nickname,
            'nickname' => $nickname,
            'personal_category_id' => $category->id,
            'activo' => true,
            'has_login' => false,
            'in_bitacora' => true,
        ]);
    }

    // Cobertura del pedido 2026-09-19 (sección 14.16): el selector de
    // "Personas de la actividad" debe mostrar horas REALES acumuladas en
    // Bitácora (ya no el Excel de ejemplo), sumando solo los días
    // anteriores a la fecha que se está viendo/programando — el día que se
    // está programando no debe contarse ahí (se muestra aparte como "+ hoy"
    // en el frontend, con lo que se escriba en el formulario).
    public function test_index_exposes_accumulated_bitacora_hours_before_the_viewed_date_only(): void
    {
        $admin = $this->superadmin();
        $company = $this->company();
        $employee = $this->empleadoParaHoras('empleado-horas-test');

        // Día 5: 8h programadas, sin corrección -> cuenta 8.
        $act5 = Activity::create([
            'date' => '2026-03-05', 'company_id' => $company->id, 'description' => 'Día 5',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 8,
        ]);
        $act5->personas()->sync([$employee->id]);

        // Día 7: 6h programadas, pero corregidas a 10 en Bitácora -> cuenta 10, no 6.
        $act7 = Activity::create([
            'date' => '2026-03-07', 'company_id' => $company->id, 'description' => 'Día 7',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 6,
        ]);
        $act7->personas()->sync([$employee->id]);
        BitacoraEntry::create(['employee_id' => $employee->id, 'date' => '2026-03-07', 'corrected_value' => '10']);

        // Día 10 (el que se está viendo/programando) y día 15 (futuro): NO deben contar.
        $act10 = Activity::create([
            'date' => '2026-03-10', 'company_id' => $company->id, 'description' => 'Día 10',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 100,
        ]);
        $act10->personas()->sync([$employee->id]);
        $act15 = Activity::create([
            'date' => '2026-03-15', 'company_id' => $company->id, 'description' => 'Día 15',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 50,
        ]);
        $act15->personas()->sync([$employee->id]);

        $response = $this->actingAs($admin)->get(route('personal.programacion.index', ['date' => '2026-03-10']));

        // @js() sobre un array complejo lo envuelve en JSON.parse('...') con
        // las comillas escapadas como codigo unicode " (no comillas
        // literales) — ver Illuminate\Support\Js::from(). Se arma con
        // chr(92) en vez de escribir la secuencia literal para que ninguna
        // herramienta de edicion la normalice a una comilla real.
        $backslashU0022 = chr(92).'u0022';
        $response->assertOk();
        $response->assertSee("horasAcumuladas{$backslashU0022}:18", false);
    }

    public function test_bitacora_hours_calculator_prefers_correction_and_excludes_non_numeric_from_sum(): void
    {
        $company = $this->company();
        $empleado = $this->empleadoParaHoras('empleado-calc-test');
        $otro = $this->empleadoParaHoras('empleado-sin-datos-test');

        $act = Activity::create([
            'date' => '2026-05-03', 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 4,
        ]);
        $act->personas()->sync([$empleado->id, $otro->id]);

        // $empleado: corrección numérica ese día sobrescribe lo programado (4 -> 9).
        BitacoraEntry::create(['employee_id' => $empleado->id, 'date' => '2026-05-03', 'corrected_value' => '9']);
        // $otro: corrección "L" (licencia, no numérica) -> no suma horas, y
        // como es su único día calificado en el rango, el total queda null
        // (se distingue "0 horas" de "sin datos numéricos").
        BitacoraEntry::create(['employee_id' => $otro->id, 'date' => '2026-05-03', 'corrected_value' => 'L']);

        $totales = (new BitacoraHoursCalculator())->totalPorEmpleado(2026, 5, 1, 31);

        $this->assertSame(9.0, $totales[$empleado->id]);
        $this->assertNull($totales[$otro->id]);
    }
}
