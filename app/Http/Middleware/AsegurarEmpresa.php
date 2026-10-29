<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use App\Support\EmpresaActual;
use App\Support\Tenencia;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AsegurarEmpresa
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario?->es_maestro && ! EmpresaActual::id()) {
            return redirect()->route('empresas.index');
        }

        $empresa = EmpresaActual::empresa();

        if ($empresa instanceof Empresa) {
            Tenencia::conectar($empresa);
        }

        return $next($request);
    }
}
