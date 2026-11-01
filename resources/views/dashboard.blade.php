@extends('layouts.app')

@section('titulo', ($mostrar ? ($informe['nombre'] ?? 'Cobro') : 'Hoy').' · Cobros')

@section('contenido')
    @if (! $mostrar)
        <header class="encabezado">
            <div>
                <p class="kicker">Jornada</p>
                <h1>Hoy</h1>
                <p>{{ now()->translatedFormat('l d \d\e F') }}</p>
            </div>
        </header>

        <section class="panel">
            <h2>Cobros</h2>
            <p class="ayuda">Entra a un cobro por su nombre. Ahí se enciende el menú y se ve todo lo de ese cobro.</p>
            @forelse ($cobros as $item)
                <div class="entrada-cobro">
                    <p>{{ $item['nombre'] }}</p>
                    <a class="btn" href="{{ cobro_route('cobro.entrar', ['caja' => $item['id']]) }}">Entrar</a>
                </div>
            @empty
                <p class="vacio">Todavía no hay cobros abiertos.</p>
            @endforelse

            @if ($puedeAperturar)
                <h2 style="margin-top:18px">Nuevo cobro</h2>
                <form method="post" action="{{ cobro_route('cuadre.abrir') }}">
                    @csrf
                    <input type="hidden" name="seguir" value="hoy">
                    @include('partials.equipo-cobro')
                    <div class="fila-form">
                        <label>Nombre del cobro
                            <input name="nombre" value="{{ old('nombre', auth()->user()->name) }}" maxlength="80" placeholder="Ej. Norte">
                        </label>
                        <label>Valor inicial
                            <input name="valor_inicial" type="number" min="1" step="1" value="{{ old('valor_inicial') }}" required>
                        </label>
                    </div>
                    <button class="btn" type="submit">Abrir cobro</button>
                </form>
            @endif
        </section>
    @else
        <header class="encabezado">
            <div>
                <p class="kicker">Cobro</p>
                <h1>{{ $informe['nombre'] }}</h1>
                <p>{{ $informe['equipo'] ?? 'Solo' }}</p>
            </div>
            <div class="acciones">
                <a class="btn-linea" href="{{ cobro_route('cobro.salir') }}">Volver a los cobros</a>
            </div>
        </header>

        @if (! empty($editaEquipo) && $cajaActiva && $cobradoresEmpresa->isNotEmpty())
            <section class="panel">
                <form method="post" action="{{ cobro_route('cuadre.equipo') }}">
                    @csrf
                    <input type="hidden" name="seguir" value="hoy">
                    <input type="hidden" name="caja_id" value="{{ $cajaActiva }}">
                    @include('partials.equipo-cobro')
                    <button class="btn" type="submit">Guardar cobradores</button>
                </form>
            </section>
        @endif

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
    @endif
@endsection
