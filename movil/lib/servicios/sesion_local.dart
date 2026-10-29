import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

import 'cobros_api.dart';

Future<void> guardarConfiguracion(CobrosApi api, {String? correo}) async {
  final prefs = await SharedPreferences.getInstance();
  await prefs.setString('servidor', api.raiz);
  final tenant = api.tenant;
  if (tenant != null && tenant.isNotEmpty) {
    await prefs.setString('tenant', tenant);
    await prefs.setString('direccion', '${api.raiz}/tenant/$tenant/login');
  }
  final correoLimpio = correo?.trim() ?? '';
  if (correoLimpio.isNotEmpty) await prefs.setString('correo', correoLimpio);
  await prefs.setString('galletas', jsonEncode(api.galletas));
}

Future<void> olvidarSesion() async {
  final prefs = await SharedPreferences.getInstance();
  await prefs.remove('galletas');
}

Future<CobrosApi?> apiGuardada() async {
  final prefs = await SharedPreferences.getInstance();
  final servidor = prefs.getString('servidor');
  final crudo = prefs.getString('galletas');
  if (servidor == null || servidor.isEmpty || crudo == null || crudo.isEmpty) return null;

  final decodificado = jsonDecode(crudo);
  if (decodificado is! Map || decodificado.isEmpty) return null;

  final galletas = <String, String>{};
  decodificado.forEach((clave, valor) {
    galletas['$clave'] = '$valor';
  });

  final api = CobrosApi(servidor, tenant: prefs.getString('tenant'));
  api.restaurarGalletas(galletas);
  return api;
}

bool sesionVencida(Object error) {
  final texto = '$error';
  return texto.contains('(401)') || texto.contains('(419)') || texto.contains('(302)');
}
