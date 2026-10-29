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

        if (app()->bound('empresa.tenant')) {
            $empresa = app('empresa.tenant');

            if ($usuario && ! $usuario->es_maestro && (int) $usuario->empresa_id !== (int) $empresa->id) {
                abort(403, 'Esta dirección es de otra empresa.');
            }

            Tenencia::conectar($empresa);

            return $next($request);
        }

        if ($usuario && ! $usuario->es_maestro && $usuario->empresa_id) {
            $propia = Empresa::query()->find($usuario->empresa_id);

            if ($propia?->base_datos) {
                return redirect()->route('tenant.dashboard', ['tenant' => $propia->uuid]);
            }
        }

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
