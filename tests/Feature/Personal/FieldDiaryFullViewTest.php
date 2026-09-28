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
use App\Services\FieldDiary\FieldDiaryFullView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vista unica del Diario de Campo (pedido 2026-09-28): modo "Por dia" (por
 * defecto hoy, sin columna Fecha) y modo "Todas las fechas" (?vista=todas,
 * lo mas reciente primero), paginada de a 100, con filtros por columna
 * combinables al estilo Excel (FieldDiaryFullView) y celdas editables. Una
 * fila = un grupo de horas (Activity::diaryHourGroups()).
 */
class FieldDiaryFullViewTest extends TestCase
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

    private function company(string $name): Company
    {
        return Company::firstOrCreate(['name' => $name], ['is_default' => false, 'archived' => false]);
    }

    private function empleado(string $nickname): Employee
    {
        $category = PersonalCategory::firstOrCreate(['name' => 'Campo Full View Test'], ['activo' => true]);

        return Employee::create([
            'nombre' => 'Empleado '.$nickname,
            'nickname' => $nickname,
            'personal_category_id' => $category->id,
            'activo' => true,
            'has_login' => false,
            'in_bitacora' => true,
        ]);
    }

    private function empleadoConPermisoDiario(bool $verDiario): Employee
    {
        $category = PersonalCategory::firstOrCreate(['name' => 'Campo Full View Test'], ['activo' => true]);
        $rol = PersonalRole::create([
            'name' => 'Rol Full View Test '.uniqid(),
            'personal_category_id' => $category->id,
            'activo' => true, 'disponible_en_programacion' => false, 'responsable_actividad' => false,
            'ver_diario_campo' => $verDiario, 'cerrar_diario_campo' => false,
        ]);

        return Employee::create([
            'nombre' => 'Con rol', 'nickname' => 'con-rol-'.uniqid(),
            'personal_category_id' => $category->id, 'personal_role_id' => $rol->id,
            'activo' => true, 'has_login' => true, 'username' => 'full_view_'.uniqid(),
            'password' => bcrypt('secret'), 'in_bitacora' => true,
        ]);
    }

    private function actividad(string $date, array $overrides = [], array $personas = []): Activity
    {
        $a = Activity::create(array_merge([
            'date' => $date, 'company_id' => $this->company('ARGOS')->id, 'description' => 'Actividad '.uniqid(),
            'activity_type' => 'S', 'shift' => 'Diurno', 'estimated_hours' => 8,
        ], $overrides));
        $a->personas()->sync(collect($personas)->pluck('id'));

        return $a;
    }

    // Modo "Todas las fechas" de la vista unica del Diario de Campo.
    private function completo(User|Employee $quien, array $query = [])
    {
        return $this->diario($quien, ['vista' => 'todas'] + $query);
    }

    private function diario(User|Employee $quien, array $query = [])
    {
        $guard = $quien instanceof Employee ? 'personal' : 'web';

        return $this->actingAs($quien, $guard)->get(route('personal.diario-campo.index', $query));
    }

    public function test_requires_ver_diario_campo_permission(): void
    {
        $this->completo($this->empleadoConPermisoDiario(false))->assertStatus(403);
        $this->completo($this->empleadoConPermisoDiario(true))->assertOk();
    }

    public function test_lists_all_dates_most_recent_first(): void
    {
        $this->actividad('2026-09-01');
        $this->actividad('2026-09-15');
        $this->actividad('2026-09-10');

        $filas = $this->completo($this->superadmin())->assertOk()->viewData('filas');

        $this->assertSame(['2026-09-15', '2026-09-10', '2026-09-01'], collect($filas->items())->pluck('fecha')->all());
    }

    public function test_paginates_by_100_rows(): void
    {
        for ($i = 0; $i < 101; $i++) {
            $this->actividad('2026-09-'.str_pad((string) ($i % 28 + 1), 2, '0', STR_PAD_LEFT));
        }
        $admin = $this->superadmin();

        $pagina1 = $this->completo($admin)->viewData('filas');
        $this->assertCount(FieldDiaryFullView::PER_PAGE, $pagina1->items());
        $this->assertSame(101, $pagina1->total());
        $this->assertSame(2, $pagina1->lastPage());

        $this->assertCount(1, $this->completo($admin, ['page' => 2])->viewData('filas')->items());
    }

    public function test_filters_combine_across_columns(): void
    {
        $this->actividad('2026-09-20', ['company_id' => $this->company('ARGOS')->id, 'shift' => 'Nocturno']);
        $this->actividad('2026-09-20', ['company_id' => $this->company('ARGOS')->id, 'shift' => 'Diurno']);
        $this->actividad('2026-09-20', ['company_id' => $this->company('CORONA')->id, 'shift' => 'Nocturno']);

        $response = $this->completo($this->superadmin(), ['empresa' => ['ARGOS'], 'jornada' => ['Nocturno']]);

        $filas = collect($response->viewData('filas')->items());
        $this->assertCount(1, $filas);
        $this->assertSame(['ARGOS', 'Nocturno'], [$filas[0]['empresa'], $filas[0]['jornada']]);
        $this->assertSame(3, $response->viewData('totalGenerado'));
        $this->assertSame(1, $response->viewData('totalFiltrado'));
    }

    // Una actividad con horas distintas por persona son 2 filas (igual que
    // la vista diaria), y el filtro de Horas actua por fila, no por
    // actividad completa.
    public function test_hours_filter_works_per_hour_group(): void
    {
        $ana = $this->empleado('Ana');
        $beto = $this->empleado('Beto');
        $a = $this->actividad('2026-09-20', ['estimated_hours' => 10, 'all_worked_scheduled_hours' => false], [$ana, $beto]);
        ActivityEmployeeHour::create(['activity_id' => $a->id, 'employee_id' => $ana->id, 'worked' => true, 'worked_hours' => 10]);
        ActivityEmployeeHour::create(['activity_id' => $a->id, 'employee_id' => $beto->id, 'worked' => true, 'worked_hours' => 8.5]);
        $admin = $this->superadmin();

        $todas = collect($this->completo($admin)->viewData('filas')->items());
        $this->assertCount(2, $todas);

        $filtradas = collect($this->completo($admin, ['horas' => ['8.5']])->viewData('filas')->items());
        $this->assertCount(1, $filtradas);
        $this->assertSame(['Beto'], $filtradas[0]['personas']);
        $this->assertSame(8.5, $filtradas[0]['horas']);
    }

    public function test_personas_and_n_personas_filters(): void
    {
        $ana = $this->empleado('Ana');
        $beto = $this->empleado('Beto');
        $caro = $this->empleado('Caro');
        $this->actividad('2026-09-20', ['description' => 'Con Ana y Beto'], [$ana, $beto]);
        $this->actividad('2026-09-20', ['description' => 'Solo Caro'], [$caro]);
        $this->actividad('2026-09-20', ['description' => 'Sin personas']);
        $admin = $this->superadmin();

        $porPersona = collect($this->completo($admin, ['personas' => ['Beto', 'Caro']])->viewData('filas')->items());
        $this->assertEqualsCanonicalizing(['Con Ana y Beto', 'Solo Caro'], $porPersona->pluck('actividad')->all());

        $porCantidad = collect($this->completo($admin, ['n_personas' => ['2']])->viewData('filas')->items());
        $this->assertSame(['Con Ana y Beto'], $porCantidad->pluck('actividad')->all());

        $sinPersonas = collect($this->completo($admin, ['personas' => [FieldDiaryFullView::EMPTY_VALUE]])->viewData('filas')->items());
        $this->assertSame(['Sin personas'], $sinPersonas->pluck('actividad')->all());
    }

    public function test_empty_value_filter_on_text_column(): void
    {
        $this->actividad('2026-09-20', ['process' => 'SST']);
        $this->actividad('2026-09-20', ['process' => null]);
        $this->actividad('2026-09-20', ['process' => '']);

        $filas = collect($this->completo($this->superadmin(), ['proceso' => [FieldDiaryFullView::EMPTY_VALUE]])->viewData('filas')->items());

        $this->assertCount(2, $filas);
        $this->assertTrue($filas->every(fn ($f) => trim((string) $f['proceso']) === ''));
    }

    public function test_date_range_filter_and_invalid_dates_are_ignored(): void
    {
        $this->actividad('2026-09-01');
        $this->actividad('2026-09-10');
        $this->actividad('2026-09-20');
        $admin = $this->superadmin();

        $rango = collect($this->completo($admin, ['fecha_desde' => '2026-09-05', 'fecha_hasta' => '2026-09-15'])->viewData('filas')->items());
        $this->assertSame(['2026-09-10'], $rango->pluck('fecha')->all());

        $invalida = $this->completo($admin, ['fecha_desde' => '2026-02-31', 'fecha_hasta' => 'mañana']);
        $this->assertSame(['from' => null, 'to' => null], $invalida->viewData('filtrosActivos')['fecha']);
        $this->assertSame(3, $invalida->viewData('totalFiltrado'));
    }

    // Como Excel: las opciones de una columna ignoran el filtro de esa
    // misma columna (se pueden agregar mas valores) pero respetan los
    // demas filtros.
    public function test_options_ignore_own_column_filter_but_respect_the_others(): void
    {
        $this->actividad('2026-09-20', ['company_id' => $this->company('ARGOS')->id, 'team' => 'Horno 2']);
        $this->actividad('2026-09-20', ['company_id' => $this->company('CORONA')->id, 'team' => 'Rioclaro']);

        $opciones = $this->completo($this->superadmin(), ['empresa' => ['CORONA']])->viewData('opciones');

        $this->assertSame(['ARGOS', 'CORONA'], collect($opciones['empresa'])->pluck('value')->all());
        $this->assertSame(['Rioclaro'], collect($opciones['equipo'])->pluck('value')->all());
    }

    public function test_renders_column_filters_active_chip_and_clear_button(): void
    {
        $this->actividad('2026-09-20', ['company_id' => $this->company('CORONA')->id]);

        $response = $this->completo($this->superadmin(), ['empresa' => ['CORONA']]);

        $response->assertOk();
        $html = $response->getContent();
        $this->assertSame(count(FieldDiaryFullView::COLUMNS), substr_count($html, 'class="dc-th-btn'));
        $response->assertSee('Empresa:</span> CORONA', false);
        $response->assertSee('Limpiar filtros');
        // La primera columna es Fecha.
        $this->assertLessThan(strpos($html, 'Filtrar por Empresa'), strpos($html, 'Filtrar por Fecha'));

        // Version compacta (pedido 2026-09-28): menos margen en <main>, sin
        // texto de ayuda bajo el titulo, una sola paginacion (arriba).
        $this->assertStringContainsString('dc-main-compacto', $html);
        $this->assertStringNotContainsString('clic en el encabezado de una columna para filtrar', $html);
        $this->assertLessThanOrEqual(1, substr_count($html, 'class="custom-pagination"'));

        // Filtrado primero (resaltado), Total despues.
        $this->assertLessThan(strpos($html, 'Total: '), strpos($html, 'Filtrado:'));
        $this->assertStringContainsString('<span class="dc-metrica-principal">1</span>', $html);
    }

    // --- Modo "Por dia" (pedido 2026-09-28: una sola vista) ---

    public function test_default_mode_is_today_without_date_column(): void
    {
        $this->actividad(today()->toDateString(), ['description' => 'De hoy']);
        $this->actividad(today()->subDay()->toDateString(), ['description' => 'De ayer']);

        $response = $this->diario($this->superadmin())->assertOk();

        $this->assertSame(['De hoy'], collect($response->viewData('filas')->items())->pluck('actividad')->all());
        $this->assertSame(today()->toDateString(), $response->viewData('dia'));
        $this->assertFalse($response->viewData('modoTodas'));
        $this->assertArrayNotHasKey('fecha', $response->viewData('columnas'));
        $this->assertSame(1, $response->viewData('totalGenerado'));
        $response->assertDontSee('Filtrar por Fecha');
        $response->assertSee('Por día');
        $response->assertSee('Todas las fechas');
    }

    public function test_date_param_selects_the_day_and_ignores_date_range_filter(): void
    {
        $this->actividad('2026-09-10', ['description' => 'Dia diez', 'company_id' => $this->company('ARGOS')->id]);
        $this->actividad('2026-09-10', ['description' => 'Dia diez CORONA', 'company_id' => $this->company('CORONA')->id]);
        $this->actividad('2026-09-11', ['description' => 'Dia once']);

        $response = $this->diario($this->superadmin(), [
            'date' => '2026-09-10',
            'fecha_desde' => '2026-09-11',
            'empresa' => ['CORONA'],
        ]);

        $this->assertSame(['Dia diez CORONA'], collect($response->viewData('filas')->items())->pluck('actividad')->all());
        $this->assertSame(['from' => null, 'to' => null], $response->viewData('filtrosActivos')['fecha']);
        $this->assertSame(2, $response->viewData('totalGenerado'));
    }

    public function test_invalid_date_param_falls_back_to_today(): void
    {
        $response = $this->diario($this->superadmin(), ['date' => '2026-13-45']);

        $this->assertSame(today()->toDateString(), $response->viewData('dia'));
    }

    public function test_all_dates_mode_shows_date_column(): void
    {
        $this->actividad('2026-09-10');

        $response = $this->completo($this->superadmin());

        $this->assertTrue($response->viewData('modoTodas'));
        $this->assertNull($response->viewData('dia'));
        $this->assertArrayHasKey('fecha', $response->viewData('columnas'));
        $response->assertSee('Filtrar por Fecha');
    }

    // Celdas editables con permiso: Equipo, Proceso, Actividad programada,
    // Actividad ejecutada/Comentarios, Horas y las columnas grises. No:
    // Personas, N°, Empresa, Jornada.
    public function test_editable_cells_cover_the_requested_columns_only(): void
    {
        $ana = $this->empleado('Ana');
        $this->actividad(today()->toDateString(), ['team' => 'Horno', 'process' => 'SST', 'comments' => 'Hecho'], [$ana]);

        $html = $this->diario($this->superadmin())->getContent();

        foreach (['team', 'process', 'description', 'comments', 'corrected_hours', 'zcom', 'line_code', 'ot_sap', 'acta_entrega', 'we_code'] as $campo) {
            $this->assertStringContainsString('data-field="'.$campo.'"', $html, $campo);
        }
        // "Actividad ejecutada" y "Comentarios" = mismo campo, dos celdas.
        $this->assertSame(2, substr_count($html, 'data-field="comments"'));
        foreach (['personas', 'company_id', 'shift', 'date'] as $campo) {
            $this->assertStringNotContainsString('data-field="'.$campo.'"', $html, $campo);
        }
    }
}
