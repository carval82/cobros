<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use App\Support\Tenencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistroEmpresaTest extends TestCase
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

    public function test_la_raiz_crea_la_empresa_su_direccion_y_el_administrador(): void
    {
        $this->get('/')->assertOk()->assertSee('Crea tu empresa');

        $respuesta = $this->post('/registrar', [
            'nombre' => 'Norte',
            'usuario_nombre' => 'Ana Admin',
            'usuario_email' => 'ana@norte.test',
            'usuario_password' => 'secreto12',
            'usuario_password_confirmation' => 'secreto12',
        ]);

        $empresa = Empresa::query()->where('email', 'ana@norte.test')->first();
        $this->assertNotNull($empresa);
        $this->assertNotNull($empresa->base_datos);
        $this->assertAuthenticated();
        $this->assertSame('administrador', auth()->user()->rol);
        $this->assertSame($empresa->id, auth()->user()->empresa_id);

        $respuesta->assertRedirect(route('tenant.dashboard', ['tenant' => $empresa->uuid]));
        $respuesta->assertSessionHas('ok');
        $this->assertStringContainsString('/tenant/'.$empresa->uuid, session('ok'));
    }

    public function test_el_celular_recibe_la_direccion_y_la_sesion(): void
    {
        $this->postJson('/registrar', [
            'nombre' => 'Sur',
            'usuario_nombre' => 'Beto',
            'usuario_email' => 'beto@sur.test',
            'usuario_password' => 'secreto12',
            'usuario_password_confirmation' => 'secreto12',
        ])->assertOk()
            ->assertJsonPath('rol', 'administrador')
            ->assertJsonPath('empresa', 'Sur');

        $empresa = Empresa::query()->where('email', 'beto@sur.test')->first();

        $this->getJson('/sesion/datos')
            ->assertOk()
            ->assertJsonPath('tenant', $empresa->uuid)
            ->assertJsonPath('rol', 'administrador')
            ->assertJsonPath('usuarios', true);
    }

    public function test_el_administrador_crea_usuarios_y_el_rol_limita(): void
    {
        $empresa = Empresa::create([
            'nombre' => 'Norte',
            'slug' => 'norte',
            'activo' => true,
            'fecha_vencimiento' => now()->addDays(30),
        ]);
        $admin = app(\App\Services\ProvisionarTenant::class)->aprovisionar($empresa, 'Ana', 'ana@norte.test', 'secreto12');
        $empresa->refresh();

        $this->actingAs($admin)
            ->post('/tenant/'.$empresa->uuid.'/usuarios', [
                'name' => 'Luis Cobra',
                'email' => 'luis@norte.test',
                'password' => 'secreto12',
                'password_confirmation' => 'secreto12',
                'rol' => 'cobrador',
            ])
            ->assertRedirect();

        $cobrador = User::query()->where('email', 'luis@norte.test')->first();
        $this->assertSame('cobrador', $cobrador->rol);
        $this->assertTrue($cobrador->puede('cobros.registrar'));
        $this->assertFalse($cobrador->puede('usuarios.gestionar'));

        $this->actingAs($cobrador)
            ->get('/tenant/'.$empresa->uuid.'/usuarios')
            ->assertForbidden();

        $this->actingAs($admin)
            ->post('/tenant/'.$empresa->uuid.'/usuarios', [
                'name' => 'Vera',
                'email' => 'vera@norte.test',
                'password' => 'secreto12',
                'password_confirmation' => 'secreto12',
                'rol' => 'consulta',
            ])
            ->assertRedirect();

        $consulta = User::query()->where('email', 'vera@norte.test')->first();

        $this->actingAs($consulta)
            ->post('/tenant/'.$empresa->uuid.'/prestamos', [])
            ->assertForbidden();

        $this->actingAs($admin)
            ->get('/tenant/'.$empresa->uuid.'/usuarios')
            ->assertOk()
            ->assertSee('Luis Cobra')
            ->assertSee('Cobrador');
    }
}
