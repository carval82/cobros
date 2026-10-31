@extends('layouts.app')

@section('titulo', 'Licencias · Cobras')

@section('contenido')
    <header class="encabezado">
        <div>
            <p class="kicker">Activador</p>
            <h1>Licencias</h1>
            <p>Cada empresa, su código de solicitud, la clave que la abre y la dirección del tenant.</p>
        </div>
    </header>

    <section class="grid-cifras">
        <article class="cifra"><span>Empresas</span><strong>{{ $stats['total'] }}</strong></article>
        <article class="cifra"><span>Demo</span><strong>{{ $stats['demo'] }}</strong></article>
        <article class="cifra"><span>Completas</span><strong>{{ $stats['completas'] }}</strong></article>
        <article class="cifra alerta"><span>Vencidas</span><strong>{{ $stats['vencidas'] }}</strong></article>
    </section>

    @if ($clave)
        <section class="panel">
            <h2>Clave {{ $clave['empresa'] ? 'de '.$clave['empresa'] : 'generada' }}</h2>
            <p class="ayuda">{{ ucfirst($clave['plan']) }} · {{ $clave['dias'] }} días. Se pega en Acerca de de esa empresa.</p>
            <p class="direccion-tenant"><span>Código de solicitud</span>{{ $clave['solicitud'] }}</p>
            <p class="direccion-tenant"><span>Clave de activación</span>{{ $clave['codigo'] }}</p>
        </section>
    @endif

    <section class="panel">
        <h2>Generar una clave</h2>
        <p class="ayuda">Si el código de solicitud llegó de otra instalación, pégalo aquí. El resultado es el mismo que el del activador.</p>
        <form method="post" action="{{ route('licencias.generar') }}">
            @csrf
            <label>Código de solicitud
                <input name="solicitud" value="{{ old('solicitud') }}" autocomplete="off" required>
            </label>
            <div class="fila-form">
                <label>Plan
                    <select name="plan" required>
                        @foreach ($planes as $plan)
                            <option value="{{ $plan }}" @selected(old('plan', 'basico') === $plan)>{{ ucfirst($plan) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Duración
                    <select name="dias" required>
                        @foreach ($duraciones as $dias)
                            <option value="{{ $dias }}" @selected((int) old('dias', 365) === $dias)>{{ $dias }} días</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <button class="btn" type="submit">Generar clave</button>
        </form>
    </section>

    <section class="panel">
        <h2>Empresas</h2>
        <form class="buscar" method="get" action="{{ route('licencias.index') }}">
            <input name="q" value="{{ $busqueda }}" placeholder="Nombre, correo, tenant o código">
            <button class="btn" type="submit">Buscar</button>
        </form>

        @if ($empresas->isEmpty())
            <p class="vacio">No hay empresas con esa búsqueda.</p>
        @else
            <div class="tabla-wrap"><table>
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Dirección del tenant</th>
                        <th>Solicitud</th>
                        <th>Licencia</th>
                        <th>Gestionar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($empresas as $empresa)
                        @php
                            $direccion = $empresa->direccion();
                            $vencida = $empresa->fecha_vencimiento && $empresa->fecha_vencimiento->isPast();
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $empresa->nombre }}</strong>
                                @if ($empresa->email)
                                    <div class="muted">{{ $empresa->email }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($direccion)
                                    <p class="direccion-tenant"><span>Pega esta dirección</span>{{ $direccion }}</p>
                                    <p class="ayuda">/tenant/{{ $empresa->uuid }}</p>
                                @else
                                    <span class="muted">Base central, sin dirección pública.</span>
                                @endif
                            </td>
                            <td><span class="direccion-corta">{{ $empresa->codigo_solicitud }}</span></td>
                            <td>
                                @if ($empresa->esCompleta())
                                    <span class="badge badge-ok">{{ ucfirst($empresa->plan) }}</span>
                                @else
                                    <span class="badge badge-plan">Demo</span>
                                @endif
                                <div class="muted">
                                    @if ($empresa->fecha_vencimiento)
                                        {{ $vencida ? 'Venció' : 'Hasta' }} {{ $empresa->fecha_vencimiento->format('d/m/Y') }}
                                    @else
                                        Sin fecha
                                    @endif
                                </div>
                            </td>
                            <td>
                                <form class="equipo-form" method="post" action="{{ route('licencias.aplicar', $empresa) }}">
                                    @csrf
                                    <select name="plan" aria-label="Plan de {{ $empresa->nombre }}">
                                        @foreach ($planes as $plan)
                                            <option value="{{ $plan }}">{{ ucfirst($plan) }}</option>
                                        @endforeach
                                    </select>
                                    <select name="dias" aria-label="Días de {{ $empresa->nombre }}">
                                        @foreach ($duraciones as $dias)
                                            <option value="{{ $dias }}" @selected($dias === 365)>{{ $dias }} días</option>
                                        @endforeach
                                    </select>
                                    <button class="btn-linea" type="submit" name="modo" value="clave">Generar clave</button>
                                    <button class="btn" type="submit" name="modo" value="activar">Activar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        @endif
    </section>
@endsection
