<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\User;
use App\Services\ActivacionLicencia;
use App\Services\ProvisionarTenant;
use App\Support\Tenencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantBaseTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Tenencia::cerrar();
        DB::disconnect('tenant');
        DB::purge('tenant');
        foreach (glob(storage_path('framework/tenants/*.sqlite')) ?: [] as $archivo) {
            @unlink($archivo);
        }
        parent::tearDown();
    }

    public function test_cada_empresa_tiene_su_propia_base(): void
    {
        $maestro = User::factory()->create(['es_maestro' => true]);
        $norte = Empresa::create(['nombre' => 'Norte', 'slug' => 'norte', 'activo' => true, 'fecha_vencimiento' => now()->addDays(30)]);
        $sur = Empresa::create(['nombre' => 'Sur', 'slug' => 'sur', 'activo' => true, 'fecha_vencimiento' => now()->addDays(30)]);

        $provision = app(ProvisionarTenant::class);
        $provision->aprovisionar($norte, 'Ana', 'ana@norte.test', 'secreto12');
        $provision->aprovisionar($sur, 'Beto', 'beto@sur.test', 'secreto12');

        Tenencia::conectar($norte->fresh());
        Cliente::create(['nombre' => 'Cliente Norte']);
        Tenencia::cerrar();

        Tenencia::conectar($sur->fresh());
        $this->assertSame(0, Cliente::query()->count());
        Tenencia::cerrar();

        $this->actingAs($maestro)
            ->withSession(['empresa_id' => $norte->id])
            ->get('/clientes')
            ->assertOk()
            ->assertSee('Cliente Norte')
            ->assertDontSee('Beto');
    }

    public function test_un_codigo_valido_renueva_la_empresa(): void
    {
        $maestro = User::factory()->create(['es_maestro' => true]);
        $empresa = Empresa::create([
            'nombre' => 'Norte',
            'slug' => 'norte-lic',
            'activo' => true,
            'base_datos' => null,
            'codigo_solicitud' => 'ABCD-EFGH-JKLM-NPQR',
            'fecha_vencimiento' => now()->subDay(),
        ]);

        $codigo = ActivacionLicencia::generar($empresa->codigo_solicitud, 'basico', 365);

        $this->actingAs($maestro)
            ->withSession(['empresa_id' => $empresa->id])
            ->post('/licencia', ['codigo' => $codigo])
            ->assertRedirect(route('dashboard'));

        $empresa->refresh();
        $this->assertSame('basico', $empresa->plan);
        $this->assertNotNull($empresa->licencia_activada_at);
        $this->assertTrue($empresa->puedeOperar());
    }
}
