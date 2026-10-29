import 'package:flutter/material.dart';

import 'piezas.dart';

ThemeData temaCobros() {
  final borde = OutlineInputBorder(
    borderRadius: BorderRadius.circular(16),
    borderSide: const BorderSide(color: Color(0xFFE4D7C4)),
  );

  return ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: senal,
      primary: tinta,
      secondary: senal,
      surface: marfil,
    ).copyWith(secondary: senal),
    scaffoldBackgroundColor: papel,
    fontFamily: 'Segoe UI',
    appBarTheme: const AppBarTheme(
      backgroundColor: tinta,
      foregroundColor: senal,
      elevation: 0,
      centerTitle: false,
      titleTextStyle: TextStyle(color: senal, fontSize: 20, fontWeight: FontWeight.w700, letterSpacing: 0.4),
    ),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: tinta,
      height: 68,
      indicatorColor: senal,
      labelTextStyle: WidgetStateProperty.resolveWith((states) {
        final activo = states.contains(WidgetState.selected);
        return TextStyle(
          color: activo ? senal : const Color(0xFFCBB892),
          fontSize: 12,
          fontWeight: activo ? FontWeight.w700 : FontWeight.w500,
        );
      }),
      iconTheme: WidgetStateProperty.resolveWith((states) {
        final activo = states.contains(WidgetState.selected);
        return IconThemeData(color: activo ? tinta : senal);
      }),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: senal,
        foregroundColor: tinta,
        minimumSize: const Size(120, 52),
        textStyle: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
        shape: const StadiumBorder(),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        foregroundColor: tinta,
        side: const BorderSide(color: Color(0xFFC9962E)),
        minimumSize: const Size.fromHeight(48),
        shape: const StadiumBorder(),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      labelStyle: const TextStyle(color: muted),
      border: borde,
      enabledBorder: borde,
      focusedBorder: borde.copyWith(borderSide: const BorderSide(color: oro, width: 1.6)),
    ),
  );
}
