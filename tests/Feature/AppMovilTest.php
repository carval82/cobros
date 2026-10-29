<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppMovilTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pagina_explica_como_configurar_el_celular(): void
    {
        $this->get('/app')
            ->assertOk()
            ->assertSee('Configura el celular')
            ->assertSee('Ya tengo empresa')
            ->assertSee('/tenant/')
            ->assertSee('La próxima vez')
            ->assertSee('Entrar para verla');
    }

    public function test_muestra_el_tenant_si_la_empresa_ya_existe(): void
    {
        $empresa = Empresa::create([
            'nombre' => 'Norte',
            'slug' => 'norte',
            'base_datos' => 'cobros_e9',
            'plan' => 'demo',
            'activo' => true,
        ]);
        $usuario = User::factory()->create([
            'empresa_id' => $empresa->id,
            'rol' => 'administrador',
        ]);

        $this->actingAs($usuario)
            ->get('/app')
            ->assertOk()
            ->assertSee('Norte')
            ->assertSee('/tenant/'.$empresa->uuid)
            ->assertSee('Número del tenant');
    }
}
