import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import 'terms_screen.dart';
import 'package:package_info_plus/package_info_plus.dart';

class AboutScreen extends StatelessWidget {
  const AboutScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text('Tentang WiroFin', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF1E293B),
        elevation: 0,
      ),
      body: SingleChildScrollView(
        child: Column(
          children: [
            const SizedBox(height: 40),
            // App Logo / Icon
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: const Color(0xFF0D47A1).withOpacity(0.1),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.account_balance_wallet, size: 80, color: Color(0xFF0D47A1)),
            ),
            const SizedBox(height: 24),
            const Text(
              'WiroFin',
              style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
            ),
            FutureBuilder<PackageInfo>(
              future: PackageInfo.fromPlatform(),
              builder: (context, snapshot) {
                if (snapshot.hasData) {
                  return Text(
                    'Versi ${snapshot.data!.version}',
                    style: const TextStyle(color: Colors.grey),
                  );
                }
                return const Text('Versi ...', style: TextStyle(color: Colors.grey));
              },
            ),
            const SizedBox(height: 40),
            
            // Info Sections
            _buildInfoTile(
              context,
              icon: Icons.business,
              title: 'Pengembang',
              content: 'Wirodayan Digital Software House',
            ),
            _buildInfoTile(
              context,
              icon: Icons.language,
              title: 'Website',
              content: 'wirodev.com',
              onTap: () async {
                final url = Uri.parse('https://wirodev.com');
                if (await canLaunchUrl(url)) {
                  await launchUrl(url);
                }
              },
            ),
            _buildInfoTile(
              context,
              icon: Icons.calendar_today,
              title: 'Dibuat Pada',
              content: '2026',
            ),
            _buildInfoTile(
              context,
              icon: Icons.verified_user,
              title: 'Lisensi',
              content: 'Free to Use',
            ),
            _buildInfoTile(
              context,
              icon: Icons.description,
              title: 'Hukum',
              content: 'Syarat & Ketentuan',
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (context) => const TermsScreen()),
                );
              },
            ),
            
            const SizedBox(height: 60),
            const Text(
              '© 2026 Wirodayan Digital',
              style: TextStyle(color: Colors.grey, fontSize: 12),
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoTile(BuildContext context, {required IconData icon, required String title, required String content, VoidCallback? onTap}) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ListTile(
        onTap: onTap,
        leading: Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: const Color(0xFFF1F5F9),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Icon(icon, color: const Color(0xFF0D47A1), size: 20),
        ),
        title: Text(title, style: const TextStyle(fontSize: 12, color: Colors.grey)),
        subtitle: Text(content, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF1E293B))),
        trailing: onTap != null ? const Icon(Icons.open_in_new, size: 16, color: Colors.grey) : null,
      ),
    );
  }
}
