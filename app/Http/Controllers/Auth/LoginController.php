<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login', [
            'primeraCuenta' => User::query()->doesntExist(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
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

        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Escribe el correo.',
            'email.email' => 'Ese correo no es válido.',
            'password.required' => 'Escribe la contraseña.',
        ]);

        if (! Auth::attempt(
            ['email' => $datos['email'], 'password' => $datos['password']],
            $request->boolean('recordar')
        )) {
            throw ValidationException::withMessages([
                'email' => 'El correo o la contraseña no coinciden.',
            ]);
        }

        $request->session()->regenerate();

        return $this->despuesDeEntrar($request);
    }

    private function despuesDeEntrar(Request $request): RedirectResponse
    {
        if ($request->user()?->es_maestro) {
            return redirect()->route('empresas.index');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
