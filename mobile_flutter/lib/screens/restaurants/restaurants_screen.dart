import 'package:flutter/material.dart';
import 'package:mobile_flutter/app_state.dart';
import 'package:mobile_flutter/models/restaurant.dart';
import 'package:mobile_flutter/screens/restaurants/restaurant_detail_screen.dart';
import 'package:mobile_flutter/screens/restaurants/restaurant_form_screen.dart';
import 'package:mobile_flutter/widgets/restaurant_image.dart';

class RestaurantsScreen extends StatefulWidget {
  const RestaurantsScreen({super.key, required this.appState});

  final AppState appState;

  @override
  State<RestaurantsScreen> createState() => _RestaurantsScreenState();
}

class _RestaurantsScreenState extends State<RestaurantsScreen> {
  static const int _perPage = 10;
  final _searchCtrl = TextEditingController();
  final _scrollCtrl = ScrollController();
  final List<Restaurant> _items = [];

  bool _loading = false;
  bool _loadingMore = false;
  bool _hasMore = true;
  int _page = 1;
  String _query = '';

  @override
  void initState() {
    super.initState();
    _scrollCtrl.addListener(_onScroll);
    _load(reset: true);
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (!_hasMore || _loading || _loadingMore) {
      return;
    }
    if (!_scrollCtrl.hasClients) {
      return;
    }
    final threshold = _scrollCtrl.position.maxScrollExtent * 0.8;
    if (_scrollCtrl.position.pixels >= threshold) {
      _loadMore();
    }
  }

  Future<void> _load({required bool reset}) async {
    if (reset) {
      setState(() {
        _loading = true;
        _page = 1;
        _hasMore = true;
        _items.clear();
      });
    }
    try {
      final rows = await widget.appState.api.restaurants(
        page: _page,
        perPage: _perPage,
        search: _query.isEmpty ? null : _query,
      );
      final fetched = rows
          .map((e) => Restaurant.fromJson(e as Map<String, dynamic>))
          .toList();
      final currentIds = _items.map((e) => e.id).toSet();
      final unique = fetched.where((e) => !currentIds.contains(e.id)).toList();

      setState(() {
        _items.addAll(unique);
        _hasMore = unique.length >= _perPage;
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Erreur chargement: $e')));
      }
    } finally {
      if (mounted) {
        setState(() {
          _loading = false;
          _loadingMore = false;
        });
      }
    }
  }

  Future<void> _loadMore() async {
    setState(() => _loadingMore = true);
    _page += 1;
    await _load(reset: false);
  }

  Future<void> _search() async {
    _query = _searchCtrl.text.trim();
    await _load(reset: true);
  }

  bool _canManage(Restaurant r) {
    final user = widget.appState.currentUser!;
    return user.isAdmin || r.createdBy == user.id;
  }

  Future<void> _deleteRestaurant(Restaurant restaurant) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Supprimer ?'),
          content: Text('Supprimer ${restaurant.name} ?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Non'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Oui'),
            ),
          ],
        );
      },
    );
    if (ok != true) {
      return;
    }
    try {
      await widget.appState.api.deleteRestaurant(restaurant.id);
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(const SnackBar(content: Text('Restaurant supprimé')));
      }
      await _load(reset: true);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Suppression impossible: $e')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Restaurants')),
      floatingActionButton: FloatingActionButton(
        onPressed: () async {
          final changed = await Navigator.of(context).push<bool>(
            MaterialPageRoute(
              builder: (_) => RestaurantFormScreen(appState: widget.appState),
            ),
          );
          if (changed == true) {
            await _load(reset: true);
          }
        },
        child: const Icon(Icons.add),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(12),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _searchCtrl,
                    decoration: const InputDecoration(
                      hintText: 'Rechercher...',
                      border: OutlineInputBorder(),
                    ),
                    onSubmitted: (_) => _search(),
                  ),
                ),
                const SizedBox(width: 8),
                FilledButton(onPressed: _search, child: const Text('OK')),
              ],
            ),
          ),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : RefreshIndicator(
                    onRefresh: () => _load(reset: true),
                    child: ListView.builder(
                      controller: _scrollCtrl,
                      itemCount: _items.length + (_loadingMore ? 1 : 0),
                      itemBuilder: (context, index) {
                        if (index >= _items.length) {
                          return const Padding(
                            padding: EdgeInsets.all(16),
                            child: Center(child: CircularProgressIndicator()),
                          );
                        }

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
                                RestaurantImage(url: r.photo, height: 140),
                                const SizedBox(height: 8),
                                Text(
                                  r.name,
                                  style: Theme.of(
                                    context,
                                  ).textTheme.titleMedium,
                                ),
                                Text('Prix moyen: ${r.averagePrice} EUR'),
                                const SizedBox(height: 8),
                                Wrap(
                                  spacing: 8,
                                  children: [
                                    OutlinedButton(
                                      onPressed: () async {
                                        final changed =
                                            await Navigator.of(
                                              context,
                                            ).push<bool>(
                                              MaterialPageRoute(
                                                builder: (_) =>
                                                    RestaurantDetailScreen(
                                                      appState: widget.appState,
                                                      restaurantId: r.id,
                                                    ),
                                              ),
                                            );
                                        if (changed == true) {
                                          await _load(reset: true);
                                        }
                                      },
                                      child: const Text('Détails'),
                                    ),
                                    if (_canManage(r))
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
                                            await _load(reset: true);
                                          }
                                        },
                                        child: const Text('Modifier'),
                                      ),
                                    if (_canManage(r))
                                      OutlinedButton(
                                        onPressed: () => _deleteRestaurant(r),
                                        child: const Text('Supprimer'),
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
          ),
        ],
      ),
    );
  }
}
