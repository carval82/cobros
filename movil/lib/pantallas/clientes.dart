import 'package:flutter/material.dart';

import '../formato.dart';
import '../servicios/cobros_api.dart';
import 'cliente.dart';
import 'cliente_form.dart';

class Clientes extends StatefulWidget {
  const Clientes({super.key, required this.api, required this.puedeEscribir, required this.puedePrestamos});

  final CobrosApi api;
  final bool puedeEscribir;
  final bool puedePrestamos;

  @override
  State<Clientes> createState() => _ClientesState();
}

class _ClientesState extends State<Clientes> {
  final _buscar = TextEditingController();
  List _lista = const [];
  String? _error;
  bool _cargando = true;

  @override
  void initState() {
    super.initState();
    _cargar();
  }

  @override
  void dispose() {
    _buscar.dispose();
    super.dispose();
  }

  Future<void> _cargar() async {
    setState(() => _error = null);
    try {
      final datos = await widget.api.clientes(q: _buscar.text);
      if (mounted) setState(() => _lista = (datos['clientes'] as List?) ?? const []);
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    } finally {
      if (mounted) setState(() => _cargando = false);
    }
  }

  Future<void> _nuevo() async {
    final creo = await Navigator.of(context).push<bool>(
      MaterialPageRoute(builder: (_) => ClienteForm(api: widget.api)),
    );
    if (creo == true) await _cargar();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            child: TextField(
              controller: _buscar,
              decoration: const InputDecoration(labelText: 'Buscar cliente', prefixIcon: Icon(Icons.search)),
              onSubmitted: (_) => _cargar(),
            ),
          ),
          Expanded(
            child: _cargando
                ? const Center(child: CircularProgressIndicator())
                : RefreshIndicator(
                    onRefresh: _cargar,
                    child: ListView(
                      padding: const EdgeInsets.all(16),
                      children: [
                        if (_lista.isEmpty) const Text('Todavía no hay clientes.'),
                        for (final item in _lista)
                          if (item is Map)
                            Card(
                              color: const Color(0xFFFFFAF2),
                              child: ListTile(
                                title: Text(item['nombre']?.toString() ?? ''),
                                subtitle: Text(
                                  [
                                    if ((item['direccion']?.toString() ?? '').isNotEmpty) item['direccion'].toString(),
                                    if ((item['telefono']?.toString() ?? '').isNotEmpty) item['telefono'].toString(),
                                    'Saldo ${pesos(item['saldo'])}',
                                    numero(item['latitud']) == null ? 'Sin GPS' : 'En el mapa',
                                  ].join('\n'),
                                ),
                                onTap: () async {
                                  await Navigator.of(context).push(
                                    MaterialPageRoute(
                                      builder: (_) => ClienteVista(
                                        api: widget.api,
                                        clienteId: entero(item['id']),
                                        puedeEscribir: widget.puedeEscribir,
                                        puedePrestamos: widget.puedePrestamos,
                                      ),
                                    ),
                                  );
                                  await _cargar();
                                },
                              ),
                            ),
                        if (_error != null)
                          Text(_error!, style: const TextStyle(color: Color(0xFFA32632))),
                      ],
                    ),
                  ),
          ),
        ],
      ),
      floatingActionButton: widget.puedeEscribir
          ? FloatingActionButton.extended(
              onPressed: _nuevo,
              backgroundColor: const Color(0xFFE4C16A),
              foregroundColor: const Color(0xFF1C140C),
              icon: const Icon(Icons.person_add),
              label: const Text('Cliente'),
            )
          : null,
    );
  }
}
