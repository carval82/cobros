@extends('layouts.app')

@section('titulo', 'Ruta de cobro · Cobros')

@section('contenido')
    @php
        $paradas = $ruta['paradas'];
        $resumen = $ruta['resumen'];
        $centro = $ruta['centro'];
    @endphp

    <header class="encabezado">
        <div>
            <h1>Ruta de cobro</h1>
            <p>Paradas de hoy y atrasadas, ordenadas por cercanía. El GPS se toma al registrar cada abono.</p>
        </div>
        <div class="acciones">
            <button class="btn" type="button" id="ordenar-aqui">Ordenar desde aquí</button>
            @if ($ruta['mapas'])
                <a class="btn-linea" href="{{ $ruta['mapas'] }}" target="_blank" rel="noopener">Abrir en Google Maps</a>
            @endif
        </div>
    </header>

    <form id="form-origen" method="get" action="{{ route('ruta') }}" hidden>
        <input name="latitud">
        <input name="longitud">
    </form>

    <section class="grid-cifras">
        <article class="cifra"><span>Paradas</span><strong>{{ $resumen['paradas'] }}</strong></article>
        <article class="cifra"><span>En el mapa</span><strong>{{ $resumen['en_mapa'] }}</strong></article>
        <article class="cifra"><span>Sin GPS</span><strong>{{ $resumen['sin_gps'] }}</strong></article>
        <article class="cifra"><span>Por cobrar</span><strong>@dinero($resumen['saldo'])</strong></article>
    </section>

    @if ($centro)
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <div id="mapa-ruta" class="mapa"></div>
    @else
        <p class="panel">Todavía no hay coordenadas. Al guardar un abono con el teléfono, el cliente queda en el mapa.</p>
    @endif

    @if ($paradas === [])
        <p class="vacio">No hay cuotas para cobrar hoy.</p>
    @else
        <section class="panel">
            @foreach ($paradas as $parada)
                <article class="parada" @if ($parada['tiene_coordenadas']) data-lat="{{ $parada['latitud'] }}" data-lng="{{ $parada['longitud'] }}" id="parada-{{ $parada['cliente_id'] }}" @endif>
                    <span class="parada-orden">{{ $parada['orden'] }}</span>
                    <div>
                        <a href="{{ route('clientes.show', $parada['cliente_id']) }}"><strong>{{ $parada['nombre'] }}</strong></a>
                        @if ($parada['direccion'])
                            <div class="muted">{{ $parada['direccion'] }}</div>
                        @endif
                        <div class="muted">
                            @dinero($parada['saldo'])
                            · {{ $parada['cuotas'] }} cuota{{ $parada['cuotas'] === 1 ? '' : 's' }}
                            @if ($parada['atraso']) · atrasada @endif
                            @if (! $parada['tiene_coordenadas']) · sin GPS @endif
                        </div>
                    </div>
                    @if ($parada['tiene_coordenadas'])
                        <a class="btn-linea" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination={{ $parada['latitud'] }},{{ $parada['longitud'] }}">Ir</a>
                    @endif
                </article>
            @endforeach
        </section>
    @endif
@endsection

@push('scripts')
    @if ($centro)
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            const paradas = @json(collect($paradas)->where('tiene_coordenadas', true)->values());
            const centro = @json($centro);
            const mapa = L.map('mapa-ruta').setView([centro.lat, centro.lng], centro.zoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(mapa);
            const limites = [];
            paradas.forEach((parada) => {
                const icono = L.divIcon({
                    className: '',
                    html: '<div class="marca-mapa">' + parada.orden + '</div>',
                    iconSize: [28, 28],
                    iconAnchor: [14, 14]
                });
                L.marker([parada.latitud, parada.longitud], { icon: icono })
                    .addTo(mapa)
                    .bindPopup('<strong>' + parada.nombre + '</strong>');
                limites.push([parada.latitud, parada.longitud]);
            });
            if (limites.length > 1) mapa.fitBounds(limites, { padding: [24, 24] });
        </script>
    @endif
    <script>
        document.getElementById('ordenar-aqui')?.addEventListener('click', () => {
            if (!navigator.geolocation) return;
            navigator.geolocation.getCurrentPosition((posicion) => {
                const form = document.getElementById('form-origen');
                form.latitud.value = posicion.coords.latitude;
                form.longitud.value = posicion.coords.longitude;
                form.submit();
            });
        });
    </script>
@endpush
