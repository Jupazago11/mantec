<?php

namespace Tests\Feature\Personal;

use App\Models\Activity;
use App\Models\ActivityEvidence;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PersonalCategory;
use App\Models\PersonalRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Revision de seguridad del modulo Personal (2026-09-28). Cada prueba fija
 * un hueco que se reprodujo con peticiones reales antes de corregirlo:
 * - "Administrar roles y permisos" como permiso (Rol Y Subrol), en vez de
 *   solo superadmin;
 * - Programacion: crear/editar/eliminar exige "editar_programacion" (antes
 *   un rol SIN permisos podia borrar actividades de hoy);
 * - Empleados: sin "administrar_roles" no se puede asignar subrol ni
 *   cambiar usuario/contrasena de cuentas con acceso (antes un supervisor
 *   se daba el rol Administrativo y le cambiaba la clave a otros);
 * - inactivar/quitar acceso/cambiar clave corta la sesion y los tokens.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        $role = Role::firstOrCreate(['key' => 'superadmin'], ['name' => 'superadmin', 'status' => true]);

        return User::create([
            'name' => 'Admin Test', 'username' => 'admin_test_'.uniqid(),
            'password' => bcrypt('secret'), 'role_id' => $role->id, 'status' => true,
        ]);
    }

    private function categoria(bool $administra = false): PersonalCategory
    {
        return PersonalCategory::create(['name' => 'Rol '.uniqid(), 'activo' => true, 'administrar_roles' => $administra]);
    }

    private function subrol(PersonalCategory $cat, array $permisos = []): PersonalRole
    {
        return PersonalRole::create(array_merge([
            'name' => 'Subrol '.uniqid(), 'personal_category_id' => $cat->id, 'activo' => true,
            'disponible_en_programacion' => true, 'responsable_actividad' => false,
        ], $permisos));
    }

    private function cuenta(PersonalRole $rol, string $username, string $clave = 'secret'): Employee
    {
        return Employee::create([
            'nombre' => 'Cuenta '.$username, 'nickname' => $username, 'personal_category_id' => $rol->personal_category_id,
            'personal_role_id' => $rol->id, 'activo' => true, 'has_login' => true,
            'username' => $username, 'password' => bcrypt($clave), 'in_bitacora' => false,
        ]);
    }

    private function trabajador(PersonalCategory $cat, ?PersonalRole $rol = null): Employee
    {
        return Employee::create([
            'nombre' => 'Trabajador '.uniqid(), 'nickname' => 'trab-'.uniqid(), 'personal_category_id' => $cat->id,
            'personal_role_id' => $rol?->id, 'activo' => true, 'has_login' => false, 'in_bitacora' => true,
        ]);
    }

    private function actividad(array $extra = []): Activity
    {
        $company = Company::firstOrCreate(['name' => 'Empresa Acceso'], ['is_default' => true, 'archived' => false]);

        return Activity::create(array_merge([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'Actividad '.uniqid(),
            'activity_type' => 'S', 'shift' => 'Diurno', 'estimated_hours' => 8,
        ], $extra));
    }

    private function payloadEmpleado(Employee $e, array $cambios = []): array
    {
        return array_merge([
            'nombre' => $e->nombre, 'nickname' => $e->nickname, 'personal_category_id' => $e->personal_category_id,
            'personal_role_id' => $e->personal_role_id, 'has_login' => (bool) $e->has_login,
            'username' => $e->username, 'password' => '', 'in_bitacora' => (bool) $e->in_bitacora,
        ], $cambios);
    }

    // --- Administrar roles y permisos (Rol Y Subrol) ---

    public function test_roles_screen_requires_flag_on_role_and_subrole(): void
    {
        $sinRol = $this->cuenta($this->subrol($this->categoria(false), ['administrar_roles' => true]), 'solo_subrol');
        $completo = $this->cuenta($this->subrol($this->categoria(true), ['administrar_roles' => true]), 'rol_y_subrol');

        $this->actingAs($sinRol, 'personal')->get(route('personal.roles.index'))->assertStatus(403);

        $this->actingAs($completo, 'personal')->get(route('personal.roles.index'))->assertOk();
        $html = $this->actingAs($completo, 'personal')->get(route('personal.roles.index'))->getContent();
        $this->assertStringContainsString('<span>Roles y permisos</span>', $html);
    }

    public function test_administrar_roles_and_editar_programacion_are_saved_from_roles_screen(): void
    {
        $admin = $this->superadmin();

        $cat = $this->actingAs($admin)->postJson(route('personal.categorias.store'), ['name' => 'Coordinacion', 'administrar_roles' => true])
            ->assertOk()->json('category');
        $this->assertTrue($cat['administrar_roles']);

        $rol = $this->actingAs($admin)->postJson(route('personal.roles.store'), [
            'name' => 'Coordinador', 'personal_category_id' => $cat['id'],
            'ver_programacion' => true, 'editar_programacion' => true, 'administrar_roles' => true,
        ])->assertOk()->json('role');

        $this->assertTrue($rol['editar_programacion']);
        $this->assertTrue($rol['administrar_roles']);
    }

    // --- Programacion ---

    // Reproduce el hueco: un rol SIN ningun permiso borraba actividades de hoy.
    public function test_role_without_permissions_cannot_update_or_delete_activities(): void
    {
        $sinPermisos = $this->cuenta($this->subrol($this->categoria()), 'sin_permisos');
        $a = $this->actividad();

        $this->actingAs($sinPermisos, 'personal')->deleteJson(route('personal.programacion.destroy', $a))->assertStatus(403);
        $this->actingAs($sinPermisos, 'personal')->putJson(route('personal.programacion.update', $a), [
            'date' => $a->date->toDateString(), 'company_id' => $a->company_id, 'description' => 'Cambiada',
            'activity_type' => 'S', 'shift' => 'Diurno',
        ])->assertStatus(403);

        $this->assertDatabaseHas('activities', ['id' => $a->id, 'description' => $a->description]);
    }

    // "Ver" sin "editar": puede entrar a Programacion pero no crear/editar/borrar.
    public function test_view_only_role_cannot_create_update_or_delete(): void
    {
        $soloVer = $this->cuenta($this->subrol($this->categoria(), ['ver_programacion' => true, 'editar_programacion' => false]), 'solo_ver');
        $a = $this->actividad();

        $this->actingAs($soloVer, 'personal')->get(route('personal.programacion.index'))->assertOk();
        $this->actingAs($soloVer, 'personal')->postJson(route('personal.programacion.store'), [
            'date' => today()->toDateString(), 'company_id' => $a->company_id, 'description' => 'Nueva',
            'activity_type' => 'S', 'shift' => 'Diurno',
        ])->assertStatus(403);
        $this->actingAs($soloVer, 'personal')->deleteJson(route('personal.programacion.destroy', $a))->assertStatus(403);

        $this->assertDatabaseCount('activities', 1);
    }

    public function test_programming_role_can_create(): void
    {
        $coordinador = $this->cuenta($this->subrol($this->categoria(), ['ver_programacion' => true, 'editar_programacion' => true]), 'coordinador');
        $company = Company::firstOrCreate(['name' => 'Empresa Acceso'], ['is_default' => true, 'archived' => false]);

        $this->actingAs($coordinador, 'personal')->postJson(route('personal.programacion.store'), [
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'Programada por coordinador',
            'activity_type' => 'S', 'shift' => 'Diurno',
        ])->assertOk();

        $this->assertDatabaseHas('activities', ['description' => 'Programada por coordinador']);
    }

    public function test_invalid_date_in_programacion_url_falls_back_to_today(): void
    {
        $this->actingAs($this->superadmin())->get(route('personal.programacion.index', ['date' => 'abc']))->assertOk();
    }

    public function test_estimated_hours_cannot_exceed_24(): void
    {
        $company = Company::firstOrCreate(['name' => 'Empresa Acceso'], ['is_default' => true, 'archived' => false]);

        $this->actingAs($this->superadmin())->postJson(route('personal.programacion.store'), [
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'S', 'shift' => 'Diurno', 'estimated_hours' => 25,
        ])->assertStatus(422)->assertJsonValidationErrors('estimated_hours');
    }

    // Borrar una actividad borra tambien los archivos de sus evidencias en
    // R2 (antes quedaban huerfanos).
    public function test_deleting_activity_removes_evidence_files_from_storage(): void
    {
        Storage::fake('r2');
        $a = $this->actividad(['hours_registered_at' => now()]);
        Storage::disk('r2')->put('personal-actividades/prueba/foto.jpg', 'contenido');
        $responsable = $this->cuenta($this->subrol($this->categoria()), 'responsable_evidencia');
        ActivityEvidence::create([
            'activity_id' => $a->id, 'uploaded_by_employee_id' => $responsable->id,
            'disk' => 'r2', 'path' => 'personal-actividades/prueba/foto.jpg',
            'original_name' => 'foto.jpg', 'stored_name' => 'foto.jpg', 'mime_type' => 'image/jpeg',
            'extension' => 'jpg', 'file_type' => 'image', 'size_bytes' => 9, 'sort_order' => 1,
        ]);
        $admin = $this->superadmin();

        // La vista recibe con que advertir antes de borrar.
        $fila = collect($this->actingAs($admin)->get(route('personal.programacion.index'))->viewData('actividadesJs'))->firstWhere('id', $a->id);
        $this->assertTrue($fila['registrada']);
        $this->assertSame(1, $fila['evidencias']);

        $this->actingAs($admin)->deleteJson(route('personal.programacion.destroy', $a))->assertOk();

        Storage::disk('r2')->assertMissing('personal-actividades/prueba/foto.jpg');
        $this->assertDatabaseCount('activity_evidences', 0);
    }

    // --- Empleados: cierre de la escalada de privilegios ---

    public function test_without_admin_permission_cannot_give_self_a_higher_role(): void
    {
        $cat = $this->categoria();
        $supervisor = $this->cuenta($this->subrol($cat, ['ver_empleados' => true]), 'supervisor');
        $rolAdmin = $this->subrol($cat, ['ver_empleados' => true, 'ver_bitacora' => true]);

        $this->actingAs($supervisor, 'personal')
            ->putJson(route('personal.empleados.update', $supervisor), $this->payloadEmpleado($supervisor, ['personal_role_id' => $rolAdmin->id]))
            ->assertStatus(422)->assertJsonValidationErrors('has_login');

        $this->assertNotSame($rolAdmin->id, $supervisor->fresh()->personal_role_id);
    }

    public function test_without_admin_permission_cannot_change_another_account_password(): void
    {
        $cat = $this->categoria();
        $supervisor = $this->cuenta($this->subrol($cat, ['ver_empleados' => true]), 'supervisor2');
        $otro = $this->cuenta($this->subrol($cat), 'otra_cuenta', 'original');

        $this->actingAs($supervisor, 'personal')
            ->putJson(route('personal.empleados.update', $otro), $this->payloadEmpleado($otro, ['password' => 'Tomada123']))
            ->assertStatus(422);

        $this->assertTrue(Hash::check('original', $otro->fresh()->password));
    }

    public function test_without_admin_permission_cannot_create_an_account_with_access(): void
    {
        $cat = $this->categoria();
        $supervisor = $this->cuenta($this->subrol($cat, ['ver_empleados' => true]), 'supervisor3');

        $this->actingAs($supervisor, 'personal')->postJson(route('personal.empleados.store'), [
            'nombre' => 'Nueva cuenta', 'nickname' => 'nueva', 'personal_category_id' => $cat->id,
            'has_login' => true, 'username' => 'nueva_cuenta', 'password' => 'secret123', 'in_bitacora' => true,
        ])->assertStatus(422)->assertJsonValidationErrors('has_login');

        $this->assertDatabaseMissing('employees', ['username' => 'nueva_cuenta']);
    }

    // Sin acceso de login el subrol no otorga permisos: el trabajo diario
    // (crear trabajadores de campo, cambiar su subrol, editar nombres)
    // sigue igual para quien solo tiene ver_empleados.
    public function test_without_admin_permission_can_still_manage_employees_without_access(): void
    {
        $cat = $this->categoria();
        $supervisor = $this->cuenta($this->subrol($cat, ['ver_empleados' => true]), 'supervisor4');
        $operario = $this->subrol($cat);
        $trabajador = $this->trabajador($cat);

        $this->actingAs($supervisor, 'personal')
            ->putJson(route('personal.empleados.update', $trabajador), $this->payloadEmpleado($trabajador, ['personal_role_id' => $operario->id, 'nombre' => 'Nombre nuevo']))
            ->assertOk();
        $this->assertSame($operario->id, $trabajador->fresh()->personal_role_id);

        $this->actingAs($supervisor, 'personal')->postJson(route('personal.empleados.store'), [
            'nombre' => 'Operario nuevo', 'nickname' => 'op-nuevo', 'personal_category_id' => $cat->id,
            'personal_role_id' => $operario->id, 'has_login' => false, 'in_bitacora' => true,
        ])->assertOk();

        // Editar solo el nombre de una cuenta con acceso tambien se permite.
        $this->actingAs($supervisor, 'personal')
            ->putJson(route('personal.empleados.update', $supervisor), $this->payloadEmpleado($supervisor, ['nombre' => 'Supervisor renombrado']))
            ->assertOk();
    }

    public function test_toggling_an_account_requires_admin_permission_and_revokes_tokens(): void
    {
        $cat = $this->categoria(true);
        $supervisor = $this->cuenta($this->subrol($cat, ['ver_empleados' => true]), 'supervisor5');
        $admin = $this->cuenta($this->subrol($cat, ['ver_empleados' => true, 'administrar_roles' => true]), 'administrador');
        $cuenta = $this->cuenta($this->subrol($cat), 'cuenta_app');
        $trabajador = $this->trabajador($cat);
        $cuenta->createToken('supervisor-app');

        $this->actingAs($supervisor, 'personal')->patchJson(route('personal.empleados.toggle-status', $cuenta))->assertStatus(403);
        $this->actingAs($supervisor, 'personal')->patchJson(route('personal.empleados.toggle-status', $trabajador))->assertOk();

        $this->actingAs($admin, 'personal')->patchJson(route('personal.empleados.toggle-status', $cuenta))->assertOk();
        $this->assertFalse($cuenta->fresh()->activo);
        $this->assertSame(0, $cuenta->tokens()->count());
    }

    public function test_removing_access_or_changing_password_revokes_tokens(): void
    {
        $admin = $this->superadmin();
        $cat = $this->categoria();
        $cuenta = $this->cuenta($this->subrol($cat), 'cuenta_tokens');

        $cuenta->createToken('supervisor-app');
        $this->actingAs($admin)->putJson(route('personal.empleados.update', $cuenta), $this->payloadEmpleado($cuenta, ['password' => 'NuevaClave1']))->assertOk();
        $this->assertSame(0, $cuenta->tokens()->count());

        $cuenta->createToken('supervisor-app');
        $this->actingAs($admin)->putJson(route('personal.empleados.update', $cuenta), $this->payloadEmpleado($cuenta->fresh(), ['has_login' => false]))->assertOk();
        $this->assertSame(0, $cuenta->tokens()->count());
    }

    // --- Sesion web ---

    public function test_deactivated_employee_session_is_cut_on_next_request(): void
    {
        $cuenta = $this->cuenta($this->subrol($this->categoria(), ['ver_programacion' => true]), 'sesion_inactiva');

        $this->actingAs($cuenta, 'personal')->get(route('personal.programacion.index'))->assertOk();

        $cuenta->update(['activo' => false]);

        $this->actingAs($cuenta->fresh(), 'personal')->get(route('personal.programacion.index'))
            ->assertRedirect(route('personal.login'));
        $this->actingAs($cuenta->fresh(), 'personal')->getJson(route('personal.programacion.dias-con-datos'))
            ->assertStatus(401);
    }

    public function test_password_changed_by_admin_cuts_the_open_session(): void
    {
        $cuenta = $this->cuenta($this->subrol($this->categoria(), ['ver_programacion' => true]), 'sesion_clave', 'clave-vieja');

        $this->post(route('personal.login.attempt'), ['username' => 'sesion_clave', 'password' => 'clave-vieja'])->assertRedirect();
        $this->get(route('personal.programacion.index'))->assertOk();

        $cuenta->update(['password' => bcrypt('clave-nueva')]);
        $this->app['auth']->forgetGuards();

        $this->get(route('personal.programacion.index'))->assertRedirect(route('personal.login'));
    }

    public function test_web_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('personal.login.attempt'), ['username' => 'nadie', 'password' => 'mala'])->assertStatus(302);
        }

        $this->post(route('personal.login.attempt'), ['username' => 'nadie', 'password' => 'mala'])->assertStatus(429);
    }

    // --- Topes de horas (antes un valor grande daba error 500) ---

    public function test_hours_limits_in_diario_and_bitacora(): void
    {
        $admin = $this->superadmin();
        $a = $this->actividad();
        $trabajador = $this->trabajador($this->categoria());

        $this->actingAs($admin)->patchJson(route('personal.diario-campo.inline-update', $a), ['field' => 'corrected_hours', 'value' => '25'])
            ->assertStatus(422);
        $this->actingAs($admin)->postJson(route('personal.bitacora.quota.store'), ['year' => 2026, 'month' => 9, 'quota_hours' => 745])
            ->assertStatus(422);
        $this->actingAs($admin)->postJson(route('personal.bitacora.entries.store'), ['employee_id' => $trabajador->id, 'date' => '2026-09-10', 'corrected_value' => '30'])
            ->assertStatus(422);
        // Los codigos de texto siguen permitidos.
        $this->actingAs($admin)->postJson(route('personal.bitacora.entries.store'), ['employee_id' => $trabajador->id, 'date' => '2026-09-10', 'corrected_value' => 'L'])
            ->assertOk();
    }
}
