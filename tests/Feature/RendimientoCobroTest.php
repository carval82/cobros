<?php

namespace Tests\Feature;

use App\Models\Pago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RendimientoCobroTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_cobrador_se_queda_con_el_porcentaje_de_lo_que_cobra(): void
    {
        $cobrador = User::factory()->create([
            'name' => 'Luis Cobra',
            'rol' => 'cobrador',
            'porcentaje_cobro' => 10,
        ]);

        $this->actingAs($cobrador);
        $this->postJson('/clientes', [
            'nombre' => 'Ana',
            'telefono' => '3001112233',
        ])->assertOk();

        $clienteId = \App\Models\Cliente::query()->value('id');
        $this->postJson('/prestamos', [
            'cliente_id' => $clienteId,
            'capital' => 100000,
            'porcentaje' => 20,
            'cuotas' => 1,
            'frecuencia' => 'diario',
            'fecha_primer_pago' => now()->toDateString(),
        ])->assertOk();

        $prestamoId = \App\Models\Prestamo::query()->value('id');
        $this->postJson('/prestamos/'.$prestamoId.'/pagar', [
            'monto' => 10000,
            'fecha' => now()->toDateString(),
        ])->assertOk();

        $pago = Pago::query()->first();
        $this->assertSame($cobrador->id, $pago->user_id);
        $this->assertEquals(10, (float) $pago->porcentaje);
        $this->assertSame(1000, (int) $pago->comision);

        $this->get('/rendimiento')->assertForbidden();
        $this->get('/mi-rendimiento')
            ->assertOk()
            ->assertSee('Luis Cobra')
            ->assertSee('1.000');

        $admin = User::factory()->create([
            'name' => 'Ana Admin',
            'rol' => 'administrador',
        ]);

        $this->actingAs($admin)
            ->get('/rendimiento')
            ->assertOk()
            ->assertSee('Luis Cobra')
            ->assertSee('10%')
            ->assertSee('1.000');

        $this->actingAs($admin)
            ->getJson('/rendimiento/datos')
            ->assertOk()
            ->assertJsonPath('cobradores.0.comision', 1000)
            ->assertJsonPath('cobrado', 10000);

        $this->actingAs($admin)->post('/usuarios/'.$cobrador->id, [
            'rol' => 'cobrador',
            'porcentaje_cobro' => 15,
        ])->assertRedirect();

        $pago->refresh();
        $this->assertSame(1000, (int) $pago->comision);

        $this->actingAs($cobrador->fresh())->postJson('/prestamos/'.$prestamoId.'/pagar', [
            'monto' => 10000,
            'fecha' => now()->toDateString(),
        ])->assertOk();

        $this->assertSame(1500, (int) Pago::query()->latest('id')->value('comision'));

        $this->actingAs($admin)->post('/usuarios', [
            'name' => 'Nueva',
            'email' => 'nueva-cobra@test.com',
            'password' => 'secreto12',
            'password_confirmation' => 'secreto12',
            'rol' => 'cobrador',
        ])->assertRedirect();

        $this->assertEquals(10, (float) User::query()->where('email', 'nueva-cobra@test.com')->value('porcentaje_cobro'));
    }
}
