import 'dart:convert';
import 'dart:io';
import 'package:file_picker/file_picker.dart';
import 'package:path_provider/path_provider.dart';
import 'database_helper.dart';

class BackupService {
  static final BackupService instance = BackupService._();
  BackupService._();

  Future<String?> exportData() async {
    try {
      final db = DatabaseHelper.instance;
      final transactions = await db.queryAll('expenses');
      final accounts = await db.queryAll('accounts');
      final categories = await db.queryAll('categories');

      final backupData = {
        'version': 1,
        'timestamp': DateTime.now().toIso8601String(),
        'expenses': transactions,
        'accounts': accounts,
        'categories': categories,
      };

      final jsonString = jsonEncode(backupData);
      
      // Save to a temporary file first
      final tempDir = await getTemporaryDirectory();
      final file = File('${tempDir.path}/expense_backup_${DateTime.now().millisecondsSinceEpoch}.json');
      await file.writeAsString(jsonString);

      // Use FilePicker to save it permanently
      String? outputFile = await FilePicker.platform.saveFile(
        dialogTitle: 'Pilih lokasi simpan backup',
        fileName: 'expense_tracker_backup.json',
        type: FileType.custom,
        allowedExtensions: ['json'],
      );

      if (outputFile != null) {
        final finalFile = File(outputFile);
        await finalFile.writeAsString(jsonString);
        return outputFile;
      }
      return null;
    } catch (e) {
      print('Export error: $e');
      return null;
    }
  }

  Future<bool> importData() async {
    try {
      FilePickerResult? result = await FilePicker.platform.pickFiles(
        type: FileType.custom,
        allowedExtensions: ['json'],
      );

      if (result != null) {
        File file = File(result.files.single.path!);
        String content = await file.readAsString();
        Map<String, dynamic> data = jsonDecode(content);

        if (data['expenses'] == null) return false;

        final db = await DatabaseHelper.instance.database;

        await db.transaction((txn) async {
          // Warning: This clears existing data to restore backup
          await txn.delete('expenses');
          await txn.delete('accounts');
          await txn.delete('categories');

          for (var item in data['accounts']) {
            await txn.insert('accounts', item);
          }
          for (var item in data['categories']) {
            await txn.insert('categories', item);
          }
          for (var item in data['expenses']) {
            await txn.insert('expenses', item);
          }
        });

        return true;
      }
      return false;
    } catch (e) {
      print('Import error: $e');
      return false;
    }
  }
}
