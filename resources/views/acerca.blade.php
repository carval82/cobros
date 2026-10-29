@extends('layouts.app')

@section('titulo', 'Acerca de · Cobros')

@section('contenido')
    <header class="encabezado">
        <div>
            <p class="kicker">Cobros</p>
            <h1>Acerca de</h1>
            <p>Cartera, ruta y cobro en la calle. La demo deja crear {{ \App\Models\Empresa::LIMITE_DEMO }} préstamos. La clave pasa esta empresa a versión completa.</p>
        </div>
    </header>

    <div class="tablero">
        <section class="panel">
            <h2>{{ $empresa?->esCompleta() ? 'Versión completa' : 'Versión demo' }}</h2>
            @if ($empresa)
                <p>{{ $empresa->nombre }}</p>
                <p class="ayuda">
                    Plan {{ $empresa->plan }}
                    @if ($empresa->fecha_vencimiento)
                        · hasta {{ $empresa->fecha_vencimiento->format('d/m/Y') }}
                    @endif
                </p>
                @if (! $empresa->esCompleta())
                    <p>Préstamos usados: {{ $usados }} de {{ \App\Models\Empresa::LIMITE_DEMO }}.</p>
                @else
                    <p>Sin límite de préstamos.</p>
                @endif
            @else
                <p class="ayuda">Entra a una empresa para ver su licencia y activarla.</p>
            @endif
        </section>

        @if ($empresa)
            <section class="panel">
                <h2>Clave de activación</h2>
                <p class="ayuda">Lleva este código de solicitud al activador y pega aquí la clave que te devuelve.</p>
                <p class="direccion-tenant"><span>Código de solicitud</span>{{ $empresa->codigo_solicitud }}</p>
                <form method="post" action="{{ cobro_route('acerca.activar') }}">
                    @csrf
                    <label>Clave
                        <input name="codigo" value="{{ old('codigo') }}" autocomplete="off" required>
                    </label>
                    <button class="btn" type="submit">Activar versión completa</button>
                </form>
            </section>
        @endif
    </div>
@endsection
