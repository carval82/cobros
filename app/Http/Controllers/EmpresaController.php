<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Prestamo;
use App\Services\ActivacionLicencia;
use App\Services\ProvisionarTenant;
use App\Support\EmpresaActual;
use App\Support\Tenencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmpresaController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! auth()->user()->es_maestro) {
            $propia = Empresa::query()->find(auth()->user()->empresa_id);

            if ($propia?->base_datos) {
                return redirect()->route('tenant.dashboard', ['tenant' => $propia->uuid]);
            }

            return redirect()->route('dashboard');
        }

        $busqueda = trim((string) $request->input('q'));
        $estado = (string) $request->input('estado');
        $plan = (string) $request->input('plan');

        $consulta = Empresa::query()->orderByDesc('created_at');

        if ($busqueda !== '') {
            $consulta->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('email', 'like', "%{$busqueda}%")
                    ->orWhere('slug', 'like', "%{$busqueda}%");
            });
        }

        if ($estado === 'activas') {
            $consulta->where('activo', true)->where(function ($q) {
                $q->whereNull('fecha_vencimiento')->orWhere('fecha_vencimiento', '>=', now());
            });
        } elseif ($estado === 'inactivas') {
            $consulta->where('activo', false);
        } elseif ($estado === 'vencidas') {
            $consulta->whereNotNull('fecha_vencimiento')->where('fecha_vencimiento', '<', now());
        }

        if (in_array($plan, ['demo', 'basico', 'premium', 'enterprise'], true)) {
            $consulta->where('plan', $plan);
        }

        return view('empresas.index', [
            'empresas' => $consulta->get(),
            'activa' => session('empresa_id'),
            'busqueda' => $busqueda,
            'estado' => $estado,
            'plan' => $plan,
            'stats' => [
                'total' => Empresa::query()->count(),
                'activas' => Empresa::query()->where('activo', true)->where(function ($q) {
                    $q->whereNull('fecha_vencimiento')->orWhere('fecha_vencimiento', '>=', now());
                })->count(),
                'inactivas' => Empresa::query()->where('activo', false)->count(),
                'vencidas' => Empresa::query()->whereNotNull('fecha_vencimiento')->where('fecha_vencimiento', '<', now())->count(),
                'este_mes' => Empresa::query()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ],
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

    public function acerca(): View
    {
        $empresa = EmpresaActual::empresa();

        if ($empresa && ! $empresa->codigo_solicitud) {
            $empresa->codigo_solicitud = ActivacionLicencia::codigoSolicitud('cobros-'.$empresa->id.'-'.$empresa->slug);
            $empresa->save();
        }

        if ($empresa) {
            Tenencia::conectar($empresa);
        }

        $usados = $empresa && ! $empresa->esCompleta()
            ? Prestamo::query()->count()
            : 0;

        return view('acerca', [
            'empresa' => $empresa,
            'usados' => $usados,
        ]);
    }

    public function acercaDatos(): JsonResponse
    {
        $empresa = EmpresaActual::empresa();

        if ($empresa) {
            Tenencia::conectar($empresa);
        }

        return response()->json([
            'nombre' => $empresa?->nombre,
            'completa' => $empresa?->esCompleta() ?? false,
            'plan' => $empresa?->plan,
            'vence' => $empresa?->fecha_vencimiento?->toDateString(),
            'codigo_solicitud' => $empresa?->codigo_solicitud,
            'usados' => $empresa && ! $empresa->esCompleta() ? Prestamo::query()->count() : 0,
            'limite' => Empresa::LIMITE_DEMO,
        ]);
    }

    public function licencia(): RedirectResponse
    {
        return redirect()->to(cobro_route('acerca'));
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
            ->to(cobro_route('acerca'))
            ->with('ok', 'Versión completa activa. Plan '.$resuelto['plan'].' por '.$resuelto['days'].' días, sin límite de préstamos.');
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
