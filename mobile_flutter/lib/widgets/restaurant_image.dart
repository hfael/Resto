import 'package:flutter/material.dart';

class RestaurantImage extends StatelessWidget {
  const RestaurantImage({super.key, required this.url, this.height = 160});

  final String url;
  final double height;

  @override
  Widget build(BuildContext context) {
    if (url.isEmpty) {
      return Container(
        height: height,
        color: Colors.grey.shade300,
        alignment: Alignment.center,
        child: const Icon(Icons.image_not_supported),
      );
    }
    return ClipRRect(
      borderRadius: BorderRadius.circular(8),
      child: Image.network(
        url,
        height: height,
        width: double.infinity,
        fit: BoxFit.cover,
        errorBuilder: (context, error, stackTrace) {
          return Container(
            height: height,
            color: Colors.grey.shade300,
            alignment: Alignment.center,
            child: const Icon(Icons.broken_image),
          );
        },
      ),
    );
  }
}
