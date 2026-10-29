import 'dart:convert';

import 'package:http/http.dart' as http;

class CobrosApi {
  CobrosApi(this.base);

  final String base;
  final Map<String, String> _galletas = {};

  Future<bool> entrar(String email, String clave) async {
    final pagina = await _pedir('GET', '/login');
    final token = RegExp(r'name="_token" value="([^"]+)"').firstMatch(pagina.body)?.group(1);
    if (token == null) {
      throw Exception('El servidor no mostró la entrada.');
    }

    final respuesta = await _pedir('POST', '/login', campos: {
      '_token': token,
      'email': email,
      'password': clave,
    });
    final lugar = respuesta.headers['location'] ?? '';
    return respuesta.statusCode == 302 && !lugar.contains('/login');
  }

  Future<Map<String, dynamic>> acerca() async {
    final respuesta = await _pedir('GET', '/acerca/datos');
    if (respuesta.statusCode != 200) {
      throw Exception('No se pudo leer Acerca de.');
    }
    final datos = jsonDecode(respuesta.body);
    if (datos is! Map<String, dynamic>) {
      throw Exception('Respuesta inesperada.');
    }
    return datos;
  }

  Future<void> activar(String codigo) async {
    final pagina = await _pedir('GET', '/acerca');
    final token = RegExp(r'name="_token" value="([^"]+)"').firstMatch(pagina.body)?.group(1);
    if (token == null) {
      throw Exception('Abre la empresa antes de activar.');
    }
    final respuesta = await _pedir('POST', '/acerca', campos: {
      '_token': token,
      'codigo': codigo,
    });
    if (respuesta.statusCode >= 400) {
      throw Exception('La clave no activó esta empresa.');
    }
  }

  Future<http.Response> _pedir(String metodo, String ruta, {Map<String, String>? campos}) async {
    final peticion = http.Request(metodo, Uri.parse('$base$ruta'))
      ..followRedirects = false
      ..headers.addAll({
        'Accept': 'application/json, text/html',
        if (_galletas.isNotEmpty) 'Cookie': _galletas.entries.map((e) => '${e.key}=${e.value}').join('; '),
      });
    if (campos != null) {
      peticion.headers['Content-Type'] = 'application/x-www-form-urlencoded';
      peticion.bodyFields = campos;
    }
    final enviada = await peticion.send();
    final respuesta = await http.Response.fromStream(enviada);
    _absorber(respuesta);
    return respuesta;
  }

  void _absorber(http.Response respuesta) {
    for (final cabecera in respuesta.headersSplitValues['set-cookie'] ?? const <String>[]) {
      final parte = cabecera.split(';').first;
      final corte = parte.indexOf('=');
      if (corte > 0) {
        _galletas[parte.substring(0, corte)] = parte.substring(corte + 1);
      }
    }
  }
}
