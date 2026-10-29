import 'package:flutter/material.dart';

ThemeData temaCobros() {
  const tinta = Color(0xFF13201B);
  const senal = Color(0xFFDFF25A);
  const papel = Color(0xFFE7EEE6);

  return ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: tinta,
      primary: tinta,
      secondary: senal,
      surface: const Color(0xFFFBFCF8),
    ).copyWith(secondary: senal),
    scaffoldBackgroundColor: papel,
    fontFamily: 'Segoe UI',
    appBarTheme: const AppBarTheme(
      backgroundColor: tinta,
      foregroundColor: Colors.white,
      centerTitle: false,
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: tinta,
        foregroundColor: Colors.white,
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
