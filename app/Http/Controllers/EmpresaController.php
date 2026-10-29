<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmpresaController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (! auth()->user()->es_maestro) {
            return redirect()->route('dashboard');
        }

        return view('empresas.index', [
            'empresas' => Empresa::query()->withCount('clientes')->orderBy('nombre')->get(),
            'activa' => session('empresa_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->es_maestro, 403);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'usuario_nombre' => ['nullable', 'string', 'max:120'],
            'usuario_email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'usuario_password' => ['nullable', 'string', 'min:8'],
        ], [
            'nombre.required' => 'Escribe el nombre de la empresa.',
            'usuario_email.unique' => 'Ese correo ya tiene una cuenta.',
            'usuario_password.min' => 'La contraseña del usuario necesita al menos 8 caracteres.',
        ]);

        if (($datos['usuario_email'] ?? null) && empty($datos['usuario_password'])) {
            return back()->withErrors(['usuario_password' => 'Escribe una contraseña para ese usuario.'])->withInput();
        }

        $empresa = Empresa::create([
            'nombre' => $datos['nombre'],
            'slug' => $this->slug($datos['nombre']),
        ]);

        if (! empty($datos['usuario_email'])) {
            User::create([
                'name' => $datos['usuario_nombre'] ?: $datos['nombre'],
                'email' => $datos['usuario_email'],
                'password' => $datos['usuario_password'],
                'empresa_id' => $empresa->id,
            ]);
        }

        session(['empresa_id' => $empresa->id]);

        return redirect()->route('dashboard')->with('ok', 'Empresa lista. Ya estás trabajando en '.$empresa->nombre.'.');
    }

    public function entrar(Empresa $empresa): RedirectResponse
    {
        abort_unless(auth()->user()->es_maestro, 403);

        session(['empresa_id' => $empresa->id]);

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
