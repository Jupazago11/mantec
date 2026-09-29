<?php

namespace Tests\Feature\Personal;

use App\Models\Employee;
use App\Models\PersonalCategory;
use App\Models\PersonalRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pedido 2026-09-29: el nickname de empleado no se repite (sin distinguir
 * mayusculas) y el usuario de acceso no se repite ni entre empleados ni
 * contra los usuarios del modulo de reportes de activos (tabla users), en
 * ambos sentidos. Ademas el formulario ya no trae placeholders de ejemplo
 * ni deja que el navegador autocomplete usuario/contrasena.
 */
class EmployeeUniqueIdentityTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(string $username = 'admin_unico'): User
    {
        $role = Role::firstOrCreate(['key' => 'superadmin'], ['name' => 'superadmin', 'status' => true]);

        return User::create([
            'name' => 'Admin Test', 'username' => $username,
            'password' => bcrypt('secret'), 'role_id' => $role->id, 'status' => true,
        ]);
    }

    private function categoria(): PersonalCategory
    {
        return PersonalCategory::firstOrCreate(['name' => 'Campo Unico'], ['activo' => true]);
    }

    private function empleado(string $nickname, ?string $username = null): Employee
    {
        return Employee::create([
            'nombre' => 'Empleado '.$nickname, 'nickname' => $nickname, 'personal_category_id' => $this->categoria()->id,
            'activo' => true, 'has_login' => $username !== null, 'username' => $username,
            'password' => $username !== null ? bcrypt('secret') : null, 'in_bitacora' => true,
        ]);
    }

    private function payload(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'Nuevo Empleado', 'nickname' => 'Nuevo', 'personal_category_id' => $this->categoria()->id,
            'personal_role_id' => null, 'has_login' => false, 'username' => '', 'password' => '', 'in_bitacora' => true,
        ], $cambios);
    }

    // --- Nickname ---

    public function test_nickname_cannot_repeat_ignoring_case_and_spaces(): void
    {
        $admin = $this->superadmin();
        $this->empleado('Fernando');

        foreach (['Fernando', 'fernando', '  FERNANDO  '] as $repetido) {
            $this->actingAs($admin)->postJson(route('personal.empleados.store'), $this->payload(['nickname' => $repetido]))
                ->assertStatus(422)
                ->assertJsonValidationErrors(['nickname' => 'Ya existe un empleado con ese nickname.']);
        }

        $this->assertSame(1, Employee::count());

        $this->actingAs($admin)->postJson(route('personal.empleados.store'), $this->payload(['nickname' => 'Fernando M']))
            ->assertOk();
    }

    public function test_editing_keeps_own_nickname_but_cannot_take_another(): void
    {
        $admin = $this->superadmin();
        $luis = $this->empleado('Luis');
        $this->empleado('Pedro');

        $this->actingAs($admin)->putJson(route('personal.empleados.update', $luis), $this->payload(['nombre' => 'Luis Editado', 'nickname' => 'LUIS']))
            ->assertOk();
        $this->assertSame('LUIS', $luis->fresh()->nickname);

        $this->actingAs($admin)->putJson(route('personal.empleados.update', $luis), $this->payload(['nickname' => 'pedro']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('nickname');
    }

    // --- Usuario de acceso ---

    public function test_employee_username_cannot_repeat_another_employee_ignoring_case(): void
    {
        $admin = $this->superadmin();
        $this->empleado('Ana', 'ana.campo');

        $this->actingAs($admin)->postJson(route('personal.empleados.store'), $this->payload([
            'has_login' => true, 'username' => 'ANA.Campo', 'password' => 'secret1',
        ]))->assertStatus(422)->assertJsonValidationErrors('username');
    }

    public function test_employee_username_cannot_repeat_a_reports_module_user(): void
    {
        $admin = $this->superadmin('superadmin');

        foreach (['superadmin', 'SuperAdmin'] as $repetido) {
            $this->actingAs($admin)->postJson(route('personal.empleados.store'), $this->payload([
                'has_login' => true, 'username' => $repetido, 'password' => 'secret1',
            ]))->assertStatus(422)->assertJsonValidationErrors([
                'username' => 'Ese nombre de usuario ya está en uso (en Empleados o en los usuarios del sistema).',
            ]);
        }

        $this->assertSame(0, Employee::count());
    }

    public function test_editing_employee_keeps_own_username(): void
    {
        $admin = $this->superadmin();
        $ana = $this->empleado('Ana', 'ana.campo');

        $this->actingAs($admin)->putJson(route('personal.empleados.update', $ana), $this->payload([
            'nombre' => 'Ana Editada', 'nickname' => 'Ana', 'has_login' => true, 'username' => 'ana.campo',
        ]))->assertOk();

        $this->assertSame('Ana Editada', $ana->fresh()->nombre);
    }

    public function test_username_is_ignored_when_employee_has_no_access(): void
    {
        $admin = $this->superadmin('superadmin');

        // Sin acceso el usuario se descarta (preparePayload), asi que no
        // debe chocar con nadie.
        $this->actingAs($admin)->postJson(route('personal.empleados.store'), $this->payload([
            'has_login' => false, 'username' => 'superadmin',
        ]))->assertOk();

        $this->assertNull(Employee::first()->username);
    }

    public function test_reports_module_user_cannot_take_an_employee_username(): void
    {
        $admin = $this->superadmin();
        $this->empleado('Ana', 'ana.campo');

        $this->actingAs($admin)->postJson(route('admin.managed-users.store'), [
            'name' => 'Usuario Reportes', 'username' => 'Ana.Campo', 'password' => 'secret1',
        ])->assertStatus(422)->assertJsonValidationErrors([
            'username' => 'Ese nombre de usuario ya está en uso (en Empleados o en los usuarios del sistema).',
        ]);

        // Y editando: el superadmin no puede renombrarse al usuario de un
        // empleado, pero si conservar el suyo.
        $this->actingAs($admin)->putJson(route('admin.managed-users.update', $admin), [
            'name' => 'Admin Test', 'username' => 'ana.campo',
        ])->assertStatus(422)->assertJsonValidationErrors('username');

        $this->actingAs($admin)->putJson(route('admin.managed-users.update', $admin), [
            'name' => 'Admin Renombrado', 'username' => 'admin_unico',
        ])->assertOk()->assertJson(['success' => true]);
        $this->assertSame('Admin Renombrado', $admin->fresh()->name);
    }

    // --- Respaldo en BD ---

    public function test_database_rejects_case_insensitive_duplicates(): void
    {
        $this->empleado('Carlos', 'carlos.op');

        try {
            // Savepoint: en Postgres un error aborta la transaccion de la prueba.
            DB::transaction(fn () => $this->empleado(' carlos '));
            $this->fail('El indice unico de nickname debio rechazar el duplicado.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('employees_nickname_lower_unique', $e->getMessage());
        }

        try {
            DB::transaction(fn () => $this->empleado('Otro', 'CARLOS.OP'));
            $this->fail('El indice unico de usuario debio rechazar el duplicado.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('employees_username_lower_unique', $e->getMessage());
        }
    }

    // --- Formulario ---

    public function test_form_has_no_example_placeholders_and_blocks_browser_autofill(): void
    {
        $html = $this->actingAs($this->superadmin())->get(route('personal.empleados.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Ej. Luis Fernando Montoya Zuluaga', $html);
        $this->assertStringNotContainsString('Ej. Fernando', $html);
        $this->assertStringNotContainsString('Ej. lfernando', $html);

        $this->assertMatchesRegularExpression('/name="empleado_usuario_nuevo"[^>]*autocomplete="off"/', $html);
        $this->assertMatchesRegularExpression('/name="empleado_clave_nueva"[^>]*autocomplete="new-password"/', $html);
        $this->assertStringNotContainsString('name="username"', $html);
        $this->assertStringNotContainsString('name="password"', $html);
    }
}
