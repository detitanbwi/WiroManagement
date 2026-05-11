import 'package:shared_preferences/shared_preferences.dart';

class PreferenceService {
  static final PreferenceService instance = PreferenceService._();
  PreferenceService._();

  SharedPreferences? _prefs;

  Future<void> init() async {
    _prefs = await SharedPreferences.getInstance();
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
}
