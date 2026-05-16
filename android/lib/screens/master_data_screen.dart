import 'package:flutter/material.dart';
import '../l10n/app_localizations.dart';
import '../services/database_helper.dart';
import '../services/backup_service.dart';
import '../core/services/preference_service.dart';
import '../widgets/top_toast.dart';

class MasterDataScreen extends StatelessWidget {
  final String activeMode;
  const MasterDataScreen({super.key, required this.activeMode});

  @override
  Widget build(BuildContext context) {
    final isPersonal = activeMode == 'personal';
    // Gunakan warna sesuai instruksi MoM
    final bgColor = isPersonal ? const Color(0xFFF8FAFC) : const Color(0xFFF1F5F9);
    final leadingIconColor = isPersonal ? const Color(0xFF2563EB) : const Color(0xFFD4AF37);
    final trailingIconColor = const Color(0xFF334155);

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        title: const Text('Master Data', style: TextStyle(color: Color(0xFF0F172A), fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: Color(0xFF0F172A)),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildMenuTile(
            context,
            icon: Icons.person_outline,
            title: 'Profil Pengguna',
            iconColor: leadingIconColor,
            trailingColor: trailingIconColor,
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ProfileSettingsPage())),
          ),
          const SizedBox(height: 12),
          _buildMenuTile(
            context,
            icon: Icons.account_balance_wallet_outlined,
            title: 'Pengelolaan Rekening',
            iconColor: leadingIconColor,
            trailingColor: trailingIconColor,
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => AccountManagementPage(activeMode: activeMode))),
          ),
          const SizedBox(height: 12),
          _buildMenuTile(
            context,
            icon: Icons.category_outlined,
            title: 'Kategori Transaksi',
            iconColor: leadingIconColor,
            trailingColor: trailingIconColor,
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => CategorySettingsPage(activeMode: activeMode))),
          ),
          const SizedBox(height: 12),
          _buildMenuTile(
            context,
            icon: Icons.backup_outlined,
            title: 'Cadangan Data (Backup & Restore)',
            iconColor: leadingIconColor,
            trailingColor: trailingIconColor,
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const BackupRestorePage())),
          ),
        ],
      ),
    );
  }

  Widget _buildMenuTile(BuildContext context, {
    required IconData icon, 
    required String title, 
    required Color iconColor, 
    required Color trailingColor, 
    required VoidCallback onTap
  }) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        highlightColor: iconColor.withOpacity(0.05),
        splashColor: iconColor.withOpacity(0.1),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
          child: Row(
            children: [
              Icon(icon, color: iconColor, size: 28),
              const SizedBox(width: 16),
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: Color(0xFF0F172A)),
                ),
              ),
              Icon(Icons.chevron_right, color: trailingColor),
            ],
          ),
        ),
      ),
    );
  }
}

// ============================================================================
// 1. PROFILE SETTINGS PAGE
// ============================================================================

class ProfileSettingsPage extends StatefulWidget {
  const ProfileSettingsPage({super.key});

  @override
  State<ProfileSettingsPage> createState() => _ProfileSettingsPageState();
}

class _ProfileSettingsPageState extends State<ProfileSettingsPage> {
  late TextEditingController nameController;

  @override
  void initState() {
    super.initState();
    nameController = TextEditingController(text: PreferenceService.instance.userName);
  }

