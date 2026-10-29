@extends('layouts.app')

@section('titulo', 'Atrasados · Cobros')

@section('contenido')
    <header class="encabezado">
        <div>
            <h1>Atrasados</h1>
            <p>{{ $atrasadas->count() }} cuotas vencidas · @dinero($mora) por recoger</p>
        </div>
    </header>
    <section class="panel">
        @if ($atrasadas->isEmpty())
            <p class="vacio">No hay cuotas vencidas. Los préstamos detenidos no entran en esta lista.</p>
        @else
            @include('partials.lista-cuotas', ['cuotas' => $atrasadas])
        @endif
    </section>
@endsection
