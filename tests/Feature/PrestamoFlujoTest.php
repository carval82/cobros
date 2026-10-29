<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Prestamo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrestamoFlujoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_crea_el_plan_cobra_y_marca_atraso(): void
    {
        $cliente = $this->cliente();

        $this->post('/prestamos', [
            'cliente_id' => $cliente->id,
            'capital' => 100000,
            'porcentaje' => 20,
            'cuotas' => 5,
            'frecuencia' => 'semanal',
            'fecha_primer_pago' => '2026-11-02',
        ])->assertRedirect();

        $prestamo = Prestamo::first();
        $this->assertSame(120000, $prestamo->total);
        $this->assertSame(24000, $prestamo->cuotas()->orderBy('numero')->first()->valor);

        $this->post('/prestamos/'.$prestamo->id.'/pagar', [
            'monto' => 24000,
            'fecha' => '2026-11-02',
        ])->assertRedirect();

        $this->assertSame(0, $prestamo->cuotas()->orderBy('numero')->first()->saldo);
        $this->assertSame(96000, $prestamo->saldo());

        Carbon::setTestNow('2026-11-10');
        $this->get('/atrasados')
            ->assertOk()
            ->assertSee('1 día de atraso');

        $this->assertDatabaseHas('movimientos', [
            'cliente_id' => $cliente->id,
            'tipo' => 'pago',
        ]);
    }

    public function test_detener_congela_la_mora_y_reanudar_corre_las_fechas(): void
    {
        $prestamo = $this->prestamo();
        Carbon::setTestNow('2026-11-10');

        $this->post('/prestamos/'.$prestamo->id.'/detener', [
            'motivo' => 'Viajó',
        ])->assertRedirect();

        $this->get('/atrasados')->assertOk()->assertDontSee($prestamo->cliente->nombre);

        Carbon::setTestNow('2026-11-12');
        $this->post('/prestamos/'.$prestamo->id.'/reanudar')->assertRedirect();

        $fechas = $prestamo->cuotas()->orderBy('numero')->get()->map(fn ($cuota) => $cuota->fecha_vencimiento->toDateString())->all();
        $this->assertSame('2026-11-04', $fechas[0]);
        $this->assertSame('activo', $prestamo->fresh()->estado);
    }

    public function test_aplazar_corre_la_cuota_y_las_siguientes(): void
    {
        $prestamo = $this->prestamo();
        $segunda = $prestamo->cuotas()->where('numero', 2)->first();

        $this->post('/cuotas/'.$segunda->id.'/aplazar', [
            'fecha' => '2026-11-12',
            'nota' => 'Pidió tres días',
        ])->assertRedirect();

        $fechas = $prestamo->cuotas()->orderBy('numero')->get()->map(fn ($cuota) => $cuota->fecha_vencimiento->toDateString())->all();
        $this->assertSame(['2026-11-02', '2026-11-12', '2026-11-19', '2026-11-26', '2026-12-03'], $fechas);
        $this->assertDatabaseHas('movimientos', ['tipo' => 'aplazar']);
    }

    public function test_un_abono_mayor_cubre_varias_cuotas_y_liquida(): void
    {
        $prestamo = $this->prestamo();

        $this->post('/prestamos/'.$prestamo->id.'/pagar', [
            'monto' => 50000,
            'fecha' => '2026-11-02',
        ])->assertRedirect();

        $saldos = $prestamo->cuotas()->orderBy('numero')->pluck('saldo')->map(fn ($saldo) => (int) $saldo)->all();
        $this->assertSame([0, 0, 22000, 24000, 24000], $saldos);

        $this->post('/prestamos/'.$prestamo->id.'/pagar', [
            'monto' => 70000,
            'fecha' => '2026-11-02',
        ])->assertRedirect();

        $this->assertSame('liquidado', $prestamo->fresh()->estado);
    }

    public function test_no_abre_prestamo_sin_cliente_y_un_cliente_puede_tener_varios(): void
    {
        $this->get('/prestamos/crear')->assertRedirect(route('clientes.create'));

        $this->post('/prestamos', [
            'capital' => 100000,
            'porcentaje' => 20,
            'cuotas' => 4,
            'frecuencia' => 'semanal',
            'fecha_primer_pago' => '2026-11-02',
        ])->assertSessionHasErrors('cliente_id');

        $cliente = $this->cliente();

        foreach ([50000, 80000] as $capital) {
            $this->post('/prestamos', [
                'cliente_id' => $cliente->id,
                'capital' => $capital,
                'porcentaje' => 20,
                'cuotas' => 4,
                'frecuencia' => 'semanal',
                'fecha_primer_pago' => '2026-11-02',
            ])->assertRedirect();
        }

        $this->assertSame(2, Prestamo::query()->where('cliente_id', $cliente->id)->count());
    }

    public function test_el_abono_queda_en_un_solo_cliente(): void
    {
        $prestamo = $this->prestamo();

        $this->post('/prestamos/'.$prestamo->id.'/pagar', [
            'monto' => 10000,
            'fecha' => '2026-11-02',
        ])->assertRedirect();

        $pago = $prestamo->pagos()->first();
        $this->assertNotNull($pago);
        $this->assertSame((int) $prestamo->cliente_id, (int) $pago->cliente_id);
    }

    public function test_refinanciar_amplia_el_mismo_prestamo(): void
    {
        $prestamo = $this->prestamo();

        $this->post('/prestamos/'.$prestamo->id.'/pagar', [
            'monto' => 10000,
            'fecha' => '2026-11-02',
        ])->assertRedirect();

        $this->post('/prestamos/'.$prestamo->id.'/refinanciar', [
            'adicional' => 50000,
            'porcentaje' => 20,
            'cuotas' => 4,
            'frecuencia' => 'semanal',
            'fecha_primer_pago' => '2026-11-09',
        ])->assertRedirect();

        $prestamo->refresh();
        $primera = $prestamo->cuotas()->orderBy('numero')->first();

        $this->assertSame(150000, (int) $prestamo->capital);
        $this->assertSame(192000, $prestamo->saldo());
        $this->assertSame(0, (int) $primera->saldo);
        $this->assertSame(10000, (int) $primera->pagado);
        $this->assertSame((int) $prestamo->cliente_id, (int) $prestamo->pagos()->first()->cliente_id);
        $this->assertDatabaseHas('movimientos', [
            'prestamo_id' => $prestamo->id,
            'tipo' => 'refinanciar',
        ]);
    }

    public function test_no_refinancia_un_prestamo_sin_saldo(): void
    {
        $prestamo = $this->prestamo();

        $this->post('/prestamos/'.$prestamo->id.'/pagar', [
            'monto' => 120000,
            'fecha' => '2026-11-02',
        ])->assertRedirect();

        $this->post('/prestamos/'.$prestamo->id.'/refinanciar', [
            'adicional' => 10000,
            'porcentaje' => 20,
            'cuotas' => 4,
            'frecuencia' => 'semanal',
            'fecha_primer_pago' => '2026-11-09',
        ])->assertSessionHasErrors('adicional');

        $this->assertSame(100000, (int) $prestamo->fresh()->capital);
    }

    private function cliente(): Cliente
    {
        $this->post('/clientes', [
            'nombre' => 'Ana Pérez',
            'telefono' => '3001112233',
            'documento' => '123',
        ])->assertRedirect();

        return Cliente::first();
    }

    private function prestamo(): Prestamo
    {
        $cliente = $this->cliente();

        $this->post('/prestamos', [
            'cliente_id' => $cliente->id,
            'capital' => 100000,
            'porcentaje' => 20,
            'cuotas' => 5,
            'frecuencia' => 'semanal',
            'fecha_primer_pago' => '2026-11-02',
        ])->assertRedirect();

        return Prestamo::with('cliente')->first();
    }
}
