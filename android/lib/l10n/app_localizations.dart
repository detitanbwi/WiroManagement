import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;

import 'app_localizations_en.dart';
import 'app_localizations_id.dart';

// ignore_for_file: type=lint

/// Callers can lookup localized strings with an instance of AppLocalizations
/// returned by `AppLocalizations.of(context)`.
///
/// Applications need to include `AppLocalizations.delegate()` in their app's
/// `localizationDelegates` list, and the locales they support in the app's
/// `supportedLocales` list. For example:
///
/// ```dart
/// import 'l10n/app_localizations.dart';
///
/// return MaterialApp(
///   localizationsDelegates: AppLocalizations.localizationsDelegates,
///   supportedLocales: AppLocalizations.supportedLocales,
///   home: MyApplicationHome(),
/// );
/// ```
///
/// ## Update pubspec.yaml
///
/// Please make sure to update your pubspec.yaml to include the following
/// packages:
///
/// ```yaml
/// dependencies:
///   # Internationalization support.
///   flutter_localizations:
///     sdk: flutter
///   intl: any # Use the pinned version from flutter_localizations
///
///   # Rest of dependencies
/// ```
///
/// ## iOS Applications
///
/// iOS applications define key application metadata, including supported
/// locales, in an Info.plist file that is built into the application bundle.
/// To configure the locales supported by your app, you’ll need to edit this
/// file.
///
/// First, open your project’s ios/Runner.xcworkspace Xcode workspace file.
/// Then, in the Project Navigator, open the Info.plist file under the Runner
/// project’s Runner folder.
///
/// Next, select the Information Property List item, select Add Item from the
/// Editor menu, then select Localizations from the pop-up menu.
///
/// Select and expand the newly-created Localizations item then, for each
/// locale your application supports, add a new item and select the locale
/// you wish to add from the pop-up menu in the Value field. This list should
/// be consistent with the languages listed in the AppLocalizations.supportedLocales
/// property.
abstract class AppLocalizations {
  AppLocalizations(String locale)
    : localeName = intl.Intl.canonicalizedLocale(locale.toString());

  final String localeName;

