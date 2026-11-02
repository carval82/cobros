@extends('layouts.app')

@section('titulo', 'Nuevo préstamo · Cobros')

@section('contenido')
    <header class="encabezado">
        <div>
            <h1>Nuevo préstamo</h1>
            <p class="ayuda">El préstamo sale de este cobro y de un solo cliente. Si ese cliente ya debe en otro cobro, queda restringido y solo el administrador aprueba el préstamo nuevo. El porcentaje es el interés de todo el préstamo: 100.000 al 20% en 5 semanas quedan en 120.000.</p>
            @if (! empty($demo))
                <p class="ayuda">Versión demo: {{ $usados }} de {{ \App\Models\Empresa::LIMITE_DEMO }} préstamos. La completa se activa en Acerca de.</p>
            @endif
        </div>
    </header>

    @if ($cobros->isEmpty())
        <section class="panel">
            <h2>Abre el cobro</h2>
            <p>Un préstamo sale de un cobro que ya tenga el día abierto con su valor inicial.</p>
            <a class="btn" href="{{ cobro_route('cuadre') }}">Aperturar cobro</a>
        </section>
    @else
    <div class="dos">
        <section class="panel">
            <form method="post" action="{{ cobro_route('prestamos.store') }}" id="form-prestamo">
                @csrf
                @if ($cobros->count() === 1)
                    <input type="hidden" name="caja_cobro_id" value="{{ $cobros->first()->id }}">
                @else
                    <label>Cobro
                        <select name="caja_cobro_id" required>
                            @foreach ($cobros as $cobro)
                                <option value="{{ $cobro->id }}" @selected((string) old('caja_cobro_id') === (string) $cobro->id)>{{ $cobro->nombre }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <label>Cliente
                    <select name="cliente_id" required>
                        <option value="" @selected(! old('cliente_id', $clienteId))>Elegir cliente</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->id }}" @selected((string) old('cliente_id', $clienteId) === (string) $cliente->id)>
                                {{ $cliente->nombre }}{{ $cliente->restringido ? ' · restringido' : '' }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <div class="fila-form">
                    <label>Capital prestado
                        <input id="capital" name="capital" type="number" min="1" step="1" value="{{ old('capital', 100000) }}" required>
                    </label>
                    <label>Interés del préstamo (%)
                        <input id="porcentaje" name="porcentaje" type="number" min="0" max="1000" step="0.01" value="{{ old('porcentaje', 20) }}" required>
                    </label>
                </div>
                <div class="fila-form">
                    <label>Número de cuotas
                        <input id="cuotas" name="cuotas" type="number" min="1" max="366" step="1" value="{{ old('cuotas', 5) }}" required>
                    </label>
                    <label>Frecuencia
                        <select id="frecuencia" name="frecuencia">
                            @foreach ($frecuencias as $clave => $nombre)
                                <option value="{{ $clave }}" @selected(old('frecuencia', 'semanal') === $clave)>{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <label>Fecha del primer pago
                    <input id="fecha_primer_pago" name="fecha_primer_pago" type="date" value="{{ old('fecha_primer_pago', \App\Services\PlanPagos::primeraFecha(now(), old('frecuencia', 'semanal'))->toDateString()) }}" required>
                </label>
                <p class="ayuda">El préstamo sale hoy. Diario se cobra mañana, semanal a los 8 días, quincenal a los 15 y mensual al mes siguiente. Las cuotas siguientes conservan ese ritmo. Quincenal sigue cada 15 días y mensual cae el mismo día, o el último del mes si no existe.</p>
                <label>Nota
                    <textarea name="nota">{{ old('nota') }}</textarea>
                </label>
                <button class="btn" type="submit">Crear préstamo</button>
            </form>
        </section>
        <section class="panel">
            <h2>Plan</h2>
            <div id="preview"></div>
        </section>
    </div>
    @endif
@endsection

@if ($cobros->isNotEmpty())
@push('scripts')
    <script src="{{ asset('js/simular.js') }}"></script>
@endpush
@endif
