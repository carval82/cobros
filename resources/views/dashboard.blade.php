@extends('layouts.app')

@section('titulo', 'Hoy · Cobros')

@section('contenido')
    <header class="encabezado">
        <div>
            <p class="kicker">Jornada</p>
            <h1>Hoy</h1>
            <p>{{ now()->translatedFormat('l d \d\e F') }}</p>
        </div>
        <div class="acciones">
            <a class="btn" href="{{ cobro_route('prestamos.create') }}">Nuevo préstamo</a>
            <a class="btn-linea" href="{{ cobro_route('clientes.create') }}">Nuevo cliente</a>
        </div>
    </header>

    <section class="panel">
        <h2>Cobros</h2>
        <p class="ayuda">Cada cobro se entra desde aquí. Si es nuevo, se abre en esta vista y aquí mismo se desarrolla.</p>
        @forelse ($cobros as $item)
            <div @class(['entrada-cobro', 'activa' => $informe && $informe['id'] === $item['id']])>
                <p>{{ $item['linea'] }}</p>
                @if ($informe && $informe['id'] === $item['id'])
                    <a class="btn-linea" href="{{ cobro_route('dashboard') }}">Volver</a>
                @else
                    <a class="btn" href="{{ cobro_route('dashboard', ['cobro' => $item['id']]) }}">Entrar</a>
                @endif
            </div>
        @empty
            <p class="vacio">Todavía no hay cobros abiertos.</p>
        @endforelse

        @if (($informe && $informe['sin_apertura'] && ! empty($informe['edita'])) || ($puedeAperturar && ! $informe))
            <h2 style="margin-top:18px">{{ $informe ? 'Desarrollar este cobro' : 'Nuevo cobro' }}</h2>
            <form method="post" action="{{ cobro_route('cuadre.abrir') }}">
                @csrf
                <input type="hidden" name="seguir" value="hoy">
                @if ($informe)
                    <input type="hidden" name="caja_id" value="{{ $informe['id'] }}">
                @endif
                <div class="fila-form">
                    <label>Nombre del cobro
                        <input name="nombre" value="{{ old('nombre', $informe['nombre'] ?? auth()->user()->name) }}" maxlength="80">
                    </label>
                    <label>Valor inicial
                        <input name="valor_inicial" type="number" min="1" step="1" value="{{ old('valor_inicial') }}" required>
                    </label>
                </div>
                <button class="btn" type="submit">Abrir y desarrollar</button>
            </form>
        @endif
    </section>

    @include('partials.desarrollo-cobro', ['seguir' => 'hoy'])

    <section class="grid-cifras">
        <article class="cifra">
            <span>En caja</span>
            <strong>@dinero($enCaja)</strong>
        </article>
        <article class="cifra">
            <span>Cartera en la calle</span>
            <strong>@dinero($cartera)</strong>
        </article>
        <article class="cifra">
            <span>Para cobrar hoy</span>
            <strong>@dinero($cobrarHoy)</strong>
        </article>
        <article class="cifra alerta">
            <span>Atrasado</span>
            <strong>@dinero($mora)</strong>
        </article>
        <article class="cifra">
            <span>Cobrado hoy · {{ $detenidos }} detenidos</span>
            <strong>@dinero($cobradoHoy)</strong>
        </article>
    </section>

    <div class="dos">
        <section class="panel">
            <h2>Cuotas de hoy</h2>
            @if ($hoy->isEmpty())
                <p class="vacio">Hoy no hay cuotas por cobrar.</p>
            @else
                @include('partials.lista-cuotas', ['cuotas' => $hoy])
            @endif

            <h2 style="margin-top:28px">Atrasados</h2>
            @if ($atrasadas->isEmpty())
                <p class="vacio">No hay cuotas vencidas.</p>
            @else
                @include('partials.lista-cuotas', ['cuotas' => $atrasadas->take(8)])
                @if ($atrasadas->count() > 8)
                    <p><a href="{{ cobro_route('atrasados') }}">Ver los {{ $atrasadas->count() }} atrasados</a></p>
                @endif
            @endif
        </section>
        <section class="panel">
            <h2>Historial reciente</h2>
            @if ($movimientos->isEmpty())
                <p class="vacio">Todavía no hay movimientos.</p>
            @else
                <ul class="historial">
                    @foreach ($movimientos as $movimiento)
                        <li>
                            <time>{{ $movimiento->fecha->translatedFormat('d M') }}</time>
                            <div>
                                <strong>{{ $movimiento->cliente->nombre }}</strong>
                                <div class="muted">{{ $movimiento->descripcion }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
