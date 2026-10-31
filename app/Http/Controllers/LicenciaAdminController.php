<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Services\ActivacionLicencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LicenciaAdminController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->es_maestro, 403);

        $busqueda = trim((string) $request->input('q'));
        $consulta = Empresa::query()->orderBy('nombre');

        if ($busqueda !== '') {
            $consulta->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('email', 'like', "%{$busqueda}%")
                    ->orWhere('uuid', 'like', "%{$busqueda}%")
                    ->orWhere('codigo_solicitud', 'like', "%{$busqueda}%");
            });
        }

        $empresas = $consulta->get();

        foreach ($empresas as $empresa) {
            $this->asegurarSolicitud($empresa);
        }

        $todas = Empresa::query()->get(['id', 'plan', 'licencia_activada_at', 'fecha_vencimiento', 'base_datos']);

        return view('licencias.index', [
            'empresas' => $empresas,
            'busqueda' => $busqueda,
            'planes' => ActivacionLicencia::PLANES,
            'duraciones' => ActivacionLicencia::DURACIONES,
            'clave' => session('clave'),
            'stats' => [
                'total' => $todas->count(),
                'demo' => $todas->filter(fn (Empresa $empresa) => ! $empresa->esCompleta())->count(),
                'completas' => $todas->filter(fn (Empresa $empresa) => $empresa->esCompleta())->count(),
                'vencidas' => $todas->filter(fn (Empresa $empresa) => $empresa->fecha_vencimiento && $empresa->fecha_vencimiento->isPast())->count(),
            ],
        ]);
    }

    public function generar(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->es_maestro, 403);

        $datos = $this->validar($request);
        $solicitud = strtoupper(trim($datos['solicitud']));

        if (strlen(preg_replace('/[^A-Z0-9]/i', '', $solicitud)) < 16) {
            return back()
                ->withErrors(['solicitud' => 'El código de solicitud necesita 16 caracteres.'])
                ->withInput();
        }

        return back()->with('clave', [
            'empresa' => null,
            'solicitud' => $solicitud,
            'codigo' => ActivacionLicencia::generar($solicitud, $datos['plan'], (int) $datos['dias']),
            'plan' => $datos['plan'],
            'dias' => (int) $datos['dias'],
        ])->with('ok', 'Clave generada.');
    }

    public function aplicar(Request $request, Empresa $empresa): RedirectResponse
    {
        abort_unless($request->user()?->es_maestro, 403);

        $this->asegurarSolicitud($empresa);
        $datos = $this->validar($request);
        $plan = $datos['plan'];
        $dias = (int) $datos['dias'];
        $codigo = ActivacionLicencia::generar((string) $empresa->codigo_solicitud, $plan, $dias);

        if ($request->input('modo') === 'activar') {
            $empresa->update([
                'activo' => true,
                'plan' => $plan,
                'licencia_activada_at' => now(),
                'fecha_vencimiento' => now()->addDays($dias),
            ]);
            $aviso = $empresa->nombre.' quedó en '.ucfirst($plan).' por '.$dias.' días.';
        } else {
            $aviso = 'Clave lista para '.$empresa->nombre.'.';
        }

        return back()->with('ok', $aviso)->with('clave', [
            'empresa' => $empresa->nombre,
            'solicitud' => $empresa->codigo_solicitud,
            'codigo' => $codigo,
            'plan' => $plan,
            'dias' => $dias,
        ]);
    }

    private function asegurarSolicitud(Empresa $empresa): void
    {
        if ($empresa->codigo_solicitud) {
            return;
        }

        $empresa->codigo_solicitud = ActivacionLicencia::codigoSolicitud('cobros-'.$empresa->id.'-'.$empresa->slug);
        $empresa->save();
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'solicitud' => ['nullable', 'string', 'max:40'],
            'plan' => ['required', Rule::in(ActivacionLicencia::PLANES)],
            'dias' => ['required', 'integer', Rule::in(ActivacionLicencia::DURACIONES)],
        ], [
            'plan.required' => 'Elige el plan.',
            'dias.required' => 'Elige la duración.',
        ]);
    }
}
