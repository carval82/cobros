import 'package:flutter/material.dart';

import '../formato.dart';
import '../servicios/cobros_api.dart';
import '../servicios/ubicacion.dart';
import 'prestamo.dart';

class Hoy extends StatefulWidget {
  const Hoy({super.key, required this.api});

  final CobrosApi api;

  @override
  State<Hoy> createState() => _HoyState();
}

class _HoyState extends State<Hoy> {
  Map<String, dynamic>? _datos;
  String? _error;
  bool _cargando = true;

  @override
  void initState() {
    super.initState();
    _cargar();
  }

  Future<void> _cargar() async {
    setState(() {
      _cargando = true;
      _error = null;
    });
    try {
      final datos = await widget.api.hoy();
      if (mounted) setState(() => _datos = datos);
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    } finally {
      if (mounted) setState(() => _cargando = false);
    }
  }

  Future<void> _abrir(int prestamoId) async {
    await Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => PrestamoVista(api: widget.api, prestamoId: prestamoId)),
    );
    await _cargar();
  }

  @override
  Widget build(BuildContext context) {
    final datos = _datos;
    if (_cargando && datos == null) {
      return const Center(child: CircularProgressIndicator());
    }
    final hoy = (datos?['hoy'] as List?) ?? const [];
    final atrasadas = (datos?['atrasadas'] as List?) ?? const [];

    return RefreshIndicator(
      onRefresh: _cargar,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _Cifra(titulo: 'Cobrar hoy', valor: pesos(datos?['cobrar_hoy'])),
              _Cifra(titulo: 'Mora', valor: pesos(datos?['mora'])),
              _Cifra(titulo: 'Cobrado hoy', valor: pesos(datos?['cobrado_hoy'])),
              _Cifra(titulo: 'Cartera', valor: pesos(datos?['cartera'])),
            ],
          ),
          const SizedBox(height: 16),
          Text('Para hoy', style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 8),
          if (hoy.isEmpty) const Text('No hay cuotas que venzan hoy.'),
          for (final item in hoy)
            if (item is Map) _CuotaTile(item: item, onTap: () => _abrir(entero(item['prestamo_id']))),
          const SizedBox(height: 16),
          Text('Atrasados', style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 8),
          if (atrasadas.isEmpty) const Text('Nadie está en mora.'),
          for (final item in atrasadas)
            if (item is Map) _CuotaTile(item: item, onTap: () => _abrir(entero(item['prestamo_id']))),
          if (_error != null) ...[
            const SizedBox(height: 12),
            Text(_error!, style: const TextStyle(color: Color(0xFFA32632))),
          ],
        ],
      ),
    );
  }
}

class _Cifra extends StatelessWidget {
  const _Cifra({required this.titulo, required this.valor});

  final String titulo;
  final String valor;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 156,
      child: Card(
        color: const Color(0xFFFFFAF2),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(titulo),
              const SizedBox(height: 4),
              Text(valor, style: Theme.of(context).textTheme.titleMedium),
            ],
          ),
        ),
      ),
    );
  }
}

class _CuotaTile extends StatelessWidget {
  const _CuotaTile({required this.item, required this.onTap});

  final Map item;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final direccion = item['direccion']?.toString();
    final lat = numero(item['latitud']);
    final lng = numero(item['longitud']);
    return Card(
      color: const Color(0xFFFFFAF2),
      child: ListTile(
        onTap: onTap,
        title: Text(item['cliente']?.toString() ?? ''),
        subtitle: Text(
          [
            if (direccion != null && direccion.isNotEmpty) direccion,
            'Cuota ${item['numero']} · ${pesos(item['saldo'])}',
          ].join('\n'),
        ),
        trailing: lat == null || lng == null
            ? const Icon(Icons.chevron_right)
            : IconButton(
                tooltip: 'Ir',
                onPressed: () => abrirDestino(lat, lng),
                icon: const Icon(Icons.navigation),
              ),
      ),
    );
  }
}
