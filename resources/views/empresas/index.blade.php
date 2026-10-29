@extends('layouts.app')

@section('titulo', 'Empresas · Cobros')

@section('contenido')
    <header class="encabezado">
        <div>
            <p class="kicker">Panel central</p>
            <h1>Empresas registradas</h1>
            <p>Todas las empresas de la plataforma. Cada una tiene su base y su dirección: la raíz, /tenant/ y su número.</p>
        </div>
    </header>

    <section class="grid-cifras cifras-empresas">
        <article class="cifra">
            <span>Total</span>
            <strong>{{ $stats['total'] }}</strong>
        </article>
        <article class="cifra">
            <span>Activas</span>
            <strong>{{ $stats['activas'] }}</strong>
        </article>
        <article class="cifra">
            <span>Inactivas</span>
            <strong>{{ $stats['inactivas'] }}</strong>
        </article>
        <article class="cifra alerta">
            <span>Vencidas</span>
            <strong>{{ $stats['vencidas'] }}</strong>
        </article>
        <article class="cifra">
            <span>Este mes</span>
            <strong>{{ $stats['este_mes'] }}</strong>
        </article>
    </section>

    <section class="panel">
        <form class="filtros" method="get" action="{{ route('empresas.index') }}">
            <label>Búsqueda
                <input name="q" value="{{ $busqueda }}" placeholder="Nombre, correo o identificador">
            </label>
            <label>Estado
                <select name="estado">
                    <option value="">Todas</option>
                    <option value="activas" @selected($estado === 'activas')>Activas</option>
                    <option value="inactivas" @selected($estado === 'inactivas')>Inactivas</option>
                    <option value="vencidas" @selected($estado === 'vencidas')>Vencidas</option>
                </select>
            </label>
            <label>Plan
                <select name="plan">
                    <option value="">Todos</option>
                    @foreach (['demo', 'basico', 'premium', 'enterprise'] as $opcion)
                        <option value="{{ $opcion }}" @selected($plan === $opcion)>{{ ucfirst($opcion) }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn" type="submit">Filtrar</button>
        </form>

        @if ($empresas->isEmpty())
            <p class="vacio">No hay empresas con esos filtros.</p>
        @else
            <div class="preview-tabla">
                <table>
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Dirección</th>
                            <th>Plan</th>
                            <th>Estado</th>
                            <th>Vencimiento</th>
                            <th>Registrada</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($empresas as $empresa)
                            @php
                                $vencida = $empresa->fecha_vencimiento && $empresa->fecha_vencimiento->isPast();
                                $direccion = $empresa->direccion();
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $empresa->nombre }}</strong>
                                    @if ($empresa->email)
                                        <div class="muted">{{ $empresa->email }}</div>
                                    @endif
                                    <div class="acciones-tabla" style="justify-content:flex-start;margin-top:8px">
                                        @if ($direccion)
                                            <a class="btn-linea" href="{{ $direccion }}" target="_blank" rel="noopener">Acceder</a>
                                        @endif
                                        <form method="post" action="{{ route('empresas.entrar', $empresa) }}">
                                            @csrf
                                            <button class="btn" type="submit">{{ (int) $activa === $empresa->id ? 'Seguir' : 'Entrar' }}</button>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    @if ($direccion)
                                        <a class="direccion-tenant direccion-corta" href="{{ $direccion }}" title="{{ $direccion }}">
                                            <span>Tenant</span>
                                            /tenant/{{ $empresa->uuid }}
                                        </a>
                                    @else
                                        <span class="muted">Base central</span>
                                    @endif
                                </td>
                                <td><span class="badge badge-plan">{{ $empresa->plan }}</span></td>
                                <td>
                                    @if (! $empresa->activo)
                                        <span class="badge badge-pause">Inactiva</span>
                                    @elseif ($vencida)
                                        <span class="badge badge-danger">Vencida</span>
                                    @else
                                        <span class="badge badge-ok">Activa</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($empresa->fecha_vencimiento)
                                        {{ $empresa->fecha_vencimiento->format('d/m/Y') }}
                                        <div class="muted">
                                            @if ($vencida)
                                                Vencida
                                            @else
                                                {{ (int) now()->diffInDays($empresa->fecha_vencimiento) }} días
                                            @endif
                                        </div>
                                    @else
                                        <span class="muted">Sin fecha</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $empresa->created_at?->format('d/m/Y') }}
                                    <div class="muted">{{ $empresa->created_at?->diffForHumans() }}</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="panel">
        <h2>Nueva empresa</h2>
        <form method="post" action="{{ route('empresas.store') }}">
            @csrf
            <div class="fila-form">
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
            </div>
            <p class="ayuda">La prueba dura 30 días y permite {{ \App\Models\Empresa::LIMITE_DEMO }} préstamos. La dirección queda en esta tabla.</p>
            <button class="btn" type="submit">Crear empresa</button>
        </form>
    </section>
@endsection
