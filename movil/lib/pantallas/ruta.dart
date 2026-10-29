import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

import '../formato.dart';
import '../piezas.dart';
import '../servicios/cobros_api.dart';
import '../servicios/ubicacion.dart';
import 'prestamo.dart';

class RutaDia extends StatefulWidget {
  const RutaDia({super.key, required this.api, required this.puedeEscribir});

  final CobrosApi api;
  final bool puedeEscribir;

  @override
  State<RutaDia> createState() => _RutaDiaState();
}

class _RutaDiaState extends State<RutaDia> {
  Map<String, dynamic>? _datos;
  String? _error;
  bool _cargando = true;
  Punto? _origen;

  @override
  void initState() {
    super.initState();
    _cargar();
  }

  Future<void> _cargar({Punto? origen}) async {
    setState(() {
      _cargando = true;
      _error = null;
    });
    try {
      final datos = await widget.api.ruta(latitud: origen?.latitud, longitud: origen?.longitud);
      if (mounted) {
        setState(() {
          _datos = datos;
          _origen = origen ?? _origen;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    } finally {
      if (mounted) setState(() => _cargando = false);
    }
  }

  Future<void> _desdeAqui() async {
    try {
      final punto = await puntoActual();
      await _cargar(origen: punto);
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    }
  }

  Future<void> _marcar(int clienteId) async {
    try {
      final punto = await puntoActual();
      await widget.api.ubicarCliente(clienteId, punto.latitud, punto.longitud);
      await _cargar(origen: _origen);
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    }
  }

  @override
  Widget build(BuildContext context) {
    final datos = _datos;
    final paradas = (datos?['paradas'] as List?) ?? const [];
    final resumen = datos?['resumen'] is Map ? datos!['resumen'] as Map : const {};
    final centro = datos?['centro'] is Map ? datos!['centro'] as Map : null;
    final mapas = datos?['mapas']?.toString();
    final conMapa = [
      for (final item in paradas)
        if (item is Map && numero(item['latitud']) != null && numero(item['longitud']) != null) item,
    ];

    return RefreshIndicator(
      onRefresh: () => _cargar(origen: _origen),
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
        children: [
          Lamina(
            relleno: tinta,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Ruta de hoy', style: TextStyle(color: Color(0xFFCBB892), fontSize: 13)),
                Text(pesos(resumen['saldo']), style: const TextStyle(color: senal, fontSize: 32, fontWeight: FontWeight.w800)),
                Text('${resumen['paradas'] ?? 0} paradas · ${resumen['sin_gps'] ?? 0} sin GPS', style: const TextStyle(color: Colors.white)),
              ],
            ),
          ),
          FilledButton.icon(
            onPressed: _desdeAqui,
            icon: const Icon(Icons.my_location),
            label: Text(_origen == null ? 'Ordenar desde aquí' : 'Recalcular desde mi ubicación'),
          ),
          if (mapas != null && mapas.isNotEmpty)
            TextButton.icon(
              onPressed: () => abrirEnlace(mapas),
              icon: const Icon(Icons.map),
              label: const Text('Abrir la ruta en Google Maps'),
            ),
          if (centro != null && conMapa.isNotEmpty) ...[
            const SizedBox(height: 8),
            SizedBox(
              height: 240,
              child: ClipRRect(
                borderRadius: BorderRadius.circular(16),
                child: FlutterMap(
                  options: MapOptions(
                    initialCenter: LatLng(numero(centro['lat']) ?? 0, numero(centro['lng']) ?? 0),
                    initialZoom: (numero(centro['zoom']) ?? 13).toDouble(),
                  ),
                  children: [
                    TileLayer(
                      urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                      userAgentPackageName: 'com.lcdesign.cobros',
                    ),
                    MarkerLayer(
                      markers: [
                        for (final item in conMapa)
                          Marker(
                            point: LatLng(numero(item['latitud'])!, numero(item['longitud'])!),
                            width: 36,
                            height: 36,
                            child: _Marca(orden: entero(item['orden'])),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
          if (_cargando) const Padding(padding: EdgeInsets.all(24), child: Center(child: CircularProgressIndicator())),
          if (!_cargando && paradas.isEmpty)
            const Padding(padding: EdgeInsets.only(top: 16), child: Text('Hoy no hay cuotas vencidas ni del día para armar la ruta.')),
          for (final item in paradas)
            if (item is Map) _Parada(
              item: item,
              puedeEscribir: widget.puedeEscribir,
              onCobrar: () async {
                await Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => PrestamoVista(api: widget.api, prestamoId: entero(item['prestamo_id'])),
                  ),
                );
                await _cargar(origen: _origen);
              },
              onMarcar: () => _marcar(entero(item['cliente_id'])),
            ),
          if (_error != null) ...[
            const SizedBox(height: 12),
            Text(_error!, style: const TextStyle(color: Color(0xFFA32632))),
          ],
        ],
      ),
    );
  }
}

class _Marca extends StatelessWidget {
  const _Marca({required this.orden});

  final int orden;

  @override
  Widget build(BuildContext context) {
    return Container(
      alignment: Alignment.center,
      decoration: const BoxDecoration(color: Color(0xFF1C140C), shape: BoxShape.circle),
      child: Text('$orden', style: const TextStyle(color: Color(0xFFE4C16A), fontWeight: FontWeight.bold)),
    );
  }
}

class _Parada extends StatelessWidget {
  const _Parada({
    required this.item,
    required this.puedeEscribir,
    required this.onCobrar,
    required this.onMarcar,
  });

  final Map item;
  final bool puedeEscribir;
  final VoidCallback onCobrar;
  final VoidCallback onMarcar;

  @override
  Widget build(BuildContext context) {
    final lat = numero(item['latitud']);
    final lng = numero(item['longitud']);
    final direccion = item['direccion']?.toString() ?? '';
    final telefono = item['telefono']?.toString() ?? '';

    return Lamina(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Monograma('${item['orden']}'),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(item['nombre']?.toString() ?? '', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
                    Text(direccion.isEmpty ? 'Sin dirección escrita' : direccion, style: const TextStyle(color: muted)),
                  ],
                ),
              ),
              Text(pesos(item['saldo']), style: const TextStyle(fontWeight: FontWeight.w800)),
            ],
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 6,
            children: [
              Pastilla('${item['cuotas']} cuota(s)'),
              if (item['atraso'] == true) const Pastilla('Atrasado', tono: peligro),
              if (lat == null) const Pastilla('Sin GPS', tono: peligro) else const Pastilla('En el mapa', tono: ok),
            ],
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            children: [
              FilledButton(onPressed: onCobrar, child: const Text('Cobrar')),
              if (lat != null) OutlinedButton(onPressed: () => abrirDestino(lat, lng!), child: const Text('Ir')),
              if (telefono.isNotEmpty) OutlinedButton(onPressed: () => llamar(telefono), child: const Text('Llamar')),
              if (puedeEscribir) OutlinedButton(onPressed: onMarcar, child: const Text('Tomar GPS')),
            ],
          ),
        ],
      ),
    );
  }
}
