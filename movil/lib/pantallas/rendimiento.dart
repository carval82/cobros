import 'package:flutter/material.dart';

import '../formato.dart';
import '../piezas.dart';
import '../servicios/cobros_api.dart';

class RendimientoVista extends StatefulWidget {
  const RendimientoVista({super.key, required this.api, required this.propio});

  final CobrosApi api;
  final bool propio;

  @override
  State<RendimientoVista> createState() => _RendimientoVistaState();
}

class _RendimientoVistaState extends State<RendimientoVista> {
  Map<String, dynamic>? _datos;
  String? _error;

  @override
  void initState() {
    super.initState();
    _cargar();
  }

  Future<void> _cargar() async {
    try {
      final datos = widget.propio ? await widget.api.miRendimiento() : await widget.api.rendimiento();
      if (mounted) setState(() => _datos = datos);
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    }
  }

  @override
  Widget build(BuildContext context) {
    final datos = _datos;
    final cobradores = (datos?['cobradores'] as List?) ?? const [];

    return Scaffold(
      appBar: AppBar(title: Text(widget.propio ? 'Mi rendimiento' : 'Rendimiento')),
      body: datos == null
          ? Center(child: _error == null ? const CircularProgressIndicator() : Padding(padding: const EdgeInsets.all(24), child: Text(_error!)))
          : ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
              children: [
                Text('Del ${datos['desde']} al ${datos['hasta']}', style: const TextStyle(color: muted)),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(child: Cifra(titulo: 'Cobrado', valor: pesos(datos['cobrado']), destacada: true)),
                    const SizedBox(width: 12),
                    Expanded(child: Cifra(titulo: widget.propio ? 'Te corresponde' : 'A pagar', valor: pesos(datos['comision']))),
                  ],
                ),
                const SizedBox(height: 8),
                Text('${datos['abonos'] ?? 0} abonos en el periodo', style: const TextStyle(color: muted)),
                const Titulo('COBRADORES'),
                if (cobradores.isEmpty)
                  const Text('Todavía no hay abonos de un cobrador en este mes.'),
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
                                  '${item['abonos']} abonos · ${item['porcentaje']}% vigente',
                                  style: const TextStyle(color: muted),
                                ),
                              ],
                            ),
                          ),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(pesos(item['cobrado']), style: const TextStyle(fontWeight: FontWeight.w800)),
                              Text(pesos(item['comision']), style: const TextStyle(color: ok, fontWeight: FontWeight.w700)),
                            ],
                          ),
                        ],
                      ),
                    ),
              ],
            ),
    );
  }
}
