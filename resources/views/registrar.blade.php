@extends('layouts.acceso')

@section('titulo', 'Crear empresa · Cobras')

@section('contenido')
    <h1>Crea tu empresa</h1>
    <p class="ayuda">Al crearla se abre su base, se compone la dirección /tenant/ y queda el administrador con el correo y la contraseña que escribas.</p>

    <form method="post" action="{{ route('registrar.store') }}">
        @csrf
        <label>Empresa
            <input name="nombre" value="{{ old('nombre') }}" required>
        </label>
        <label>Administrador
            <input name="usuario_nombre" value="{{ old('usuario_nombre') }}" required>
        </label>
        <label>Correo
            <input type="email" name="usuario_email" value="{{ old('usuario_email') }}" autocomplete="username" required>
        </label>
        <label>Contraseña
            <input type="password" name="usuario_password" autocomplete="new-password" required>
        </label>
        <label>Confirmar contraseña
            <input type="password" name="usuario_password_confirmation" autocomplete="new-password" required>
        </label>
        <p class="ayuda">La prueba dura 30 días y permite {{ \App\Models\Empresa::LIMITE_DEMO }} préstamos. Después entras por la dirección que se genera.</p>
        <button class="btn" type="submit">Crear empresa</button>
    </form>
    <p class="ayuda"><a href="{{ route('login') }}">Ya tengo empresa</a></p>
    <p class="ayuda"><a href="{{ route('app.movil') }}">Configurar y bajar la app</a></p>
@endsection
