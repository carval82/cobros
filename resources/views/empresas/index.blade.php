@extends('layouts.app')

@section('titulo', 'Empresas · Cobros')

@section('contenido')
    <header class="encabezado">
        <div>
            <p class="kicker">Escritorio maestro</p>
            <h1>Empresas</h1>
            <p>Crear una empresa carga su base y compone su dirección: la raíz del sitio, /tenant/ y el número nuevo.</p>
        </div>
    </header>

    <div class="tablero">
        <section class="panel">
            <h2>Nueva empresa</h2>
            <form method="post" action="{{ cobro_route('empresas.store') }}">
                @csrf
                <label>Nombre
                    <input name="nombre" value="{{ old('nombre') }}" required>
                </label>
                <label>Usuario de esa empresa
                    <input name="usuario_nombre" value="{{ old('usuario_nombre') }}" required>
                </label>
                <label>Correo
                    <input type="email" name="usuario_email" value="{{ old('usuario_email') }}" required>
                </label>
                <label>Contraseña
                    <input type="password" name="usuario_password" autocomplete="new-password" required>
                </label>
                <p class="ayuda">La prueba dura 30 días. El enlace de entrada aparece en la tarjeta.</p>
                <button class="btn" type="submit">Crear y entrar</button>
            </form>
        </section>

        <section>
            @if ($empresas->isEmpty())
                <p class="vacio">Todavía no hay empresas.</p>
            @else
                <div class="empresas-lista">
                    @foreach ($empresas as $empresa)
                        <article class="empresa-card">
                            <header>
                                <h3>{{ $empresa->nombre }}</h3>
                                <span class="badge badge-plan">{{ $empresa->plan }}</span>
                            </header>
                            <div class="empresa-meta muted">
                                @if ($empresa->email)
                                    <span>{{ $empresa->email }}</span>
                                @endif
                                <span>{{ $empresa->base_datos ?: 'Base central' }}</span>
                                @if ($empresa->fecha_vencimiento)
                                    <span>hasta {{ $empresa->fecha_vencimiento->format('d/m/Y') }}</span>
                                @endif
                            </div>
                            @if ($direccion = $empresa->direccion())
                                <a class="direccion-tenant" href="{{ $direccion }}">
                                    <span>Dirección del tenant</span>
                                    {{ $direccion }}
                                </a>
                            @endif
                            @if ($empresa->codigo_solicitud)
                                <p class="muted">Solicitud {{ $empresa->codigo_solicitud }}</p>
                            @endif
                            <form method="post" action="{{ cobro_route('empresas.entrar', $empresa) }}">
                                @csrf
                                <button class="btn" type="submit">{{ (int) $activa === $empresa->id ? 'Seguir aquí' : 'Entrar' }}</button>
                            </form>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
