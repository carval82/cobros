<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\User;
use App\Services\ActivacionLicencia;
use App\Services\ProvisionarTenant;
use App\Support\Tenencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

    public function test_cada_empresa_tiene_su_direccion(): void
    {
        $norte = Empresa::create(['nombre' => 'Norte', 'slug' => 'norte', 'activo' => true, 'fecha_vencimiento' => now()->addDays(30)]);
        $sur = Empresa::create(['nombre' => 'Sur', 'slug' => 'sur', 'activo' => true, 'fecha_vencimiento' => now()->addDays(30)]);

        $provision = app(ProvisionarTenant::class);
        $provision->aprovisionar($norte, 'Ana', 'ana@norte.test', 'secreto12');
        $provision->aprovisionar($sur, 'Beto', 'beto@sur.test', 'secreto12');

        Tenencia::conectar($norte->fresh());
        Cliente::create(['nombre' => 'Cliente Norte']);
        Tenencia::cerrar();

        $norte->refresh();
        $sur->refresh();

        $this->assertMatchesRegularExpression(
            '#/tenant/'.$norte->uuid.'/login$#',
            $norte->direccion()
        );

        $this->get('/tenant/norte/login')->assertNotFound();

        $this->get('/tenant/'.$norte->uuid.'/login')
            ->assertOk()
            ->assertSee('Norte')
            ->assertSee('Esta es la entrada de Norte');

        $this->post('/tenant/'.$sur->uuid.'/login', [
            'email' => 'ana@norte.test',
            'password' => 'secreto12',
        ])->assertSessionHasErrors('email');

        $this->post('/tenant/'.$norte->uuid.'/login', [
            'email' => 'ana@norte.test',
            'password' => 'secreto12',
        ])->assertRedirect(route('tenant.dashboard', ['tenant' => $norte->uuid]));

        $ana = User::query()->where('email', 'ana@norte.test')->first();

        $this->actingAs($ana)
            ->get('/tenant/'.$norte->uuid.'/clientes')
            ->assertOk()
            ->assertSee('Cliente Norte');

        $this->actingAs($ana)
            ->get('/tenant/'.$sur->uuid.'/clientes')
            ->assertForbidden();

        $this->actingAs($ana)
            ->get('/clientes')
            ->assertRedirect(route('tenant.dashboard', ['tenant' => $norte->uuid]));
    }

    public function test_la_cache_no_consulta_la_base_de_la_empresa(): void
    {
        $empresa = Empresa::create(['nombre' => 'Norte', 'slug' => 'norte', 'activo' => true, 'fecha_vencimiento' => now()->addDays(30)]);
        app(ProvisionarTenant::class)->aprovisionar($empresa, 'Ana', 'ana@norte.test', 'secreto12');

        $this->assertSame(config('tenencia.central'), config('cache.stores.database.connection'));

        Tenencia::conectar($empresa->fresh());
        Cache::store('database')->put('ping', 'ok', 60);

        $this->assertSame('ok', Cache::store('database')->get('ping'));
    }

    public function test_un_cliente_se_abre_en_la_direccion_de_su_empresa(): void
    {
        $norte = Empresa::create(['nombre' => 'Norte', 'slug' => 'norte', 'activo' => true, 'fecha_vencimiento' => now()->addDays(30)]);
        app(ProvisionarTenant::class)->aprovisionar($norte, 'Ana', 'ana@norte.test', 'secreto12');

        Tenencia::conectar($norte->fresh());
        $cliente = Cliente::create(['nombre' => 'Cliente Norte']);
        Tenencia::cerrar();

        $ana = User::query()->where('email', 'ana@norte.test')->first();

        $this->actingAs($ana)
            ->get('/tenant/'.$norte->uuid.'/clientes/'.$cliente->id)
            ->assertOk()
            ->assertSee('Cliente Norte');
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
