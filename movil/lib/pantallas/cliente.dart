import 'package:flutter/material.dart';

import '../formato.dart';
import '../piezas.dart';
import '../servicios/cobros_api.dart';
import '../servicios/ubicacion.dart';
import 'cliente_form.dart';
import 'prestamo.dart';
import 'prestamo_form.dart';

class ClienteVista extends StatefulWidget {
  const ClienteVista({
    super.key,
    required this.api,
    required this.clienteId,
    required this.puedeEscribir,
    required this.puedePrestamos,
  });

  final CobrosApi api;
  final int clienteId;
  final bool puedeEscribir;
  final bool puedePrestamos;

  @override
  State<ClienteVista> createState() => _ClienteVistaState();
}

class _ClienteVistaState extends State<ClienteVista> {
  Map<String, dynamic>? _datos;
  String? _error;
  bool _ocupado = false;

  @override
  void initState() {
    super.initState();
    _cargar();
  }

  Future<void> _cargar() async {
    try {
      final datos = await widget.api.cliente(widget.clienteId);
      if (mounted) setState(() => _datos = datos);
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    }
  }

  Future<void> _marcar() async {
    setState(() {
      _ocupado = true;
      _error = null;
    });
    try {
      final punto = await puntoActual();
      await widget.api.ubicarCliente(widget.clienteId, punto.latitud, punto.longitud);
      await _cargar();
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    } finally {
      if (mounted) setState(() => _ocupado = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final cliente = _datos?['cliente'];
    final prestamos = (_datos?['prestamos'] as List?) ?? const [];
    final mapa = cliente is Map ? cliente : const {};
    final lat = numero(mapa['latitud']);
    final lng = numero(mapa['longitud']);
    final telefono = mapa['telefono']?.toString() ?? '';
    final direccion = mapa['direccion']?.toString() ?? '';

    return Scaffold(
      appBar: AppBar(
        title: Text(mapa['nombre']?.toString() ?? 'Cliente'),
        actions: [
          if (widget.puedeEscribir && cliente is Map)
            IconButton(
              tooltip: 'Editar',
              onPressed: () async {
                final ok = await Navigator.of(context).push<bool>(
                  MaterialPageRoute(builder: (_) => ClienteForm(api: widget.api, cliente: mapa)),
                );
                if (ok == true) await _cargar();
              },
              icon: const Icon(Icons.edit),
            ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
        children: [
          Lamina(
            relleno: tinta,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Monograma(mapa['nombre']?.toString() ?? '', oscuro: true),
                const SizedBox(height: 12),
                Text(direccion.isEmpty ? 'Sin dirección de cobro' : direccion, style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w700)),
                if (telefono.isNotEmpty)
                  TextButton.icon(
                    onPressed: () => llamar(telefono),
                    icon: const Icon(Icons.phone, color: senal),
                    label: Text(telefono, style: const TextStyle(color: senal)),
                  ),
                Text(lat == null || lng == null ? 'Sin coordenadas' : '${lat.toStringAsFixed(5)}, ${lng.toStringAsFixed(5)}', style: const TextStyle(color: Color(0xFFCBB892))),
                const SizedBox(height: 12),
                if (widget.puedeEscribir)
                  FilledButton.icon(
                    onPressed: _ocupado ? null : _marcar,
                    icon: const Icon(Icons.my_location),
                    label: const Text('Tomar coordenadas aquí'),
                  ),
                if (lat != null && lng != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: OutlinedButton.icon(
                      style: OutlinedButton.styleFrom(foregroundColor: senal, side: const BorderSide(color: senal)),
                      onPressed: () => abrirDestino(lat, lng),
                      icon: const Icon(Icons.navigation),
                      label: const Text('Cómo llegar'),
                    ),
                  ),
              ],
            ),
          ),
          const Titulo('PRÉSTAMOS'),
          if (prestamos.isEmpty) const Text('Este cliente todavía no tiene préstamos.', style: TextStyle(color: muted)),
          for (final item in prestamos)
            if (item is Map)
              Lamina(
                child: InkWell(
                  onTap: () async {
                    await Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => PrestamoVista(api: widget.api, prestamoId: entero(item['id'])),
                      ),
                    );
                    await _cargar();
                  },
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('${item['frecuencia']}', style: const TextStyle(fontWeight: FontWeight.w800)),
                            Text('De ${pesos(item['total'])}', style: const TextStyle(color: muted)),
                          ],
                        ),
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text(pesos(item['saldo']), style: const TextStyle(fontWeight: FontWeight.w800)),
                          const SizedBox(height: 4),
                          Pastilla('${item['estado']}', tono: tonoEstado('${item['estado']}')),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
          if (widget.puedePrestamos) ...[
            const SizedBox(height: 8),
            FilledButton(
              onPressed: () async {
                await Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => PrestamoForm(
                      api: widget.api,
                      clienteId: widget.clienteId,
                      clienteNombre: mapa['nombre']?.toString() ?? '',
                    ),
                  ),
                );
                await _cargar();
              },
              child: const Text('Nuevo préstamo'),
            ),
          ],
          if (_error != null) Text(_error!, style: const TextStyle(color: peligro)),
        ],
      ),
    );
  }
}
