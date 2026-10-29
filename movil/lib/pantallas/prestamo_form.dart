import 'package:flutter/material.dart';

import '../formato.dart';
import '../servicios/cobros_api.dart';

class PrestamoForm extends StatefulWidget {
  const PrestamoForm({super.key, required this.api, required this.clienteId, required this.clienteNombre});

  final CobrosApi api;
  final int clienteId;
  final String clienteNombre;

  @override
  State<PrestamoForm> createState() => _PrestamoFormState();
}

class _PrestamoFormState extends State<PrestamoForm> {
  final _capital = TextEditingController(text: '100000');
  final _porcentaje = TextEditingController(text: '20');
  final _cuotas = TextEditingController(text: '5');
  final _nota = TextEditingController();
  String _frecuencia = 'semanal';
  DateTime _fecha = DateTime.now();
  String? _error;
  bool _ocupado = false;

  @override
  void dispose() {
    _capital.dispose();
    _porcentaje.dispose();
    _cuotas.dispose();
    _nota.dispose();
    super.dispose();
  }

  String get _fechaTexto {
    String dos(int n) => n.toString().padLeft(2, '0');
    return '${_fecha.year}-${dos(_fecha.month)}-${dos(_fecha.day)}';
  }

  String get _plan {
    final capital = int.tryParse(_capital.text) ?? 0;
    final porcentaje = double.tryParse(_porcentaje.text.replaceAll(',', '.')) ?? 0;
    final n = int.tryParse(_cuotas.text) ?? 0;
    if (capital < 1 || n < 1) return 'Escribe capital y cuotas para ver el plan.';
    final interes = (capital * porcentaje / 100).round();
    final normal = capital ~/ n + interes ~/ n;
    final ultima = normal + capital % n + interes % n;
    final textoCuota = normal == ultima ? pesos(normal) : '${pesos(normal)} y la última ${pesos(ultima)}';
    return 'Interés ${pesos(interes)}. Total ${pesos(capital + interes)}. Cada cuota $textoCuota.';
  }

  Future<void> _crear() async {
    setState(() {
      _ocupado = true;
      _error = null;
    });
    try {
      await widget.api.crearPrestamo(
        clienteId: widget.clienteId,
        capital: int.parse(_capital.text),
        porcentaje: _porcentaje.text.replaceAll(',', '.'),
        cuotas: int.parse(_cuotas.text),
        frecuencia: _frecuencia,
        fecha: _fechaTexto,
        nota: _nota.text.trim(),
      );
      if (mounted) Navigator.of(context).pop(true);
    } catch (e) {
      if (mounted) setState(() => _error = textoError(e));
    } finally {
      if (mounted) setState(() => _ocupado = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Nuevo préstamo')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text(widget.clienteNombre, style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 8),
          const Text('El préstamo sale de este cliente. Cada abono queda en este plan, no en otro.'),
          const SizedBox(height: 16),
          TextField(controller: _capital, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Capital prestado'), onChanged: (_) => setState(() {})),
          const SizedBox(height: 12),
          TextField(controller: _porcentaje, keyboardType: const TextInputType.numberWithOptions(decimal: true), decoration: const InputDecoration(labelText: 'Interés del préstamo (%)'), onChanged: (_) => setState(() {})),
          const SizedBox(height: 12),
          TextField(controller: _cuotas, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Número de cuotas'), onChanged: (_) => setState(() {})),
          const SizedBox(height: 12),
          DropdownButtonFormField<String>(
            value: _frecuencia,
            decoration: const InputDecoration(labelText: 'Frecuencia'),
            items: const [
              DropdownMenuItem(value: 'diario', child: Text('Diario')),
              DropdownMenuItem(value: 'semanal', child: Text('Semanal')),
              DropdownMenuItem(value: 'quincenal', child: Text('Quincenal')),
              DropdownMenuItem(value: 'mensual', child: Text('Mensual')),
            ],
            onChanged: (valor) {
              if (valor != null) setState(() => _frecuencia = valor);
            },
          ),
          const SizedBox(height: 12),
          ListTile(
            contentPadding: EdgeInsets.zero,
            title: const Text('Primer pago'),
            subtitle: Text(_fechaTexto),
            trailing: const Icon(Icons.calendar_today),
            onTap: () async {
              final elegida = await showDatePicker(
                context: context,
                initialDate: _fecha,
                firstDate: DateTime(2020),
                lastDate: DateTime(2100),
              );
              if (elegida != null) setState(() => _fecha = elegida);
            },
          ),
          TextField(controller: _nota, decoration: const InputDecoration(labelText: 'Nota')),
          const SizedBox(height: 12),
          Text(_plan),
          const SizedBox(height: 16),
          FilledButton(onPressed: _ocupado ? null : _crear, child: const Text('Crear préstamo')),
          if (_error != null) ...[
            const SizedBox(height: 12),
            Text(_error!, style: const TextStyle(color: Color(0xFFA32632))),
          ],
        ],
      ),
    );
  }
}
