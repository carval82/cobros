<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use App\Services\ActivacionLicencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenciaAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_maestro_ve_el_tenant_y_abre_la_licencia(): void
    {
        $maestro = User::factory()->create(['es_maestro' => true]);
        $empresa = Empresa::create([
            'nombre' => 'Norte',
            'slug' => 'norte',
            'base_datos' => 'cobros_e9',
            'plan' => 'demo',
            'activo' => true,
            'codigo_solicitud' => ActivacionLicencia::codigoSolicitud('cobros-norte'),
        ]);
        $clave = ActivacionLicencia::generar($empresa->codigo_solicitud, 'premium', 90);

        $this->actingAs(User::factory()->create())->get('/licencias')->assertForbidden();

        $this->actingAs($maestro)
            ->get('/licencias')
            ->assertOk()
            ->assertSee('Norte')
            ->assertSee('/tenant/'.$empresa->uuid)
            ->assertSee($empresa->codigo_solicitud);

        $this->actingAs($maestro)
            ->post('/licencias/'.$empresa->id, [
                'plan' => 'premium',
                'dias' => 90,
                'modo' => 'clave',
            ])
            ->assertRedirect()
            ->assertSessionHas('clave.codigo', $clave);

        $this->actingAs($maestro)
            ->post('/licencias/'.$empresa->id, [
                'plan' => 'premium',
                'dias' => 90,
                'modo' => 'activar',
            ])
            ->assertRedirect();

        $empresa->refresh();
        $this->assertSame('premium', $empresa->plan);
        $this->assertNotNull($empresa->licencia_activada_at);
        $this->assertTrue($empresa->fecha_vencimiento->greaterThan(now()->addDays(80)));
        $this->assertSame(
            ['plan' => 'premium', 'days' => 90],
            ActivacionLicencia::resolver($clave, $empresa->codigo_solicitud)
        );
    }
}