  @override
  void dispose() {
    nameController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final primaryColor = Theme.of(context).primaryColor;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Profil Pengguna', style: TextStyle(fontWeight: FontWeight.bold)),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            CircleAvatar(
              radius: 50,
              backgroundColor: primaryColor,
              child: const Icon(Icons.person, size: 50, color: Colors.white),
            ),
            const SizedBox(height: 24),
            Text(
              AppLocalizations.of(context)?.profile ?? 'Profil Pengguna',
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: nameController,
              decoration: const InputDecoration(
                labelText: 'Nama Panggilan',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.badge),
              ),
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () async {
                if (nameController.text.isNotEmpty) {
                  await PreferenceService.instance.setUserName(nameController.text);
                  if (mounted) {
                    TopToast.show(context, 'Profil berhasil disimpan');
                  }
                }
              },
              style: ElevatedButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 16),
                backgroundColor: primaryColor,
                foregroundColor: Colors.white,
              ),
              child: const Text('Simpan Profil'),
            ),
            const SizedBox(height: 32),
            Text(
              AppLocalizations.of(context)?.language ?? 'Bahasa / Language',
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 16),
            ValueListenableBuilder<Locale>(
              valueListenable: PreferenceService.instance.localeNotifier,
              builder: (context, locale, child) {
                return SegmentedButton<String>(
                  showSelectedIcon: false,
                  segments: const [
                    ButtonSegment<String>(
                      value: 'id',
                      label: Text('🇮🇩 Indonesia', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    ),
                    ButtonSegment<String>(
                      value: 'en',
                      label: Text('🇬🇧 English', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    ),
                  ],
                  selected: {locale.languageCode},
                  onSelectionChanged: (Set<String> newSelection) {
                    final lang = newSelection.first;
                    PreferenceService.instance.setLanguage(lang);
                    TopToast.show(context, lang == 'id' ? 'Bahasa diubah ke Indonesia' : 'Language changed to English');
                  },
                  style: ButtonStyle(
                    backgroundColor: MaterialStateProperty.resolveWith<Color>((states) {
                      if (states.contains(MaterialState.selected)) {
                        return primaryColor.withOpacity(0.1);
                      }
                      return Colors.white;
                    }),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}

// ============================================================================
// 2. ACCOUNT MANAGEMENT PAGE
// ============================================================================

class AccountManagementPage extends StatefulWidget {
  final String activeMode;
  const AccountManagementPage({super.key, required this.activeMode});

  @override
  State<AccountManagementPage> createState() => _AccountManagementPageState();
}

class _AccountManagementPageState extends State<AccountManagementPage> {
  List<Map<String, dynamic>> _accounts = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final accounts = await DatabaseHelper.instance.getAccounts(widget.activeMode);
    setState(() {
      _accounts = accounts;
      _isLoading = false;
    });
  }

  void _showAddEditDialog({Map<String, dynamic>? item}) {
    final TextEditingController nameController = TextEditingController(text: item?['name'] ?? '');
    final TextEditingController balanceController = TextEditingController(text: (item?['balance'] ?? 0).toString());

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text(
          item == null ? 'Tambah Rekening' : 'Edit Rekening',
          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 20),
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            TextField(
              controller: nameController,
              decoration: InputDecoration(
                labelText: 'Nama Rekening',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              ),
              autofocus: true,
            ),
            const SizedBox(height: 16),
            TextField(
              controller: balanceController,
              decoration: InputDecoration(
                labelText: 'Saldo Awal',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              ),
              keyboardType: TextInputType.number,
            ),
          ],
        ),
        actionsPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text('Batal', style: TextStyle(color: Colors.grey.shade600)),
          ),
          ElevatedButton(
            onPressed: () async {
              if (nameController.text.isNotEmpty) {
                final balance = int.tryParse(balanceController.text) ?? 0;
                final data = {'name': nameController.text.trim(), 'type': widget.activeMode, 'balance': balance};
                if (item == null) {
                  await DatabaseHelper.instance.insertAccount(data);
                } else {
                  await DatabaseHelper.instance.updateAccount(item['id'], data);
                }
                Navigator.pop(context);
                _loadData();
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Theme.of(context).primaryColor,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              elevation: 0,
            ),
            child: const Text('Simpan'),
          ),
        ],
      ),
    );
  }

  Future<void> _deleteItem(String id) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Hapus Data'),
        content: const Text('Apakah Anda yakin ingin menghapus rekening ini?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Batal')),
          TextButton(onPressed: () => Navigator.pop(context, true), child: const Text('Hapus', style: TextStyle(color: Colors.red))),
        ],
      ),
    );

    if (confirmed == true) {
      await DatabaseHelper.instance.deleteAccount(id);
      _loadData();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Pengelolaan Rekening', style: TextStyle(fontWeight: FontWeight.bold)),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _accounts.isEmpty
              ? Center(child: Text('Belum ada data rekening', style: TextStyle(color: Colors.grey.shade400)))
              : ListView.separated(
                  padding: const EdgeInsets.all(16),
                  itemCount: _accounts.length,
                  separatorBuilder: (context, index) => const Divider(),
                  itemBuilder: (context, index) {
                    final item = _accounts[index];
                    return ListTile(
                      title: Text(item['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.w500)),
                      trailing: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          IconButton(
                            icon: const Icon(Icons.edit, color: Colors.blue),
                            onPressed: () => _showAddEditDialog(item: item),
                          ),
                          IconButton(
                            icon: const Icon(Icons.delete, color: Colors.red),
                            onPressed: () => _deleteItem(item['id']),
                          ),
                        ],
                      ),
                    );
                  },
                ),
      floatingActionButton: FloatingActionButton(
        backgroundColor: Theme.of(context).primaryColor,
        foregroundColor: Colors.white,
        onPressed: () => _showAddEditDialog(),
        child: const Icon(Icons.add),
      ),
    );
  }
}

