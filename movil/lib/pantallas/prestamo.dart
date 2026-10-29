import 'package:flutter/material.dart';

import '../formato.dart';
import '../piezas.dart';
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
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
        children: [
          Lamina(
            relleno: tinta,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Pastilla('${mapa['frecuencia_nombre']} · $estado', tono: senal),
                const SizedBox(height: 14),
                const Text('Saldo', style: TextStyle(color: Color(0xFFCBB892), fontSize: 13)),
                Text(pesos(mapa['saldo']), style: const TextStyle(color: senal, fontSize: 34, fontWeight: FontWeight.w800, height: 1.05)),
                const SizedBox(height: 6),
                Text('Capital ${pesos(mapa['capital'])} + interés ${pesos(mapa['interes'])}', style: const TextStyle(color: Color(0xFFE7DCCB))),
                if ((mapa['direccion']?.toString() ?? '').isNotEmpty) ...[
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      const Icon(Icons.place_outlined, color: senal, size: 18),
                      const SizedBox(width: 6),
                      Expanded(child: Text(mapa['direccion'].toString(), style: const TextStyle(color: Colors.white))),
                    ],
                  ),
                ],
                if (lat != null && lng != null) ...[
                  const SizedBox(height: 14),
                  FilledButton.icon(
                    onPressed: () => abrirDestino(lat, lng),
                    icon: const Icon(Icons.navigation),
                    label: const Text('Cómo llegar'),
                  ),
                ],
              ],
            ),
          ),
          const Titulo('CUOTAS'),
          for (final item in cuotas)
            if (item is Map)
              Lamina(
                child: Row(
                  children: [
                    Monograma('${item['numero']}', oscuro: item['estado'] == 'pagada'),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Cuota ${item['numero']}', style: const TextStyle(fontWeight: FontWeight.w800)),
                          Text('${item['fecha']}', style: const TextStyle(color: muted)),
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
          if (puedeCobrar) ...[
            const Titulo('REGISTRAR ABONO'),
            Lamina(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  TextField(
                    controller: _monto,
                    keyboardType: TextInputType.number,
                    style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w800),
                    decoration: const InputDecoration(labelText: 'Valor del abono'),
                    onChanged: (_) => setState(() {}),
                  ),
                  if (int.tryParse(_monto.text) != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 8),
                      child: Text('Vas a registrar ${pesos(int.parse(_monto.text))}', style: const TextStyle(color: muted)),
                    ),
                  const SizedBox(height: 8),
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const Icon(Icons.calendar_today, color: oro),
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
                    activeColor: oro,
                    title: const Text('Guardar la ubicación de este cobro'),
                    onChanged: (valor) => setState(() => _guardarGps = valor),
                  ),
                  FilledButton(onPressed: _ocupado ? null : _pagar, child: const Text('Registrar abono')),
                ],
              ),
            ),
          ],
          if ((_datos?['cobros'] == true && (estado == 'activo' || estado == 'detenido')) || (_datos?['anular'] == true && estado != 'anulado'))
            const Titulo('ACCIONES'),
          if (_datos?['cobros'] == true && estado == 'activo')
            _Accion(
              icono: Icons.pause_circle_outline,
              titulo: 'Detener cobro',
              detalle: 'La mora se congela hasta reanudarlo.',
              onTap: _ocupado
                  ? null
                  : () async {
                      final motivo = await _motivo('Detener cobro');
                      if (motivo == null || motivo.isEmpty) return;
                      await _accion(() => widget.api.detener(widget.prestamoId, motivo));
                    },
            ),
          if (_datos?['cobros'] == true && estado == 'detenido')
            _Accion(
              icono: Icons.play_circle_outline,
              titulo: 'Reanudar cobro',
              detalle: 'Las cuotas pendientes se corren los días detenidos.',
              onTap: _ocupado ? null : () => _accion(() => widget.api.reanudar(widget.prestamoId)),
            ),
          if (_datos?['anular'] == true && estado != 'anulado')
            _Accion(
              icono: Icons.block,
              titulo: 'Anular préstamo',
              detalle: 'Sale de la cartera. No se puede deshacer desde aquí.',
              peligro: true,
              onTap: _ocupado
                  ? null
                  : () async {
                      final motivo = await _motivo('Anular préstamo');
                      if (motivo == null || motivo.isEmpty) return;
                      await _accion(() => widget.api.anular(widget.prestamoId, motivo));
                    },
            ),
          if (pagos.isNotEmpty) ...[
            const Titulo('ABONOS'),
            for (final item in pagos)
              if (item is Map)
                Lamina(
                  child: Row(
                    children: [
                      const Monograma('\$', oscuro: true),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(pesos(item['monto']), style: const TextStyle(fontWeight: FontWeight.w800)),
                            Text(
                              [
                                '${item['fecha']}',
                                if ((item['nota']?.toString() ?? '').isNotEmpty) item['nota'].toString(),
                                if (numero(item['latitud']) != null) 'Con coordenadas',
                              ].join(' · '),
                              style: const TextStyle(color: muted),
                            ),
                          ],
                        ),
                      ),
                      if (numero(item['latitud']) != null)
                        IconButton(
                          tooltip: 'Ver punto',
                          onPressed: () => abrirDestino(numero(item['latitud'])!, numero(item['longitud'])!),
                          icon: const Icon(Icons.place, color: oro),
                        ),
                    ],
                  ),
                ),
          ],
          if (_error != null) Text(_error!, style: const TextStyle(color: peligro)),
        ],
      ),
    );
  }
}

class _Accion extends StatelessWidget {
  const _Accion({required this.icono, required this.titulo, required this.detalle, required this.onTap, this.peligro = false});

  final IconData icono;
  final String titulo;
  final String detalle;
  final VoidCallback? onTap;
  final bool peligro;

  @override
  Widget build(BuildContext context) {
    final color = peligro ? const Color(0xFFA32632) : tinta;
    return Lamina(
      child: InkWell(
        onTap: onTap,
        child: Row(
          children: [
            Icon(icono, color: color),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(titulo, style: TextStyle(fontWeight: FontWeight.w800, color: color)),
                  Text(detalle, style: const TextStyle(color: muted)),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, color: muted),
          ],
        ),
      ),
    );
  }
}
