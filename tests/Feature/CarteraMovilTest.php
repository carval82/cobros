<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Prestamo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarteraMovilTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_celular_crea_cliente_cobra_y_arma_la_ruta(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson('/clientes', [
            'nombre' => 'Ana',
            'direccion' => 'Calle 10',
            'telefono' => '3001112233',
        ])->assertOk()->assertJsonPath('nombre', 'Ana');

        $cliente = Cliente::query()->first();

        $this->postJson('/clientes/'.$cliente->id.'/ubicacion', [
            'latitud' => 4.70,
            'longitud' => -74.07,
        ])->assertOk()->assertJsonPath('latitud', 4.7);

        $this->postJson('/prestamos', [
            'cliente_id' => $cliente->id,
            'capital' => 100000,
            'porcentaje' => 20,
            'cuotas' => 1,
            'frecuencia' => 'diario',
            'fecha_primer_pago' => now()->toDateString(),
        ])->assertOk();

        $prestamo = Prestamo::query()->first();

        $this->getJson('/hoy/datos')
            ->assertOk()
            ->assertJsonPath('hoy.0.cliente', 'Ana')
            ->assertJsonPath('hoy.0.direccion', 'Calle 10');

        $this->getJson('/prestamos/'.$prestamo->id.'/datos')
            ->assertOk()
            ->assertJsonPath('prestamo.cliente', 'Ana')
            ->assertJsonPath('cobros', true);

        $abono = $this->postJson('/prestamos/'.$prestamo->id.'/pagar', [
            'monto' => 10000,
            'fecha' => now()->toDateString(),
            'latitud' => 4.711,
            'longitud' => -74.072,
        ])->assertOk();

        $this->assertStringContainsString('wa.me/573001112233', (string) $abono->json('whatsapp'));
        $this->assertStringContainsString('110.000', (string) $abono->json('whatsapp'));

        $this->assertEquals(4.711, Pago::query()->first()->latitud);

        $this->getJson('/ruta/datos?latitud=4.71&longitud=-74.07')
            ->assertOk()
            ->assertJsonPath('paradas.0.nombre', 'Ana')
            ->assertJsonPath('paradas.0.direccion', 'Calle 10');

        $this->getJson('/clientes/datos')
            ->assertOk()
            ->assertJsonPath('clientes.0.nombre', 'Ana');
    }

    public function test_consulta_no_crea_clientes_ni_cobra(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'consulta']));

        $this->postJson('/clientes', ['nombre' => 'Ana'])->assertForbidden();
    }
}
