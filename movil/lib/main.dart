import 'package:flutter/material.dart';

import 'pantallas/entrada.dart';
import 'tema.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const CobrosApp());
}

class CobrosApp extends StatelessWidget {
  const CobrosApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Cobros',
      debugShowCheckedModeBanner: false,
      theme: temaCobros(),
      home: const Entrada(),
    );
  }
}
