import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../servicios/cobros_api.dart';
import 'inicio.dart';

class Entrada extends StatefulWidget {
  const Entrada({super.key});

  @override
  State<Entrada> createState() => _EntradaState();
}

class _EntradaState extends State<Entrada> {
  final _servidor = TextEditingController(text: 'https://cobros-production-4dda.up.railway.app');
  final _correo = TextEditingController();
  final _clave = TextEditingController();
  String? _error;
  bool _ocupado = false;

  @override
  void initState() {
    super.initState();
    _recordar();
  }

  Future<void> _recordar() async {
    final prefs = await SharedPreferences.getInstance();
    final guardado = prefs.getString('servidor');
    if (guardado != null && guardado.isNotEmpty && mounted) {
      _servidor.text = guardado;
    }
  }

  @override
  void dispose() {
    _servidor.dispose();
    _correo.dispose();
    _clave.dispose();
    super.dispose();
  }

  Future<void> _entrar() async {
    setState(() {
      _ocupado = true;
      _error = null;
    });
    final base = _servidor.text.trim().replaceAll(RegExp(r'/+$'), '');
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('servidor', base);
      final api = CobrosApi(base);
      final ok = await api.entrar(_correo.text.trim(), _clave.text);
      if (!mounted) return;
      if (!ok) {
        setState(() => _error = 'El correo o la clave no coinciden.');
        return;
      }
      await Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => Inicio(api: api, correo: _correo.text.trim())),
      );
    } catch (e) {
      if (mounted) setState(() => _error = 'No se pudo conectar. Revisa la dirección del servidor.');
    } finally {
      if (mounted) setState(() => _ocupado = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            const SizedBox(height: 24),
            Text('Cobros', style: Theme.of(context).textTheme.displaySmall),
            const SizedBox(height: 8),
            const Text('La cartera camina con quien cobra.'),
            const SizedBox(height: 28),
            TextField(
              controller: _servidor,
              decoration: const InputDecoration(labelText: 'Servidor'),
              keyboardType: TextInputType.url,
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _correo,
              decoration: const InputDecoration(labelText: 'Correo'),
              keyboardType: TextInputType.emailAddress,
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _clave,
              decoration: const InputDecoration(labelText: 'Clave'),
              obscureText: true,
            ),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _ocupado ? null : _entrar,
              child: const Text('Entrar'),
            ),
            if (_error != null) ...[
              const SizedBox(height: 12),
              Text(_error!, style: const TextStyle(color: Color(0xFFA32632))),
            ],
          ],
        ),
      ),
    );
  }
}
