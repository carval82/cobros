@extends('layouts.app')

@section('titulo', 'Empresas · Cobros')

@section('contenido')
    <header class="encabezado">
        <div>
            <h1>Empresas</h1>
            <p>Cada empresa tiene su propia cartera. Entra a una para cobrar.</p>
        </div>
    </header>

    <section class="panel" style="max-width:720px">
        <h2>Nueva empresa</h2>
        <form method="post" action="{{ route('empresas.store') }}">
            @csrf
            <label>Nombre
                <input name="nombre" value="{{ old('nombre') }}" required>
            </label>
            <div class="fila-form">
                <label>Usuario de esa empresa
                    <input name="usuario_nombre" value="{{ old('usuario_nombre') }}">
                </label>
                <label>Correo
                    <input type="email" name="usuario_email" value="{{ old('usuario_email') }}">
                </label>
            </div>
            <label>Contraseña
                <input type="password" name="usuario_password" autocomplete="new-password">
            </label>
            <p class="ayuda">El usuario es opcional. Si lo dejas vacío, solo el maestro entra a esa cartera.</p>
            <button class="btn" type="submit">Crear y entrar</button>
        </form>
    </section>

    <section class="panel">
        <h2>Carteras</h2>
        @if ($empresas->isEmpty())
            <p class="vacio">Todavía no hay empresas.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th class="num">Clientes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($empresas as $empresa)
                        <tr>
                            <td>{{ $empresa->nombre }}</td>
                            <td class="num">{{ $empresa->clientes_count }}</td>
                            <td class="num">
                                <form method="post" action="{{ route('empresas.entrar', $empresa) }}">
                                    @csrf
                                    <button class="btn" type="submit">{{ (int) $activa === $empresa->id ? 'Seguir aquí' : 'Entrar' }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
