import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../servicios/cobros_api.dart';
import '../servicios/sesion_local.dart';
import 'inicio.dart';

class Entrada extends StatefulWidget {
  const Entrada({super.key});

  @override
  State<Entrada> createState() => _EntradaState();
}

class _EntradaState extends State<Entrada> {
  final _servidor = TextEditingController(text: 'https://cobros-production-4dda.up.railway.app');
  final _empresa = TextEditingController();
  final _administrador = TextEditingController();
  final _direccion = TextEditingController();
  final _correo = TextEditingController();
  final _clave = TextEditingController();
  final _confirmar = TextEditingController();
  String? _error;
  bool _ocupado = false;
  bool _crear = true;

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
    if ((direccion != null && direccion.isNotEmpty) || (correo != null && correo.isNotEmpty)) {
      setState(() => _crear = false);
    }
  }

  @override
  void dispose() {
    _servidor.dispose();
    _empresa.dispose();
    _administrador.dispose();
    _direccion.dispose();
    _correo.dispose();
    _clave.dispose();
    _confirmar.dispose();
    super.dispose();
  }

  String get _raiz => _servidor.text.trim().replaceAll(RegExp(r'/+$'), '');

  Future<void> _abrir(CobrosApi api) async {
    await guardarConfiguracion(api, correo: _correo.text);
    if (!mounted) return;
    await Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => Inicio(api: api)),
    );
  }

  Future<void> _crearEmpresa() async {
    if (_clave.text != _confirmar.text) {
      setState(() => _error = 'La confirmación no coincide.');
      return;
    }
    setState(() {
      _ocupado = true;
      _error = null;
    });
    try {
      final api = CobrosApi(_raiz);
      await api.crearEmpresa(
        empresa: _empresa.text.trim(),
        administrador: _administrador.text.trim(),
        correo: _correo.text.trim(),
        clave: _clave.text,
      );
      await _abrir(api);
    } catch (e) {
      if (mounted) setState(() => _error = '$e'.replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => _ocupado = false);
    }
  }

  Future<void> _entrar() async {
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
              _crear ? 'Crea tu empresa' : 'Entra a tu empresa',
              style: Theme.of(context).textTheme.headlineMedium,
            ),
            const SizedBox(height: 8),
            Text(
              _crear
                  ? 'Se abre su base, se compone la dirección /tenant/ y quedas como administrador.'
                  : 'Pega la dirección que se generó, o entra solo con el correo si ya la guardamos.',
            ),
            const SizedBox(height: 20),
            TextField(
              controller: _servidor,
              decoration: const InputDecoration(labelText: 'Servidor'),
              keyboardType: TextInputType.url,
            ),
            if (_crear) ...[
              const SizedBox(height: 12),
              TextField(controller: _empresa, decoration: const InputDecoration(labelText: 'Empresa')),
              const SizedBox(height: 12),
              TextField(controller: _administrador, decoration: const InputDecoration(labelText: 'Administrador')),
            ] else ...[
              const SizedBox(height: 12),
              TextField(
                controller: _direccion,
                decoration: const InputDecoration(labelText: 'Dirección del tenant'),
              ),
            ],
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
            if (_crear) ...[
              const SizedBox(height: 12),
              TextField(
                controller: _confirmar,
                decoration: const InputDecoration(labelText: 'Confirmar clave'),
                obscureText: true,
              ),
            ],
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _ocupado ? null : (_crear ? _crearEmpresa : _entrar),
              child: Text(_crear ? 'Crear empresa' : 'Entrar'),
            ),
            TextButton(
              onPressed: _ocupado
                  ? null
                  : () => setState(() {
                        _crear = !_crear;
                        _error = null;
                      }),
              child: Text(_crear ? 'Ya tengo empresa' : 'Crear una empresa'),
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
