import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:mobile_flutter/models/user_profile.dart';
import 'package:mobile_flutter/services/api_service.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AppState extends ChangeNotifier {
  AppState({ApiService? api}) : api = api ?? ApiService();

  static const _tokenKey = 'resto_token';
  static const _userKey = 'resto_user';

  final ApiService api;
  UserProfile? currentUser;
  bool initialized = false;

  bool get isLoggedIn =>
      currentUser != null && (api.token?.isNotEmpty ?? false);

  Future<void> initialize() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString(_tokenKey);
    final userJson = prefs.getString(_userKey);

    if (token != null && token.isNotEmpty) {
      api.token = token;
      if (userJson != null && userJson.isNotEmpty) {
        currentUser = UserProfile.fromJson(
          json.decode(userJson) as Map<String, dynamic>,
        );
      }
      try {
        final me = await api.me();
        currentUser = UserProfile.fromJson(me);
        await _saveSession();
      } catch (_) {
        await clearSession();
      }
    }

    initialized = true;
    notifyListeners();
  }

  Future<void> login({required String email, required String password}) async {
    final data = await api.login(email: email, password: password);
    api.token = data['token'] as String?;
    currentUser = UserProfile.fromJson(data);
    await _saveSession();
    notifyListeners();
  }

  Future<void> register({
    required String username,
    required String email,
    required String password,
  }) async {
    final data = await api.register(
      username: username,
      email: email,
      password: password,
    );
    api.token = data['token'] as String?;
    currentUser = UserProfile.fromJson(data);
    await _saveSession();
    notifyListeners();
  }

  Future<void> refreshMe() async {
    final me = await api.me();
    currentUser = UserProfile.fromJson(me);
    await _saveSession();
    notifyListeners();
  }

  Future<void> logout() async {
    await api.logout();
    await clearSession();
    notifyListeners();
  }

  Future<void> clearSession() async {
    api.token = null;
    currentUser = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
    await prefs.remove(_userKey);
  }

  Future<void> _saveSession() async {
    if (api.token == null || currentUser == null) {
      return;
    }
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, api.token!);
    await prefs.setString(_userKey, json.encode(currentUser!.toJson()));
  }
}
