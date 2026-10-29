import 'package:flutter/material.dart';

import '../servicios/cobros_api.dart';
import 'acerca.dart';

class Inicio extends StatelessWidget {
  const Inicio({super.key, required this.api, required this.correo});

  final CobrosApi api;
  final String correo;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Cobras'),
        actions: [
          IconButton(
            tooltip: 'Acerca de',
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => Acerca(api: api)),
              );
            },
            icon: const Icon(Icons.info_outline),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text('En la calle', style: Theme.of(context).textTheme.labelLarge),
          const SizedBox(height: 8),
          Text('Hoy', style: Theme.of(context).textTheme.headlineLarge),
          const SizedBox(height: 8),
          Text('Sesión de $correo. Cada préstamo sale de un cliente y cada cobro queda a su nombre.'),
          const SizedBox(height: 20),
          const _Tarjeta(titulo: 'Clientes', detalle: 'Un cliente, varios préstamos.'),
          const _Tarjeta(titulo: 'Ruta', detalle: 'El cobro del día, con la ubicación del abono.'),
          const _Tarjeta(titulo: 'Demo', detalle: 'Cinco préstamos. La clave en Acerca de abre la versión completa.'),
        ],
      ),
    );
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
      child: ListTile(
        title: Text(titulo),
        subtitle: Text(detalle),
      ),
    );
  }
}
