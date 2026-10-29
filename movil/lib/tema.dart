import 'package:flutter/material.dart';

ThemeData temaCobros() {
  const tinta = Color(0xFF1C140C);
  const senal = Color(0xFFE4C16A);
  const papel = Color(0xFFF3EADC);

  return ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: senal,
      primary: tinta,
      secondary: senal,
      surface: const Color(0xFFFFFAF2),
    ).copyWith(secondary: senal),
    scaffoldBackgroundColor: papel,
    fontFamily: 'Segoe UI',
    appBarTheme: const AppBarTheme(
      backgroundColor: tinta,
      foregroundColor: senal,
      centerTitle: false,
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: senal,
        foregroundColor: tinta,
        minimumSize: const Size.fromHeight(48),
        shape: const StadiumBorder(),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
    ),
  );
}
