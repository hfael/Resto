import 'package:flutter/material.dart';
import 'package:mobile_flutter/app_state.dart';
import 'package:mobile_flutter/models/restaurant.dart';
import 'package:mobile_flutter/widgets/restaurant_image.dart';
import 'package:mobile_flutter/widgets/restaurant_map.dart';
import 'package:url_launcher/url_launcher.dart';

class RestaurantDetailScreen extends StatefulWidget {
  const RestaurantDetailScreen({
    super.key,
    required this.appState,
    required this.restaurantId,
  });

  final AppState appState;
  final int restaurantId;

  @override
  State<RestaurantDetailScreen> createState() => _RestaurantDetailScreenState();
}

class _RestaurantDetailScreenState extends State<RestaurantDetailScreen> {
  Restaurant? _restaurant;
  bool _loading = true;
  bool _submitting = false;
  DateTime? _selectedDate;
  TimeOfDay? _selectedTime;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final row = await widget.appState.api.restaurant(widget.restaurantId);
      setState(() => _restaurant = Restaurant.fromJson(row));
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

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      firstDate: now,
      lastDate: now.add(const Duration(days: 365)),
      initialDate: _selectedDate ?? now,
    );
    if (picked != null) {
      setState(() => _selectedDate = picked);
    }
  }

  Future<void> _pickTime() async {
    final picked = await showTimePicker(
      context: context,
      initialTime: _selectedTime ?? const TimeOfDay(hour: 20, minute: 0),
    );
    if (picked != null) {
      setState(() => _selectedTime = picked);
    }
  }

  Future<void> _openPdf() async {
    final url = widget.appState.api.restaurantPdfUrl(widget.restaurantId);
    final uri = Uri.parse(url);
    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Impossible d\'ouvrir le PDF')),
      );
    }
  }

  Future<void> _reserve() async {
    final r = _restaurant;
    if (r == null) return;
    if (_selectedDate == null || _selectedTime == null) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Date et heure requises')));
      return;
    }

    final day = _selectedDate!;
    final hour = _selectedTime!;
    final date =
        '${day.year.toString().padLeft(4, '0')}-${day.month.toString().padLeft(2, '0')}-${day.day.toString().padLeft(2, '0')}';
    final time =
        '${hour.hour.toString().padLeft(2, '0')}:${hour.minute.toString().padLeft(2, '0')}';

    setState(() => _submitting = true);
    try {
      final data = await widget.appState.api.createReservation(
        restaurantId: r.id,
        reservationDate: date,
        reservationTime: time,
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Réservation créée. Code: ${data['code'] ?? ''}'),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Réservation impossible: $e')));
      }
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final r = _restaurant;
    final hasLocation = r != null && (r.latitude != 0 || r.longitude != 0);
    return Scaffold(
      appBar: AppBar(title: const Text('Détails')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : r == null
          ? const Center(child: Text('Restaurant introuvable'))
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                RestaurantImage(url: r.photo, height: 220),
                const SizedBox(height: 12),
                Text(r.name, style: Theme.of(context).textTheme.headlineSmall),
                const SizedBox(height: 8),
                Text(r.description),
                const SizedBox(height: 12),
                Text('Date: ${r.eventDate}'),
                Text('Prix moyen: ${r.averagePrice} EUR'),
                Text('Latitude: ${r.latitude}'),
                Text('Longitude: ${r.longitude}'),
                const SizedBox(height: 8),
                Text(
                  'Localisation',
                  style: Theme.of(context).textTheme.titleSmall,
                ),
                const SizedBox(height: 8),
                if (hasLocation)
                  RestaurantMap(
                    latitude: r.latitude,
                    longitude: r.longitude,
                  )
                else
                  const Text('Localisation non renseignee'),
                const SizedBox(height: 8),
                Text('Contact: ${r.contactName}'),
                Text('Email: ${r.contactEmail}'),
                const SizedBox(height: 12),
                OutlinedButton(
                  onPressed: _openPdf,
                  child: const Text('Exporter en PDF'),
                ),
                const Divider(height: 24),
                Text(
                  'Réserver',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: _pickDate,
                        child: Text(
                          _selectedDate == null
                              ? 'Choisir date'
                              : '${_selectedDate!.day.toString().padLeft(2, '0')}/${_selectedDate!.month.toString().padLeft(2, '0')}/${_selectedDate!.year}',
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: OutlinedButton(
                        onPressed: _pickTime,
                        child: Text(
                          _selectedTime == null
                              ? 'Choisir heure'
                              : '${_selectedTime!.hour.toString().padLeft(2, '0')}:${_selectedTime!.minute.toString().padLeft(2, '0')}',
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                FilledButton(
                  onPressed: _submitting ? null : _reserve,
                  child: _submitting
                      ? const SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Text('Réserver'),
                ),
              ],
            ),
    );
  }
}