// ============================================================================
// 3. CATEGORY SETTINGS PAGE
// ============================================================================

class CategorySettingsPage extends StatefulWidget {
  final String activeMode;
  const CategorySettingsPage({super.key, required this.activeMode});

  @override
  State<CategorySettingsPage> createState() => _CategorySettingsPageState();
}

class _CategorySettingsPageState extends State<CategorySettingsPage> {
  List<Map<String, dynamic>> _categories = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final categories = await DatabaseHelper.instance.getCategories(widget.activeMode);
    setState(() {
      _categories = categories;
      _isLoading = false;
    });
  }

  void _showAddEditDialog({Map<String, dynamic>? item}) {
    final TextEditingController nameController = TextEditingController(text: item?['name'] ?? '');
    String selectedTxType = item?['transaction_type'] ?? 'expense';

    showDialog(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) {
          return AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            title: Text(
              item == null ? 'Tambah Kategori' : 'Edit Kategori',
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 20),
            ),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                TextField(
                  controller: nameController,
                  decoration: InputDecoration(
                    labelText: 'Nama Kategori',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  ),
                  autofocus: true,
                ),
                if (item == null) ...[
                  const SizedBox(height: 16),
                  const Text('Jenis Transaksi', style: TextStyle(fontSize: 13, color: Colors.grey, fontWeight: FontWeight.w600)),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(
                        child: GestureDetector(
                          onTap: () => setDialogState(() => selectedTxType = 'expense'),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            decoration: BoxDecoration(
                              color: selectedTxType == 'expense' ? Colors.red.shade50 : Colors.grey.shade100,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: selectedTxType == 'expense' ? Colors.red.shade300 : Colors.transparent),
                            ),
                            child: Center(
                              child: Text(
                                'Pengeluaran',
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.bold,
                                  color: selectedTxType == 'expense' ? Colors.red.shade700 : Colors.grey.shade600,
                                ),
                              ),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: GestureDetector(
                          onTap: () => setDialogState(() => selectedTxType = 'income'),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            decoration: BoxDecoration(
                              color: selectedTxType == 'income' ? Colors.green.shade50 : Colors.grey.shade100,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: selectedTxType == 'income' ? Colors.green.shade300 : Colors.transparent),
                            ),
                            child: Center(
                              child: Text(
                                'Pemasukan',
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.bold,
                                  color: selectedTxType == 'income' ? Colors.green.shade700 : Colors.grey.shade600,
                                ),
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ],
            ),
            actionsPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: Text('Batal', style: TextStyle(color: Colors.grey.shade600)),
              ),
              ElevatedButton(
                onPressed: () async {
                  if (nameController.text.isNotEmpty) {
                    final data = {'name': nameController.text.trim(), 'type': widget.activeMode, 'transaction_type': selectedTxType};
                    if (item == null) {
                      await DatabaseHelper.instance.insertCategory(data);
                    } else {
                      await DatabaseHelper.instance.updateCategory(item['id'], data);
                    }
                    Navigator.pop(context);
                    _loadData();
                  }
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: Theme.of(context).primaryColor,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  elevation: 0,
                ),
                child: const Text('Simpan'),
              ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _deleteItem(String id, String name) async {
    if (name == 'Other') {
      TopToast.show(context, 'Kategori default "Other" tidak dapat dihapus.');
      return;
    }

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Hapus Data'),
        content: const Text('Apakah Anda yakin ingin menghapus kategori ini?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Batal')),
          TextButton(onPressed: () => Navigator.pop(context, true), child: const Text('Hapus', style: TextStyle(color: Colors.red))),
        ],
      ),
    );

    if (confirmed == true) {
      await DatabaseHelper.instance.deleteCategory(id);
      _loadData();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Kategori Transaksi', style: TextStyle(fontWeight: FontWeight.bold)),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _categories.isEmpty
              ? Center(child: Text('Belum ada data kategori', style: TextStyle(color: Colors.grey.shade400)))
              : ListView.separated(
                  padding: const EdgeInsets.all(16),
                  itemCount: _categories.length,
                  separatorBuilder: (context, index) => const Divider(),
                  itemBuilder: (context, index) {
                    final item = _categories[index];
                    final isOther = item['name'] == 'Other';
                    final txTypeStr = item['transaction_type'] == 'income' ? 'Pemasukan' : 'Pengeluaran';
                    final badgeColor = item['transaction_type'] == 'income' ? Colors.green : Colors.red;

                    return ListTile(
                      title: Text(item['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.w500)),
                      subtitle: Text(txTypeStr, style: TextStyle(fontSize: 12, color: badgeColor, fontWeight: FontWeight.bold)),
                      trailing: isOther
                          ? Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(color: Colors.grey.shade200, borderRadius: BorderRadius.circular(12)),
                              child: const Text('Default', style: TextStyle(fontSize: 11, color: Colors.grey)),
                            )
                          : Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                IconButton(
                                  icon: const Icon(Icons.edit, color: Colors.blue),
                                  onPressed: () => _showAddEditDialog(item: item),
                                ),
                                IconButton(
                                  icon: const Icon(Icons.delete, color: Colors.red),
                                  onPressed: () => _deleteItem(item['id'], item['name']),
                                ),
                              ],
                            ),
                    );
                  },
                ),
      floatingActionButton: FloatingActionButton(
        backgroundColor: Theme.of(context).primaryColor,
        foregroundColor: Colors.white,
        onPressed: () => _showAddEditDialog(),
        child: const Icon(Icons.add),
      ),
    );
  }
}

