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
  int? _caja;

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
      final datos = await widget.api.hoy(caja: _caja);
      if (mounted) setState(() => _datos = datos);
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    } finally {
      if (mounted) setState(() => _cargando = false);
    }
  }

  Future<void> _entrar(int id) async {
    setState(() => _caja = id);
    await _cargar();
  }

  Future<void> _volver() async {
    setState(() => _caja = null);
    await _cargar();
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
    final dentro = _caja != null && datos?['mostrar'] == true;
    return RefreshIndicator(
      onRefresh: _cargar,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
        children: [
          if (!dentro) ..._lista(datos) else ..._cobro(datos!),
          if (_error != null) Text(_error!, style: const TextStyle(color: peligro)),
        ],
      ),
    );
  }

  List<Widget> _lista(Map<String, dynamic>? datos) {
    final cobros = (datos?['cobros'] as List?) ?? const [];
    return [
      const Text('Hoy', style: TextStyle(fontSize: 32, fontWeight: FontWeight.w800, height: 1)),
      const SizedBox(height: 6),
      const Text(
        'Entra a un cobro por su nombre. Ahí se ve todo lo de ese cobro.',
        style: TextStyle(color: muted),
      ),
      const Titulo('COBROS'),
      if (cobros.isEmpty) const Text('Todavía no hay cobros abiertos.', style: TextStyle(color: muted)),
      for (final item in cobros)
        if (item is Map)
          Lamina(
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    item['nombre']?.toString() ?? 'Cobro',
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                  ),
                ),
                FilledButton(
                  onPressed: () => _entrar(entero(item['id'])),
                  child: const Text('Entrar'),
                ),
              ],
            ),
          ),
    ];
  }

  List<Widget> _cobro(Map<String, dynamic> datos) {
    final hoy = (datos['hoy'] as List?) ?? const [];
    final atrasadas = (datos['atrasadas'] as List?) ?? const [];
    final movimientos = (datos['movimientos'] as List?) ?? const [];
    final detenidos = entero(datos['detenidos']);
    return [
      Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(datos['nombre']?.toString() ?? 'Cobro', style: const TextStyle(fontSize: 32, fontWeight: FontWeight.w800, height: 1)),
                const SizedBox(height: 4),
                Text(datos['equipo']?.toString().isNotEmpty == true ? datos['equipo'].toString() : 'Solo', style: const TextStyle(color: muted)),
              ],
            ),
          ),
        ],
      ),
      const SizedBox(height: 10),
      OutlinedButton(onPressed: _volver, child: const Text('Volver a los cobros')),
      const SizedBox(height: 12),
      GridView.count(
        crossAxisCount: 2,
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        mainAxisSpacing: 10,
        crossAxisSpacing: 10,
        childAspectRatio: 1.35,
        children: [
          Cifra(titulo: 'En caja', valor: pesos(datos['en_caja']), destacada: true),
          Cifra(titulo: 'Cartera en la calle', valor: pesos(datos['cartera'])),
          Cifra(titulo: 'Para cobrar hoy', valor: pesos(datos['cobrar_hoy'])),
          Cifra(titulo: 'Atrasado', valor: pesos(datos['mora']), alerta: entero(datos['mora']) > 0),
          Cifra(
            titulo: detenidos > 0 ? 'Cobrado hoy · $detenidos detenidos' : 'Cobrado hoy',
            valor: pesos(datos['cobrado_hoy']),
          ),
        ],
      ),
      const Titulo('CUOTAS DE HOY'),
      if (hoy.isEmpty) const Text('Hoy no hay cuotas por cobrar.', style: TextStyle(color: muted)),
      for (final item in hoy)
        if (item is Map) _CuotaTile(item: item, onTap: () => _abrir(entero(item['prestamo_id']))),
      const Titulo('ATRASADOS'),
      if (atrasadas.isEmpty) const Text('No hay cuotas vencidas.', style: TextStyle(color: muted)),
      for (final item in atrasadas)
        if (item is Map) _CuotaTile(item: item, onTap: () => _abrir(entero(item['prestamo_id'])), mora: true),
      const Titulo('HISTORIAL RECIENTE'),
      if (movimientos.isEmpty) const Text('Todavía no hay movimientos.', style: TextStyle(color: muted)),
      for (final item in movimientos)
        if (item is Map)
          Lamina(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(item['fecha']?.toString() ?? '', style: const TextStyle(color: muted, fontSize: 12)),
                Text(item['cliente']?.toString() ?? '', style: const TextStyle(fontWeight: FontWeight.w800)),
                if ((item['descripcion']?.toString() ?? '').isNotEmpty)
                  Text(item['descripcion'].toString(), style: const TextStyle(color: muted)),
              ],
            ),
          ),
    ];
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
