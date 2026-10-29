import 'package:flutter/material.dart';

import '../servicios/cobros_api.dart';

class Acerca extends StatefulWidget {
  const Acerca({super.key, required this.api});

  final CobrosApi api;

  @override
  State<Acerca> createState() => _AcercaState();
}

class _AcercaState extends State<Acerca> {
  Map<String, dynamic>? _datos;
  String? _error;
  final _clave = TextEditingController();
  bool _ocupado = false;

  @override
  void initState() {
    super.initState();
    _cargar();
  }

  @override
  void dispose() {
    _clave.dispose();
    super.dispose();
  }

  Future<void> _cargar() async {
    setState(() => _error = null);
    try {
      final datos = await widget.api.acerca();
      if (mounted) setState(() => _datos = datos);
    } catch (e) {
      if (mounted) setState(() => _error = '$e');
    }
  }

  Future<void> _activar() async {
    setState(() => _ocupado = true);
    try {
      await widget.api.activar(_clave.text.trim());
      await _cargar();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Clave enviada. Revisa si ya quedó completa.')),
        );
      }
    } catch (e) {
      if (mounted) setState(() => _error = '$e');
    } finally {
      if (mounted) setState(() => _ocupado = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final datos = _datos;
    final completa = datos?['completa'] == true;
    final limite = datos?['limite'] ?? 5;
    final usados = datos?['usados'] ?? 0;

    return Scaffold(
      appBar: AppBar(title: const Text('Acerca de')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text(
            completa ? 'Versión completa' : 'Versión demo',
            style: Theme.of(context).textTheme.headlineMedium,
          ),
          const SizedBox(height: 8),
          Text(datos?['nombre']?.toString() ?? 'Empresa'),
          const SizedBox(height: 8),
          Text(
            completa
                ? 'Plan ${datos?['plan']} hasta ${datos?['vence'] ?? 'sin fecha'}. Sin límite de préstamos.'
                : 'Demo: $usados de $limite préstamos. La clave pasa esta empresa a versión completa.',
          ),
          if (datos?['codigo_solicitud'] != null) ...[
            const SizedBox(height: 16),
            SelectableText('Solicitud ${datos!['codigo_solicitud']}'),
          ],
          const SizedBox(height: 20),
          TextField(
            controller: _clave,
            decoration: const InputDecoration(labelText: 'Clave de activación'),
          ),
          const SizedBox(height: 12),
          FilledButton(
            onPressed: _ocupado ? null : _activar,
            child: const Text('Activar versión completa'),
          ),
          if (_error != null) ...[
            const SizedBox(height: 12),
            Text(_error!, style: const TextStyle(color: Color(0xFFA32632))),
          ],
        ],
      ),
    );
  }
}
