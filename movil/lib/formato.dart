String pesos(Object? valor) {
  final n = valor is num ? valor.round() : int.tryParse('$valor') ?? 0;
  final negativo = n < 0;
  final texto = n.abs().toString();
  final buf = StringBuffer();
  for (var i = 0; i < texto.length; i++) {
    if (i > 0 && (texto.length - i) % 3 == 0) buf.write('.');
    buf.write(texto[i]);
  }
  return '${negativo ? '-' : ''}\$$buf';
}

double? numero(Object? valor) {
  if (valor == null) return null;
  if (valor is num) return valor.toDouble();
  return double.tryParse('$valor');
}

int entero(Object? valor) {
  if (valor is num) return valor.round();
  return int.tryParse('$valor') ?? 0;
}

String fechaHoy() {
  final hoy = DateTime.now();
  String dos(int n) => n.toString().padLeft(2, '0');
  return '${hoy.year}-${dos(hoy.month)}-${dos(hoy.day)}';
}

String textoError(Object error) => '$error'.replaceFirst('Exception: ', '');

String? numeroWhatsapp(String? telefono) {
  final digitos = (telefono ?? '').replaceAll(RegExp(r'\D'), '');
  if (digitos.isEmpty) return null;
  if (digitos.startsWith('57') && digitos.length >= 12) return digitos;
  if (digitos.length == 10) return '57$digitos';
  if (digitos.length >= 11 && digitos.length <= 15) return digitos;
  return null;
}

String enlaceAbonoWhatsapp({
  required String telefono,
  required String nombre,
  required int monto,
  required int saldo,
  String? proximaFecha,
  int? proximaValor,
}) {
  final numero = numeroWhatsapp(telefono);
  if (numero == null) return '';

  var texto = 'Hola $nombre, registramos tu abono de ${pesos(monto)}. Saldo pendiente: ${pesos(saldo)}.';
  final fecha = _fechaCorta(proximaFecha);
  if (fecha != null) {
    texto += ' Próxima cuota: $fecha';
    if (proximaValor != null && proximaValor > 0) texto += ' por ${pesos(proximaValor)}';
    texto += '.';
  } else if (saldo <= 0) {
    texto += ' El préstamo quedó saldado.';
  }

  return 'https://wa.me/$numero?text=${Uri.encodeComponent(texto)}';
}

String? _fechaCorta(String? fecha) {
  if (fecha == null || fecha.isEmpty) return null;
  final partes = fecha.split('-');
  if (partes.length == 3 && partes[0].length == 4) {
    return '${partes[2]}/${partes[1]}/${partes[0]}';
  }
  return fecha;
}
