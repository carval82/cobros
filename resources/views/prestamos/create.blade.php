@extends('layouts.app')

@section('titulo', 'Nuevo préstamo · Cobros')

@section('contenido')
    <header class="encabezado">
        <div>
            <h1>Nuevo préstamo</h1>
            <p class="ayuda">Siempre sale de un cliente. Ese cliente puede tener varios préstamos, y cada abono queda en uno solo. El porcentaje es el interés de todo el préstamo: 100.000 al 20% en 5 semanas quedan en 120.000.</p>
            @if (! empty($demo))
                <p class="ayuda">Versión demo: {{ $usados }} de {{ \App\Models\Empresa::LIMITE_DEMO }} préstamos. La completa se activa en Acerca de.</p>
            @endif
        </div>
    </header>

    <div class="dos">
        <section class="panel">
            <form method="post" action="{{ cobro_route('prestamos.store') }}" id="form-prestamo">
                @csrf
                <label>Cliente
                    <select name="cliente_id" required>
                        <option value="" @selected(! old('cliente_id', $clienteId))>Elegir cliente</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->id }}" @selected((string) old('cliente_id', $clienteId) === (string) $cliente->id)>
                                {{ $cliente->nombre }}
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
                    <input id="fecha_primer_pago" name="fecha_primer_pago" type="date" value="{{ old('fecha_primer_pago', now()->toDateString()) }}" required>
                </label>
                <p class="ayuda">Quincenal cuenta cada 15 días desde esa fecha. Mensual cae el mismo día, o el último del mes si no existe.</p>
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
@endsection

@push('scripts')
    <script src="{{ asset('js/simular.js') }}"></script>
@endpush
