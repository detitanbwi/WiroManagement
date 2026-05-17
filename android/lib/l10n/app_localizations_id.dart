// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Indonesian (`id`).
class AppLocalizationsId extends AppLocalizations {
  AppLocalizationsId([String locale = 'id']) : super(locale);

  @override
  String get appName => 'WiroFin';

  @override
  String get greetingPrefix => 'Halo';

  @override
  String get personalMode => 'Pribadi';

  @override
  String get companyMode => 'Perusahaan';

  @override
  String get dashboard => 'Beranda';

  @override
  String get transactions => 'Transaksi';

  @override
  String get masterData => 'Master Data';

  @override
  String get allTime => 'Semua Waktu';

  @override
  String get netBalance => 'Saldo Bersih';

  @override
  String get income => 'Pemasukan';

  @override
  String get expense => 'Pengeluaran';

  @override
  String get liveUpdate => 'Terbaru';

  @override
  String get expenseDistribution => 'Distribusi Pengeluaran';

  @override
  String get transactionHistory => 'Riwayat Transaksi';

  @override
  String get seeAll => 'Lihat Semua';

  @override
  String get exitAppTitle => 'Keluar Aplikasi';

  @override
  String get exitAppMessage =>
      'Apakah Anda yakin ingin keluar dari aplikasi WiroFin?';

  @override
  String get cancel => 'Batal';

  @override
  String get exit => 'Keluar';

  @override
  String get recordTransaction => 'Catat Transaksi';

  @override
  String get editTransaction => 'Edit Transaksi';

  @override
  String get amount => 'Nominal';

  @override
  String get descriptionOptional => 'Keterangan (Opsional)';

  @override
  String get save => 'Simpan';

  @override
  String get delete => 'Hapus';

  @override
  String get confirmDelete => 'Apakah Anda yakin ingin menghapus ini?';

  @override
  String get successSave => 'Data berhasil disimpan';

  @override
  String get successDelete => 'Data berhasil dihapus';

  @override
  String get successUpdate => 'Data berhasil diperbarui';

  @override
  String get successSaveTransaction => 'Transaksi berhasil tersimpan';

  @override
  String get amountEmptyError => 'Nominal tidak boleh kosong';

  @override
  String get nlpTitle => 'Pencatatan Cerdas (NLP)';

  @override
  String get nlpSubtitle => 'Ketik kalimat kasual, WiroFin otomatis mengisinya';

  @override
  String get nlpExampleHint => 'Contoh: Beli Bensin 50rb kemarin...';

  @override
  String get nlpTrySentence => 'Coba kalimat berikut:';

  @override
  String get nlpSample1 => 'Ngopi kopi kenangan 28k';

  @override
  String get nlpSample2 => 'Gaji bulanan masuk 5jt';

  @override
  String get nlpSample3 => 'Beli bensin pertamax 50rb kemarin';

  @override
  String get nlpSample4 => 'Makan siang warteg 20000';

  @override
  String get nlpErrorNoAmount => 'Nominal transaksi tidak ditemukan';

  @override
  String get nlpErrorProcess => 'Gagal memproses kalimat';

  @override
  String get allTransactionsTitle => 'Semua Transaksi';

  @override
  String get showingAll => 'Menampilkan Semua';

  @override
  String get filterLabel => 'Filter';

  @override
  String get transactionCountLabel => 'Transaksi';

  @override
  String get successUpdateTransaction => 'Transaksi berhasil diperbarui';

  @override
  String get deleteTransactionTitle => 'Hapus Transaksi';

  @override
  String get confirmDeleteTransaction =>
      'Apakah Anda yakin ingin menghapus transaksi ini?';

  @override
  String get successDeleteTransaction => 'Transaksi berhasil dihapus';

  @override
  String get emptyTransaction => 'Belum ada transaksi';

  @override
  String get aboutWiroFin => 'Tentang WiroFin';

  @override
  String get versionLabel => 'Versi';

  @override
  String get developerLabel => 'Pengembang';

  @override
  String get createdOnLabel => 'Dibuat Pada';

  @override
  String get licenseLabel => 'Lisensi';

  @override
  String get freeToUseLabel => 'Free to Use';

  @override
  String get legalLabel => 'Hukum';

  @override
  String get websiteLabel => 'Website';

  @override
  String get userProfile => 'Profil Pengguna';

  @override
  String get bankAccountManagement => 'Pengelolaan Rekening';

  @override
  String get transactionCategory => 'Kategori Transaksi';

