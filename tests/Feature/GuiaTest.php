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

        $this->actingAs(User::factory()->create())
            ->get('/guia')
            ->assertOk()
            ->assertSee('Guía de Cobros')
            ->assertSee('Cada cobro es su propia caja')
            ->assertSee('Primero se entra al cobro')
            ->assertSee('Restringido')
            ->assertSee('El administrador aprueba con el préstamo')
            ->assertSee('La misma ruta, en el celular')
            ->assertDontSee('caja_cobro');
    }
}
