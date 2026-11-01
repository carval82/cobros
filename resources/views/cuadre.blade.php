@extends('layouts.app')

@section('titulo', 'Cuadre · Cobras')

@section('contenido')
    <header class="encabezado">
        <div>
            <p class="kicker">Ruta</p>
            <h1>Cuadre del día</h1>
            <p>Cada cobro vive en su línea. La empresa los ve juntos y el día se desarrolla dentro de uno solo.</p>
        </div>
        @if ($informe)
            <div class="acciones">
                <a class="btn-linea" href="{{ cobro_route('cuadre', ['fecha' => $fecha]) }}">Todos los cobros</a>
            </div>
        @endif
    </header>

    <p class="ayuda">El valor inicial es la base de ese cobro. De ahí salen sus préstamos y a ella vuelve su recaudo. Al cerrar, retiras la ganancia o la sumas a su base. Otro cobro no entra en esa cuenta.</p>
    @if ($coordina && ! $informe)
        <p class="ayuda">El administrador entra a cada cobro. Puede desarrollarlo solo o con uno o varios cobradores en la misma ruta.</p>
    @endif

    @if (! $informe)
        @if ($puedeAperturar)
            <section class="panel">
                <h2>Aperturar cobro</h2>
                <p class="ayuda">El valor inicial sale a la calle y queda como base de este cobro.</p>
                <form method="post" action="{{ cobro_route('cuadre.abrir') }}">
                    @csrf
                    <label>Nombre del cobro
                        <input name="nombre" value="{{ old('nombre', auth()->user()->name) }}" maxlength="80">
                    </label>
                    <label>Valor inicial
                        <input name="valor_inicial" type="number" min="1" step="1" value="{{ old('valor_inicial') }}" required>
                    </label>
                    @include('partials.equipo-cobro')
                    <button class="btn" type="submit">Abrir cobro</button>
                </form>
            </section>
        @endif

        @if ($informes->isNotEmpty())
            <form class="filtros" method="get">
                <label>Día
                    <input type="date" name="fecha" value="{{ $fecha }}">
                </label>
                <button class="btn" type="submit">Ver día</button>
            </form>
            @foreach ($informes as $item)
                @include('partials.linea-cobro', ['item' => $item, 'fecha' => $fecha])
            @endforeach
        @elseif (! $puedeAperturar)
            <p class="vacio">Todavía no hay cobros.</p>
        @endif
    @else
        <p class="linea-cobro">{{ $informe['linea'] }}</p>

        @if ($informe['sin_apertura'])
            <section class="panel">
                <h2>Aperturar este cobro</h2>
                <p class="ayuda">El valor inicial sale a la calle y queda como base de este cobro.</p>
                <form method="post" action="{{ cobro_route('cuadre.abrir') }}">
                    @csrf
                    <input type="hidden" name="caja_id" value="{{ $informe['id'] }}">
                    <label>Nombre del cobro
                        <input name="nombre" value="{{ old('nombre', $informe['nombre']) }}" maxlength="80">
                    </label>
                    <label>Valor inicial
                        <input name="valor_inicial" type="number" min="1" step="1" value="{{ old('valor_inicial') }}" required>
                    </label>
                    @include('partials.equipo-cobro')
                    <button class="btn" type="submit">Abrir cobro</button>
                </form>
            </section>
        @else
            <form class="filtros" method="get">
                <input type="hidden" name="caja" value="{{ $informe['id'] }}">
                <label>Día
                    <input type="date" name="fecha" value="{{ $fecha }}">
                </label>
                <button class="btn" type="submit">Ver día</button>
            </form>
        @endif
    @endif

    @include('partials.desarrollo-cobro')
@endsection
