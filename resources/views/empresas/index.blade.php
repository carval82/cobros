@extends('layouts.app')

@section('titulo', 'Empresas · Cobros')

@section('contenido')
    <header class="encabezado">
        <div>
            <h1>Empresas</h1>
            <p>Cada empresa nueva abre su propia base de datos. La prueba dura 30 días; después se activa con el código de solicitud.</p>
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
                    <input name="usuario_nombre" value="{{ old('usuario_nombre') }}" required>
                </label>
                <label>Correo
                    <input type="email" name="usuario_email" value="{{ old('usuario_email') }}" required>
                </label>
            </div>
            <label>Contraseña
                <input type="password" name="usuario_password" autocomplete="new-password" required>
            </label>
            <p class="ayuda">Ese usuario entra directo a su base. El maestro también puede abrirla desde aquí.</p>
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
                        <th>Base</th>
                        <th>Licencia</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($empresas as $empresa)
                        <tr>
                            <td>
                                {{ $empresa->nombre }}
                                @if ($empresa->email)
                                    <div class="muted">{{ $empresa->email }}</div>
                                @endif
                                @if ($empresa->codigo_solicitud)
                                    <div class="muted">Solicitud {{ $empresa->codigo_solicitud }}</div>
                                @endif
                            </td>
                            <td>{{ $empresa->base_datos ?: 'Principal, en la base central' }}</td>
                            <td>
                                {{ $empresa->plan }}
                                @if ($empresa->fecha_vencimiento)
                                    <div class="muted">hasta {{ $empresa->fecha_vencimiento->format('d/m/Y') }}</div>
                                @endif
                            </td>
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
