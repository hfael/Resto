import 'package:flutter/material.dart';
import 'package:mobile_flutter/app_state.dart';
import 'package:mobile_flutter/models/reservation.dart';

class MyReservationsScreen extends StatefulWidget {
  const MyReservationsScreen({super.key, required this.appState});

  final AppState appState;

  @override
  State<MyReservationsScreen> createState() => _MyReservationsScreenState();
}

class _MyReservationsScreenState extends State<MyReservationsScreen> {
  bool _loading = true;
  List<Reservation> _items = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final rows = await widget.appState.api.userReservations();
      _items = rows
          .map((e) => Reservation.fromJson(e as Map<String, dynamic>))
          .toList();
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Mes réservations')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: _items.isEmpty
                  ? const ListTile(title: Text('Aucune réservation'))
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
                                Text(
                                  r.restaurantName ?? '-',
                                  style: Theme.of(
                                    context,
                                  ).textTheme.titleMedium,
                                ),
                                const SizedBox(height: 4),
                                Text('Date: ${r.reservationDate}'),
                                Text('Heure: ${r.reservationTime}'),
                                Text('Code: ${r.code}'),
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
