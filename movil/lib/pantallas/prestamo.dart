import 'package:flutter/material.dart';

import '../formato.dart';
import '../servicios/cobros_api.dart';
import '../servicios/ubicacion.dart';

class PrestamoVista extends StatefulWidget {
  const PrestamoVista({super.key, required this.api, required this.prestamoId});

  final CobrosApi api;
  final int prestamoId;

  @override
  State<PrestamoVista> createState() => _PrestamoVistaState();
}

class _PrestamoVistaState extends State<PrestamoVista> {
  Map<String, dynamic>? _datos;
  String? _error;
  bool _ocupado = false;
  final _monto = TextEditingController();
  final _nota = TextEditingController();
  DateTime _fecha = DateTime.now();
  bool _guardarGps = true;

  @override
  void initState() {
    super.initState();
    _cargar();
  }

  @override
  void dispose() {
    _monto.dispose();
    _nota.dispose();
    super.dispose();
  }

  String get _fechaTexto {
    String dos(int n) => n.toString().padLeft(2, '0');
    return '${_fecha.year}-${dos(_fecha.month)}-${dos(_fecha.day)}';
  }

  Future<void> _cargar() async {
    try {
      final datos = await widget.api.prestamo(widget.prestamoId);
      if (mounted) {
        setState(() {
          _datos = datos;
          final saldo = entero((datos['prestamo'] as Map?)?['saldo']);
          if (_monto.text.isEmpty && saldo > 0) {
            final cuotas = (datos['cuotas'] as List?) ?? const [];
            final abierta = cuotas.cast<dynamic>().whereType<Map>().where((c) => entero(c['saldo']) > 0);
            _monto.text = '${abierta.isEmpty ? saldo : entero(abierta.first['saldo'])}';
          }
        });
      }
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    }
  }

  Future<void> _pagar() async {
    setState(() {
      _ocupado = true;
      _error = null;
    });
    try {
      double? lat;
      double? lng;
      if (_guardarGps) {
        final punto = await puntoActual();
        lat = punto.latitud;
        lng = punto.longitud;
      }
      await widget.api.pagar(
        prestamoId: widget.prestamoId,
        monto: int.parse(_monto.text),
        fecha: _fechaTexto,
        nota: _nota.text.trim(),
        latitud: lat,
        longitud: lng,
      );
      _nota.clear();
      await _cargar();
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    } finally {
      if (mounted) setState(() => _ocupado = false);
    }
  }

