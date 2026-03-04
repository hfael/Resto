import 'package:flutter/material.dart';
import 'package:mobile_flutter/app_state.dart';
import 'package:mobile_flutter/models/restaurant.dart';
import 'package:mobile_flutter/screens/restaurants/bookings_screen.dart';
import 'package:mobile_flutter/screens/restaurants/restaurant_detail_screen.dart';
import 'package:mobile_flutter/screens/restaurants/restaurant_form_screen.dart';
import 'package:mobile_flutter/widgets/restaurant_image.dart';

class MyRestaurantsScreen extends StatefulWidget {
  const MyRestaurantsScreen({super.key, required this.appState});

  final AppState appState;

  @override
  State<MyRestaurantsScreen> createState() => _MyRestaurantsScreenState();
}

class _MyRestaurantsScreenState extends State<MyRestaurantsScreen> {
  bool _loading = true;
  final List<Restaurant> _items = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final rows = await widget.appState.api.myRestaurants();
      _items
        ..clear()
        ..addAll(
          rows.map((e) => Restaurant.fromJson(e as Map<String, dynamic>)),
        );
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Chargement impossible: $e')));
      }
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _delete(Restaurant r) async {
    try {
      await widget.appState.api.deleteRestaurant(r.id);
      await _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Suppression impossible: $e')));
      }
    }
  }

  Future<void> _cancel(Restaurant r) async {
    try {
      await widget.appState.api.cancelRestaurant(r.id);
      await _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Annulation impossible: $e')));
      }
    }
  }

  String _statusLabel(String status) {
    switch (status) {
      case 'pending':
        return 'En attente';
      case 'accepted':
        return 'Accepté';
      case 'rejected':
        return 'Refusé';
      case 'cancelled':
        return 'Annulé';
      default:
        return status;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Mes restaurants')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: _items.isEmpty
                  ? const ListTile(title: Text('Aucun restaurant'))
                  : ListView.builder(
                      itemCount: _items.length,
                      itemBuilder: (context, index) {
                        final r = _items[index];
                        return Card(
                          margin: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 8,
                          ),
                          child: Padding(
                            padding: const EdgeInsets.all(12),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                RestaurantImage(url: r.photo, height: 130),
                                const SizedBox(height: 8),
                                Text(
                                  r.name,
                                  style: Theme.of(
                                    context,
                                  ).textTheme.titleMedium,
                                ),
                                Text(
                                  r.description,
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                const SizedBox(height: 6),
                                Text('Statut: ${_statusLabel(r.status)}'),
                                if (r.status == 'rejected' &&
                                    r.rejectionReason != null &&
                                    r.rejectionReason!.isNotEmpty)
                                  Text('Motif: ${r.rejectionReason}'),
                                const SizedBox(height: 8),
                                Wrap(
                                  spacing: 8,
                                  children: [
                                    if (r.status == 'pending')
                                      OutlinedButton(
                                        onPressed: () => _cancel(r),
                                        child: const Text('Annuler'),
                                      ),
                                    if (r.status == 'accepted')
                                      OutlinedButton(
                                        onPressed: () {
                                          Navigator.of(context).push(
                                            MaterialPageRoute(
                                              builder: (_) =>
                                                  RestaurantDetailScreen(
                                                    appState: widget.appState,
                                                    restaurantId: r.id,
                                                  ),
                                            ),
                                          );
                                        },
                                        child: const Text('Voir'),
                                      ),
                                    if (r.status == 'accepted' ||
                                        r.status == 'rejected')
                                      OutlinedButton(
                                        onPressed: () async {
                                          final changed =
                                              await Navigator.of(
                                                context,
                                              ).push<bool>(
                                                MaterialPageRoute(
                                                  builder: (_) =>
                                                      RestaurantFormScreen(
                                                        appState:
                                                            widget.appState,
                                                        restaurant: r,
                                                      ),
                                                ),
                                              );
                                          if (changed == true) {
                                            await _load();
                                          }
                                        },
                                        child: Text(
                                          r.status == 'rejected'
                                              ? 'Corriger et renvoyer'
                                              : 'Modifier',
                                        ),
                                      ),
                                    if (r.status == 'accepted')
                                      OutlinedButton(
                                        onPressed: () => _delete(r),
                                        child: const Text('Supprimer'),
                                      ),
                                    if (r.status == 'accepted')
                                      OutlinedButton(
                                        onPressed: () {
                                          Navigator.of(context).push(
                                            MaterialPageRoute(
                                              builder: (_) => BookingsScreen(
                                                appState: widget.appState,
                                                restaurantId: r.id,
                                              ),
                                            ),
                                          );
                                        },
                                        child: const Text(
                                          'Voir les réservations',
                                        ),
                                      ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
            ),
    );
  }
}
