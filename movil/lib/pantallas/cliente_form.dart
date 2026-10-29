import 'package:flutter/material.dart';

import '../formato.dart';
import '../servicios/cobros_api.dart';
import '../servicios/ubicacion.dart';

class ClienteForm extends StatefulWidget {
  const ClienteForm({super.key, required this.api, this.cliente});

  final CobrosApi api;
  final Map? cliente;

  @override
  State<ClienteForm> createState() => _ClienteFormState();
}

class _ClienteFormState extends State<ClienteForm> {
  late final TextEditingController _nombre;
  late final TextEditingController _documento;
  late final TextEditingController _telefono;
  late final TextEditingController _direccion;
  late final TextEditingController _notas;
  Punto? _punto;
  String? _error;
  bool _ocupado = false;

  @override
  void initState() {
    super.initState();
    final cliente = widget.cliente;
    _nombre = TextEditingController(text: cliente?['nombre']?.toString() ?? '');
    _documento = TextEditingController(text: cliente?['documento']?.toString() ?? '');
    _telefono = TextEditingController(text: cliente?['telefono']?.toString() ?? '');
    _direccion = TextEditingController(text: cliente?['direccion']?.toString() ?? '');
    _notas = TextEditingController(text: cliente?['notas']?.toString() ?? '');
    final lat = numero(cliente?['latitud']);
    final lng = numero(cliente?['longitud']);
    if (lat != null && lng != null) _punto = Punto(lat, lng);
  }

  @override
  void dispose() {
    _nombre.dispose();
    _documento.dispose();
    _telefono.dispose();
    _direccion.dispose();
    _notas.dispose();
    super.dispose();
  }

  Future<void> _marcar() async {
    setState(() => _error = null);
    try {
      final punto = await puntoActual();
      if (mounted) setState(() => _punto = punto);
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    }
  }

  Future<void> _guardar() async {
    setState(() {
      _ocupado = true;
      _error = null;
    });
    try {
      final idPrevio = widget.cliente == null ? null : entero(widget.cliente!['id']);
      final datos = await widget.api.guardarCliente(
        id: idPrevio,
        nombre: _nombre.text.trim(),
        documento: _documento.text.trim(),
        telefono: _telefono.text.trim(),
        direccion: _direccion.text.trim(),
        notas: _notas.text.trim(),
      );
      final id = idPrevio ?? entero(datos['id']);
      final punto = _punto;
      if (punto != null && id > 0) {
        await widget.api.ubicarCliente(id, punto.latitud, punto.longitud);
      }
      if (mounted) Navigator.of(context).pop(true);
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    } finally {
      if (mounted) setState(() => _ocupado = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final punto = _punto;
    return Scaffold(
      appBar: AppBar(title: Text(widget.cliente == null ? 'Nuevo cliente' : 'Editar cliente')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          TextField(controller: _nombre, decoration: const InputDecoration(labelText: 'Nombre')),
          const SizedBox(height: 12),
          TextField(controller: _documento, decoration: const InputDecoration(labelText: 'Documento')),
          const SizedBox(height: 12),
          TextField(controller: _telefono, keyboardType: TextInputType.phone, decoration: const InputDecoration(labelText: 'Teléfono')),
          const SizedBox(height: 12),
          TextField(controller: _direccion, decoration: const InputDecoration(labelText: 'Dirección de cobro')),
          const SizedBox(height: 12),
          TextField(controller: _notas, maxLines: 3, decoration: const InputDecoration(labelText: 'Notas')),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: _marcar,
            icon: const Icon(Icons.my_location),
            label: Text(punto == null ? 'Tomar coordenadas' : 'Ubicación lista'),
          ),
          if (punto != null)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Text('${punto.latitud.toStringAsFixed(5)}, ${punto.longitud.toStringAsFixed(5)}'),
            ),
          const SizedBox(height: 16),
          FilledButton(onPressed: _ocupado ? null : _guardar, child: const Text('Guardar cliente')),
          if (_error != null) ...[
            const SizedBox(height: 12),
            Text(_error!, style: const TextStyle(color: Color(0xFFA32632))),
          ],
        ],
      ),
    );
  }
}
