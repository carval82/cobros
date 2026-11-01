import 'package:flutter/material.dart';

import '../formato.dart';
import '../piezas.dart';
import '../servicios/cobros_api.dart';

class CuadreVista extends StatefulWidget {
  const CuadreVista({super.key, required this.api});

  final CobrosApi api;

  @override
  State<CuadreVista> createState() => _CuadreVistaState();
}

class _CuadreVistaState extends State<CuadreVista> {
  Map<String, dynamic>? _datos;
  String? _error;
  int _indice = 0;
  bool _ocupado = false;
  final _valor = TextEditingController();
  final _gasto = TextEditingController();
  final _nota = TextEditingController();
  final _refuerzo = TextEditingController();

  @override
  void initState() {
    super.initState();
    _cargar();
  }

  @override
  void dispose() {
    _valor.dispose();
    _gasto.dispose();
    _nota.dispose();
    _refuerzo.dispose();
    super.dispose();
  }

  Future<void> _cargar() async {
    try {
      final datos = await widget.api.cuadre();
      if (!mounted) return;
      setState(() {
        _datos = datos;
        _error = null;
        final cajas = (datos['cajas'] as List?) ?? const [];
        if (_indice >= cajas.length) _indice = 0;
      });
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    }
  }

  Future<void> _abrir(Map? mapa) async {
    setState(() => _ocupado = true);
    try {
      await widget.api.abrirCobro(
        valorInicial: int.parse(_valor.text),
        cajaId: mapa == null ? null : entero(mapa['id']),
      );
      _valor.clear();
      await _cargar();
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    } finally {
      if (mounted) setState(() => _ocupado = false);
    }
  }

  Future<void> _cerrar(int id, String decision) async {
    setState(() => _ocupado = true);
    try {
      await widget.api.cerrarDia(cajaId: id, decision: decision, fecha: fechaHoy());
      await _cargar();
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    } finally {
      if (mounted) setState(() => _ocupado = false);
    }
  }

