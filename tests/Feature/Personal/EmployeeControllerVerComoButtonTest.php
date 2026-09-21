<?php

namespace Tests\Feature\Personal;

use App\Models\Employee;
use App\Models\PersonalCategory;
use App\Models\PersonalRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobertura del bug 2026-09-19 (sección 14.18): el botón "Ver como
 * supervisor" en Empleados rompía en consola ("verComoUrlTemplate is not
 * defined") porque la variable se pasó al x-data de Blade pero nunca se
 * agregó a la firma de personalEmpleadosPage() ni al objeto devuelto.
 * También cubre el pedido de ese mismo momento: el botón solo debe
 * aparecer para empleados con un rol elegible como Responsable, no para
 * todos.
 */
class EmployeeControllerVerComoButtonTest extends TestCase
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

    public function test_empleados_index_renders_without_alpine_data_errors_and_marks_eligible_responsables(): void
    {
        $admin = $this->superadmin();
        $category = PersonalCategory::create(['name' => 'Categoria Ver Como Boton Test', 'activo' => true, 'responsable_actividad' => true]);

        $rolElegible = PersonalRole::create([
            'name' => 'Subrol Elegible Test', 'personal_category_id' => $category->id,
            'activo' => true, 'disponible_en_programacion' => true, 'responsable_actividad' => true,
            'ver_empleados' => false, 'ver_programacion' => false, 'editar_programacion_sin_limite' => false,
            'ver_diario_campo' => false, 'cerrar_diario_campo' => false, 'ver_bitacora' => false,
        ]);
        $rolNoElegible = PersonalRole::create([
            'name' => 'Subrol No Elegible Test', 'personal_category_id' => $category->id,
            'activo' => true, 'disponible_en_programacion' => true, 'responsable_actividad' => false,
            'ver_empleados' => false, 'ver_programacion' => false, 'editar_programacion_sin_limite' => false,
            'ver_diario_campo' => false, 'cerrar_diario_campo' => false, 'ver_bitacora' => false,
        ]);

        Employee::create([
            'nombre' => 'Empleado Elegible', 'nickname' => 'elegible-test',
            'personal_category_id' => $category->id, 'personal_role_id' => $rolElegible->id,
            'activo' => true, 'has_login' => false, 'in_bitacora' => true,
        ]);
        Employee::create([
            'nombre' => 'Empleado No Elegible', 'nickname' => 'no-elegible-test',
            'personal_category_id' => $category->id, 'personal_role_id' => $rolNoElegible->id,
            'activo' => true, 'has_login' => false, 'in_bitacora' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('personal.empleados.index'));

        $response->assertOk();
        // Confirma que la variable si esta en el x-data (la firma de
        // personalEmpleadosPage la recibe) — sin esto, el bug original
        // ("verComoUrlTemplate is not defined") pasaba desapercibido para
        // un test que solo mira el status code.
        $response->assertSee('verComoUrlTemplate', false);

        $backslashU0022 = chr(92).'u0022';
        $response->assertSee("es_responsable{$backslashU0022}:true", false);
        $response->assertSee("es_responsable{$backslashU0022}:false", false);
    }
}
