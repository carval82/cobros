@extends('layouts.app')

@section('titulo', 'Hoy · Cobros')

@section('contenido')
    <header class="encabezado">
        <div>
            <h1>Hoy</h1>
            <p>{{ now()->translatedFormat('l d \d\e F') }}</p>
        </div>
        <div class="acciones">
            <a class="btn" href="{{ route('prestamos.create') }}">Nuevo préstamo</a>
            <a class="btn-linea" href="{{ route('clientes.create') }}">Nuevo cliente</a>
        </div>
    </header>

    <section class="grid-cifras">
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
                    <p><a href="{{ route('atrasados') }}">Ver los {{ $atrasadas->count() }} atrasados</a></p>
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
