<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_visitante_va_al_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/clientes')->assertRedirect(route('login'));
    }

    public function test_la_primera_visita_crea_la_cuenta_y_entra(): void
    {
        $this->get('/login')->assertOk()->assertSee('Crea la primera cuenta');

        $this->post('/login', [
            'name' => 'Laura Cobros',
            'email' => 'laura@cobros.test',
            'password' => 'secreto12',
            'password_confirmation' => 'secreto12',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'laura@cobros.test']);
        $this->get('/')->assertOk();
    }

    public function test_rechaza_una_contrasena_incorrecta(): void
    {
        User::factory()->create([
            'email' => 'laura@cobros.test',
            'password' => 'secreto12',
        ]);

        $this->post('/login', [
            'email' => 'laura@cobros.test',
            'password' => 'otra-clave',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_el_usuario_maestro_puede_entrar(): void
    {
        $this->artisan('cobros:usuario-maestro')->assertSuccessful();

        $this->get('/login')->assertOk()->assertSee('Entrar')->assertDontSee('Crea la primera cuenta');

        $this->post('/login', [
            'email' => 'pcapacho24@gmail.com',
            'password' => 'anaval33',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        $this->artisan('cobros:usuario-maestro')->assertSuccessful();
        $this->assertSame(1, User::query()->count());
    }

    public function test_entra_y_puede_salir(): void
    {
        User::factory()->create([
            'name' => 'Laura Cobros',
            'email' => 'laura@cobros.test',
            'password' => 'secreto12',
        ]);

        $this->post('/login', [
            'email' => 'laura@cobros.test',
            'password' => 'secreto12',
        ])->assertRedirect(route('dashboard'));

        $this->get('/')->assertOk()->assertSee('Laura Cobros');

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get('/')->assertRedirect(route('login'));
    }
}
