import 'package:flutter/material.dart';
import '../services/database_helper.dart';
import '../services/backup_service.dart';
import '../core/services/preference_service.dart';

class MasterDataScreen extends StatefulWidget {
  final String activeMode;
  const MasterDataScreen({super.key, required this.activeMode});

  @override
  State<MasterDataScreen> createState() => _MasterDataScreenState();
}

class _MasterDataScreenState extends State<MasterDataScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<Map<String, dynamic>> _accounts = [];
  List<Map<String, dynamic>> _categories = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    _loadData();
    _tabController.addListener(() {
      setState(() {}); // Refresh FAB visibility
    });
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final accounts = await DatabaseHelper.instance.getAccounts(widget.activeMode);
    final categories = await DatabaseHelper.instance.getCategories(widget.activeMode);
    setState(() {
      _accounts = accounts;
      _categories = categories;
      _isLoading = false;
    });
  }

  void _showAddEditDialog({required String type, Map<String, dynamic>? item}) {
    final TextEditingController nameController = TextEditingController(text: item?['name'] ?? '');
    final TextEditingController balanceController = TextEditingController(text: (item?['balance'] ?? 0).toString());

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(item == null ? 'Tambah $type' : 'Edit $type'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: nameController,
              decoration: InputDecoration(hintText: 'Nama $type'),
              autofocus: true,
            ),
            if (type == 'Rekening') ...[
              const SizedBox(height: 16),
              TextField(
                controller: balanceController,
                decoration: const InputDecoration(hintText: 'Saldo Awal'),
                keyboardType: TextInputType.number,
              ),
            ]
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Batal')),
          ElevatedButton(
            onPressed: () async {
              if (nameController.text.isNotEmpty) {
                final balance = int.tryParse(balanceController.text) ?? 0;
                final data = type == 'Rekening' 
                  ? {'name': nameController.text, 'type': widget.activeMode, 'balance': balance}
                  : {'name': nameController.text, 'type': widget.activeMode};
                if (item == null) {
                  if (type == 'Rekening') {
                    await DatabaseHelper.instance.insertAccount(data);
                  } else {
                    await DatabaseHelper.instance.insertCategory(data);
                  }
                } else {
                  if (type == 'Rekening') {
                    await DatabaseHelper.instance.updateAccount(item['id'], data);
                  } else {
                    await DatabaseHelper.instance.updateCategory(item['id'], data);
                  }
                }
                Navigator.pop(context);
                _loadData();
              }
            },
            child: const Text('Simpan'),
          ),
        ],
      ),
    );
  }

  Future<void> _deleteItem(String type, String id) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Hapus Data'),
        content: Text('Apakah Anda yakin ingin menghapus $type ini?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Batal')),
          TextButton(onPressed: () => Navigator.pop(context, true), child: const Text('Hapus', style: TextStyle(color: Colors.red))),
        ],
      ),
    );

    if (confirmed == true) {
      if (type == 'Rekening') {
        await DatabaseHelper.instance.deleteAccount(id);
      } else {
        await DatabaseHelper.instance.deleteCategory(id);
      }
      _loadData();
    }
  }

  @override
  Widget build(BuildContext context) {
    final Color themeColor = widget.activeMode == 'personal' ? Colors.orange.shade700 : Colors.teal.shade700;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Master Data', style: TextStyle(fontWeight: FontWeight.bold)),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: themeColor,
          labelColor: themeColor,
          tabs: const [
            Tab(text: 'Profil'),
            Tab(text: 'Rekening'),
            Tab(text: 'Kategori'),
            Tab(text: 'Backup'),
          ],
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : TabBarView(
              controller: _tabController,
              children: [
                _buildProfileSection(),
                _buildList('Rekening', _accounts),
                _buildList('Kategori', _categories),
                _buildBackupSection(),
              ],
            ),
      floatingActionButton: (_tabController.index == 0 || _tabController.index == 3)
          ? null 
          : FloatingActionButton(
              onPressed: () => _showAddEditDialog(type: _tabController.index == 1 ? 'Rekening' : 'Kategori'),
              backgroundColor: themeColor,
              child: const Icon(Icons.add, color: Colors.white),
            ),
    );
  }

  Widget _buildProfileSection() {
    final TextEditingController nameController = TextEditingController(text: PreferenceService.instance.userName);
    return Padding(
      padding: const EdgeInsets.all(24.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Icon(Icons.account_circle, size: 80, color: Colors.blue),
          const SizedBox(height: 16),
          const Text(
            'Profil Pengguna',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 32),
          TextField(
            controller: nameController,
            decoration: const InputDecoration(
              labelText: 'Nama Panggilan',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 16),
          ElevatedButton(
            onPressed: () async {
              if (nameController.text.isNotEmpty) {
                await PreferenceService.instance.setUserName(nameController.text);
                if (mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Profil berhasil disimpan')),
                  );
                }
              }
            },
            style: ElevatedButton.styleFrom(
              padding: const EdgeInsets.symmetric(vertical: 16),
              backgroundColor: Colors.blue.shade600,
              foregroundColor: Colors.white,
            ),
            child: const Text('Simpan Profil'),
          ),
        ],
      ),
    );
  }

  Widget _buildBackupSection() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Icon(Icons.cloud_upload_outlined, size: 80, color: Colors.blue),
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
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text('Data berhasil diekspor ke $path')),
                );
              }
            },
            icon: const Icon(Icons.upload_file),
            label: const Text('Ekspor Data ke JSON'),
            style: ElevatedButton.styleFrom(
              padding: const EdgeInsets.symmetric(vertical: 16),
              backgroundColor: Colors.blue.shade50,
              foregroundColor: Colors.blue.shade700,
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
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Data berhasil diimpor!')),
                  );
                  _loadData();
                }
              }
            },
            icon: const Icon(Icons.file_download),
            label: const Text('Impor Data dari JSON'),
            style: ElevatedButton.styleFrom(
              padding: const EdgeInsets.symmetric(vertical: 16),
              backgroundColor: Colors.orange.shade50,
              foregroundColor: Colors.orange.shade700,
              elevation: 0,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildList(String type, List<Map<String, dynamic>> items) {
    if (items.isEmpty) {
      return Center(
        child: Text('Belum ada data $type', style: TextStyle(color: Colors.grey.shade400)),
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: items.length,
      separatorBuilder: (context, index) => const Divider(),
      itemBuilder: (context, index) {
        final item = items[index];
        return ListTile(
          title: Text(item['name'] ?? ''),
          trailing: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              IconButton(
                icon: const Icon(Icons.edit, color: Colors.blue),
                onPressed: () => _showAddEditDialog(type: type, item: item),
              ),
              IconButton(
                icon: const Icon(Icons.delete, color: Colors.red),
                onPressed: () => _deleteItem(type, item['id']),
              ),
            ],
          ),
        );
      },
    );
  }
}
