@extends('layouts.app')

@section('titulo', 'Clientes · Cobros')

@section('contenido')
    <header class="encabezado">
        <div>
            <h1>Clientes</h1>
            <p>Cada cliente guarda sus préstamos, abonos y el historial.</p>
        </div>
        <a class="btn" href="{{ cobro_route('clientes.create') }}">Nuevo cliente</a>
    </header>

    <form class="buscar" method="get" action="{{ cobro_route('clientes.index') }}">
        <input type="search" name="q" value="{{ $q }}" placeholder="Nombre, documento o teléfono">
        <button class="btn" type="submit">Buscar</button>
    </form>

    <section class="panel">
        @if ($clientes->isEmpty())
            <p class="vacio">No hay clientes con ese dato.</p>
        @else
            <div class="tabla-wrap"><table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Préstamos</th>
                        <th class="num">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($clientes as $cliente)
                        <tr>
                            <td>
                                <a href="{{ cobro_route('clientes.show', $cliente) }}"><strong>{{ $cliente->nombre }}</strong></a>
                                <div class="muted">{{ $cliente->documento }}</div>
                            </td>
                            <td>{{ $cliente->telefono }}</td>
                            <td>{{ $cliente->prestamos_count }}</td>
                            <td class="num">@dinero($cliente->saldo ?? 0)</td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        @endif
    </section>
@endsection
