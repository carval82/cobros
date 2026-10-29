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
        $middleware->prepend(\App\Http\Middleware\IdentificarTenant::class);
        $middleware->redirectGuestsTo(fn () => cobro_route('login'));
        $middleware->redirectUsersTo(function () {
            $usuario = auth()->user();
            $empresa = app()->bound('empresa.tenant') ? app('empresa.tenant') : null;

            if ($empresa) {
                if ($usuario?->es_maestro || (int) $usuario?->empresa_id === (int) $empresa->id) {
                    return cobro_route('dashboard');
                }

                if ($usuario?->empresa_id) {
                    $propia = \App\Models\Empresa::query()->find($usuario->empresa_id);

                    if ($propia?->base_datos) {
                        return route('tenant.dashboard', ['tenant' => $propia->uuid]);
                    }
                }
            }

            if ($usuario?->es_maestro && ! session('empresa_id')) {
                return route('empresas.index');
            }

            if ($usuario && ! $usuario->es_maestro && $usuario->empresa_id) {
                $propia = \App\Models\Empresa::query()->find($usuario->empresa_id);

                if ($propia?->base_datos) {
                    return route('tenant.dashboard', ['tenant' => $propia->uuid]);
                }
            }

            return route('dashboard');
        });
        $middleware->alias([
            'empresa' => \App\Http\Middleware\AsegurarEmpresa::class,
            'vigente' => \App\Http\Middleware\LicenciaVigente::class,
            'puede' => \App\Http\Middleware\ExigirPermiso::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
