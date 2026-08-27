<?php

namespace Tests\Feature\Admin;

use App\Models\Area;
use App\Models\BandEvent;
use App\Models\Client;
use App\Models\Element;
use App\Models\ElementType;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BandEventAveriaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * El wizard de "Cambio de banda" y el modal de edición del histórico
     * exponen un checkbox "¿Avería?" (campo `averia` en band_events /
     * band_event_drafts). Debe: (a) persistir al publicar un draft nuevo,
     * y (b) poder editarse en un evento ya publicado.
     */
    private function buildElement(): array
    {
        $role = Role::create(['name' => 'Superadmin', 'key' => 'superadmin', 'status' => true]);
        $user = User::create([
            'name' => 'Admin Test',
            'username' => 'admin_test',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
            'status' => true,
        ]);
        $this->actingAs($user);

        $client = Client::create(['name' => 'Cliente Test', 'status' => true]);
        $area = Area::create(['name' => 'Area Test', 'code' => 'A1', 'client_id' => $client->id, 'status' => true]);
        $elementType = ElementType::create(['client_id' => $client->id, 'name' => 'Tipo Test', 'status' => true]);
        $group = Group::create(['client_id' => $client->id, 'name' => 'Grupo Test', 'status' => true]);

        $element = Element::create([
            'area_id' => $area->id,
            'element_type_id' => $elementType->id,
            'group_id' => $group->id,
            'name' => 'Activo Test',
            'status' => true,
        ]);

        return compact('user', 'element');
    }

    private function bandPayload(bool $averia): array
    {
        return [
            'type' => 'band',
            'report_date' => now()->format('Y-m-d'),
            'vulcanization_type' => 'mechanical',
            'brand' => 'MARCA-TEST',
            'total_thickness' => 10,
            'top_cover_thickness' => 5,
            'bottom_cover_thickness' => 3,
            'plies' => 3,
            'width' => 600,
            'length' => 200,
            'roll_count' => 1,
            'motor_current' => 15,
            'alignment' => 'OK',
            'material_accumulation' => 'OK',
            'guard' => 'OK',
            'idler_condition' => 'OK',
            'averia' => $averia,
        ];
    }

    public function test_publishing_a_band_draft_persists_averia_true(): void
    {
        ['element' => $element] = $this->buildElement();

        $this->postJson(route('band-events.draft.create', $element->id), ['type' => 'band'])
            ->assertOk();

        $this->putJson(route('band-events.draft.update', $element->id), $this->bandPayload(true))
            ->assertOk();

        $response = $this->postJson(route('band-events.draft.publish', $element->id), $this->bandPayload(true));

        $response->assertOk();
        $response->assertJsonPath('report.averia', true);

        $event = BandEvent::where('element_id', $element->id)->where('type', 'band')->firstOrFail();
        $this->assertTrue((bool) $event->averia);
    }

    public function test_editing_a_published_band_event_can_flip_averia_to_false(): void
    {
        ['element' => $element] = $this->buildElement();

        $this->postJson(route('band-events.draft.create', $element->id), ['type' => 'band'])->assertOk();
        $this->putJson(route('band-events.draft.update', $element->id), $this->bandPayload(true))->assertOk();
        $this->postJson(route('band-events.draft.publish', $element->id), $this->bandPayload(true))->assertOk();

        $event = BandEvent::where('element_id', $element->id)->where('type', 'band')->firstOrFail();
        $this->assertTrue((bool) $event->averia);

        $payload = $this->bandPayload(false);
        $response = $this->putJson(
            route('band-events.reports.update', ['element' => $element->id, 'event' => $event->id]),
            $payload
        );

        $response->assertOk();
        $response->assertJsonPath('report.averia', false);

        $this->assertFalse((bool) $event->fresh()->averia);
    }
}
