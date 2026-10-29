<?php

namespace App\Http\Middleware;

use App\Support\EmpresaActual;
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

        return $next($request);
    }
}
