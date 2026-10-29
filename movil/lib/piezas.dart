import 'package:flutter/material.dart';

const tinta = Color(0xFF1C140C);
const senal = Color(0xFFE4C16A);
const oro = Color(0xFFC9962E);
const marfil = Color(0xFFFFFCF7);
const papel = Color(0xFFF3EADC);
const ok = Color(0xFF0F7A52);
const peligro = Color(0xFFA32632);
const muted = Color(0xFF6E6254);

class Lamina extends StatelessWidget {
  const Lamina({super.key, required this.child, this.relleno = marfil, this.margen});

  final Widget child;
  final Color relleno;
  final EdgeInsetsGeometry? margen;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: margen ?? const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: relleno,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFE7DCCB)),
        boxShadow: const [
          BoxShadow(color: Color(0x141C140C), blurRadius: 18, offset: Offset(0, 8)),
        ],
      ),
      child: child,
    );
  }
}

class Titulo extends StatelessWidget {
  const Titulo(this.texto, {super.key});

  final String texto;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 8, bottom: 10),
      child: Text(
        texto,
        style: const TextStyle(fontSize: 13, letterSpacing: 1.3, fontWeight: FontWeight.w700, color: oro),
      ),
    );
  }
}

class Pastilla extends StatelessWidget {
  const Pastilla(this.texto, {super.key, this.tono = tinta});

  final String texto;
  final Color tono;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: tono.withOpacity(0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        texto,
        style: TextStyle(color: tono, fontSize: 12, fontWeight: FontWeight.w700),
      ),
    );
  }
}

class Monograma extends StatelessWidget {
  const Monograma(this.nombre, {super.key, this.oscuro = false});

  final String nombre;
  final bool oscuro;

  @override
  Widget build(BuildContext context) {
    final letras = iniciales(nombre);
    return Container(
      width: 46,
      height: 46,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: oscuro ? senal : tinta,
        shape: BoxShape.circle,
      ),
      child: Text(
        letras,
        style: TextStyle(color: oscuro ? tinta : senal, fontWeight: FontWeight.w800),
      ),
    );
  }
}

class Cifra extends StatelessWidget {
  const Cifra({super.key, required this.titulo, required this.valor, this.destacada = false, this.alerta = false});

  final String titulo;
  final String valor;
  final bool destacada;
  final bool alerta;

  @override
  Widget build(BuildContext context) {
    final fondo = destacada ? tinta : marfil;
    final colorValor = destacada ? senal : (alerta ? peligro : tinta);
    final colorTitulo = destacada ? const Color(0xFFCBB892) : muted;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: fondo,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: destacada ? tinta : const Color(0xFFE7DCCB)),
        boxShadow: const [
          BoxShadow(color: Color(0x141C140C), blurRadius: 18, offset: Offset(0, 8)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(titulo, style: TextStyle(color: colorTitulo, fontSize: 12, fontWeight: FontWeight.w600)),
          Text(valor, style: TextStyle(color: colorValor, fontSize: 18, fontWeight: FontWeight.w800, height: 1.1)),
        ],
      ),
    );
  }
}

String iniciales(String nombre) {
  final partes = nombre.trim().split(RegExp(r'\s+')).where((p) => p.isNotEmpty).take(2);
  if (partes.isEmpty) return 'C';
  return partes.map((p) => p[0].toUpperCase()).join();
}

Color tonoEstado(String estado) {
  switch (estado) {
    case 'pagada':
    case 'activo':
      return ok;
    case 'vencida':
    case 'anulado':
      return peligro;
    case 'parcial':
    case 'detenido':
    case 'detenida':
      return const Color(0xFF8A5A12);
    default:
      return tinta;
  }
}
