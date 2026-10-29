import 'package:flutter/material.dart';

import 'pantallas/puerta.dart';
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
      title: 'Cobras',
      debugShowCheckedModeBanner: false,
      theme: temaCobros(),
      home: const Puerta(),
    );
  }
}
