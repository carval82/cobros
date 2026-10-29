@extends('layouts.app')

@section('titulo', ($propio ? 'Mi rendimiento' : 'Rendimiento').' · Cobras')

@section('contenido')
    <header class="encabezado">
        <div>
            <p class="kicker">Equipo</p>
            <h1>{{ $propio ? 'Mi rendimiento' : 'Rendimiento' }}</h1>
            <p>{{ $propio ? 'Lo que cobraste y lo que te corresponde de esos abonos.' : 'Lo que cobró cada persona y el porcentaje que le corresponde.' }}</p>
        </div>
    </header>

    <form class="filtros filtros-fechas" method="get">
        <label>Desde
            <input type="date" name="desde" value="{{ $desde }}">
        </label>
        <label>Hasta
            <input type="date" name="hasta" value="{{ $hasta }}">
        </label>
        <button class="btn" type="submit">Ver periodo</button>
    </form>

    <section class="grid-cifras">
        <article class="cifra">
            <span>Cobrado</span>
            <strong>@dinero($informe['cobrado'])</strong>
        </article>
        <article class="cifra">
            <span>{{ $propio ? 'Te corresponde' : 'A pagar en comisión' }}</span>
            <strong>@dinero($informe['comision'])</strong>
        </article>
        <article class="cifra">
            <span>Abonos</span>
            <strong>{{ $informe['abonos'] }}</strong>
        </article>
    </section>

    <section class="panel">
        <h2>{{ $propio ? 'Tu corte' : 'Cobradores' }}</h2>
        @if ($informe['cobradores'] === [])
            <p class="vacio">En este periodo no hay cobros asignados. El porcentaje se define en Usuarios: un cobrador nuevo queda en 10%.</p>
        @else
            <div class="tabla-wrap"><table>
                <thead>
                    <tr>
                        <th>Cobrador</th>
                        <th>% vigente</th>
                        <th>Abonos</th>
                        <th>Cobrado</th>
                        <th>Le corresponde</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($informe['cobradores'] as $fila)
                        <tr>
                            <td>{{ $fila['nombre'] }}</td>
                            <td>{{ \App\Support\Dinero::porcentaje($fila['porcentaje']) }}</td>
                            <td>{{ $fila['abonos'] }}</td>
                            <td class="num">@dinero($fila['cobrado'])</td>
                            <td class="num">@dinero($fila['comision'])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            <p class="ayuda">Cada abono guarda el porcentaje de ese momento. Si lo cambias en Usuarios, solo los cobros nuevos usan el valor nuevo.</p>
        @endif
    </section>

    @if ($informe['movimientos'] !== [])
        <section class="panel">
            <h2>Abonos del periodo</h2>
            <div class="tabla-wrap"><table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Cobrador</th>
                        <th>Abono</th>
                        <th>%</th>
                        <th>Comisión</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($informe['movimientos'] as $movimiento)
                        <tr>
                            <td>{{ $movimiento['fecha'] }}</td>
                            <td>{{ $movimiento['cliente'] }}</td>
                            <td>{{ $movimiento['cobrador'] }}</td>
                            <td class="num">@dinero($movimiento['monto'])</td>
                            <td>{{ \App\Support\Dinero::porcentaje($movimiento['porcentaje']) }}</td>
                            <td class="num">@dinero($movimiento['comision'])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>
    @endif
@endsection
