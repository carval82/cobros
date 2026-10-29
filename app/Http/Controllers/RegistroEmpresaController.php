<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Services\ProvisionarTenant;
use App\Support\Tenencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegistroEmpresaController extends Controller
{
    public function portada(DashboardController $tablero): View|RedirectResponse
    {
        $usuario = auth()->user();

        if (! $usuario) {
            return view('registrar');
        }

        if ($usuario->es_maestro) {
            $elegida = session('empresa_id') ? Empresa::query()->find(session('empresa_id')) : null;

            if ($elegida?->base_datos && $elegida->uuid) {
                return redirect()->route('tenant.dashboard', ['tenant' => $elegida->uuid]);
            }

            if (! $elegida) {
                return redirect()->route('empresas.index');
            }
        }

        if (! $usuario->es_maestro && $usuario->empresa_id) {
            $propia = Empresa::query()->find($usuario->empresa_id);

            if ($propia?->base_datos && $propia->uuid) {
                return redirect()->route('tenant.dashboard', ['tenant' => $propia->uuid]);
            }
        }

        return $tablero->index();
    }

    public function store(Request $request, ProvisionarTenant $provisionar): RedirectResponse|JsonResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'usuario_nombre' => ['required', 'string', 'max:120'],
            'usuario_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'usuario_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nombre.required' => 'Escribe el nombre de la empresa.',
            'usuario_nombre.required' => 'Escribe el nombre del administrador.',
            'usuario_email.required' => 'Escribe el correo del administrador.',
            'usuario_email.unique' => 'Ese correo ya tiene una cuenta.',
            'usuario_password.required' => 'Escribe una contraseña.',
            'usuario_password.min' => 'La contraseña necesita al menos 8 caracteres.',
            'usuario_password.confirmed' => 'La confirmación no coincide.',
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
            $administrador = $provisionar->aprovisionar(
                $empresa,
                $datos['usuario_nombre'],
                $datos['usuario_email'],
                $datos['usuario_password']
            );
        } catch (\Throwable $e) {
            $empresa->delete();
            Tenencia::cerrar();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No se pudo crear la base de la empresa: '.$e->getMessage(),
                ], 500);
            }

            return back()
                ->withErrors(['nombre' => 'No se pudo crear la base de la empresa: '.$e->getMessage()])
                ->withInput();
        }

        Auth::login($administrador);
        $request->session()->regenerate();
        $empresa->refresh();

        if ($request->expectsJson()) {
            return response()->json([
                'empresa' => $empresa->nombre,
                'direccion' => $empresa->direccion(),
                'tenant' => $empresa->uuid,
                'rol' => 'administrador',
            ]);
        }

        return redirect()
            ->route('tenant.dashboard', ['tenant' => $empresa->uuid])
            ->with('ok', 'Empresa lista. La dirección de entrada es '.$empresa->direccion());
    }

    private function slug(string $nombre): string
    {
        $base = \Illuminate\Support\Str::slug($nombre) ?: 'empresa';
        $slug = $base;
        $i = 2;

        while (Empresa::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
