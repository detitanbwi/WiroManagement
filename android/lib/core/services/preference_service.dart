import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class PreferenceService {
  static final PreferenceService instance = PreferenceService._();
  PreferenceService._();

  SharedPreferences? _prefs;
  final ValueNotifier<Locale> localeNotifier = ValueNotifier<Locale>(const Locale('id'));

  Future<void> init() async {
    _prefs = await SharedPreferences.getInstance();
    final lang = _prefs?.getString('language_code') ?? 'id';
    localeNotifier.value = Locale(lang);
  }

  bool get isFirstLaunch {
    return _prefs?.getBool('is_first_launch') ?? true;
  }

  Future<void> setFirstLaunch(bool value) async {
    await _prefs?.setBool('is_first_launch', value);
  }

  String get userName {
    return _prefs?.getString('user_name') ?? 'User';
  }

  Future<void> setUserName(String name) async {
    await _prefs?.setString('user_name', name);
  }

  String get languageCode {
    return _prefs?.getString('language_code') ?? 'id';
  }

  Future<void> setLanguage(String langCode) async {
    await _prefs?.setString('language_code', langCode);
    localeNotifier.value = Locale(langCode);
  }
}