  static AppLocalizations? of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations);
  }

  static const LocalizationsDelegate<AppLocalizations> delegate =
      _AppLocalizationsDelegate();

  /// A list of this localizations delegate along with the default localizations
  /// delegates.
  ///
  /// Returns a list of localizations delegates containing this delegate along with
  /// GlobalMaterialLocalizations.delegate, GlobalCupertinoLocalizations.delegate,
  /// and GlobalWidgetsLocalizations.delegate.
  ///
  /// Additional delegates can be added by appending to this list in
  /// MaterialApp. This list does not have to be used at all if a custom list
  /// of delegates is preferred or required.
  static const List<LocalizationsDelegate<dynamic>> localizationsDelegates =
      <LocalizationsDelegate<dynamic>>[
        delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
      ];

  /// A list of this localizations delegate's supported locales.
  static const List<Locale> supportedLocales = <Locale>[
    Locale('en'),
    Locale('id'),
  ];

  /// No description provided for @appName.
  ///
  /// In id, this message translates to:
  /// **'WiroFin'**
  String get appName;

  /// No description provided for @personalMode.
  ///
  /// In id, this message translates to:
  /// **'Personal'**
  String get personalMode;

  /// No description provided for @companyMode.
  ///
  /// In id, this message translates to:
  /// **'Perusahaan'**
  String get companyMode;

  /// No description provided for @dashboard.
  ///
  /// In id, this message translates to:
  /// **'Beranda'**
  String get dashboard;

  /// No description provided for @transactions.
  ///
  /// In id, this message translates to:
  /// **'Transaksi'**
  String get transactions;

  /// No description provided for @masterData.
  ///
  /// In id, this message translates to:
  /// **'Master Data'**
  String get masterData;

  /// No description provided for @netBalance.
  ///
  /// In id, this message translates to:
  /// **'SALDO BERSIH'**
  String get netBalance;

  /// No description provided for @income.
  ///
  /// In id, this message translates to:
  /// **'PEMASUKAN'**
  String get income;

  /// No description provided for @expense.
  ///
  /// In id, this message translates to:
  /// **'PENGELUARAN'**
  String get expense;

  /// No description provided for @addTransaction.
  ///
  /// In id, this message translates to:
  /// **'Catat Transaksi'**
  String get addTransaction;

  /// No description provided for @editTransaction.
  ///
  /// In id, this message translates to:
  /// **'Edit Transaksi'**
  String get editTransaction;

  /// No description provided for @amount.
  ///
  /// In id, this message translates to:
  /// **'Nominal'**
  String get amount;

  /// No description provided for @descriptionOptional.
  ///
  /// In id, this message translates to:
  /// **'Keterangan (Opsional)'**
  String get descriptionOptional;

  /// No description provided for @save.
  ///
  /// In id, this message translates to:
  /// **'Simpan'**
  String get save;

  /// No description provided for @cancel.
  ///
  /// In id, this message translates to:
  /// **'Batal'**
  String get cancel;

  /// No description provided for @delete.
  ///
  /// In id, this message translates to:
  /// **'Hapus'**
  String get delete;

  /// No description provided for @confirmDelete.
  ///
  /// In id, this message translates to:
  /// **'Apakah Anda yakin ingin menghapus ini?'**
  String get confirmDelete;

  /// No description provided for @successSave.
  ///
  /// In id, this message translates to:
  /// **'Data berhasil disimpan'**
  String get successSave;

  /// No description provided for @successDelete.
  ///
  /// In id, this message translates to:
  /// **'Data berhasil dihapus'**
  String get successDelete;

  /// No description provided for @successUpdate.
  ///
  /// In id, this message translates to:
  /// **'Data berhasil diperbarui'**
  String get successUpdate;

  /// No description provided for @emptyTransaction.
  ///
  /// In id, this message translates to:
  /// **'Belum ada transaksi'**
  String get emptyTransaction;

  /// No description provided for @profile.
  ///
  /// In id, this message translates to:
  /// **'Profil'**
  String get profile;

  /// No description provided for @account.
  ///
  /// In id, this message translates to:
  /// **'Rekening'**
  String get account;

  /// No description provided for @category.
  ///
  /// In id, this message translates to:
  /// **'Kategori'**
  String get category;

  /// No description provided for @backupRestore.
  ///
  /// In id, this message translates to:
  /// **'Backup & Restore'**
  String get backupRestore;

  /// No description provided for @exportJson.
  ///
  /// In id, this message translates to:
  /// **'Ekspor Data ke JSON'**
  String get exportJson;

  /// No description provided for @importJson.
  ///
  /// In id, this message translates to:
  /// **'Impor Data dari JSON'**
  String get importJson;

  /// No description provided for @voiceRecord.
  ///
  /// In id, this message translates to:
  /// **'Pencatatan Suara/Teks'**
  String get voiceRecord;

  /// No description provided for @language.
  ///
  /// In id, this message translates to:
  /// **'Bahasa'**
  String get language;

  /// No description provided for @languageSwitch.
  ///
  /// In id, this message translates to:
  /// **'Ganti Bahasa'**
  String get languageSwitch;

  /// No description provided for @onboardingTitle1.
  ///
  /// In id, this message translates to:
  /// **'Kelola Uang Lebih Baik'**
  String get onboardingTitle1;

  /// No description provided for @onboardingSubtitle1.
  ///
  /// In id, this message translates to:
  /// **'Pantau pengeluaran, atur kategori, dan simpan data Anda secara offline dengan aman.'**
  String get onboardingSubtitle1;

  /// No description provided for @agreeTerms.
  ///
  /// In id, this message translates to:
  /// **'Saya menyetujui '**
  String get agreeTerms;

  /// No description provided for @termsConditions.
  ///
  /// In id, this message translates to:
  /// **'Syarat & Ketentuan'**
  String get termsConditions;

  /// No description provided for @usageWiroFin.
  ///
  /// In id, this message translates to:
  /// **' penggunaan WiroFin.'**
  String get usageWiroFin;

  /// No description provided for @startJourney.
  ///
  /// In id, this message translates to:
  /// **'Mulai Perjalanan Anda'**
  String get startJourney;

  /// No description provided for @startFresh.
  ///
  /// In id, this message translates to:
  /// **'Mulai dari Awal'**
  String get startFresh;

  /// No description provided for @startFreshDesc.
  ///
  /// In id, this message translates to:
  /// **'Buat akun baru dan atur profil Anda.'**
  String get startFreshDesc;

  /// No description provided for @importOldData.
  ///
  /// In id, this message translates to:
  /// **'Import Data Lama'**
  String get importOldData;

  /// No description provided for @importOldDataDesc.
  ///
  /// In id, this message translates to:
  /// **'Pulihkan data dari file backup JSON.'**
  String get importOldDataDesc;

  /// No description provided for @whatsYourName.
  ///
  /// In id, this message translates to:
  /// **'Siapa nama Anda?'**
  String get whatsYourName;

  /// No description provided for @nameGreetingDesc.
  ///
  /// In id, this message translates to:
  /// **'Nama ini akan digunakan untuk menyapa Anda.'**
  String get nameGreetingDesc;

  /// No description provided for @enterName.
  ///
  /// In id, this message translates to:
  /// **'Masukkan nama'**
  String get enterName;

  /// No description provided for @mainAccount.
  ///
  /// In id, this message translates to:
  /// **'Rekening Utama'**
  String get mainAccount;

  /// No description provided for @enterInitialBalance.
  ///
  /// In id, this message translates to:
  /// **'Masukkan saldo awal Anda saat ini.'**
  String get enterInitialBalance;

  /// No description provided for @accountNameHint.
  ///
  /// In id, this message translates to:
  /// **'Nama Rekening (Misal: BCA / Tunai)'**
  String get accountNameHint;

  /// No description provided for @back.
  ///
  /// In id, this message translates to:
  /// **'Kembali'**
  String get back;

  /// No description provided for @next.
  ///
  /// In id, this message translates to:
  /// **'Lanjut'**
  String get next;

  /// No description provided for @finish.
  ///
  /// In id, this message translates to:
  /// **'Selesai'**
  String get finish;
}

class _AppLocalizationsDelegate
    extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  Future<AppLocalizations> load(Locale locale) {
    return SynchronousFuture<AppLocalizations>(lookupAppLocalizations(locale));
  }

  @override
  bool isSupported(Locale locale) =>
      <String>['en', 'id'].contains(locale.languageCode);

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

AppLocalizations lookupAppLocalizations(Locale locale) {
  // Lookup logic when only language code is specified.
  switch (locale.languageCode) {
    case 'en':
      return AppLocalizationsEn();
    case 'id':
      return AppLocalizationsId();
  }

  throw FlutterError(
    'AppLocalizations.delegate failed to load unsupported locale "$locale". This is likely '
    'an issue with the localizations generation tool. Please file an issue '
    'on GitHub with a reproducible sample app and the gen-l10n configuration '
    'that was used.',
  );
}