  Future<String?> _motivo(String titulo) {
    final campo = TextEditingController();
    return showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(titulo),
        content: TextField(controller: campo, decoration: const InputDecoration(labelText: 'Motivo')),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancelar')),
          FilledButton(onPressed: () => Navigator.pop(context, campo.text.trim()), child: const Text('Guardar')),
        ],
      ),
    );
  }

  Future<void> _accion(Future<void> Function() trabajo) async {
    setState(() {
      _ocupado = true;
      _error = null;
    });
    try {
      await trabajo();
      await _cargar();
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    } finally {
      if (mounted) setState(() => _ocupado = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final prestamo = _datos?['prestamo'];
    final mapa = prestamo is Map ? prestamo : null;
    if (mapa == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Préstamo')),
        body: Center(child: _error == null ? const CircularProgressIndicator() : Text(_error!)),
      );
    }
    final cuotas = (_datos?['cuotas'] as List?) ?? const [];
    final pagos = (_datos?['pagos'] as List?) ?? const [];
    final estado = mapa['estado']?.toString() ?? '';
    final puedeCobrar = _datos?['cobros'] == true && estado == 'activo' && entero(mapa['saldo']) > 0;
    final lat = numero(mapa['latitud']);
    final lng = numero(mapa['longitud']);

    return Scaffold(
      appBar: AppBar(title: Text(mapa['cliente']?.toString() ?? 'Préstamo')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text('${mapa['frecuencia_nombre']} · $estado', style: Theme.of(context).textTheme.labelLarge),
          Text('Saldo ${pesos(mapa['saldo'])}', style: Theme.of(context).textTheme.headlineSmall),
          Text('Capital ${pesos(mapa['capital'])} + interés ${pesos(mapa['interes'])}'),
          if ((mapa['direccion']?.toString() ?? '').isNotEmpty) Text(mapa['direccion'].toString()),
          if (lat != null && lng != null)
            TextButton.icon(
              onPressed: () => abrirDestino(lat, lng),
              icon: const Icon(Icons.navigation),
              label: const Text('Cómo llegar'),
            ),
          const SizedBox(height: 12),
          Text('Cuotas', style: Theme.of(context).textTheme.titleMedium),
          for (final item in cuotas)
            if (item is Map)
              ListTile(
                contentPadding: EdgeInsets.zero,
                title: Text('Cuota ${item['numero']} · ${item['fecha']}'),
                subtitle: Text('${item['estado']} · saldo ${pesos(item['saldo'])}'),
              ),
          if (puedeCobrar) ...[
            const SizedBox(height: 8),
            Text('Registrar abono', style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 8),
            TextField(controller: _monto, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Valor del abono')),
            ListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Fecha del abono'),
              subtitle: Text(_fechaTexto),
              onTap: () async {
                final elegida = await showDatePicker(
                  context: context,
                  initialDate: _fecha,
                  firstDate: DateTime(2020),
                  lastDate: DateTime(2100),
                );
                if (elegida != null) setState(() => _fecha = elegida);
              },
            ),
            TextField(controller: _nota, decoration: const InputDecoration(labelText: 'Nota')),
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              value: _guardarGps,
              title: const Text('Guardar la ubicación de este cobro'),
              onChanged: (valor) => setState(() => _guardarGps = valor),
            ),
            FilledButton(onPressed: _ocupado ? null : _pagar, child: const Text('Registrar abono')),
          ],
          if (_datos?['cobros'] == true && estado == 'activo')
            TextButton(
              onPressed: _ocupado
                  ? null
                  : () async {
                      final motivo = await _motivo('Detener cobro');
                      if (motivo == null || motivo.isEmpty) return;
                      await _accion(() => widget.api.detener(widget.prestamoId, motivo));
                    },
              child: const Text('Detener cobro'),
            ),
          if (_datos?['cobros'] == true && estado == 'detenido')
            TextButton(
              onPressed: _ocupado ? null : () => _accion(() => widget.api.reanudar(widget.prestamoId)),
              child: const Text('Reanudar cobro'),
            ),
          if (_datos?['anular'] == true && estado != 'anulado')
            TextButton(
              onPressed: _ocupado
                  ? null
                  : () async {
                      final motivo = await _motivo('Anular préstamo');
                      if (motivo == null || motivo.isEmpty) return;
                      await _accion(() => widget.api.anular(widget.prestamoId, motivo));
                    },
              child: const Text('Anular préstamo'),
            ),
          if (pagos.isNotEmpty) ...[
            const SizedBox(height: 12),
            Text('Abonos', style: Theme.of(context).textTheme.titleMedium),
            for (final item in pagos)
              if (item is Map)
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text('${item['fecha']} · ${pesos(item['monto'])}'),
                  subtitle: Text(
                    [
                      if ((item['nota']?.toString() ?? '').isNotEmpty) item['nota'].toString(),
                      if (numero(item['latitud']) != null) 'Con coordenadas',
                    ].join(' · '),
                  ),
                  trailing: numero(item['latitud']) == null
                      ? null
                      : IconButton(
                          tooltip: 'Ver punto',
                          onPressed: () => abrirDestino(numero(item['latitud'])!, numero(item['longitud'])!),
                          icon: const Icon(Icons.place),
                        ),
                ),
          ],
          if (_error != null) ...[
            const SizedBox(height: 12),
            Text(_error!, style: const TextStyle(color: Color(0xFFA32632))),
          ],
        ],
      ),
    );
  }
}
