import 'package:cobros_movil/main.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('la entrada muestra el servidor y el botón', (tester) async {
    SharedPreferences.setMockInitialValues({});
    await tester.pumpWidget(const CobrosApp());
    await tester.pumpAndSettle();
    expect(find.text('Entrar'), findsOneWidget);
    expect(find.text('Servidor'), findsOneWidget);
    expect(find.byType(Image), findsOneWidget);
  });
}
