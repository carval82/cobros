<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class GuiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_guia_muestra_los_casos_en_palabras_simples(): void
    {
        $this->assertTrue(Route::has('guia'));
        $this->assertTrue(Route::has('tenant.guia'));

        $this->get('/guia')
            ->assertOk()
            ->assertSee('¿Quieres ver cómo funciona?')
            ->assertSee('Entrar a la visita')
            ->assertSee('cualquier correo')
            ->assertSee('Registrar abono')
            ->assertSee('Cuotas de hoy')
            ->assertSee('Restringido')
            ->assertSee('Retirar la ganancia')
            ->assertSee('Crear mi cobro en demo')
            ->assertDontSee('caja_cobro');

        $this->get('/login')
            ->assertOk()
            ->assertSee('¿Quieres ver cómo funciona?');

        $this->actingAs(User::factory()->create())
            ->get('/guia')
            ->assertOk()
            ->assertSee('Visita guiada')
            ->assertSee('Envía solo esta dirección');
    }
}
