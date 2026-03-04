import 'dart:io';

import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:image_picker/image_picker.dart';
import 'package:mobile_flutter/app_state.dart';
import 'package:mobile_flutter/models/restaurant.dart';
import 'package:mobile_flutter/widgets/restaurant_image.dart';

class RestaurantFormScreen extends StatefulWidget {
  const RestaurantFormScreen({
    super.key,
    required this.appState,
    this.restaurant,
  });

  final AppState appState;
  final Restaurant? restaurant;

  @override
  State<RestaurantFormScreen> createState() => _RestaurantFormScreenState();
}

class _RestaurantFormScreenState extends State<RestaurantFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameCtrl = TextEditingController();
  final _descriptionCtrl = TextEditingController();
  final _dateCtrl = TextEditingController();
  final _priceCtrl = TextEditingController();
  final _latitudeCtrl = TextEditingController();
  final _longitudeCtrl = TextEditingController();
  final _contactNameCtrl = TextEditingController();
  final _contactEmailCtrl = TextEditingController();

  final ImagePicker _picker = ImagePicker();
  XFile? _photoFile;
  bool _submitting = false;

  bool get _isEdit => widget.restaurant != null;

  @override
  void initState() {
    super.initState();
    final r = widget.restaurant;
    if (r != null) {
      _nameCtrl.text = r.name;
      _descriptionCtrl.text = r.description;
      _dateCtrl.text = r.eventDate;
      _priceCtrl.text = r.averagePrice.toString();
      _latitudeCtrl.text = r.latitude.toString();
      _longitudeCtrl.text = r.longitude.toString();
      _contactNameCtrl.text = r.contactName;
      _contactEmailCtrl.text = r.contactEmail;
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _descriptionCtrl.dispose();
    _dateCtrl.dispose();
    _priceCtrl.dispose();
    _latitudeCtrl.dispose();
    _longitudeCtrl.dispose();
    _contactNameCtrl.dispose();
    _contactEmailCtrl.dispose();
    super.dispose();
  }

  Future<void> _pickImage(ImageSource source) async {
    final file = await _picker.pickImage(source: source, imageQuality: 80);
    if (file != null) {
      setState(() => _photoFile = file);
    }
  }

  Future<void> _chooseImageSource() async {
    await showModalBottomSheet<void>(
      context: context,
      builder: (context) {
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ListTile(
                leading: const Icon(Icons.photo_library),
                title: const Text('Galerie'),
                onTap: () async {
                  Navigator.pop(context);
                  await _pickImage(ImageSource.gallery);
                },
              ),
              ListTile(
                leading: const Icon(Icons.photo_camera),
                title: const Text('Caméra'),
                onTap: () async {
                  Navigator.pop(context);
                  await _pickImage(ImageSource.camera);
                },
              ),
            ],
          ),
        );
      },
    );
  }

  Future<void> _fillCurrentLocation() async {
    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied ||
        permission == LocationPermission.deniedForever) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Permission localisation refusée')),
        );
      }
      return;
    }
    final pos = await Geolocator.getCurrentPosition();
    _latitudeCtrl.text = pos.latitude.toStringAsFixed(7);
    _longitudeCtrl.text = pos.longitude.toStringAsFixed(7);
    setState(() {});
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final initial = DateTime.tryParse(_dateCtrl.text) ?? now;
    final picked = await showDatePicker(
      context: context,
      firstDate: DateTime(now.year - 1),
      lastDate: DateTime(now.year + 3),
      initialDate: initial,
    );
    if (picked != null) {
      _dateCtrl.text =
          '${picked.year.toString().padLeft(4, '0')}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
      setState(() {});
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }
    if (!_isEdit && _photoFile == null) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Photo obligatoire')));
      return;
    }

    setState(() => _submitting = true);
    try {
      final fields = <String, String>{
        'name': _nameCtrl.text.trim(),
        'description': _descriptionCtrl.text.trim(),
        'event_date': _dateCtrl.text.trim(),
        'average_price': _priceCtrl.text.trim(),
        'latitude': _latitudeCtrl.text.trim(),
        'longitude': _longitudeCtrl.text.trim(),
        'contact_name': _contactNameCtrl.text.trim(),
        'contact_email': _contactEmailCtrl.text.trim(),
      };

      if (_isEdit) {
        await widget.appState.api.updateRestaurant(
          id: widget.restaurant!.id,
          fields: fields,
          photo: _photoFile,
        );
      } else {
        await widget.appState.api.createRestaurant(
          fields: fields,
          photo: _photoFile!,
        );
      }

      if (mounted) {
        Navigator.of(context).pop(true);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Enregistrement impossible: $e')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final existingPhoto = widget.restaurant?.photo ?? '';
    final previewUrl = _photoFile == null ? existingPhoto : '';

    return Scaffold(
      appBar: AppBar(
        title: Text(_isEdit ? 'Modifier restaurant' : 'Créer restaurant'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (previewUrl.isNotEmpty) RestaurantImage(url: previewUrl),
              if (_photoFile != null)
                Image.file(
                  File(_photoFile!.path),
                  height: 180,
                  fit: BoxFit.cover,
                ),
              OutlinedButton.icon(
                onPressed: _chooseImageSource,
                icon: const Icon(Icons.photo_camera_back),
                label: const Text('Choisir une photo'),
              ),
              TextFormField(
                controller: _nameCtrl,
                decoration: const InputDecoration(labelText: 'Nom'),
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? 'Champ requis' : null,
              ),
              TextFormField(
                controller: _descriptionCtrl,
                minLines: 2,
                maxLines: 4,
                decoration: const InputDecoration(labelText: 'Description'),
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? 'Champ requis' : null,
              ),
              TextFormField(
                controller: _dateCtrl,
                readOnly: true,
                decoration: const InputDecoration(labelText: 'Date'),
                onTap: _pickDate,
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? 'Champ requis' : null,
              ),
              TextFormField(
                controller: _priceCtrl,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Prix moyen'),
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? 'Champ requis' : null,
              ),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _latitudeCtrl,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                        signed: true,
                      ),
                      decoration: const InputDecoration(labelText: 'Latitude'),
                      validator: (v) => (v == null || v.trim().isEmpty)
                          ? 'Champ requis'
                          : null,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: TextFormField(
                      controller: _longitudeCtrl,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                        signed: true,
                      ),
                      decoration: const InputDecoration(labelText: 'Longitude'),
                      validator: (v) => (v == null || v.trim().isEmpty)
                          ? 'Champ requis'
                          : null,
                    ),
                  ),
                ],
              ),
              Align(
                alignment: Alignment.centerLeft,
                child: TextButton.icon(
                  onPressed: _fillCurrentLocation,
                  icon: const Icon(Icons.my_location),
                  label: const Text('Utiliser la position du téléphone'),
                ),
              ),
              TextFormField(
                controller: _contactNameCtrl,
                decoration: const InputDecoration(labelText: 'Contact nom'),
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? 'Champ requis' : null,
              ),
              TextFormField(
                controller: _contactEmailCtrl,
                decoration: const InputDecoration(labelText: 'Contact email'),
                keyboardType: TextInputType.emailAddress,
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? 'Champ requis' : null,
              ),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: _submitting ? null : _submit,
                child: _submitting
                    ? const SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : Text(_isEdit ? 'Sauvegarder' : 'Créer'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
