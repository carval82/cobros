import 'package:geolocator/geolocator.dart';
import 'package:url_launcher/url_launcher.dart';

class Punto {
  const Punto(this.latitud, this.longitud);

  final double latitud;
  final double longitud;
}

Future<Punto> puntoActual() async {
  final servicio = await Geolocator.isLocationServiceEnabled();
  if (!servicio) {
    throw Exception('Activa la ubicación del teléfono.');
  }
  var permiso = await Geolocator.checkPermission();
  if (permiso == LocationPermission.denied) {
    permiso = await Geolocator.requestPermission();
  }
  if (permiso == LocationPermission.denied || permiso == LocationPermission.deniedForever) {
    throw Exception('El teléfono no dio permiso de ubicación.');
  }
  final posicion = await Geolocator.getCurrentPosition(
    desiredAccuracy: LocationAccuracy.high,
  );
  return Punto(posicion.latitude, posicion.longitude);
}

Future<void> abrirDestino(double latitud, double longitud) {
  return launchUrl(
    Uri.parse('https://www.google.com/maps/dir/?api=1&destination=$latitud,$longitud'),
    mode: LaunchMode.externalApplication,
  );
}

Future<void> abrirEnlace(String url) {
  return launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
}

Future<void> llamar(String telefono) {
  return launchUrl(Uri.parse('tel:$telefono'));
}
