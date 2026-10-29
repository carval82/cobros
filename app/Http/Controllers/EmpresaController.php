<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Services\ActivacionLicencia;
use App\Services\ProvisionarTenant;
use App\Support\EmpresaActual;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmpresaController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (! auth()->user()->es_maestro) {
            $propia = Empresa::query()->find(auth()->user()->empresa_id);

            if ($propia?->base_datos) {
                return redirect()->route('tenant.dashboard', ['tenant' => $propia->uuid]);
            }

            return redirect()->route('dashboard');
        }

        return view('empresas.index', [
            'empresas' => Empresa::query()->orderBy('nombre')->get(),
            'activa' => session('empresa_id'),
        ]);
    }

    public function store(Request $request, ProvisionarTenant $provisionar): RedirectResponse
    {
        abort_unless($request->user()->es_maestro, 403);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'usuario_nombre' => ['required', 'string', 'max:120'],
            'usuario_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'usuario_password' => ['required', 'string', 'min:8'],
        ], [
            'nombre.required' => 'Escribe el nombre de la empresa.',
            'usuario_nombre.required' => 'Escribe el nombre de quien va a entrar.',
            'usuario_email.required' => 'Escribe el correo de esa empresa.',
            'usuario_email.unique' => 'Ese correo ya tiene una cuenta.',
            'usuario_password.required' => 'Escribe una contraseña para esa empresa.',
            'usuario_password.min' => 'La contraseña necesita al menos 8 caracteres.',
        ]);

        $empresa = Empresa::create([
            'nombre' => $datos['nombre'],
            'slug' => $this->slug($datos['nombre']),
            'email' => $datos['usuario_email'],
            'activo' => true,
            'plan' => 'demo',
            'fecha_vencimiento' => now()->addDays(30),
        ]);

        try {
            $provisionar->aprovisionar(
                $empresa,
                $datos['usuario_nombre'],
                $datos['usuario_email'],
                $datos['usuario_password']
            );
        } catch (\Throwable $e) {
            $empresa->delete();

            return back()
                ->withErrors(['nombre' => 'No se pudo crear la base de la empresa: '.$e->getMessage()])
                ->withInput();
        }

        session(['empresa_id' => $empresa->id]);
        $direccion = $empresa->direccion();

        return redirect()
            ->route('tenant.dashboard', ['tenant' => $empresa->uuid])
            ->with('ok', 'Empresa creada. Su gente entra por '.$direccion);
    }

    public function licencia(): View|RedirectResponse
    {
        $empresa = EmpresaActual::empresa();

        if (! $empresa) {
            return redirect()->route('empresas.index');
        }

        return view('empresas.licencia', ['empresa' => $empresa]);
    }

    public function activar(Request $request): RedirectResponse
    {
        $empresa = EmpresaActual::empresa();
        abort_unless($empresa, 404);

        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:40'],
        ], [
            'codigo.required' => 'Pega el código de activación.',
        ]);

        $resuelto = ActivacionLicencia::resolver($datos['codigo'], (string) $empresa->codigo_solicitud);

        if (! $resuelto) {
            return back()->withErrors(['codigo' => 'Ese código no corresponde a esta empresa.']);
        }

        $empresa->update([
            'activo' => true,
            'plan' => $resuelto['plan'],
            'licencia_activada_at' => now(),
            'fecha_vencimiento' => now()->addDays($resuelto['days']),
        ]);

        return redirect()
            ->to(cobro_route('dashboard'))
            ->with('ok', 'Licencia '.$resuelto['plan'].' activa por '.$resuelto['days'].' días.');
    }

    public function entrar(Empresa $empresa): RedirectResponse
    {
        abort_unless(auth()->user()->es_maestro, 403);

        session(['empresa_id' => $empresa->id]);

        if ($empresa->base_datos) {
            return redirect()
                ->route('tenant.dashboard', ['tenant' => $empresa->uuid])
                ->with('ok', 'Trabajando en '.$empresa->nombre.'.');
        }

        return redirect()->route('dashboard')->with('ok', 'Trabajando en '.$empresa->nombre.'.');
    }

    public function salir(): RedirectResponse
    {
        abort_unless(auth()->user()->es_maestro, 403);

        session()->forget('empresa_id');

        return redirect()->route('empresas.index');
    }

    private function slug(string $nombre): string
    {
        $base = Str::slug($nombre) ?: 'empresa';
        $slug = $base;
        $i = 2;

        while (Empresa::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
