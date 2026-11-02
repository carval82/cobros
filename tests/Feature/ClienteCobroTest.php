<?php

namespace Tests\Feature;

use App\Models\CajaCobro;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteCobroTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_cliente_no_trae_el_saldo_de_otro_cobro(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador', 'name' => 'Ana Admin']);
        $luis = User::factory()->create(['rol' => 'cobrador', 'name' => 'Luis Cobra']);

        $this->actingAs($admin)->post('/cuadre/abrir', [
            'valor_inicial' => 500000,
            'nombre' => 'Norte',
        ])->assertRedirect();
        $norte = CajaCobro::query()->where('nombre', 'Norte')->value('id');

        $this->actingAs($admin)->post('/clientes', [
            'nombre' => 'Ana Perez',
            'telefono' => '3001112233',
        ])->assertRedirect();
        $clienteId = Cliente::query()->value('id');

        $this->actingAs($admin)->post('/prestamos', [
            'cliente_id' => $clienteId,
            'capital' => 100000,
            'porcentaje' => 20,
            'cuotas' => 1,
            'frecuencia' => 'diario',
            'fecha_primer_pago' => now()->toDateString(),
            'caja_cobro_id' => $norte,
        ])->assertRedirect();

        $this->actingAs($admin)->post('/cuadre/abrir', [
            'valor_inicial' => 200000,
            'nombre' => 'Sur',
        ])->assertRedirect();
        $sur = CajaCobro::query()->where('nombre', 'Sur')->value('id');

        $this->actingAs($admin)->get('/cobro/'.$sur.'/entrar')->assertRedirect();
        $this->actingAs($admin)
            ->get('/clientes')
            ->assertOk()
            ->assertSee('Ana Perez')
            ->assertSee('Restringido')
            ->assertDontSee('120.000');

        $this->actingAs($admin)
            ->get('/clientes/'.$clienteId)
            ->assertOk()
            ->assertSee('todavía no tiene préstamos')
            ->assertDontSee('120.000');

        $this->actingAs($luis)->post('/cuadre/abrir', [
            'valor_inicial' => 100000,
            'nombre' => 'Ruta Luis',
        ])->assertRedirect();
        $this->actingAs($luis)->post('/prestamos', [
            'cliente_id' => $clienteId,
            'capital' => 10000,
            'porcentaje' => 20,
            'cuotas' => 1,
            'frecuencia' => 'diario',
            'fecha_primer_pago' => now()->toDateString(),
        ])->assertSessionHasErrors('cliente_id');

        $this->actingAs($admin)->post('/prestamos', [
            'cliente_id' => $clienteId,
            'capital' => 10000,
            'porcentaje' => 20,
            'cuotas' => 1,
            'frecuencia' => 'diario',
            'fecha_primer_pago' => now()->toDateString(),
            'caja_cobro_id' => $sur,
        ])->assertRedirect();

        $this->actingAs($admin)
            ->get('/clientes/'.$clienteId)
            ->assertOk()
            ->assertSee('10.000')
            ->assertDontSee('120.000');
    }
}