  Future<void> _guardarGasto(int id) async {
    setState(() => _ocupado = true);
    try {
      await widget.api.gastoCaja(
        cajaId: id,
        monto: int.parse(_gasto.text),
        fecha: fechaHoy(),
        nota: _nota.text.trim(),
      );
      _gasto.clear();
      _nota.clear();
      await _cargar();
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    } finally {
      if (mounted) setState(() => _ocupado = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final datos = _datos;
    final cajas = (datos?['cajas'] as List?) ?? const [];
    final caja = cajas.isEmpty ? null : cajas[_indice];
    final mapa = caja is Map ? caja : null;

    return Scaffold(
      appBar: AppBar(title: const Text('Cuadre del día')),
      body: datos == null
          ? Center(child: _error == null ? const CircularProgressIndicator() : Padding(padding: const EdgeInsets.all(24), child: Text(_error!)))
          : ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
              children: [
                const Text('Un cobro se abre con un valor inicial. Esa cifra es la base. Al cerrar el día queda el efectivo del siguiente: retiras la ganancia o la sumas a la base.'),
                const SizedBox(height: 8),
                Text(datos['fecha']?.toString() ?? '', style: const TextStyle(color: muted)),
                if (cajas.length > 1) ...[
                  const SizedBox(height: 12),
                  DropdownButtonFormField<int>(
                    value: _indice,
                    decoration: const InputDecoration(labelText: 'Cobro'),
                    items: [
                      for (var i = 0; i < cajas.length; i++)
                        if (cajas[i] is Map)
                          DropdownMenuItem(
                            value: i,
                            child: Text('${(cajas[i] as Map)['nombre']} · ${(cajas[i] as Map)['tipo']}'),
                          ),
                    ],
                    onChanged: (valor) {
                      if (valor == null) return;
                      setState(() => _indice = valor);
                    },
                  ),
                ],
                if (mapa == null || mapa['sin_apertura'] == true) ...[
                  const Titulo('APERTURA'),
                  TextField(
                    controller: _valor,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Valor inicial'),
                  ),
                  const SizedBox(height: 8),
                  FilledButton(
                    onPressed: _ocupado ? null : () => _abrir(mapa),
                    child: const Text('Abrir cobro'),
                  ),
                ] else ...[
                  const SizedBox(height: 12),
                  Cifra(titulo: 'Base', valor: pesos(mapa['base']), destacada: true),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(child: Cifra(titulo: 'Valor inicial', valor: pesos(mapa['valor_inicial']))),
                      const SizedBox(width: 8),
                      Expanded(child: Cifra(titulo: 'En caja', valor: pesos(mapa['en_caja']))),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(child: Cifra(titulo: 'Recaudado', valor: pesos(mapa['recogido_hoy']))),
                      const SizedBox(width: 8),
                      Expanded(child: Cifra(titulo: 'Ganancia', valor: pesos(mapa['ganancia']))),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'Para mañana ${pesos(mapa['jornada_cerrada'] == true ? mapa['valor_siguiente'] : mapa['cierre'])}. Si retiras la ganancia, la base se sostiene.',
                    style: const TextStyle(color: muted),
                  ),
                  if (mapa['puede_abrir_dia'] == true && mapa['edita'] == true) ...[
                    const SizedBox(height: 12),
                    FilledButton(
                      onPressed: _ocupado
                          ? null
                          : () async {
                              setState(() => _ocupado = true);
                              try {
                                await widget.api.abrirDia(entero(mapa['id']));
                                await _cargar();
                              } catch (e) {
                                if (mounted) setState(() => _error = textoError(e));
                              } finally {
                                if (mounted) setState(() => _ocupado = false);
                              }
                            },
                      child: Text('Abrir el día con ${pesos(mapa['en_caja'])}'),
                    ),
                  ],
                  if (mapa['edita'] == true && mapa['jornada_abierta'] == true) ...[
                    const Titulo('CERRAR EL DÍA'),
                    FilledButton(
                      onPressed: _ocupado ? null : () => _cerrar(entero(mapa['id']), 'retirar'),
                      child: const Text('Retirar la ganancia'),
                    ),
                    const SizedBox(height: 8),
                    FilledButton(
                      onPressed: _ocupado ? null : () => _cerrar(entero(mapa['id']), 'sumar'),
                      child: const Text('Sumar la ganancia a la base'),
                    ),
                    const Titulo('SUMAR A LA BASE'),
                    TextField(
                      controller: _refuerzo,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(labelText: 'Dinero que entra a la base'),
                    ),
                    const SizedBox(height: 8),
                    FilledButton(
                      onPressed: _ocupado
                          ? null
                          : () async {
                              setState(() => _ocupado = true);
                              try {
                                await widget.api.refuerzoCaja(
                                  cajaId: entero(mapa['id']),
                                  monto: int.parse(_refuerzo.text),
                                  fecha: fechaHoy(),
                                );
                                _refuerzo.clear();
                                await _cargar();
                              } catch (e) {
                                if (mounted) setState(() => _error = textoError(e));
                              } finally {
                                if (mounted) setState(() => _ocupado = false);
                              }
                            },
                      child: const Text('Sumar a la base'),
                    ),
                    const Titulo('GASTO'),
                    TextField(
                      controller: _gasto,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(labelText: 'Valor del gasto'),
                    ),
                    const SizedBox(height: 8),
                    TextField(
                      controller: _nota,
                      decoration: const InputDecoration(labelText: 'Nota'),
                    ),
                    const SizedBox(height: 8),
                    FilledButton(
                      onPressed: _ocupado ? null : () => _guardarGasto(entero(mapa['id'])),
                      child: const Text('Guardar gasto'),
                    ),
                  ],
                ],
                if (_error != null) ...[
                  const SizedBox(height: 12),
                  Text(_error!, style: const TextStyle(color: peligro)),
                ],
              ],
            ),
    );
  }
}
