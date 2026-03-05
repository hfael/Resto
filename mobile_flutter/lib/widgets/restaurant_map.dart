import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

class RestaurantMap extends StatefulWidget {
  const RestaurantMap({
    super.key,
    required this.latitude,
    required this.longitude,
    this.onTap,
    this.height = 220,
    this.zoom = 15,
  });

  final double latitude;
  final double longitude;
  final void Function(double lat, double lng)? onTap;
  final double height;
  final double zoom;

  @override
  State<RestaurantMap> createState() => _RestaurantMapState();
}

class _RestaurantMapState extends State<RestaurantMap> {
  final MapController _controller = MapController();

  @override
  void didUpdateWidget(RestaurantMap oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.latitude == oldWidget.latitude &&
        widget.longitude == oldWidget.longitude) {
      return;
    }

    final zoom = _controller.camera.zoom;
    _controller.move(LatLng(widget.latitude, widget.longitude), zoom);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final center = LatLng(widget.latitude, widget.longitude);

    return SizedBox(
      height: widget.height,
      child: ClipRRect(
        borderRadius: BorderRadius.circular(12),
        child: FlutterMap(
          mapController: _controller,
          options: MapOptions(
            initialCenter: center,
            initialZoom: widget.zoom,
            onTap: widget.onTap == null
                ? null
                : (tapPosition, latlng) {
                    widget.onTap!(latlng.latitude, latlng.longitude);
                  },
          ),
          children: [
            TileLayer(
              urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
              userAgentPackageName: 'com.cesi.mobile_flutter',
            ),
            MarkerLayer(
              markers: [
                Marker(
                  point: center,
                  width: 44,
                  height: 44,
                  child: const Icon(
                    Icons.location_on,
                    color: Colors.red,
                    size: 36,
                  ),
                ),
              ],
            ),
            const SimpleAttributionWidget(
              source: Text('OpenStreetMap contributors'),
            ),
          ],
        ),
      ),
    );
  }
}
