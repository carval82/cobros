import 'package:flutter/material.dart';

import '../servicios/cobros_api.dart';
import 'acerca.dart';
import 'usuarios.dart';

class Inicio extends StatefulWidget {
  const Inicio({super.key, required this.api});

  final CobrosApi api;

  @override
  State<Inicio> createState() => _InicioState();
}

class _InicioState extends State<Inicio> {
  Map<String, dynamic>? _sesion;
  String? _error;

  @override
  void initState() {
    super.initState();
    _cargar();
  }

  Future<void> _cargar() async {
    try {
      final datos = await widget.api.sesion();
      if (mounted) setState(() => _sesion = datos);
    } catch (e) {
      if (mounted) setState(() => _error = '$e'.replaceFirst('Exception: ', ''));
    }
  }

  @override
  Widget build(BuildContext context) {
    final sesion = _sesion;
    final rol = sesion?['rol']?.toString() ?? '';
    final puedeUsuarios = sesion?['usuarios'] == true;
    final puedePrestamos = sesion?['prestamos'] == true;
    final puedeCobros = sesion?['cobros'] == true;

    return Scaffold(
      appBar: AppBar(
        title: Text(sesion?['empresa']?.toString() ?? 'Cobras'),
        actions: [
          IconButton(
            tooltip: 'Acerca de',
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => Acerca(api: widget.api)),
              );
            },
            icon: const Icon(Icons.info_outline),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text(rol.isEmpty ? 'Cobras' : _etiqueta(rol), style: Theme.of(context).textTheme.labelLarge),
          const SizedBox(height: 8),
          Text(sesion?['nombre']?.toString() ?? 'Hoy', style: Theme.of(context).textTheme.headlineLarge),
          if (sesion?['direccion'] != null) ...[
            const SizedBox(height: 12),
            SelectableText(sesion!['direccion'].toString()),
          ],
          const SizedBox(height: 16),
          if (puedePrestamos)
            const _Tarjeta(titulo: 'Préstamos', detalle: 'Puedes abrir préstamos de un cliente. Cada cobro queda a su nombre.'),
          if (puedeCobros)
            const _Tarjeta(titulo: 'Cobros', detalle: 'Registras el abono del día sobre el préstamo que corresponde.'),
          if (!puedePrestamos && !puedeCobros && rol == 'consulta')
            const _Tarjeta(titulo: 'Consulta', detalle: 'Este rol solo mira la cartera. No crea préstamos ni cobra.'),
          if (rol == 'maestro')
            const _Tarjeta(titulo: 'Maestro', detalle: 'Las empresas se crean en el panel de la web.'),
          const _Tarjeta(titulo: 'Demo', detalle: 'Cinco préstamos. La clave en Acerca de abre la versión completa.'),
          if (puedeUsuarios) ...[
            const SizedBox(height: 8),
            FilledButton(
              onPressed: () {
                Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => Usuarios(api: widget.api)),
                );
              },
              child: const Text('Usuarios y roles'),
            ),
          ],
          if (_error != null) ...[
            const SizedBox(height: 12),
            Text(_error!, style: const TextStyle(color: Color(0xFFA32632))),
          ],
        ],
      ),
    );
  }

  String _etiqueta(String rol) {
    switch (rol) {
      case 'administrador':
        return 'Administrador';
      case 'cobrador':
        return 'Cobrador';
      case 'consulta':
        return 'Consulta';
      case 'maestro':
        return 'Maestro';
      default:
        return rol;
    }
  }
}

class _Tarjeta extends StatelessWidget {
  const _Tarjeta({required this.titulo, required this.detalle});

  final String titulo;
  final String detalle;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: const Color(0xFFFFFAF2),
      child: ListTile(
        title: Text(titulo),
        subtitle: Text(detalle),
      ),
    );
  }
}
