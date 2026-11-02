<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        $empresa = app()->bound('empresa.tenant') ? app('empresa.tenant') : null;

        return view('auth.login', [
            'primeraCuenta' => $empresa === null && User::query()->doesntExist(),
            'empresaTenant' => $empresa,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (app()->bound('empresa.tenant')) {
            return $this->entrarEnEmpresa($request, app('empresa.tenant'));
        }

        if (User::query()->doesntExist()) {
            $datos = $request->validate([
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ], [
                'name.required' => 'Escribe tu nombre.',
                'email.required' => 'Escribe el correo.',
                'email.email' => 'Ese correo no es válido.',
                'password.required' => 'Escribe una contraseña.',
                'password.min' => 'La contraseña necesita al menos 8 caracteres.',
                'password.confirmed' => 'La confirmación no coincide.',
            ]);

            $usuario = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'password' => $datos['password'],
            ]);

            Auth::login($usuario);
            $request->session()->regenerate();

            return $this->despuesDeEntrar($request);
        }

        $datos = $this->credenciales($request);

        if (! $this->intentar($request, $datos['email'], $datos['password'])) {
            $this->fallo($request, $datos['email']);
        }

        $this->limpiarIntentos($request, $datos['email']);
        $request->session()->regenerate();

        return $this->despuesDeEntrar($request);
    }

    private function entrarEnEmpresa(Request $request, Empresa $empresa): RedirectResponse
    {
        $datos = $this->credenciales($request);

        if (! $this->intentar($request, $datos['email'], $datos['password'])) {
            $this->fallo($request, $datos['email']);
        }

        $usuario = $request->user();

        if (! $usuario->es_maestro && (int) $usuario->empresa_id !== (int) $empresa->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $this->fallo($request, $datos['email']);
        }

        $this->limpiarIntentos($request, $datos['email']);
        $request->session()->regenerate();

        return redirect()->to(cobro_route('dashboard'));
    }

    private function despuesDeEntrar(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        if ($usuario?->es_maestro) {
            return redirect()->route('empresas.index');
        }

        if ($usuario?->empresa_id) {
            $empresa = Empresa::query()->find($usuario->empresa_id);

            if ($empresa?->base_datos) {
                return redirect()->route('tenant.dashboard', ['tenant' => $empresa->uuid]);
            }
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * @return array{email: string, password: string}
     */
    private function credenciales(Request $request): array
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Escribe el correo.',
            'email.email' => 'Ese correo no es válido.',
            'password.required' => 'Escribe la contraseña.',
        ]);

        $this->frenarIntentos($request, $datos['email']);

        return $datos;
    }

    private function intentar(Request $request, string $email, string $password): bool
    {
        return Auth::attempt(
            ['email' => $email, 'password' => $password],
            $request->boolean('recordar')
        );
    }

    private function frenarIntentos(Request $request, string $email): void
    {
        $clave = $this->claveIntentos($request, $email);

        if (! RateLimiter::tooManyAttempts($clave, 5)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => 'Demasiados intentos. Espera '.RateLimiter::availableIn($clave).' segundos.',
        ]);
    }

    private function fallo(Request $request, string $email): never
    {
        RateLimiter::hit($this->claveIntentos($request, $email), 900);

        throw ValidationException::withMessages([
            'email' => 'El correo o la contraseña no coinciden.',
        ]);
    }

    private function limpiarIntentos(Request $request, string $email): void
    {
        RateLimiter::clear($this->claveIntentos($request, $email));
    }

    private function claveIntentos(Request $request, string $email): string
    {
        return 'ingreso|'.Str::lower($email).'|'.$request->ip();
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(cobro_route('login'));
    }
}
