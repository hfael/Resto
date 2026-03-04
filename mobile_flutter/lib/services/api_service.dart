import 'dart:convert';
import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';

class ApiException implements Exception {
  const ApiException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}

class ApiService {
  ApiService({String? baseUrl})
    : _baseUrl = (baseUrl ?? _resolveBaseUrl()).replaceAll(RegExp(r'/$'), '');

  static String _resolveBaseUrl() {
    const fromEnv = String.fromEnvironment('API_BASE_URL');
    if (fromEnv.isNotEmpty) {
      return fromEnv;
    }
    if (kIsWeb) {
      return 'http://localhost:8080/api';
    }
    try {
      if (Platform.isAndroid) {
        return 'http://10.0.2.2:8080/api';
      }
    } catch (_) {
      return 'http://localhost:8080/api';
    }
    return 'http://localhost:8080/api';
  }

  final String _baseUrl;
  String? token;

  String get baseUrl => _baseUrl;

  Uri _uri(String path, [Map<String, String>? query]) {
    return Uri.parse('$_baseUrl$path').replace(queryParameters: query);
  }

  Map<String, String> _headers({bool useJson = true, bool useAuth = true}) {
    final headers = <String, String>{};
    if (useJson) {
      headers['Content-Type'] = 'application/json';
    }
    if (useAuth && token != null && token!.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    return headers;
  }

  dynamic _decodeBody(http.Response response) {
    if (response.body.isEmpty) {
      return null;
    }
    try {
      return json.decode(response.body);
    } catch (_) {
      return response.body;
    }
  }

  dynamic _handleResponse(http.Response response) {
    final body = _decodeBody(response);
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return body;
    }
    if (body is Map && body['error'] != null) {
      throw ApiException(
        body['error'].toString(),
        statusCode: response.statusCode,
      );
    }
    throw ApiException(
      'HTTP ${response.statusCode}',
      statusCode: response.statusCode,
    );
  }

  Future<Map<String, dynamic>> _getMap(
    String path, {
    Map<String, String>? query,
    bool useAuth = true,
  }) async {
    final response = await http.get(
      _uri(path, query),
      headers: _headers(useJson: false, useAuth: useAuth),
    );
    final data = _handleResponse(response);
    if (data is Map<String, dynamic>) {
      return data;
    }
    throw const ApiException('Réponse API invalide');
  }

  Future<List<dynamic>> _getList(
    String path, {
    Map<String, String>? query,
    bool useAuth = true,
  }) async {
    final response = await http.get(
      _uri(path, query),
      headers: _headers(useJson: false, useAuth: useAuth),
    );
    final data = _handleResponse(response);
    if (data is List) {
      return data;
    }
    throw const ApiException('Réponse API invalide');
  }

  Future<Map<String, dynamic>> _postJson(
    String path, {
    Map<String, dynamic>? body,
    bool useAuth = true,
  }) async {
    final response = await http.post(
      _uri(path),
      headers: _headers(useJson: true, useAuth: useAuth),
      body: json.encode(body ?? <String, dynamic>{}),
    );
    final data = _handleResponse(response);
    if (data is Map<String, dynamic>) {
      return data;
    }
    throw const ApiException('Réponse API invalide');
  }

  Future<Map<String, dynamic>> _delete(
    String path, {
    bool useAuth = true,
  }) async {
    final response = await http.delete(
      _uri(path),
      headers: _headers(useJson: false, useAuth: useAuth),
    );
    final data = _handleResponse(response);
    if (data is Map<String, dynamic>) {
      return data;
    }
    throw const ApiException('Réponse API invalide');
  }

  Future<Map<String, dynamic>> _sendMultipart(
    String method,
    String path, {
    required Map<String, String> fields,
    XFile? file,
    String fileField = 'photo',
    bool useAuth = true,
  }) async {
    final request = http.MultipartRequest(method, _uri(path));
    if (useAuth && token != null && token!.isNotEmpty) {
      request.headers['Authorization'] = 'Bearer $token';
    }
    request.fields.addAll(fields);
    if (file != null) {
      request.files.add(
        await http.MultipartFile.fromPath(fileField, file.path),
      );
    }
    final streamed = await request.send();
    final response = await http.Response.fromStream(streamed);
    final data = _handleResponse(response);
    if (data is Map<String, dynamic>) {
      return data;
    }
    throw const ApiException('Réponse API invalide');
  }

