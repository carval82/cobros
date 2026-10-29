<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Pago;
use App\Models\User;
use App\Services\PrestamoService;
use App\Services\RutaCobro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmpresaRutaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_maestro_solo_ve_la_empresa_en_la_que_entro(): void
    {
        $maestro = User::factory()->create(['es_maestro' => true]);
        $norte = Empresa::create(['nombre' => 'Norte', 'slug' => 'norte']);
        $sur = Empresa::create(['nombre' => 'Sur', 'slug' => 'sur']);
        Cliente::create(['empresa_id' => $norte->id, 'nombre' => 'Ana Norte']);
        Cliente::create(['empresa_id' => $sur->id, 'nombre' => 'Beto Sur']);

        $this->actingAs($maestro)
            ->get('/clientes')
            ->assertRedirect(route('empresas.index'));

        $this->actingAs($maestro)
            ->withSession(['empresa_id' => $norte->id])
            ->get('/clientes')
            ->assertOk()
            ->assertSee('Ana Norte')
            ->assertDontSee('Beto Sur');
    }

    public function test_el_abono_guarda_coordenadas_y_ubica_al_cliente(): void
    {
        $this->actingAs(User::factory()->create());
        $cliente = Cliente::create(['nombre' => 'Ana Pérez']);

        $prestamo = app(PrestamoService::class)->crear(
            $cliente->id,
            100000,
            20,
            2,
            'semanal',
            '2026-11-02'
        );

        $this->post('/prestamos/'.$prestamo->id.'/pagar', [
            'monto' => 10000,
            'fecha' => '2026-11-02',
            'latitud' => 4.711,
            'longitud' => -74.0721,
        ])->assertRedirect();

        $pago = Pago::first();
        $this->assertEquals(4.711, $pago->latitud);
        $this->assertEquals(-74.0721, $pago->longitud);
        $this->assertEquals(4.711, $cliente->fresh()->latitud);
    }

    public function test_la_ruta_acerca_las_paradas(): void
    {
        $cerca = Cliente::create(['nombre' => 'Cerca', 'latitud' => 4.70, 'longitud' => -74.07]);
        $lejos = Cliente::create(['nombre' => 'Lejos', 'latitud' => 6.25, 'longitud' => -75.56]);
        $servicio = app(PrestamoService::class);

        foreach ([$lejos, $cerca] as $cliente) {
            $servicio->crear($cliente->id, 50000, 10, 1, 'diario', now()->subDay()->toDateString());
        }

        $nombres = array_column(
            app(RutaCobro::class)->armar(4.71, -74.07)['paradas'],
            'nombre'
        );

        $this->assertSame(['Cerca', 'Lejos'], $nombres);
    }
}
