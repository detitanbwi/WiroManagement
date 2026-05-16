// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for English (`en`).
class AppLocalizationsEn extends AppLocalizations {
  AppLocalizationsEn([String locale = 'en']) : super(locale);

  @override
  String get appName => 'WiroFin';

  @override
  String get personalMode => 'Personal';

  @override
  String get companyMode => 'Company';

  @override
  String get dashboard => 'Dashboard';

  @override
  String get transactions => 'Transactions';

  @override
  String get masterData => 'Master Data';

  @override
  String get netBalance => 'NET BALANCE';

  @override
  String get income => 'INCOME';

  @override
  String get expense => 'EXPENSE';

  @override
  String get addTransaction => 'Add Transaction';

  @override
  String get editTransaction => 'Edit Transaction';

  @override
  String get amount => 'Amount';

  @override
  String get descriptionOptional => 'Description (Optional)';

  @override
  String get save => 'Save';

  @override
  String get cancel => 'Cancel';

  @override
  String get delete => 'Delete';

  @override
  String get confirmDelete => 'Are you sure you want to delete this?';

  @override
  String get successSave => 'Data successfully saved';

  @override
  String get successDelete => 'Data successfully deleted';

  @override
  String get successUpdate => 'Data successfully updated';

  @override
  String get emptyTransaction => 'No transactions yet';

  @override
  String get profile => 'Profile';

  @override
  String get account => 'Account';

  @override
  String get category => 'Category';

  @override
  String get backupRestore => 'Backup & Restore';

  @override
  String get exportJson => 'Export Data to JSON';

  @override
  String get importJson => 'Import Data from JSON';

  @override
  String get voiceRecord => 'Voice/Text Recording';

  @override
  String get language => 'Language';

  @override
  String get languageSwitch => 'Switch Language';

  @override
  String get onboardingTitle1 => 'Manage Money Better';

  @override
  String get onboardingSubtitle1 =>
      'Track expenses, manage categories, and store your data securely offline.';

  @override
  String get agreeTerms => 'I agree to the ';

  @override
  String get termsConditions => 'Terms & Conditions';

  @override
  String get usageWiroFin => ' of WiroFin.';

  @override
  String get startJourney => 'Start Your Journey';

  @override
  String get startFresh => 'Start Fresh';

  @override
  String get startFreshDesc => 'Create a new account and set up your profile.';

  @override
  String get importOldData => 'Import Existing Data';

  @override
  String get importOldDataDesc => 'Restore your data from a JSON backup file.';

  @override
  String get whatsYourName => 'What is your name?';

  @override
  String get nameGreetingDesc => 'This name will be used to greet you.';

  @override
  String get enterName => 'Enter your name';

  @override
  String get mainAccount => 'Main Account';

  @override
  String get enterInitialBalance => 'Enter your current initial balance.';

  @override
  String get accountNameHint => 'Account Name (e.g., BCA / Cash)';

  @override
  String get back => 'Back';

  @override
  String get next => 'Next';

  @override
  String get finish => 'Finish';
}
