import 'package:flutter/material.dart';

import '../servicios/cobros_api.dart';

class Usuarios extends StatefulWidget {
  const Usuarios({super.key, required this.api});

  final CobrosApi api;

  @override
  State<Usuarios> createState() => _UsuariosState();
}

class _UsuariosState extends State<Usuarios> {
  Map<String, dynamic>? _datos;
  String? _error;
  bool _ocupado = false;
  String _rol = 'cobrador';
  final _nombre = TextEditingController();
  final _correo = TextEditingController();
  final _clave = TextEditingController();

  @override
  void initState() {
    super.initState();
    _cargar();
  }

  @override
  void dispose() {
    _nombre.dispose();
    _correo.dispose();
    _clave.dispose();
    super.dispose();
  }

  Future<void> _cargar() async {
    try {
      final datos = await widget.api.usuarios();
      if (mounted) setState(() => _datos = datos);
    } catch (e) {
      if (mounted) setState(() => _error = '$e'.replaceFirst('Exception: ', ''));
    }
  }

  Future<void> _crear() async {
    final token = _datos?['token']?.toString() ?? '';
    setState(() {
      _ocupado = true;
      _error = null;
    });
    try {
      await widget.api.crearUsuario(
        nombre: _nombre.text.trim(),
        correo: _correo.text.trim(),
        clave: _clave.text,
        rol: _rol,
        token: token,
      );
      _nombre.clear();
      _correo.clear();
      _clave.clear();
      await _cargar();
    } catch (e) {
      if (mounted) setState(() => _error = '$e'.replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => _ocupado = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final lista = (_datos?['usuarios'] as List?) ?? const [];
    final roles = _datos?['roles'];
    final opciones = roles is Map ? roles.keys.map((e) => e.toString()).toList() : <String>['administrador', 'cobrador', 'consulta'];

    return Scaffold(
      appBar: AppBar(title: const Text('Usuarios')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text('El administrador abre la empresa. El cobrador cobra. Consulta solo mira.', style: Theme.of(context).textTheme.bodyMedium),
          const SizedBox(height: 16),
          TextField(controller: _nombre, decoration: const InputDecoration(labelText: 'Nombre')),
          const SizedBox(height: 12),
          TextField(controller: _correo, decoration: const InputDecoration(labelText: 'Correo')),
          const SizedBox(height: 12),
          TextField(controller: _clave, obscureText: true, decoration: const InputDecoration(labelText: 'Clave')),
          const SizedBox(height: 12),
          DropdownButtonFormField<String>(
            value: opciones.contains(_rol) ? _rol : opciones.first,
            decoration: const InputDecoration(labelText: 'Rol'),
            items: [
              for (final rol in opciones)
                DropdownMenuItem(value: rol, child: Text(_etiqueta(rol, roles))),
            ],
            onChanged: (valor) {
              if (valor != null) setState(() => _rol = valor);
            },
          ),
          const SizedBox(height: 12),
          FilledButton(
            onPressed: _ocupado ? null : _crear,
            child: const Text('Crear usuario'),
          ),
          const SizedBox(height: 20),
          for (final item in lista)
            if (item is Map)
              ListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(item['nombre']?.toString() ?? ''),
                subtitle: Text(item['correo']?.toString() ?? ''),
                trailing: Text(_etiqueta(item['rol']?.toString() ?? '', roles)),
              ),
          if (_error != null)
            Text(_error!, style: const TextStyle(color: Color(0xFFA32632))),
        ],
      ),
    );
  }

  String _etiqueta(String rol, dynamic roles) {
    if (roles is Map && roles[rol] != null) return roles[rol].toString();
    return rol;
  }
}
