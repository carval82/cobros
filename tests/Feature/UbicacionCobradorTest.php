<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UbicacionCobradorTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_admin_ve_al_cobrador_en_vivo_y_el_cobrador_no_ve_a_los_demas(): void
    {
        $cobrador = User::factory()->create([
            'name' => 'Luis Cobra',
            'rol' => 'cobrador',
        ]);
        $admin = User::factory()->create([
            'name' => 'Ana Admin',
            'rol' => 'administrador',
        ]);

        $this->actingAs($cobrador)->postJson('/ubicacion', [
            'latitud' => 4.711,
            'longitud' => -74.072,
        ])->assertOk();

        $cobrador->refresh();
        $this->assertEquals(4.711, (float) $cobrador->latitud);
        $this->assertNotNull($cobrador->ubicacion_en);

        $this->actingAs($admin)->getJson('/en-vivo/datos')
            ->assertOk()
            ->assertJsonPath('cobradores.0.nombre', 'Luis Cobra')
            ->assertJsonPath('cobradores.0.en_linea', true)
            ->assertJsonPath('cobradores.0.latitud', 4.711);

        $this->actingAs($admin)->get('/en-vivo')->assertOk()->assertSee('En vivo');

        $this->actingAs($cobrador)->get('/en-vivo')->assertForbidden();
        $this->actingAs($admin)->postJson('/ubicacion', [
            'latitud' => 4.7,
            'longitud' => -74.07,
        ])->assertForbidden();

        $cobrador->forceFill(['ubicacion_en' => now()->subHours(3)])->save();

        $this->actingAs($admin)->getJson('/en-vivo/datos')
            ->assertOk()
            ->assertJsonPath('cobradores.0.en_linea', false);
    }
}
