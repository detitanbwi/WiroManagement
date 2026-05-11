import 'package:flutter/material.dart';

class TermsScreen extends StatelessWidget {
  const TermsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text('Syarat & Ketentuan', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF1E293B),
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Syarat dan Ketentuan Penggunaan WiroFin',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
            ),
            const SizedBox(height: 16),
            const Text(
              'Terakhir diperbarui: 11 Mei 2026',
              style: TextStyle(color: Colors.grey, fontSize: 12),
            ),
            const SizedBox(height: 24),
            _buildSection(
              '1. Penerimaan Ketentuan',
              'Dengan mengunduh atau menggunakan aplikasi WiroFin, Anda secara otomatis menyetujui semua syarat dan ketentuan yang tertulis di sini. Jika Anda tidak setuju, harap segera menghentikan penggunaan aplikasi.',
            ),
            _buildSection(
              '2. Penggunaan Aplikasi',
              'Aplikasi ini disediakan untuk membantu Anda dalam pencatatan keuangan pribadi dan bisnis. Anda bertanggung jawab penuh atas keakuratan data yang Anda masukkan.',
            ),
            _buildSection(
              '3. Batasan Tanggung Jawab',
              'WIROFIN DISEDIAKAN "APA ADANYA" TANPA JAMINAN APAPUN. SEGALA KERUGIAN ATAS PEMAKAIAN APLIKASI INI ADALAH SEPENUHNYA TANGGUNG JAWAB PENGGUNA. KAMI TIDAK BERTANGGUNG JAWAB ATAS KEHILANGAN DATA, KESALAHAN PERHITUNGAN, ATAU DAMPAK FINANSIAL APAPUN YANG TIMBUL DARI PENGGUNAAN APLIKASI INI.',
            ),
            _buildSection(
              '4. Keamanan Data',
              'Dalam mode offline, data Anda disimpan secara lokal di perangkat Anda. Anda bertanggung jawab untuk melakukan backup data Anda sendiri.',
            ),
            _buildSection(
              '5. Perubahan Layanan',
              'Kami berhak mengubah atau menghentikan layanan aplikasi kapan saja tanpa pemberitahuan sebelumnya.',
            ),
            const SizedBox(height: 40),
            Center(
              child: Text(
                'Wirodayan Digital Software House',
                style: TextStyle(color: Colors.grey.shade400, fontSize: 12, fontWeight: FontWeight.bold),
              ),
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Widget _buildSection(String title, String content) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 24.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
          ),
          const SizedBox(height: 8),
          Text(
            content,
            style: const TextStyle(fontSize: 14, color: Color(0xFF475569), height: 1.5),
          ),
        ],
      ),
    );
  }
}
