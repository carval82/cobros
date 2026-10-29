<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\User;
use App\Services\ActivacionLicencia;
use App\Services\PrestamoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoLicenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_demo_corta_en_cinco_prestamos_y_la_clave_la_abre(): void
    {
        $maestro = User::factory()->create(['es_maestro' => true]);
        $empresa = Empresa::create([
            'nombre' => 'Norte',
            'slug' => 'norte',
            'activo' => true,
            'plan' => 'demo',
            'codigo_solicitud' => 'ABCD-EFGH-JKLM-NPQR',
            'fecha_vencimiento' => now()->addDays(30),
        ]);
        $cliente = Cliente::create([
            'nombre' => 'Ana',
            'empresa_id' => $empresa->id,
        ]);
        $servicio = app(PrestamoService::class);

        for ($i = 0; $i < Empresa::LIMITE_DEMO; $i++) {
            $servicio->crear($cliente->id, 10000, 20, 4, 'semanal', '2026-11-02');
        }

        try {
            $servicio->crear($cliente->id, 10000, 20, 4, 'semanal', '2026-11-02');
            $this->fail('La demo debía negar el préstamo número seis.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('cliente_id', $e->errors());
        }

        $this->assertSame(Empresa::LIMITE_DEMO, $cliente->prestamos()->count());

        $codigo = ActivacionLicencia::generar($empresa->codigo_solicitud, 'premium', 365);

        $this->actingAs($maestro)
            ->withSession(['empresa_id' => $empresa->id])
            ->get('/acerca')
            ->assertOk()
            ->assertSee('Versión demo')
            ->assertSee('ABCD-EFGH-JKLM-NPQR');

        $this->actingAs($maestro)
            ->withSession(['empresa_id' => $empresa->id])
            ->get('/acerca/datos')
            ->assertOk()
            ->assertJsonPath('limite', Empresa::LIMITE_DEMO)
            ->assertJsonPath('completa', false);

        $this->actingAs($maestro)
            ->withSession(['empresa_id' => $empresa->id])
            ->post('/acerca', ['codigo' => $codigo])
            ->assertRedirect(route('acerca'));

        $empresa->refresh();
        $this->assertTrue($empresa->esCompleta());

        $servicio->crear($cliente->id, 10000, 20, 4, 'semanal', '2026-11-02');
        $this->assertSame(Empresa::LIMITE_DEMO + 1, $cliente->prestamos()->count());
    }
}
