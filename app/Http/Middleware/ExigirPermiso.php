<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExigirPermiso
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        abort_unless($request->user()?->puede($permiso), 403, 'Tu rol no puede hacer esto.');

        return $next($request);
    }
}
