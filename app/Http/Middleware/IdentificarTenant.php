<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use App\Support\Tenencia;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentificarTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->bound('empresa.tenant')) {
            app()->forgetInstance('empresa.tenant');
        }

        Tenencia::cerrar();

        if (! preg_match('#(?:^|/)tenant/([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})(?:/|$)#', $request->path(), $coincide)) {
            return $next($request);
        }

        try {
            $empresa = Empresa::query()->where('uuid', $coincide[1])->first();
        } catch (\Throwable) {
            return $next($request);
        }

        if (! $empresa) {
            abort(404, 'Empresa no encontrada.');
        }

        Tenencia::conectar($empresa);
        app()->instance('empresa.tenant', $empresa);
        $request->attributes->set('tenantId', $empresa->uuid);
        $request->attributes->set('tenantPorRuta', true);

        return $next($request);
    }
}
