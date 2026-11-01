<?php

namespace App\Http\Middleware;

use App\Support\CobroActivo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExigirCobroActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->expectsJson() || CobroActivo::caja()) {
            return $next($request);
        }

        return redirect()->to(cobro_route('dashboard'));
    }
}
