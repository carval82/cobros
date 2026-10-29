@extends('layouts.app')

@section('titulo', 'Préstamo · Cobros')

@section('contenido')
    @php
        $estado = $prestamo->estaEnMora() ? 'mora' : $prestamo->estado;
        $cuotasAbiertas = $prestamo->cuotas->where('saldo', '>', 0);
    @endphp

    <header class="encabezado">
        <div>
            <h1>{{ $prestamo->cliente->nombre }}</h1>
            <p>
                @dinero($prestamo->capital) al {{ \App\Support\Dinero::porcentaje($prestamo->porcentaje) }}
                · {{ $prestamo->numero_cuotas }} cuotas {{ strtolower($prestamo->frecuenciaNombre()) }}
                @include('partials.estado', ['estado' => $estado])
            </p>
        </div>
        <a class="btn-linea" href="{{ cobro_route('clientes.show', $prestamo->cliente) }}">Ver historial del cliente</a>
    </header>

    @if ($prestamo->estado === 'detenido')
        <div class="banner">
            Cobro detenido desde {{ $prestamo->detenido_en->translatedFormat('d M Y') }}.
            {{ $prestamo->motivo_detencion }}
            Mientras esté detenido, estas cuotas no se marcan como atraso.
        </div>
    @endif

    <section class="grid-cifras">
        <article class="cifra"><span>Capital</span><strong>@dinero($prestamo->capital)</strong></article>
        <article class="cifra"><span>Interés</span><strong>@dinero($prestamo->interes)</strong></article>
        <article class="cifra"><span>Total</span><strong>@dinero($prestamo->total)</strong></article>
        <article class="cifra {{ $prestamo->estaEnMora() ? 'alerta' : '' }}"><span>Saldo</span><strong>@dinero($prestamo->saldo())</strong></article>
    </section>

    <div class="dos">
        <section class="panel">
            <h2>Fechas de pago</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th class="num">Cuota</th>
                        <th class="num">Pagado</th>
                        <th class="num">Saldo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($prestamo->cuotas as $cuota)
                        <tr>
                            <td>{{ $cuota->numero }}</td>
                            <td>
                                {{ $cuota->fecha_vencimiento->translatedFormat('d M Y') }}
                                @if ($cuota->diasAtraso() > 0)
                                    <div class="muted">{{ $cuota->diasAtraso() }} día{{ $cuota->diasAtraso() === 1 ? '' : 's' }} de atraso</div>
                                @endif
                            </td>
                            <td class="num">@dinero($cuota->valor)</td>
                            <td class="num">@dinero($cuota->pagado)</td>
                            <td class="num">@dinero($cuota->saldo)</td>
                            <td>@include('partials.estado', ['estado' => $cuota->estadoVisible()])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($prestamo->nota)
                <p class="ayuda">{{ $prestamo->nota }}</p>
            @endif
        </section>

        <div>
            @if ($prestamo->admitePagos())
                <section class="panel">
                    <h2>Registrar abono</h2>
                    <form method="post" action="{{ cobro_route('prestamos.pagar', $prestamo) }}">
                        @csrf
                        <label>Valor
                            <input name="monto" type="number" min="1" step="1" value="{{ old('monto', $cuotasAbiertas->first()->saldo ?? '') }}" required>
                        </label>
                        <label>Fecha del abono
                            <input name="fecha" type="date" value="{{ old('fecha', now()->toDateString()) }}" required>
                        </label>
                        <label>Nota
                            <input name="nota" value="{{ old('nota') }}" maxlength="180">
                        </label>
                        <input type="hidden" name="latitud" value="{{ old('latitud') }}">
                        <input type="hidden" name="longitud" value="{{ old('longitud') }}">
                        <p class="ayuda" data-gps>Buscando la ubicación de este cobro…</p>
                        <button class="btn" type="submit">Guardar abono</button>
                    </form>
                    <p class="ayuda">Este cobro es solo de {{ $prestamo->cliente->nombre }}. Si cubre varias cuotas, se aplica de la más antigua a la más nueva.</p>
                </section>
            @endif

            @if ($prestamo->estado === 'activo' && $prestamo->saldo() > 0)
                <section class="panel">
                    <h2>Refinanciar</h2>
                    <p class="ayuda">Sigue siendo este préstamo y este cliente. El saldo que falta, más el dinero nuevo que le entregues, arma el plan. Cero adicional solo reacomoda las cuotas.</p>
                    <form method="post" action="{{ cobro_route('prestamos.refinanciar', $prestamo) }}" id="form-refinanciar">
                        @csrf
                        <label>Dinero adicional
                            <input id="adicional" name="adicional" type="number" min="0" step="1" value="{{ old('adicional', 0) }}" required>
                        </label>
                        <div class="fila-form">
                            <label>Interés del nuevo plan (%)
                                <input id="ref-porcentaje" name="porcentaje" type="number" min="0" max="1000" step="0.01" value="{{ old('porcentaje', $prestamo->porcentaje) }}" required>
                            </label>
                            <label>Cuotas nuevas
                                <input id="ref-cuotas" name="cuotas" type="number" min="1" max="366" step="1" value="{{ old('cuotas', $cuotasAbiertas->count()) }}" required>
                            </label>
                        </div>
                        <div class="fila-form">
                            <label>Frecuencia
                                <select id="ref-frecuencia" name="frecuencia">
                                    @foreach (\App\Services\PlanPagos::FRECUENCIAS as $clave => $nombre)
                                        <option value="{{ $clave }}" @selected(old('frecuencia', $prestamo->frecuencia) === $clave)>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Primer pago
                                <input id="ref-fecha" name="fecha_primer_pago" type="date" value="{{ old('fecha_primer_pago', now()->toDateString()) }}" required>
                            </label>
                        </div>
                        <label>Nota
                            <input name="nota" value="{{ old('nota') }}" maxlength="500">
                        </label>
                        <p class="ayuda" id="ref-preview" data-saldo="{{ $prestamo->saldo() }}"></p>
                        <button class="btn" type="submit">Refinanciar este préstamo</button>
                    </form>
                </section>
            @endif

            @if ($prestamo->estado === 'activo')
                <section class="panel">
                    <h2>Detener cobro</h2>
                    <form method="post" action="{{ cobro_route('prestamos.detener', $prestamo) }}">
                        @csrf
                        <label>Motivo
                            <input name="motivo" required maxlength="180" placeholder="Viaje, acuerdo, enfermedad">
                        </label>
                        <button class="btn-pausa" type="submit">Detener</button>
                    </form>
                </section>
            @endif

            @if ($prestamo->estado === 'detenido')
                <section class="panel">
                    <h2>Reanudar</h2>
                    <p class="ayuda">Las cuotas pendientes se corren los días que estuvo detenido, para que esa pausa no se vuelva mora.</p>
                    <form method="post" action="{{ cobro_route('prestamos.reanudar', $prestamo) }}">
                        @csrf
                        <button class="btn" type="submit">Reanudar cobro</button>
                    </form>
                </section>
            @endif

            @if ($cuotasAbiertas->isNotEmpty() && in_array($prestamo->estado, ['activo', 'detenido']))
                <section class="panel">
                    <h2>Aplazar cuota</h2>
                    <form method="post" id="form-aplazar">
                        @csrf
                        <label>Cuota
                            <select id="cuota_aplazar" required>
                                @foreach ($cuotasAbiertas as $cuota)
                                    <option value="{{ $cuota->id }}" data-fecha="{{ $cuota->fecha_vencimiento->toDateString() }}">
                                        Cuota {{ $cuota->numero }} · {{ $cuota->fecha_vencimiento->translatedFormat('d M Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label>Nueva fecha
                            <input name="fecha" type="date" required>
                        </label>
                        <label>Nota
                            <input name="nota" maxlength="180">
                        </label>
                        <button class="btn-linea" type="submit">Aplazar y correr las siguientes</button>
                    </form>
                </section>
            @endif

            @if ($prestamo->pagos->isEmpty() && in_array($prestamo->estado, ['activo', 'detenido']))
                <section class="panel">
                    <h2>Anular</h2>
                    <form method="post" action="{{ cobro_route('prestamos.anular', $prestamo) }}" onsubmit="return confirm('¿Anular este préstamo?')">
                        @csrf
                        <label>Motivo
                            <input name="motivo" required maxlength="180">
                        </label>
                        <button class="btn-peligro" type="submit">Anular préstamo</button>
                    </form>
                </section>
            @endif

            <section class="panel">
                <h2>Abonos</h2>
                @if ($prestamo->pagos->isEmpty())
                    <p class="vacio">Todavía no hay abonos.</p>
                @else
                    <ul class="historial">
                        @foreach ($prestamo->pagos as $pago)
                            <li>
                                <time>{{ $pago->fecha->translatedFormat('d M Y') }}</time>
                                <div>
                                    <strong>@dinero($pago->monto)</strong>
                                    <div class="muted">{{ $pago->nota }}</div>
                                    @if ($pago->latitud && $pago->longitud)
                                        <div class="muted">Ubicación guardada</div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const form = document.getElementById('form-aplazar');
        if (form) {
            const select = document.getElementById('cuota_aplazar');
            const plantilla = @json(cobro_route('cuotas.aplazar', ['cuota' => 0]));
            const actualizar = () => {
                form.action = plantilla.replace('/cuotas/0/', '/cuotas/' + select.value + '/');
            };
            select.addEventListener('change', actualizar);
            actualizar();
        }

        const preview = document.getElementById('ref-preview');
        if (preview) {
            const saldo = Number(preview.dataset.saldo) || 0;
            const dinero = (valor) => '$ ' + Math.round(valor).toLocaleString('es-CO');
            const pintar = () => {
                const adicional = Math.max(0, Math.round(Number(document.getElementById('adicional').value) || 0));
                const porcentaje = Number(document.getElementById('ref-porcentaje').value) || 0;
                const cuotas = parseInt(document.getElementById('ref-cuotas').value, 10) || 0;
                const base = saldo + adicional;
                const interes = Math.round(base * porcentaje / 100);
                const total = base + interes;
                const cuota = cuotas > 0 ? Math.round(total / cuotas) : 0;
                preview.textContent = 'Saldo ' + dinero(saldo) + ' + ' + dinero(adicional) + ' = ' + dinero(base)
                    + '. Con el ' + porcentaje + '% queda en ' + dinero(total)
                    + (cuotas > 0 ? ', cerca de ' + dinero(cuota) + ' por cuota.' : '.');
            };
            document.getElementById('form-refinanciar').addEventListener('input', pintar);
            pintar();
        }

        const latitud = document.querySelector('input[name="latitud"]');
        const longitud = document.querySelector('input[name="longitud"]');
        const avisoGps = document.querySelector('[data-gps]');
        if (latitud && longitud && navigator.geolocation && !latitud.value) {
            navigator.geolocation.getCurrentPosition((posicion) => {
                latitud.value = posicion.coords.latitude.toFixed(7);
                longitud.value = posicion.coords.longitude.toFixed(7);
                if (avisoGps) avisoGps.textContent = 'Ubicación lista. Se guardará con el abono.';
            }, () => {
                if (avisoGps) avisoGps.textContent = 'No se pudo leer la ubicación. El abono se puede guardar igual.';
            }, { enableHighAccuracy: true, timeout: 8000 });
        }
    </script>
@endpush
