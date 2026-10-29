import 'package:flutter/material.dart';

import '../servicios/sesion_local.dart';
import 'entrada.dart';
import 'inicio.dart';

class Puerta extends StatefulWidget {
  const Puerta({super.key});

  @override
  State<Puerta> createState() => _PuertaState();
}

class _PuertaState extends State<Puerta> {
  Widget? _destino;

  @override
  void initState() {
    super.initState();
    _resolver();
  }

  Future<void> _resolver() async {
    final api = await apiGuardada();
    if (!mounted) return;
    if (api == null) {
      setState(() => _destino = const Entrada());
      return;
    }

    try {
      final datos = await api.sesion();
      await guardarConfiguracion(api, correo: datos['correo']?.toString());
      if (!mounted) return;
      setState(() => _destino = Inicio(api: api));
    } catch (e) {
      if (sesionVencida(e)) await olvidarSesion();
      if (!mounted) return;
      setState(() => _destino = sesionVencida(e) ? const Entrada() : Inicio(api: api));
    }
  }

  @override
  Widget build(BuildContext context) {
    return _destino ?? const Scaffold(body: Center(child: CircularProgressIndicator()));
  }
}
