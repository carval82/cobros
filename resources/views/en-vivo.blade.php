@extends('layouts.app')

@section('titulo', 'En vivo · Cobras')

@section('contenido')
    <header class="encabezado">
        <div>
            <p class="kicker">Equipo</p>
            <h1>En vivo</h1>
            <p>Dónde está cada cobrador mientras tiene Cobras abierto. Si cierra la app, queda la última posición.</p>
        </div>
    </header>

    <div id="mapa-vivo" class="mapa" hidden></div>
    <p id="mapa-vacio" class="panel">Todavía nadie comparte ubicación. Cuando un cobrador abra la app, el punto aparece aquí.</p>

    <section class="panel">
        <h2>Cobradores</h2>
        <div class="tabla-wrap"><table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Última señal</th>
                </tr>
            </thead>
            <tbody id="lista-viva">
                <tr><td colspan="3">Buscando…</td></tr>
            </tbody>
        </table></div>
    </section>
@endsection

@push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const destino = @json(cobro_route('en-vivo.datos'));
        const mapaEl = document.getElementById('mapa-vivo');
        const vacio = document.getElementById('mapa-vacio');
        const lista = document.getElementById('lista-viva');
        let mapa = null;
        let capa = null;

        function texto(valor) {
            return String(valor ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        function pintar(cobradores) {
            const conPunto = cobradores.filter((c) => c.latitud !== null && c.longitud !== null);
            lista.innerHTML = cobradores.length === 0
                ? '<tr><td colspan="3">No hay cobradores en esta empresa.</td></tr>'
                : cobradores.map((c) => `<tr>
                    <td>${texto(c.nombre)}</td>
                    <td>${c.en_linea ? 'En línea' : (c.hace ? 'Sin señal reciente' : 'Sin abrir la app')}</td>
                    <td>${texto(c.hace) || '—'}</td>
                </tr>`).join('');

            if (conPunto.length === 0) {
                mapaEl.hidden = true;
                vacio.hidden = false;
                return;
            }

            mapaEl.hidden = false;
            vacio.hidden = true;
            if (!mapa) {
                mapa = L.map(mapaEl).setView([conPunto[0].latitud, conPunto[0].longitud], 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(mapa);
                capa = L.layerGroup().addTo(mapa);
                setTimeout(() => mapa.invalidateSize(), 80);
            }
            capa.clearLayers();
            const limites = [];
            conPunto.forEach((c) => {
                const punto = [c.latitud, c.longitud];
                limites.push(punto);
                L.circleMarker(punto, {
                    radius: 10,
                    color: c.en_linea ? '#1c140c' : '#6e6254',
                    fillColor: c.en_linea ? '#e4c16a' : '#d9c7a4',
                    fillOpacity: 1,
                    weight: 2,
                }).bindTooltip(texto(c.nombre) + (c.en_linea ? ' · en línea' : ' · ' + (texto(c.hace) || 'sin señal')), { permanent: true, direction: 'top' }).addTo(capa);
            });
            if (limites.length === 1) {
                mapa.setView(limites[0], 15);
            } else {
                mapa.fitBounds(limites, { padding: [30, 30] });
            }
        }

        async function leer() {
            const respuesta = await fetch(destino, { headers: { 'Accept': 'application/json' } });
            if (!respuesta.ok) return;
            const datos = await respuesta.json();
            pintar(datos.cobradores || []);
        }

        leer();
        setInterval(leer, 15000);
    </script>
@endpush