  @override
  String get dataBackupRestore => 'Cadangan Data (Backup & Restore)';

  @override
  String get profile => 'Profil';

  @override
  String get usernameLabel => 'Nama Panggilan';

  @override
  String get saveProfile => 'Simpan Profil';

  @override
  String get successSaveProfile => 'Profil Berhasil Disimpan';

  @override
  String get language => 'Bahasa';

  @override
  String get languageSwitch => 'Indonesia / English';

  @override
  String get addBankAccount => 'Tambah Rekening';

  @override
  String get editBankAccount => 'Edit Rekening';

  @override
  String get bankAccountName => 'Nama Rekening';

  @override
  String get initialBalance => 'Saldo Awal';

  @override
  String get deleteDataTitle => 'Hapus Data';

  @override
  String get confirmDeleteAccount =>
      'Apakah Anda yakin ingin menghapus rekening ini?';

  @override
  String get successSaveAccount => 'Rekening berhasil disimpan';

  @override
  String get successDeleteAccount => 'Rekening berhasil dihapus';

  @override
  String get emptyAccount => 'Belum ada data rekening';

  @override
  String get addCategory => 'Tambah Kategori';

  @override
  String get editCategory => 'Edit Kategori';

  @override
  String get categoryName => 'Nama Kategori';

  @override
  String get transactionType => 'Jenis Transaksi';

  @override
  String get confirmDeleteCategory =>
      'Apakah Anda yakin ingin menghapus kategori ini?';

  @override
  String get successSaveCategory => 'Kategori berhasil disimpan';

  @override
  String get successDeleteCategory => 'Kategori berhasil dihapus';

  @override
  String get emptyCategory => 'Belum ada data kategori';

  @override
  String get defaultCategoryError =>
      'Kategori default \"Other\" tidak dapat dihapus.';

  @override
  String get defaultLabel => 'Default';

  @override
  String get backupRestoreTitle => 'Cadangan & Pemulihan Data';

  @override
  String get backupRestoreDesc =>
      'Simpan data Anda ke file lokal agar bisa dipulihkan kembali nanti';

  @override
  String get exportJson => 'Ekspor Data ke JSON';

  @override
  String get importJson => 'Impor Data dari JSON';

  @override
  String get successExport => 'Data Berhasil diekspor ke ';

  @override
  String get confirmRestoreTitle => 'Konfirmasi Restore';

  @override
  String get confirmRestoreWarning =>
      'PERHATIAN: Mengimpor data akan menghapus semua data saat ini dan menggantinya dengan isi file backup. Lanjutkan?';

  @override
  String get continueLabel => 'Lanjutkan';

  @override
  String get successImport => 'Data berhasil diimpor!';

  @override
  String get voiceRecord => 'Pencatatan Suara/Teks';

  @override
  String get onboardingTitle1 => 'Kelola Uang Lebih Baik';

  @override
  String get onboardingSubtitle1 =>
      'Pantau pengeluaran, atur kategori, dan simpan data Anda secara offline dengan aman.';

  @override
  String get agreeTerms => 'Saya menyetujui ';

  @override
  String get termsConditions => 'Syarat & Ketentuan';

  @override
  String get usageWiroFin => ' penggunaan WiroFin.';

  @override
  String get startJourney => 'Mulai Perjalanan Anda';

  @override
  String get startFresh => 'Mulai dari Awal';

  @override
  String get startFreshDesc => 'Buat akun baru dan atur profil Anda.';

  @override
  String get importOldData => 'Import Data Lama';

  @override
  String get importOldDataDesc => 'Pulihkan data dari file backup JSON.';

  @override
  String get whatsYourName => 'Siapa nama Anda?';

  @override
  String get nameGreetingDesc => 'Nama ini akan digunakan untuk menyapa Anda.';

  @override
  String get enterName => 'Masukkan nama';

  @override
  String get mainAccount => 'Rekening Utama';

  @override
  String get enterInitialBalance => 'Masukkan saldo awal Anda saat ini.';

  @override
  String get accountNameHint => 'Nama Rekening (Misal: BCA / Tunai)';

  @override
  String get back => 'Kembali';

  @override
  String get next => 'Lanjut';

  @override
  String get finish => 'Selesai';

  @override
  String get updateAvailableTitle => 'Update Tersedia!';

  @override
  String get updateAvailableMessage =>
      'Versi terbaru WiroFin sudah tersedia di Play Store. Silakan update untuk melanjutkan menggunakan aplikasi.';

  @override
  String get updateNow => 'Update Sekarang';
}
