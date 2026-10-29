<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\EmpresaActual;
use App\Support\Permisos;
use App\Support\Tenencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function sesion(): JsonResponse
    {
        $usuario = auth()->user();
        $empresa = EmpresaActual::empresa();

        if ($empresa) {
            Tenencia::conectar($empresa);
        }

        return response()->json([
            'nombre' => $usuario->name,
            'correo' => $usuario->email,
            'rol' => $usuario->es_maestro ? 'maestro' : ($usuario->rol ?: 'administrador'),
            'empresa' => $empresa?->nombre,
            'direccion' => $empresa?->direccion(),
            'tenant' => $empresa?->uuid,
            'usuarios' => $usuario->puede('usuarios.gestionar'),
            'prestamos' => $usuario->puede('prestamos.crear'),
            'cobros' => $usuario->puede('cobros.registrar'),
            'clientes' => $usuario->puede('clientes.escribir'),
            'anular' => $usuario->puede('prestamos.anular'),
            'porcentaje' => (float) $usuario->porcentaje_cobro,
            'demo' => $empresa ? ! $empresa->esCompleta() : false,
            'limite_demo' => \App\Models\Empresa::LIMITE_DEMO,
            'token' => csrf_token(),
        ]);
    }

    public function datos(): JsonResponse
    {
        $empresaId = EmpresaActual::id();

        return response()->json([
            'roles' => Permisos::ROLES,
            'token' => csrf_token(),
            'usuarios' => User::query()
                ->where('empresa_id', $empresaId)
                ->where('es_maestro', false)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'rol', 'porcentaje_cobro'])
                ->map(fn (User $usuario) => [
                    'id' => $usuario->id,
                    'nombre' => $usuario->name,
                    'correo' => $usuario->email,
                    'rol' => $usuario->rol ?: 'administrador',
                    'porcentaje' => (float) $usuario->porcentaje_cobro,
                ])
                ->values(),
        ]);
    }

    public function index(): View
    {
        $empresaId = EmpresaActual::id();

        return view('usuarios.index', [
            'usuarios' => User::query()
                ->where('empresa_id', $empresaId)
                ->where('es_maestro', false)
                ->orderBy('name')
                ->get(),
            'roles' => Permisos::ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $datos = $this->validar($request);
        $porcentaje = $this->porcentaje($datos);

        User::query()->create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => $datos['password'],
            'empresa_id' => EmpresaActual::id(),
            'es_maestro' => false,
            'rol' => $datos['rol'],
            'porcentaje_cobro' => $porcentaje,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'nombre' => $datos['name'],
                'correo' => $datos['email'],
                'rol' => $datos['rol'],
                'porcentaje' => $porcentaje,
            ]);
        }

        return back()->with('ok', $datos['name'].' ya puede entrar con el rol '.Permisos::ROLES[$datos['rol']].'.');
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $this->mismaEmpresa($usuario);

        $datos = $request->validate([
            'rol' => ['required', Rule::in(array_keys(Permisos::ROLES))],
            'porcentaje_cobro' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if ($usuario->is(auth()->user()) && $datos['rol'] !== 'administrador') {
            return back()->withErrors(['rol' => 'No puedes quitarte el rol de administrador.']);
        }

        $usuario->update([
            'rol' => $datos['rol'],
            'porcentaje_cobro' => $this->porcentaje($datos, (float) $usuario->porcentaje_cobro),
        ]);

        return back()->with('ok', 'Rol de '.$usuario->name.' actualizado.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'rol' => ['required', Rule::in(array_keys(Permisos::ROLES))],
            'porcentaje_cobro' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'name.required' => 'Escribe el nombre.',
            'email.required' => 'Escribe el correo.',
            'email.unique' => 'Ese correo ya tiene una cuenta.',
            'password.required' => 'Escribe una contraseña.',
            'password.min' => 'La contraseña necesita al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación no coincide.',
            'rol.required' => 'Elige el rol.',
            'porcentaje_cobro.max' => 'El porcentaje no puede pasar de 100.',
        ]);
    }

    private function porcentaje(array $datos, float $actual = 0): float
    {
        if (isset($datos['porcentaje_cobro']) && $datos['porcentaje_cobro'] !== '') {
            return round((float) $datos['porcentaje_cobro'], 2);
        }

        if (($datos['rol'] ?? '') === 'cobrador' && $actual <= 0) {
            return 10;
        }

        return $actual;
    }

    private function mismaEmpresa(User $usuario): void
    {
        abort_unless((int) $usuario->empresa_id === (int) EmpresaActual::id() && ! $usuario->es_maestro, 404);
    }
}
