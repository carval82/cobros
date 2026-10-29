import 'dart:convert';

import 'package:http/http.dart' as http;

class CobrosApi {
  CobrosApi(this.raiz, {this.tenant});

  final String raiz;
  String? tenant;
  final Map<String, String> _galletas = {};

  String get _prefijo {
    final id = tenant;
    if (id == null || id.isEmpty) return '';
    return '/tenant/$id';
  }

  Future<Map<String, dynamic>> crearEmpresa({
    required String empresa,
    required String administrador,
    required String correo,
    required String clave,
  }) async {
    final token = await _token('/');
    final respuesta = await _pedir('POST', '/registrar', json: true, campos: {
      '_token': token,
      'nombre': empresa,
      'usuario_nombre': administrador,
      'usuario_email': correo,
      'usuario_password': clave,
      'usuario_password_confirmation': clave,
    });
    if (respuesta.statusCode >= 400) {
      throw Exception(_mensaje(respuesta));
    }
    final datos = _mapa(respuesta.body);
    final id = datos['tenant']?.toString();
    if (id != null && id.isNotEmpty) tenant = id;
    return datos;
  }

  Future<bool> entrar(String email, String clave) async {
    final ruta = '$_prefijo/login';
    final token = await _token(ruta);
    final respuesta = await _pedir('POST', ruta, campos: {
      '_token': token,
      'email': email,
      'password': clave,
    });
    final lugar = respuesta.headers['location'] ?? '';
    if (respuesta.statusCode == 302 && !lugar.contains('/login')) {
      final id = RegExp(r'/tenant/([0-9a-fA-F-]{36})').firstMatch(lugar)?.group(1);
      if (id != null) tenant = id;
      return true;
    }
    return false;
  }

  Future<Map<String, dynamic>> sesion() async {
    final respuesta = await _pedir('GET', '/sesion/datos', json: true);
    if (respuesta.statusCode != 200) {
      throw Exception('No se pudo leer la sesión.');
    }
    final datos = _mapa(respuesta.body);
    final id = datos['tenant']?.toString();
    if (id != null && id.isNotEmpty) tenant = id;
    return datos;
  }

  Future<Map<String, dynamic>> acerca() async {
    final respuesta = await _pedir('GET', '$_prefijo/acerca/datos', json: true);
    if (respuesta.statusCode != 200) {
      throw Exception('No se pudo leer Acerca de.');
    }
    return _mapa(respuesta.body);
  }

  Future<void> activar(String codigo) async {
    final pagina = await _pedir('GET', '$_prefijo/acerca');
    final token = RegExp(r'name="_token" value="([^"]+)"').firstMatch(pagina.body)?.group(1);
    if (token == null) {
      throw Exception('Abre la empresa antes de activar.');
    }
    final respuesta = await _pedir('POST', '$_prefijo/acerca', campos: {
      '_token': token,
      'codigo': codigo,
    });
    if (respuesta.statusCode >= 400) {
      throw Exception('La clave no activó esta empresa.');
    }
  }

  Future<Map<String, dynamic>> usuarios() async {
    final respuesta = await _pedir('GET', '$_prefijo/usuarios/datos', json: true);
    if (respuesta.statusCode == 403) {
      throw Exception('Tu rol no administra usuarios.');
    }
    if (respuesta.statusCode != 200) {
      throw Exception('No se pudo leer el equipo.');
    }
    return _mapa(respuesta.body);
  }

  Future<void> crearUsuario({
    required String nombre,
    required String correo,
    required String clave,
    required String rol,
    required String token,
  }) async {
    final respuesta = await _pedir('POST', '$_prefijo/usuarios', json: true, campos: {
      '_token': token,
      'name': nombre,
      'email': correo,
      'password': clave,
      'password_confirmation': clave,
      'rol': rol,
    });
    if (respuesta.statusCode >= 400) {
      throw Exception(_mensaje(respuesta));
    }
  }

  Future<String> _token(String ruta) async {
    final pagina = await _pedir('GET', ruta);
    final token = RegExp(r'name="_token" value="([^"]+)"').firstMatch(pagina.body)?.group(1);
    if (token == null) {
      throw Exception('El servidor no mostró la entrada.');
    }
    return token;
  }

  Future<http.Response> _pedir(String metodo, String ruta, {Map<String, String>? campos, bool json = false}) async {
    final peticion = http.Request(metodo, Uri.parse('$raiz$ruta'))
      ..followRedirects = false
      ..headers.addAll({
        'Accept': json ? 'application/json' : 'text/html, application/json',
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

  Map<String, dynamic> _mapa(String cuerpo) {
    final datos = jsonDecode(cuerpo);
    if (datos is! Map<String, dynamic>) {
      throw Exception('Respuesta inesperada.');
    }
    return datos;
  }

  String _mensaje(http.Response respuesta) {
    try {
      final datos = jsonDecode(respuesta.body);
      if (datos is Map && datos['errors'] is Map) {
        final primero = (datos['errors'] as Map).values.first;
        if (primero is List && primero.isNotEmpty) return primero.first.toString();
      }
      if (datos is Map && datos['message'] != null) return datos['message'].toString();
    } catch (_) {}
    return 'No se pudo completar.';
  }
}

String? tenantEnDireccion(String texto) {
  return RegExp(r'/tenant/([0-9a-fA-F-]{36})').firstMatch(texto)?.group(1);
}
