<?php

namespace App\Http\Middleware;

use App\Support\EmpresaActual;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LicenciaVigente
{
    public function handle(Request $request, Closure $next): Response
    {
        $empresa = EmpresaActual::empresa();

        if ($empresa && $empresa->base_datos && ! $empresa->puedeOperar()) {
            return redirect()->to(cobro_route('acerca'));
        }

        return $next($request);
    }
}
