<div class="tabla-wrap">
    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Cuota</th>
                <th>Fecha</th>
                <th class="num">Saldo</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cuotas as $cuota)
                <tr>
                    <td>
                        <strong>{{ $cuota->prestamo->cliente->nombre }}</strong>
                        <div class="muted">{{ $cuota->prestamo->cliente->telefono }}</div>
                    </td>
                    <td>{{ $cuota->numero }} de {{ $cuota->prestamo->numero_cuotas }}</td>
                    <td>
                        {{ $cuota->fecha_vencimiento->translatedFormat('d M Y') }}
                        @if ($cuota->diasAtraso() > 0)
                            <div class="muted">{{ $cuota->diasAtraso() }} día{{ $cuota->diasAtraso() === 1 ? '' : 's' }} de atraso</div>
                        @endif
                    </td>
                    <td class="num">@dinero($cuota->saldo)</td>
                    <td><a href="{{ cobro_route('prestamos.show', $cuota->prestamo) }}">Abrir</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
