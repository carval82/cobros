@extends('layouts.acceso')

@section('titulo', ($primeraCuenta ? 'Primera cuenta' : 'Entrar').' · Cobros')

@section('contenido')
    <h1>{{ $primeraCuenta ? 'Crea la primera cuenta' : 'Entrar' }}</h1>
    <p class="ayuda">
        {{ $primeraCuenta
            ? 'Todavía no hay usuarios. Esta cuenta será la que abra la cartera.'
            : 'Usa el correo y la contraseña de tu cuenta.' }}
    </p>

    <form method="post" action="{{ route('login.store') }}">
        @csrf
        @if ($primeraCuenta)
            <label>Nombre
                <input name="name" value="{{ old('name') }}" autocomplete="name" required>
            </label>
        @endif
        <label>Correo
            <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required>
        </label>
        <label>Contraseña
            <input type="password" name="password" autocomplete="{{ $primeraCuenta ? 'new-password' : 'current-password' }}" required>
        </label>
        @if ($primeraCuenta)
            <label>Confirmar contraseña
                <input type="password" name="password_confirmation" autocomplete="new-password" required>
            </label>
        @else
            <label class="check">
                <input type="checkbox" name="recordar" value="1" @checked(old('recordar'))>
                <span>Recordarme en este equipo</span>
            </label>
        @endif
        <button class="btn" type="submit">{{ $primeraCuenta ? 'Crear cuenta y entrar' : 'Entrar' }}</button>
    </form>
@endsection
