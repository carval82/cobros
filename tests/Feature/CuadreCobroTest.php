<?php

namespace Tests\Feature;

use App\Models\Prestamo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuadreCobroTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_cuadre_resta_prestamos_y_suma_abonos(): void
    {
        $cobrador = User::factory()->create([
            'name' => 'Luis Cobra',
            'rol' => 'cobrador',
        ]);
        $admin = User::factory()->create([
            'name' => 'Ana Admin',
            'rol' => 'administrador',
        ]);

        $this->actingAs($cobrador);
        $this->post('/cuadre/abrir', [
            'valor_inicial' => 40000000,
        ])->assertRedirect();
        $cajaId = \App\Models\CajaCobro::query()->value('id');

        $this->postJson('/clientes', [
            'nombre' => 'Ana',
            'telefono' => '3001112233',
        ])->assertOk();

        $clienteId = \App\Models\Cliente::query()->value('id');
        $this->postJson('/prestamos', [
            'cliente_id' => $clienteId,
            'capital' => 10000000,
            'porcentaje' => 20,
            'cuotas' => 1,
            'frecuencia' => 'diario',
            'fecha_primer_pago' => now()->toDateString(),
        ])->assertOk();

        $prestamoId = Prestamo::query()->value('id');
        $this->postJson('/prestamos/'.$prestamoId.'/pagar', [
            'monto' => 1000000,
            'fecha' => now()->toDateString(),
        ])->assertOk();

        $this->post('/cuadre/gastos', [
            'caja_id' => $cajaId,
            'monto' => 100000,
            'fecha' => now()->toDateString(),
            'nota' => 'Transporte',
        ])->assertRedirect();

        $this->postJson('/prestamos', [
            'cliente_id' => $clienteId,
            'capital' => 5000000,
            'porcentaje' => 0,
            'cuotas' => 1,
            'frecuencia' => 'diario',
            'fecha_primer_pago' => now()->toDateString(),
        ])->assertOk();

        $extra = Prestamo::query()->latest('id')->first();
        $this->actingAs($admin)->post('/prestamos/'.$extra->id.'/anular', [
            'motivo' => 'Se reversó el desembolso',
        ])->assertRedirect();

        $this->actingAs($cobrador)->getJson('/cuadre/datos')
            ->assertOk()
            ->assertJsonPath('cajas.0.en_caja', 30900000)
            ->assertJsonPath('cajas.0.prestado', 10000000)
            ->assertJsonPath('cajas.0.recogido_hoy', 1000000)
            ->assertJsonPath('cajas.0.gastos_hoy', 100000)
            ->assertJsonPath('cajas.0.cruce_hoy', 1000000 - 10000000 - 100000)
            ->assertJsonPath('cajas.0.ganancia', 66667);

        $this->actingAs($admin)
            ->get('/')
            ->assertOk()
            ->assertSee('Entrar')
            ->assertSee('Luis Cobra')
            ->assertDontSee('Retirar la ganancia');

        $this->actingAs($admin)
            ->get('/?cobro='.$cajaId)
            ->assertOk()
            ->assertSee('Retirar la ganancia')
            ->assertSee('30.900.000');

        $this->actingAs($admin)
            ->get('/cuadre')
            ->assertOk()
            ->assertSee('Luis Cobra')
            ->assertSee('30.900.000')
            ->assertDontSee('Retirar la ganancia');

        $this->actingAs($admin)
            ->get('/cuadre?caja='.$cajaId)
            ->assertOk()
            ->assertSee('Cuadre')
            ->assertSee('En caja')
            ->assertSee('Luis Cobra')
            ->assertSee('30.900.000')
            ->assertSee('Retirar la ganancia');

        $otro = User::factory()->create(['rol' => 'cobrador', 'name' => 'Otro']);
        $this->actingAs($otro)->post('/cuadre/cerrar', [
            'caja_id' => $cajaId,
            'fecha' => now()->toDateString(),
            'decision' => 'retirar',
        ])->assertForbidden();

        $consulta = User::factory()->create(['rol' => 'consulta', 'name' => 'Lector']);
        $this->actingAs($consulta)->get('/cuadre')->assertOk()->assertSee('Luis Cobra');
        $this->actingAs($consulta)->post('/cuadre/gastos', [
            'caja_id' => $cajaId,
            'monto' => 1,
            'fecha' => now()->toDateString(),
        ])->assertForbidden();
    }

    public function test_la_primera_cuota_se_recalcula_si_cae_hoy_o_antes(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/cuadre/abrir', ['valor_inicial' => 500000])->assertRedirect();
        $this->post('/clientes', [
            'nombre' => 'Ana',
            'telefono' => '3001112233',
        ])->assertRedirect();

        $clienteId = \App\Models\Cliente::query()->value('id');
        $hoy = now()->toDateString();

        $this->post('/prestamos', [
            'cliente_id' => $clienteId,
            'capital' => 100000,
            'porcentaje' => 20,
            'cuotas' => 4,
            'frecuencia' => 'diario',
            'fecha_primer_pago' => $hoy,
        ])->assertRedirect();

        $diario = Prestamo::query()->latest('id')->first();
        $this->assertSame(now()->addDay()->toDateString(), $diario->fecha_primer_pago->toDateString());

        $this->post('/prestamos', [
            'cliente_id' => $clienteId,
            'capital' => 100000,
            'porcentaje' => 20,
            'cuotas' => 4,
            'frecuencia' => 'semanal',
            'fecha_primer_pago' => $hoy,
        ])->assertRedirect();

        $semanal = Prestamo::query()->latest('id')->first();
        $fechas = $semanal->cuotas()->orderBy('numero')->get()->map(fn ($cuota) => $cuota->fecha_vencimiento->toDateString())->all();
        $this->assertSame(now()->addDays(8)->toDateString(), $fechas[0]);
        $this->assertSame(now()->addDays(15)->toDateString(), $fechas[1]);

        $futura = now()->addDays(20)->toDateString();
        $this->post('/prestamos', [
            'cliente_id' => $clienteId,
            'capital' => 100000,
            'porcentaje' => 20,
            'cuotas' => 2,
            'frecuencia' => 'quincenal',
            'fecha_primer_pago' => $futura,
        ])->assertRedirect();

        $this->assertSame($futura, Prestamo::query()->latest('id')->first()->fecha_primer_pago->toDateString());
    }

    public function test_cerrar_el_dia_retira_la_ganancia_o_la_suma_a_la_base(): void
    {
        $retirar = User::factory()->create(['rol' => 'cobrador', 'name' => 'Retira']);
        $this->actingAs($retirar);
        $this->post('/cuadre/abrir', ['valor_inicial' => 100000])->assertRedirect();
        $cajaRetira = \App\Models\CajaCobro::query()->where('user_id', $retirar->id)->value('id');
        $this->post('/clientes', ['nombre' => 'Ana', 'telefono' => '3001112233'])->assertRedirect();
        $clienteId = \App\Models\Cliente::query()->value('id');
        $this->post('/prestamos', [
            'cliente_id' => $clienteId,
            'capital' => 40000,
            'porcentaje' => 20,
            'cuotas' => 1,
            'frecuencia' => 'diario',
            'fecha_primer_pago' => now()->toDateString(),
        ])->assertRedirect();
        $prestamoId = Prestamo::query()->where('caja_cobro_id', $cajaRetira)->value('id');
        $this->post('/prestamos/'.$prestamoId.'/pagar', [
            'monto' => 10000,
            'fecha' => now()->toDateString(),
        ])->assertRedirect();
        $this->post('/cuadre/gastos', [
            'caja_id' => $cajaRetira,
            'monto' => 500,
            'fecha' => now()->toDateString(),
            'nota' => 'Bus',
        ])->assertRedirect();

        $this->postJson('/cuadre/cerrar', [
            'caja_id' => $cajaRetira,
            'fecha' => now()->toDateString(),
            'decision' => 'retirar',
        ])->assertOk()
            ->assertJsonPath('base', 100000)
            ->assertJsonPath('retiro', 1167)
            ->assertJsonPath('valor_siguiente', 68333);

        $sumar = User::factory()->create(['rol' => 'cobrador', 'name' => 'Suma']);
        $this->actingAs($sumar);
        $this->post('/cuadre/abrir', ['valor_inicial' => 100000])->assertRedirect();
        $cajaSuma = \App\Models\CajaCobro::query()->where('user_id', $sumar->id)->value('id');
        $this->post('/prestamos', [
            'cliente_id' => $clienteId,
            'capital' => 40000,
            'porcentaje' => 20,
            'cuotas' => 1,
            'frecuencia' => 'diario',
            'fecha_primer_pago' => now()->toDateString(),
            'caja_cobro_id' => $cajaSuma,
        ])->assertRedirect();
        $prestamoSuma = Prestamo::query()->where('caja_cobro_id', $cajaSuma)->value('id');
        $this->post('/prestamos/'.$prestamoSuma.'/pagar', [
            'monto' => 10000,
            'fecha' => now()->toDateString(),
        ])->assertRedirect();
        $this->post('/cuadre/gastos', [
            'caja_id' => $cajaSuma,
            'monto' => 500,
            'fecha' => now()->toDateString(),
            'nota' => 'Bus',
        ])->assertRedirect();

        $this->postJson('/cuadre/cerrar', [
            'caja_id' => $cajaSuma,
            'fecha' => now()->toDateString(),
            'decision' => 'sumar',
        ])->assertOk()
            ->assertJsonPath('base', 101167)
            ->assertJsonPath('retiro', 0)
            ->assertJsonPath('valor_siguiente', 69500);

        Carbon::setTestNow(now()->addDay());
        $this->post('/cuadre/dia', ['caja_id' => $cajaSuma])->assertRedirect();
        $this->assertSame(69500, (int) \App\Models\Jornada::query()
            ->where('caja_cobro_id', $cajaSuma)
            ->whereDate('fecha', now()->toDateString())
            ->value('valor_inicial'));
        Carbon::setTestNow();
    }
}
