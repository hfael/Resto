import 'package:flutter/material.dart';
import 'package:mobile_flutter/app_state.dart';
import 'package:mobile_flutter/screens/admin/admin_pending_screen.dart';
import 'package:mobile_flutter/screens/reservations/my_reservations_screen.dart';
import 'package:mobile_flutter/screens/restaurants/my_restaurants_screen.dart';
import 'package:mobile_flutter/screens/restaurants/restaurants_screen.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key, required this.appState});

  final AppState appState;

  @override
  Widget build(BuildContext context) {
    final user = appState.currentUser!;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Resto App'),
        actions: [
          IconButton(
            onPressed: () async {
              await appState.logout();
            },
            icon: const Icon(Icons.logout),
            tooltip: 'Déconnexion',
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Connecté: ${user.username}',
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  Text('Rôle: ${user.role}'),
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),
          FilledButton(
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => RestaurantsScreen(appState: appState),
                ),
              );
            },
            child: const Text('Voir tous les restaurants'),
          ),
          const SizedBox(height: 8),
          FilledButton(
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => MyReservationsScreen(appState: appState),
                ),
              );
            },
            child: const Text('Mes réservations'),
          ),
          const SizedBox(height: 8),
          FilledButton(
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => MyRestaurantsScreen(appState: appState),
                ),
              );
            },
            child: const Text('Mes établissements'),
          ),
          if (user.isAdmin) ...[
            const SizedBox(height: 8),
            FilledButton.tonal(
              onPressed: () {
                Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => AdminPendingScreen(appState: appState),
                  ),
                );
              },
              child: const Text('Valider les restaurants'),
            ),
          ],
        ],
      ),
    );
  }
}
