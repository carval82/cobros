import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../servicios/cobros_api.dart';
import '../servicios/sesion_local.dart';
import '../servicios/ubicacion.dart';
import 'inicio.dart';

class Entrada extends StatefulWidget {
  const Entrada({super.key});

  @override
  State<Entrada> createState() => _EntradaState();
}

class _EntradaState extends State<Entrada> {
  final _servidor = TextEditingController(text: 'https://cobros-production-4dda.up.railway.app');
  final _direccion = TextEditingController();
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
    final servidor = prefs.getString('servidor');
    final direccion = prefs.getString('direccion');
    final correo = prefs.getString('correo');
    if (!mounted) return;
    if (servidor != null && servidor.isNotEmpty) _servidor.text = servidor;
    if (direccion != null) _direccion.text = direccion;
    if (correo != null) _correo.text = correo;
  }

  @override
  void dispose() {
    _servidor.dispose();
    _direccion.dispose();
    _correo.dispose();
    _clave.dispose();
    super.dispose();
  }

  String get _raiz => _servidor.text.trim().replaceAll(RegExp(r'/+$'), '');

  String? _avisoServidor() {
    final uri = Uri.tryParse(_raiz);
    if (uri == null || uri.host.isEmpty) return 'Escribe la dirección del servidor.';
    if (uri.scheme == 'https') return null;
    const locales = {'localhost', '127.0.0.1', '10.0.2.2'};
    if (uri.scheme == 'http' && locales.contains(uri.host)) return null;
    return 'El servidor tiene que usar https.';
  }

  Future<void> _abrir(CobrosApi api) async {
    await guardarConfiguracion(api, correo: _correo.text);
    if (!mounted) return;
    await Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => Inicio(api: api)),
    );
  }

  Future<void> _entrar() async {
    final aviso = _avisoServidor();
    if (aviso != null) {
      setState(() => _error = aviso);
      return;
    }
    setState(() {
      _ocupado = true;
      _error = null;
    });
    try {
      final api = CobrosApi(_raiz, tenant: tenantEnDireccion(_direccion.text));
      final ok = await api.entrar(_correo.text.trim(), _clave.text);
      if (!mounted) return;
      if (!ok) {
        setState(() => _error = 'El correo o la clave no coinciden.');
        return;
      }
      await _abrir(api);
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
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const SizedBox(height: 12),
            Center(
              child: Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(28),
                  boxShadow: const [BoxShadow(color: Color(0x141C140C), blurRadius: 18, offset: Offset(0, 8))],
                ),
                child: Image.asset('assets/cobras.png', height: 120),
              ),
            ),
            const SizedBox(height: 16),
            Text(
              'Entra a tu empresa',
              style: Theme.of(context).textTheme.headlineMedium,
            ),
            const SizedBox(height: 8),
            const Text('Pega la dirección de ingreso que quedó al crear el cobro. Si aún no tienes empresa, ábrela en la guía.'),
            const SizedBox(height: 20),
            TextField(
              controller: _servidor,
              decoration: const InputDecoration(labelText: 'Servidor'),
              keyboardType: TextInputType.url,
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _direccion,
              decoration: const InputDecoration(labelText: 'Dirección de ingreso'),
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
            TextButton(
              onPressed: _ocupado ? null : () => abrirEnlace('${_raiz}/guia'),
              child: const Text('Aún no tengo empresa'),
            ),
            if (_error != null)
              Text(_error!, style: const TextStyle(color: Color(0xFFA32632))),
          ],
          ),
        ),
      ),
    );
  }
}
