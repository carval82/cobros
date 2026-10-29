@extends('layouts.app')

@section('titulo', $cliente->nombre.' · Cobros')

@section('contenido')
    <header class="encabezado">
        <div>
            <h1>{{ $cliente->nombre }}</h1>
            <p>
                {{ $cliente->documento }}
                @if ($cliente->telefono) · {{ $cliente->telefono }} @endif
                @if ($cliente->direccion) · {{ $cliente->direccion }} @endif
                @if ($cliente->latitud && $cliente->longitud) · Ubicación en la ruta @endif
            </p>
        </div>
        <div class="acciones">
            <a class="btn" href="{{ cobro_route('prestamos.create', ['cliente' => $cliente->id]) }}">Nuevo préstamo</a>
            <a class="btn-linea" href="{{ cobro_route('clientes.edit', $cliente) }}">Editar</a>
        </div>
    </header>

    @if ($cliente->notas)
        <p class="panel">{{ $cliente->notas }}</p>
    @endif

    <div class="dos">
        <section class="panel">
            <h2>Préstamos</h2>
            <p class="ayuda">Este cliente puede tener varios. Cada abono pertenece a un solo préstamo y solo a su nombre.</p>
            @if ($cliente->prestamos->isEmpty())
                <p class="vacio">Este cliente todavía no tiene préstamos.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Préstamo</th>
                            <th>Estado</th>
                            <th class="num">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cliente->prestamos as $prestamo)
                            <tr>
                                <td>
                                    <a href="{{ cobro_route('prestamos.show', $prestamo) }}">
                                        @dinero($prestamo->capital) al {{ \App\Support\Dinero::porcentaje($prestamo->porcentaje) }}
                                    </a>
                                    <div class="muted">{{ $prestamo->numero_cuotas }} cuotas {{ strtolower($prestamo->frecuenciaNombre()) }}</div>
                                </td>
                                <td>
                                    @include('partials.estado', ['estado' => $prestamo->estaEnMora() ? 'mora' : $prestamo->estado])
                                </td>
                                <td class="num">@dinero($prestamo->saldo())</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
        <section class="panel">
            <h2>Historial</h2>
            @if ($cliente->movimientos->isEmpty())
                <p class="vacio">Sin movimientos.</p>
            @else
                <ul class="historial">
                    @foreach ($cliente->movimientos as $movimiento)
                        <li>
                            <time>{{ $movimiento->fecha->translatedFormat('d M Y') }}</time>
                            <div>{{ $movimiento->descripcion }}</div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
