import 'package:flutter/material.dart';
import 'package:mobile_flutter/app_state.dart';
import 'package:mobile_flutter/screens/auth/login_screen.dart';
import 'package:mobile_flutter/screens/home_screen.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final appState = AppState();
  await appState.initialize();
  runApp(RestoMobileApp(appState: appState));
}

class RestoMobileApp extends StatelessWidget {
  const RestoMobileApp({super.key, required this.appState});

  final AppState appState;

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: appState,
      builder: (context, _) {
        return MaterialApp(
          debugShowCheckedModeBanner: false,
          title: 'Resto Mobile',
          theme: ThemeData(
            colorScheme: ColorScheme.fromSeed(seedColor: Colors.teal),
            useMaterial3: true,
          ),
          home: _buildHome(),
        );
      },
    );
  }

  Widget _buildHome() {
    if (!appState.initialized) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    if (appState.isLoggedIn) {
      return HomeScreen(appState: appState);
    }
    return LoginScreen(appState: appState);
  }
}
