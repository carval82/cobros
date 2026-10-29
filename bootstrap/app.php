<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(function () {
            $usuario = auth()->user();

            if ($usuario?->es_maestro && ! session('empresa_id')) {
                return route('empresas.index');
            }

            return route('dashboard');
        });
        $middleware->alias([
            'empresa' => \App\Http\Middleware\AsegurarEmpresa::class,
            'vigente' => \App\Http\Middleware\LicenciaVigente::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
