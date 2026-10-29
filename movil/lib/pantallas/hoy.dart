import 'package:flutter/material.dart';

import '../formato.dart';
import '../piezas.dart';
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
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
        children: [
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: 10,
            crossAxisSpacing: 10,
            childAspectRatio: 1.55,
            children: [
              Cifra(titulo: 'Cobrar hoy', valor: pesos(datos?['cobrar_hoy']), destacada: true),
              Cifra(titulo: 'Mora', valor: pesos(datos?['mora']), alerta: entero(datos?['mora']) > 0),
              Cifra(titulo: 'Cobrado hoy', valor: pesos(datos?['cobrado_hoy'])),
              Cifra(titulo: 'Cartera', valor: pesos(datos?['cartera'])),
            ],
          ),
          const Titulo('PARA HOY'),
          if (hoy.isEmpty) const Text('No hay cuotas que venzan hoy.', style: TextStyle(color: muted)),
          for (final item in hoy)
            if (item is Map) _CuotaTile(item: item, onTap: () => _abrir(entero(item['prestamo_id']))),
          const Titulo('ATRASADOS'),
          if (atrasadas.isEmpty) const Text('Nadie está en mora.', style: TextStyle(color: muted)),
          for (final item in atrasadas)
            if (item is Map) _CuotaTile(item: item, onTap: () => _abrir(entero(item['prestamo_id'])), mora: true),
          if (_error != null) Text(_error!, style: const TextStyle(color: peligro)),
        ],
      ),
    );
  }
}

class _CuotaTile extends StatelessWidget {
  const _CuotaTile({required this.item, required this.onTap, this.mora = false});

  final Map item;
  final VoidCallback onTap;
  final bool mora;

  @override
  Widget build(BuildContext context) {
    final nombre = item['cliente']?.toString() ?? '';
    final direccion = item['direccion']?.toString() ?? '';
    final lat = numero(item['latitud']);
    final lng = numero(item['longitud']);
    return Lamina(
      child: InkWell(
        onTap: onTap,
        child: Row(
          children: [
            Monograma(nombre),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(nombre, style: const TextStyle(fontWeight: FontWeight.w800)),
                  if (direccion.isNotEmpty) Text(direccion, style: const TextStyle(color: muted)),
                  const SizedBox(height: 6),
                  Pastilla(mora ? 'Atrasada · cuota ${item['numero']}' : 'Cuota ${item['numero']}', tono: mora ? peligro : tinta),
                ],
              ),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(pesos(item['saldo']), style: const TextStyle(fontWeight: FontWeight.w800)),
                if (lat != null && lng != null)
                  IconButton(
                    tooltip: 'Ir',
                    onPressed: () => abrirDestino(lat, lng),
                    icon: const Icon(Icons.navigation, color: oro),
                  )
                else
                  const Icon(Icons.chevron_right, color: muted),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
