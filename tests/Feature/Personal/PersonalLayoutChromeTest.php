<?php

namespace Tests\Feature\Personal;

use App\Models\PersonalCategory;
use App\Models\PersonalRole;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Layout de /personal/* sin header en escritorio (pedido 2026-09-28): el
 * titulo ya esta en cada pantalla; "Salir" y "Volver al panel admin"
 * pasaron al pie del sidebar. En movil queda una barra minima (lg:hidden)
 * con el boton del menu, y con el sidebar colapsado una pestana fija lo
 * reabre.
 */
class PersonalLayoutChromeTest extends TestCase
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

    private function empleadoConProgramacion(): Employee
    {
        $category = PersonalCategory::firstOrCreate(['name' => 'Layout Test'], ['activo' => true]);
        $rol = PersonalRole::create([
            'name' => 'Rol Layout Test '.uniqid(),
            'personal_category_id' => $category->id,
            'activo' => true, 'disponible_en_programacion' => false, 'responsable_actividad' => false,
            'ver_programacion' => true,
        ]);

        return Employee::create([
            'nombre' => 'Layout', 'nickname' => 'layout-'.uniqid(),
            'personal_category_id' => $category->id, 'personal_role_id' => $rol->id,
            'activo' => true, 'has_login' => true, 'username' => 'layout_'.uniqid(),
            'password' => bcrypt('secret'), 'in_bitacora' => true,
        ]);
    }

    public function test_header_only_on_mobile_and_logout_lives_in_sidebar(): void
    {
        $admin = $this->superadmin();

        foreach (['personal.programacion.index', 'personal.diario-campo.index', 'personal.bitacora.index', 'personal.empleados.index'] as $ruta) {
            $html = $this->actingAs($admin)->get(route($ruta))->assertOk()->getContent();

            $this->assertStringContainsString('<header class="border-b border-slate-200 bg-white lg:hidden">', $html, $ruta);

            // "Salir" y "Volver al panel admin" dentro del <aside>, no en el header.
            $aside = substr($html, strpos($html, '<aside'), strpos($html, '</aside>') - strpos($html, '<aside'));
            $this->assertStringContainsString('action="'.route('personal.logout').'"', $aside, $ruta);
            $this->assertStringContainsString('Volver al panel admin', $aside, $ruta);

            $header = substr($html, strpos($html, '<header'), strpos($html, '</header>') - strpos($html, '<header'));
            $this->assertStringNotContainsString('personal.logout', $header, $ruta);
            $this->assertStringNotContainsString(route('personal.logout'), $header, $ruta);

            // Pestana para reabrir el sidebar colapsado.
            $this->assertStringContainsString('class="personal-sidebar-reopen"', $html, $ruta);
        }
    }

    // Pedido 2026-09-28: el sidebar parpadeaba (se abria y se cerraba) al
    // recargar con el menu colapsado. El estado colapsado debe fijarse con
    // un script sincronico en <head>, antes del <body>, y el ancho del
    // <aside> no debe depender del :class de Alpine.
    public function test_sidebar_collapsed_state_is_set_before_first_paint(): void
    {
        $html = $this->actingAs($this->superadmin())->get(route('personal.programacion.index'))->assertOk()->getContent();

        $script = strpos($html, "localStorage.getItem('personal_sidebar_collapsed')");
        $this->assertNotFalse($script);
        $this->assertLessThan(strpos($html, '<body'), $script);
        $this->assertLessThan(strpos($html, '<body'), strpos($html, "html.classList.add('personal-sidebar-collapsed')"));

        $aside = substr($html, strpos($html, '<aside'), strpos($html, '>', strpos($html, '<aside')) - strpos($html, '<aside'));
        $this->assertStringContainsString('personal-sidebar ', $aside);
        $this->assertStringNotContainsString('lg:w-0', $aside);
        $this->assertStringNotContainsString('transition-all', $aside);
    }

    public function test_back_to_admin_panel_only_for_superadmin(): void
    {
        $html = $this->actingAs($this->empleadoConProgramacion(), 'personal')
            ->get(route('personal.programacion.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Volver al panel admin', $html);
        $this->assertStringContainsString('action="'.route('personal.logout').'"', $html);
    }

    // Pedido 2026-09-28 ("toda notificacion correcta, como toasts"):
    // confirmaciones con el dialogo compartido (no el confirm() nativo) y
    // el resultado de "Copiar como imagen" en el toast compartido (no una
    // burbuja propia por vista).
    public function test_notifications_use_shared_toast_and_confirm_dialog(): void
    {
        $admin = $this->superadmin();

        foreach (['personal.programacion.index', 'personal.empleados.index', 'personal.diario-campo.index', 'personal.bitacora.index'] as $ruta) {
            $html = $this->actingAs($admin)->get(route($ruta))->assertOk()->getContent();

            $this->assertStringContainsString('id="confirmDialog"', $html, $ruta);
            $this->assertStringContainsString('id="crudToast"', $html, $ruta);
            $this->assertStringNotContainsString('mensajeExport', $html, $ruta);
            $this->assertStringNotContainsString("if (!confirm(", $html, $ruta);
        }

        $programacion = $this->actingAs($admin)->get(route('personal.programacion.index'))->getContent();
        $this->assertStringContainsString('await confirmarAccion({', $programacion);
    }

    // El logo "ManTec" lleva al primer modulo permitido del rol, no a
    // Empleados fijo (un rol sin "ver_empleados" recibia 403 al hacer clic).
    public function test_logo_links_to_first_module_allowed_for_the_role(): void
    {
        $html = $this->actingAs($this->empleadoConProgramacion(), 'personal')
            ->get(route('personal.programacion.index'))
            ->getContent();

        $this->assertStringContainsString('<a href="'.route('personal.programacion.index').'" class="text-2xl font-extrabold', $html);
        $this->assertStringNotContainsString('<a href="'.route('personal.empleados.index').'" class="text-2xl font-extrabold', $html);
    }
}