// ============================================================================
// 4. BACKUP & RESTORE PAGE
// ============================================================================

class BackupRestorePage extends StatefulWidget {
  const BackupRestorePage({super.key});

  @override
  State<BackupRestorePage> createState() => _BackupRestorePageState();
}

class _BackupRestorePageState extends State<BackupRestorePage> {
  @override
  Widget build(BuildContext context) {
    final primaryColor = Theme.of(context).primaryColor;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Cadangan Data', style: TextStyle(fontWeight: FontWeight.bold)),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Icon(Icons.cloud_upload_outlined, size: 80, color: primaryColor),
            const SizedBox(height: 16),
            const Text(
              'Backup & Restore Data',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            const Text(
              'Simpan data Anda ke file lokal agar bisa dipulihkan kembali nanti.',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey),
            ),
            const SizedBox(height: 48),
            ElevatedButton.icon(
              onPressed: () async {
                final path = await BackupService.instance.exportData();
                if (path != null && mounted) {
                  TopToast.show(context, 'Data berhasil diekspor ke $path');
                }
              },
              icon: const Icon(Icons.upload_file),
              label: Text(AppLocalizations.of(context)?.exportJson ?? 'Ekspor Data ke JSON'),
              style: ElevatedButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 16),
                backgroundColor: primaryColor.withOpacity(0.1),
                foregroundColor: primaryColor,
                elevation: 0,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: () async {
                final confirmed = await showDialog<bool>(
                  context: context,
                  builder: (context) => AlertDialog(
                    title: const Text('Konfirmasi Restore'),
                    content: const Text('PERHATIAN: Mengimpor data akan menghapus semua data saat ini dan menggantinya dengan isi file backup. Lanjutkan?'),
                    actions: [
                      TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Batal')),
                      TextButton(onPressed: () => Navigator.pop(context, true), child: const Text('Lanjutkan', style: TextStyle(color: Colors.red))),
                    ],
                  ),
                );
                
                if (confirmed == true) {
                  final success = await BackupService.instance.importData();
                  if (success && mounted) {
                    TopToast.show(context, 'Data berhasil diimpor!');
                  }
                }
              },
              icon: const Icon(Icons.file_download),
              label: Text(AppLocalizations.of(context)?.importJson ?? 'Impor Data dari JSON'),
              style: ElevatedButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 16),
                backgroundColor: Theme.of(context).primaryColorDark.withOpacity(0.1),
                foregroundColor: Theme.of(context).primaryColorDark,
                elevation: 0,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
