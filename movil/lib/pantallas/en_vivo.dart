import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

import '../formato.dart';
import '../piezas.dart';
import '../servicios/cobros_api.dart';

class EnVivo extends StatefulWidget {
  const EnVivo({super.key, required this.api});

  final CobrosApi api;

  @override
  State<EnVivo> createState() => _EnVivoState();
}

class _EnVivoState extends State<EnVivo> {
  Map<String, dynamic>? _datos;
  String? _error;
  Timer? _reloj;
  final _mapa = MapController();
  bool _encuadrado = false;

  @override
  void initState() {
    super.initState();
    _cargar();
    _reloj = Timer.periodic(const Duration(seconds: 15), (_) => _cargar());
  }

  @override
  void dispose() {
    _reloj?.cancel();
    _mapa.dispose();
    super.dispose();
  }

  Future<void> _cargar() async {
    try {
      final datos = await widget.api.enVivo();
      if (!mounted) return;
      setState(() => _datos = datos);
      final puntos = _puntos(datos);
      if (puntos.isEmpty || _encuadrado) return;
      _encuadrado = true;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        try {
          _mapa.fitCamera(CameraFit.coordinates(coordinates: puntos, padding: const EdgeInsets.all(48)));
        } catch (_) {}
      });
    } catch (e) {
      if (mounted && _datos == null) setState(() => _error = textoError(e));
    }
  }

  List<LatLng> _puntos(Map<String, dynamic> datos) {
    final lista = (datos['cobradores'] as List?) ?? const [];
    return [
      for (final item in lista)
        if (item is Map && numero(item['latitud']) != null && numero(item['longitud']) != null)
          LatLng(numero(item['latitud'])!, numero(item['longitud'])!),
    ];
  }

  @override
  Widget build(BuildContext context) {
    final datos = _datos;
    final cobradores = (datos?['cobradores'] as List?) ?? const [];
    final puntos = datos == null ? const <LatLng>[] : _puntos(datos);

    return Scaffold(
      appBar: AppBar(title: const Text('En vivo')),
      body: datos == null
          ? Center(child: _error == null ? const CircularProgressIndicator() : Padding(padding: const EdgeInsets.all(24), child: Text(_error!)))
          : ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
              children: [
                const Text('Se actualiza solo mientras el cobrador tiene la app abierta.', style: TextStyle(color: muted)),
                const SizedBox(height: 12),
                if (puntos.isNotEmpty)
                  SizedBox(
                    height: 280,
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(22),
                      child: FlutterMap(
                        mapController: _mapa,
                        options: MapOptions(initialCenter: puntos.first, initialZoom: 14),
                        children: [
                          TileLayer(
                            urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                            userAgentPackageName: 'com.lcdesign.cobros',
                          ),
                          MarkerLayer(
                            markers: [
                              for (final item in cobradores)
                                if (item is Map && numero(item['latitud']) != null)
                                  Marker(
                                    point: LatLng(numero(item['latitud'])!, numero(item['longitud'])!),
                                    width: 44,
                                    height: 44,
                                    child: Monograma(item['nombre']?.toString() ?? '', oscuro: item['en_linea'] == true),
                                  ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  )
                else
                  const Lamina(child: Text('Todavía ningún cobrador comparte su ubicación.')),
                const Titulo('COBRADORES'),
                if (cobradores.isEmpty) const Text('No hay cobradores en esta empresa.'),
                for (final item in cobradores)
                  if (item is Map)
                    Lamina(
                      child: Row(
                        children: [
                          Monograma(item['nombre']?.toString() ?? ''),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(item['nombre']?.toString() ?? '', style: const TextStyle(fontWeight: FontWeight.w800)),
                                Text(
                                  item['en_linea'] == true ? 'En línea' : (item['hace']?.toString() ?? 'Sin abrir la app'),
                                  style: TextStyle(color: item['en_linea'] == true ? ok : muted),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
              ],
            ),
    );
  }
}
