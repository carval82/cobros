import 'dart:async';

import 'package:flutter/material.dart';

import '../formato.dart';
import '../piezas.dart';
import '../servicios/cobros_api.dart';
import '../servicios/sesion_local.dart';
import '../servicios/ubicacion.dart';
import 'acerca.dart';
import 'en_vivo.dart';
import 'entrada.dart';
import 'clientes.dart';
import 'hoy.dart';
import 'rendimiento.dart';
import 'ruta.dart';
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
  int _pestana = 0;
  Timer? _rastreo;

  @override
  void dispose() {
    _rastreo?.cancel();
    super.dispose();
  }

  @override
  void initState() {
    super.initState();
    _cargar();
  }

  Future<void> _cargar() async {
    try {
      final datos = await widget.api.sesion();
      await guardarConfiguracion(widget.api, correo: datos['correo']?.toString());
      if (!mounted) return;
      setState(() => _sesion = datos);
      _seguir(datos);
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    }
  }

  void _seguir(Map<String, dynamic> datos) {
    if (datos['rol']?.toString() != 'cobrador') {
      _rastreo?.cancel();
      _rastreo = null;
      return;
    }
    if (_rastreo != null) return;
    _enviarPunto();
    _rastreo = Timer.periodic(const Duration(seconds: 25), (_) => _enviarPunto());
  }

  Future<void> _enviarPunto() async {
    try {
      final punto = await puntoActual();
      await widget.api.reportarUbicacion(punto.latitud, punto.longitud);
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final sesion = _sesion;
    final empresa = sesion?['empresa']?.toString();
    final tenant = sesion?['tenant']?.toString() ?? '';
    final puedeUsuarios = sesion?['usuarios'] == true;
    final puedeCobros = sesion?['cobros'] == true;
    final puedeClientes = sesion?['clientes'] == true;
    final puedePrestamos = sesion?['prestamos'] == true;

    return Scaffold(
      appBar: AppBar(
        title: Text(empresa == null || empresa.isEmpty ? 'Cobras' : empresa),
        actions: [
          IconButton(
            tooltip: 'Acerca de',
            onPressed: () {
              Navigator.of(context).push(MaterialPageRoute(builder: (_) => Acerca(api: widget.api)));
            },
            icon: const Icon(Icons.info_outline),
          ),
        ],
      ),
      body: sesion == null
          ? Center(child: _error == null ? const CircularProgressIndicator() : Padding(padding: const EdgeInsets.all(24), child: Text(_error!)))
          : tenant.isEmpty
              ? const Padding(
                  padding: EdgeInsets.all(24),
                  child: Text('Esta cuenta no está dentro de una empresa. Entra con la dirección /tenant/ que se generó al crearla.'),
                )
              : IndexedStack(
                  index: _pestana,
                  children: [
                    Hoy(api: widget.api),
                    Clientes(api: widget.api, puedeEscribir: puedeClientes, puedePrestamos: puedePrestamos),
                    RutaDia(api: widget.api, puedeEscribir: puedeClientes),
                    _Mas(api: widget.api, sesion: sesion, puedeUsuarios: puedeUsuarios, puedeCobros: puedeCobros),
                  ],
                ),
      bottomNavigationBar: sesion == null || tenant.isEmpty
          ? null
          : NavigationBar(
              selectedIndex: _pestana,
              onDestinationSelected: (indice) => setState(() => _pestana = indice),
              destinations: const [
                NavigationDestination(icon: Icon(Icons.today), label: 'Hoy'),
                NavigationDestination(icon: Icon(Icons.people), label: 'Clientes'),
                NavigationDestination(icon: Icon(Icons.alt_route), label: 'Ruta'),
                NavigationDestination(icon: Icon(Icons.more_horiz), label: 'Más'),
              ],
            ),
    );
  }
}

class _Mas extends StatelessWidget {
  const _Mas({required this.api, required this.sesion, required this.puedeUsuarios, required this.puedeCobros});

  final CobrosApi api;
  final Map<String, dynamic> sesion;
  final bool puedeUsuarios;
  final bool puedeCobros;

  @override
  Widget build(BuildContext context) {
    final direccion = sesion['direccion']?.toString();
    final demo = sesion['demo'] == true;

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
      children: [
        Lamina(
          relleno: tinta,
          child: Row(
            children: [
              Monograma(sesion['nombre']?.toString() ?? '', oscuro: true),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(sesion['nombre']?.toString() ?? '', style: const TextStyle(color: senal, fontSize: 22, fontWeight: FontWeight.w800)),
                    Text(_etiqueta(sesion['rol']?.toString() ?? ''), style: const TextStyle(color: Color(0xFFCBB892))),
                  ],
                ),
              ),
            ],
          ),
        ),
        if (direccion != null && direccion.isNotEmpty)
          Lamina(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Dirección de la empresa', style: TextStyle(color: muted, fontSize: 12)),
                const SizedBox(height: 4),
                SelectableText(direccion, style: const TextStyle(fontWeight: FontWeight.w700)),
              ],
            ),
          ),
        if (demo) ...[
          const SizedBox(height: 12),
          Text('Versión demo: hasta ${sesion['limite_demo']} préstamos. La clave se pega en Acerca de.'),
        ],
        const SizedBox(height: 16),
        if (puedeUsuarios) ...[
          FilledButton(
            onPressed: () {
              Navigator.of(context).push(MaterialPageRoute(builder: (_) => EnVivo(api: api)));
            },
            child: const Text('Cobradores en vivo'),
          ),
          const SizedBox(height: 8),
          FilledButton(
            onPressed: () {
              Navigator.of(context).push(MaterialPageRoute(builder: (_) => RendimientoVista(api: api, propio: false)));
            },
            child: const Text('Rendimiento de cobradores'),
          ),
          const SizedBox(height: 8),
          FilledButton(
            onPressed: () {
              Navigator.of(context).push(MaterialPageRoute(builder: (_) => Usuarios(api: api)));
            },
            child: const Text('Usuarios y roles'),
          ),
        ] else if (puedeCobros)
          FilledButton(
            onPressed: () {
              Navigator.of(context).push(MaterialPageRoute(builder: (_) => RendimientoVista(api: api, propio: true)));
            },
            child: const Text('Mi rendimiento'),
          ),
        TextButton(
          onPressed: () {
            Navigator.of(context).push(MaterialPageRoute(builder: (_) => Acerca(api: api)));
          },
          child: const Text('Acerca de y activación'),
        ),
        TextButton(
          onPressed: () async {
            await olvidarSesion();
            if (!context.mounted) return;
            Navigator.of(context).pushAndRemoveUntil(
              MaterialPageRoute(builder: (_) => const Entrada()),
              (_) => false,
            );
          },
          child: const Text('Salir'),
        ),
      ],
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
