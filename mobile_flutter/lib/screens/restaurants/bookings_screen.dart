import 'package:flutter/material.dart';
import 'package:mobile_flutter/app_state.dart';
import 'package:mobile_flutter/models/reservation.dart';

class BookingsScreen extends StatefulWidget {
  const BookingsScreen({
    super.key,
    required this.appState,
    required this.restaurantId,
  });

  final AppState appState;
  final int restaurantId;

  @override
  State<BookingsScreen> createState() => _BookingsScreenState();
}

class _BookingsScreenState extends State<BookingsScreen> {
  bool _loading = true;
  String _restaurantName = '';
  List<Reservation> _bookings = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final data = await widget.appState.api.restaurantBookings(
        widget.restaurantId,
      );
      final restaurant = data['restaurant'] as Map<String, dynamic>? ?? {};
      final rows = (data['bookings'] as List<dynamic>? ?? []);
      _restaurantName = (restaurant['name'] ?? 'Restaurant') as String;
      _bookings = rows
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
      appBar: AppBar(title: Text('Reservations: $_restaurantName')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _bookings.isEmpty
          ? const Center(child: Text('Aucune reservation'))
          : ListView.separated(
              itemCount: _bookings.length,
              separatorBuilder: (context, index) => const Divider(height: 1),
              itemBuilder: (context, index) {
                final b = _bookings[index];
                return ListTile(
                  title: Text('${b.username ?? '-'} (${b.email ?? '-'})'),
                  subtitle: Text(
                    'Date: ${b.reservationDate} - Heure: ${b.reservationTime}',
                  ),
                  trailing: Text(b.code),
                );
              },
            ),
    );
  }
}
