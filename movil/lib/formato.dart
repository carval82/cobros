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
