<?php

namespace Tests\Feature\Api\Personal;

use App\Models\Employee;
use App\Models\PersonalCategory;
use App\Models\PersonalRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Login/logout API del supervisor (sección 14.24) — Employee con Sanctum,
 * independiente del login del Inspector (User). Mismo criterio de
 * PersonalAuthController (web): has_login=true, activo=true, Hash::check.
 */
class AuthApiControllerTest extends TestCase
{
    use RefreshDatabase;

    private function empleadoConLogin(string $username, string $password = 'secret123', array $overrides = []): Employee
    {
        $category = PersonalCategory::firstOrCreate(['name' => 'Campo Auth API Test'], ['activo' => true]);
        $role = PersonalRole::create([
            'name' => 'Rol Auth API Test '.uniqid(),
            'personal_category_id' => $category->id,
            'activo' => true, 'disponible_en_programacion' => true, 'responsable_actividad' => true,
        ]);

        return Employee::create(array_merge([
            'nombre' => 'Supervisor Test', 'nickname' => 'sup-test',
            'personal_category_id' => $category->id, 'personal_role_id' => $role->id,
            'activo' => true, 'has_login' => true, 'username' => $username,
            'password' => bcrypt($password), 'in_bitacora' => true,
        ], $overrides));
    }

    public function test_login_with_correct_credentials_returns_token(): void
    {
        $this->empleadoConLogin('supervisor_login_test');

        $response = $this->postJson('/api/personal/login', [
            'username' => 'supervisor_login_test',
            'password' => 'secret123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('employee.nickname', 'sup-test');
        $response->assertJsonStructure(['token', 'employee' => ['id', 'nombre', 'nickname', 'personal_role' => ['id', 'name', 'responsable_actividad']]]);
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        $this->empleadoConLogin('supervisor_login_wrong_pw');

        $response = $this->postJson('/api/personal/login', [
            'username' => 'supervisor_login_wrong_pw',
            'password' => 'incorrecta',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('success', false);
    }

    public function test_login_rejects_employee_without_has_login(): void
    {
        $this->empleadoConLogin('supervisor_sin_login', overrides: ['has_login' => false]);

        $response = $this->postJson('/api/personal/login', [
            'username' => 'supervisor_sin_login',
            'password' => 'secret123',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_rejects_inactive_employee(): void
    {
        $this->empleadoConLogin('supervisor_inactivo', overrides: ['activo' => false]);

        $response = $this->postJson('/api/personal/login', [
            'username' => 'supervisor_inactivo',
            'password' => 'secret123',
        ]);

        $response->assertStatus(401);
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $employee = $this->empleadoConLogin('supervisor_logout_test');
        Sanctum::actingAs($employee, ['*']);

        $response = $this->postJson('/api/personal/logout');

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }
}