  Future<Map<String, dynamic>> register({
    required String username,
    required String email,
    required String password,
  }) {
    return _postJson(
      '/auth/register',
      useAuth: false,
      body: <String, dynamic>{
        'username': username,
        'email': email,
        'password': password,
      },
    );
  }

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) {
    return _postJson(
      '/auth/login',
      useAuth: false,
      body: <String, dynamic>{'email': email, 'password': password},
    );
  }

  Future<Map<String, dynamic>> me() {
    return _getMap('/auth/me');
  }

  Future<void> logout() async {
    try {
      await _postJson('/auth/logout', useAuth: false);
    } catch (_) {}
  }

  Future<List<dynamic>> restaurants({
    int page = 1,
    int perPage = 20,
    String? search,
  }) async {
    if (search != null && search.trim().isNotEmpty) {
      return _getList(
        '/restaurants/search',
        query: <String, String>{
          'q': search.trim(),
          'page': '$page',
          'per_page': '$perPage',
        },
        useAuth: false,
      );
    }
    return _getList(
      '/restaurants',
      query: <String, String>{'page': '$page', 'per_page': '$perPage'},
      useAuth: false,
    );
  }

  Future<Map<String, dynamic>> restaurant(int id) {
    return _getMap('/restaurants/$id', useAuth: false);
  }

  Future<List<dynamic>> myRestaurants() {
    return _getList('/restaurants/mine');
  }

  Future<List<dynamic>> pendingRestaurants() {
    return _getList('/restaurants/pending');
  }

  Future<Map<String, dynamic>> createRestaurant({
    required Map<String, String> fields,
    required XFile photo,
  }) {
    return _sendMultipart('POST', '/restaurants', fields: fields, file: photo);
  }

  Future<Map<String, dynamic>> updateRestaurant({
    required int id,
    required Map<String, String> fields,
    XFile? photo,
  }) {
    return _sendMultipart(
      'POST',
      '/restaurants/$id',
      fields: fields,
      file: photo,
    );
  }

  Future<Map<String, dynamic>> deleteRestaurant(int id) {
    return _delete('/restaurants/$id');
  }

  Future<Map<String, dynamic>> cancelRestaurant(int id) async {
    final response = await http.post(
      _uri('/restaurants/$id/cancel'),
      headers: _headers(useJson: false, useAuth: true),
    );
    final data = _handleResponse(response);
    if (data is Map<String, dynamic>) {
      return data;
    }
    throw const ApiException('Réponse API invalide');
  }

  Future<Map<String, dynamic>> acceptRestaurant(int id) async {
    final response = await http.post(
      _uri('/restaurants/$id/accept'),
      headers: _headers(useJson: false, useAuth: true),
    );
    final data = _handleResponse(response);
    if (data is Map<String, dynamic>) {
      return data;
    }
    throw const ApiException('Réponse API invalide');
  }

  Future<Map<String, dynamic>> rejectRestaurant(int id, String reason) {
    return _postJson(
      '/restaurants/$id/reject',
      body: <String, dynamic>{'reason': reason},
    );
  }

  Future<Map<String, dynamic>> adminUpdateRestaurantStatus({
    required int id,
    required String status,
    String? reason,
  }) async {
    final response = await http.patch(
      _uri('/admin/restaurants/$id/status'),
      headers: _headers(useJson: true, useAuth: true),
      body: json.encode(<String, dynamic>{
        'status': status,
        if (reason != null && reason.trim().isNotEmpty) 'reason': reason.trim(),
      }),
    );
    final data = _handleResponse(response);
    if (data is Map<String, dynamic>) {
      return data;
    }
    throw const ApiException('Réponse API invalide');
  }

  Future<Map<String, dynamic>> restaurantBookings(int id) {
    return _getMap('/restaurants/$id/bookings');
  }

  Future<Map<String, dynamic>> createReservation({
    required int restaurantId,
    required String reservationDate,
    required String reservationTime,
  }) {
    return _postJson(
      '/reservations',
      body: <String, dynamic>{
        'restaurant_id': restaurantId,
        'reservation_date': reservationDate,
        'reservation_time': reservationTime,
      },
    );
  }

  Future<List<dynamic>> userReservations() {
    return _getList('/reservations/user');
  }

  Future<Map<String, dynamic>> deleteReservation(int reservationId) {
    return _delete('/reservations/$reservationId');
  }

  String restaurantPdfUrl(int restaurantId) {
    return '$_baseUrl/restaurants/$restaurantId/pdf';
  }
}
