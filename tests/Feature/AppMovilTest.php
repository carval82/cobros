<?php

namespace Tests\Feature;

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
            ->assertSee('La próxima vez');
    }
}
