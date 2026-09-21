<?php

namespace Tests\Feature\Personal;

use App\Models\Activity;
use App\Models\ActivityEmployeeHour;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PersonalCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobertura de Activity::diaryHourGroups() (sección 14.21, pedido
 * 2026-09-19): Diario de Campo agrupa las filas por valor de horas
 * DISTINTO entre las personas de una actividad — no una fila por
 * actividad con un total agregado, ni una fila por persona.
 */
class ActivityDiaryHourGroupsTest extends TestCase
{
    use RefreshDatabase;

    private function company(): Company
    {
        return Company::create(['name' => 'Empresa Test '.uniqid(), 'is_default' => true, 'archived' => false]);
    }

    private function empleado(string $nickname): Employee
    {
        $category = PersonalCategory::firstOrCreate(['name' => 'Campo Diary Groups Test'], ['activo' => true]);

        return Employee::create([
            'nombre' => 'Empleado '.$nickname,
            'nickname' => $nickname,
            'personal_category_id' => $category->id,
            'activo' => true,
            'has_login' => false,
            'in_bitacora' => true,
        ]);
    }

    public function test_activity_without_any_registration_yields_a_single_group_with_estimated_hours(): void
    {
        $company = $this->company();
        $p1 = $this->empleado('sin-registro-1');
        $p2 = $this->empleado('sin-registro-2');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 8,
        ]);
        $activity->personas()->sync([$p1->id, $p2->id]);

        $grupos = $activity->fresh(['personas', 'employeeHours'])->diaryHourGroups();

        $this->assertCount(1, $grupos);
        $this->assertSame(8.0, $grupos[0]['horas']);
        $this->assertEqualsCanonicalizing([$p1->id, $p2->id], $grupos[0]['personas']->pluck('id')->all());
    }

    public function test_all_worked_scheduled_hours_yields_a_single_group_with_reported_hours(): void
    {
        $company = $this->company();
        $p1 = $this->empleado('todos-trabajaron-1');
        $p2 = $this->empleado('todos-trabajaron-2');
        $p3 = $this->empleado('todos-trabajaron-3');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 12,
            'all_worked_scheduled_hours' => true, 'reported_hours' => 12,
        ]);
        $activity->personas()->sync([$p1->id, $p2->id, $p3->id]);

        $grupos = $activity->fresh(['personas', 'employeeHours'])->diaryHourGroups();

        $this->assertCount(1, $grupos);
        $this->assertSame(12.0, $grupos[0]['horas']);
        $this->assertSame(3, $grupos[0]['personas']->count());
    }

    public function test_different_worked_hours_per_person_split_into_separate_groups(): void
    {
        $company = $this->company();
        $ocho1 = $this->empleado('ocho-horas-1');
        $ocho2 = $this->empleado('ocho-horas-2');
        $diez1 = $this->empleado('diez-horas-1');
        $diez2 = $this->empleado('diez-horas-2');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 9,
            'all_worked_scheduled_hours' => false,
        ]);
        $activity->personas()->sync([$ocho1->id, $ocho2->id, $diez1->id, $diez2->id]);
        foreach ([$ocho1->id, $ocho2->id] as $id) {
            ActivityEmployeeHour::create(['activity_id' => $activity->id, 'employee_id' => $id, 'worked' => true, 'worked_hours' => 8]);
        }
        foreach ([$diez1->id, $diez2->id] as $id) {
            ActivityEmployeeHour::create(['activity_id' => $activity->id, 'employee_id' => $id, 'worked' => true, 'worked_hours' => 10]);
        }

        $grupos = $activity->fresh(['personas', 'employeeHours'])->diaryHourGroups();

        $this->assertCount(2, $grupos);
        $porHoras = collect($grupos)->keyBy('horas');
        $this->assertEqualsCanonicalizing([$ocho1->id, $ocho2->id], $porHoras[8.0]['personas']->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$diez1->id, $diez2->id], $porHoras[10.0]['personas']->pluck('id')->all());
    }

    public function test_same_worked_hours_for_everyone_still_yields_a_single_group(): void
    {
        $company = $this->company();
        $p1 = $this->empleado('mismo-valor-1');
        $p2 = $this->empleado('mismo-valor-2');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 6,
            'all_worked_scheduled_hours' => false,
        ]);
        $activity->personas()->sync([$p1->id, $p2->id]);
        ActivityEmployeeHour::create(['activity_id' => $activity->id, 'employee_id' => $p1->id, 'worked' => true, 'worked_hours' => 6]);
        ActivityEmployeeHour::create(['activity_id' => $activity->id, 'employee_id' => $p2->id, 'worked' => true, 'worked_hours' => 6]);

        $grupos = $activity->fresh(['personas', 'employeeHours'])->diaryHourGroups();

        $this->assertCount(1, $grupos);
        $this->assertSame(6.0, $grupos[0]['horas']);
    }

    // corrected_hours (correccion administrativa) es un valor unico para
    // toda la actividad, nunca por persona — debe colapsar a 1 fila aunque
    // exista detalle distinto por persona.
    public function test_corrected_hours_collapses_to_a_single_group_regardless_of_per_person_detail(): void
    {
        $company = $this->company();
        $ocho = $this->empleado('corregida-ocho');
        $diez = $this->empleado('corregida-diez');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 9,
            'all_worked_scheduled_hours' => false, 'corrected_hours' => 9.5,
        ]);
        $activity->personas()->sync([$ocho->id, $diez->id]);
        ActivityEmployeeHour::create(['activity_id' => $activity->id, 'employee_id' => $ocho->id, 'worked' => true, 'worked_hours' => 8]);
        ActivityEmployeeHour::create(['activity_id' => $activity->id, 'employee_id' => $diez->id, 'worked' => true, 'worked_hours' => 10]);

        $grupos = $activity->fresh(['personas', 'employeeHours'])->diaryHourGroups();

        $this->assertCount(1, $grupos);
        $this->assertSame(9.5, $grupos[0]['horas']);
        $this->assertSame(2, $grupos[0]['personas']->count());
    }

    // Caso borde: una persona agregada a la actividad DESPUES de que el
    // supervisor ya registro el detalle por persona no tiene fila en
    // activity_employee_hours todavia — se agrupa aparte, con horas null
    // (en vez de fallar o desaparecer de la tabla).
    public function test_person_without_a_registered_hour_row_gets_its_own_group_with_null_hours(): void
    {
        $company = $this->company();
        $registrada = $this->empleado('con-registro');
        $sinRegistro = $this->empleado('sin-registro-tardio');

        $activity = Activity::create([
            'date' => today()->toDateString(), 'company_id' => $company->id, 'description' => 'x',
            'activity_type' => 'P', 'shift' => 'Diurno', 'estimated_hours' => 8,
            'all_worked_scheduled_hours' => false,
        ]);
        $activity->personas()->sync([$registrada->id, $sinRegistro->id]);
        ActivityEmployeeHour::create(['activity_id' => $activity->id, 'employee_id' => $registrada->id, 'worked' => true, 'worked_hours' => 8]);

        $grupos = $activity->fresh(['personas', 'employeeHours'])->diaryHourGroups();

        $this->assertCount(2, $grupos);
        $sinHoras = collect($grupos)->firstWhere('horas', null);
        $this->assertNotNull($sinHoras);
        $this->assertEqualsCanonicalizing([$sinRegistro->id], $sinHoras['personas']->pluck('id')->all());
    }
}
