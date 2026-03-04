import 'package:flutter/material.dart';
import 'package:mobile_flutter/app_state.dart';
import 'package:mobile_flutter/models/restaurant.dart';
import 'package:mobile_flutter/widgets/restaurant_image.dart';

class AdminPendingScreen extends StatefulWidget {
  const AdminPendingScreen({super.key, required this.appState});

  final AppState appState;

  @override
  State<AdminPendingScreen> createState() => _AdminPendingScreenState();
}

class _AdminPendingScreenState extends State<AdminPendingScreen> {
  bool _loading = true;
  List<Restaurant> _items = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final rows = await widget.appState.api.pendingRestaurants();
      _items = rows
          .map((e) => Restaurant.fromJson(e as Map<String, dynamic>))
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

  Future<void> _accept(Restaurant r) async {
    try {
      await widget.appState.api.acceptRestaurant(r.id);
      await _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Action impossible: $e')));
      }
    }
  }

  Future<void> _reject(Restaurant r) async {
    final reasonCtrl = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Motif du refus'),
          content: TextField(
            controller: reasonCtrl,
            maxLines: 3,
            decoration: const InputDecoration(hintText: 'Motif'),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Annuler'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Refuser'),
            ),
          ],
        );
      },
    );
    if (confirmed != true) {
      return;
    }
    final reason = reasonCtrl.text.trim();
    if (reason.isEmpty) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(const SnackBar(content: Text('Motif obligatoire')));
      }
      return;
    }
    try {
      await widget.appState.api.rejectRestaurant(r.id, reason);
      await _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Action impossible: $e')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Validation restaurants')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: _items.isEmpty
                  ? const ListTile(title: Text('Aucun restaurant en attente'))
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
                                  'Proposé par: ${r.ownerName ?? 'Inconnu'}',
                                ),
                                const SizedBox(height: 8),
                                Row(
                                  children: [
                                    Expanded(
                                      child: FilledButton(
                                        onPressed: () => _accept(r),
                                        child: const Text('Accepter'),
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    Expanded(
                                      child: FilledButton.tonal(
                                        onPressed: () => _reject(r),
                                        child: const Text('Refuser'),
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
