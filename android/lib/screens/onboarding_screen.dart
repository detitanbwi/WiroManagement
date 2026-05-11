import 'package:flutter/material.dart';
import 'dart:io';
import '../core/services/preference_service.dart';
import '../services/database_helper.dart';
import '../services/backup_service.dart';
import 'package:intl/intl.dart';
import 'terms_screen.dart';

class OnboardingScreen extends StatefulWidget {
  final VoidCallback onFinish;

  const OnboardingScreen({super.key, required this.onFinish});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final PageController _pageController = PageController();
  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _walletController = TextEditingController(text: 'Tunai');
  int _currentPage = 0;

  // State data
  String _userName = '';
  String _walletName = 'Tunai';
  String _initialBalanceStr = '0';
  bool _isLoading = false;
  bool _termsAccepted = false;

  void _nextPage() {
    FocusScope.of(context).unfocus();
    if (_currentPage < 3) {
      _pageController.nextPage(
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeInOut,
      );
    } else {
      _finishOnboarding();
    }
  }

  void _prevPage() {
    FocusScope.of(context).unfocus();
    if (_currentPage > 0) {
      _pageController.previousPage(
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeInOut,
      );
    }
  }

  Future<void> _finishOnboarding() async {
    setState(() => _isLoading = true);
    try {
      // Save user name
      final name = _nameController.text.trim();
      await PreferenceService.instance.setUserName(name.isEmpty ? 'User' : name);
      
      // Save initial wallet
      final int balance = int.tryParse(_initialBalanceStr) ?? 0;
      final walletName = _walletController.text.trim();
      await DatabaseHelper.instance.insertAccount({
        'name': walletName.isEmpty ? 'Tunai' : walletName,
        'type': 'personal',
        'balance': balance,
      });

      // Mark as not first launch
      await PreferenceService.instance.setFirstLaunch(false);
      
      widget.onFinish();
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _importData() async {
    final success = await BackupService.instance.importData();
    if (success && mounted) {
      await PreferenceService.instance.setFirstLaunch(false);
      widget.onFinish();
    }
  }
  @override
  void dispose() {
    _pageController.dispose();
    _nameController.dispose();
    _walletController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      resizeToAvoidBottomInset: true,
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: PageView(
                controller: _pageController,
                physics: const NeverScrollableScrollPhysics(), // Disable swipe
                onPageChanged: (index) {
                  setState(() {
                    _currentPage = index;
                  });
                },
                children: [
                  _buildWelcomePage(),
                  _buildChoicePage(),
                  _buildProfilePage(),
                  _buildWalletSetupPage(),
                ],
              ),
            ),
            _buildBottomControls(),
          ],
        ),
      ),
    );
  }

  Widget _buildWelcomePage() {
    return SingleChildScrollView(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 32.0, vertical: 48.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              height: 280,
              width: double.infinity,
              decoration: BoxDecoration(
                color: Colors.transparent,
                borderRadius: BorderRadius.circular(32),
              ),
              clipBehavior: Clip.antiAlias,
              child: Image.asset(
                'assets/images/onboarding1.png',
                fit: BoxFit.contain,
                errorBuilder: (context, error, stackTrace) => Icon(Icons.account_balance_wallet, size: 100, color: Colors.teal.shade600),
              ),
            ),
            const SizedBox(height: 48),
            const Text(
              'Kelola Uang Lebih Baik',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
            ),
            const SizedBox(height: 16),
            Text(
              'Pantau pengeluaran, atur kategori, dan simpan data Anda secara offline dengan aman.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 16, color: Colors.grey.shade600, height: 1.5),
            ),
            const SizedBox(height: 48),
            // Terms and Conditions Checkbox
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.orange.withOpacity(0.05),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.orange.withOpacity(0.1)),
              ),
              child: Row(
                children: [
                  Checkbox(
                    value: _termsAccepted,
                    activeColor: Colors.orange,
                    onChanged: (val) => setState(() => _termsAccepted = val ?? false),
                  ),
                  Expanded(
                    child: GestureDetector(
                      onTap: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(builder: (context) => const TermsScreen()),
                        );
                      },
                      child: RichText(
                        text: TextSpan(
                          style: TextStyle(fontSize: 13, color: Colors.grey.shade700, height: 1.4),
                          children: [
                            const TextSpan(text: 'Saya menyetujui '),
                            TextSpan(
                              text: 'Syarat & Ketentuan',
                              style: TextStyle(
                                color: Colors.orange.shade700,
                                fontWeight: FontWeight.bold,
                                decoration: TextDecoration.underline,
                              ),
                            ),
                            const TextSpan(text: ' penggunaan WiroFin.'),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildChoicePage() {
    return SingleChildScrollView(
      child: Padding(
        padding: const EdgeInsets.all(32.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const SizedBox(height: 40),
            const Text(
              'Mulai Perjalanan Anda',
              style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
            ),
            const SizedBox(height: 48),
            _buildActionCard(
              icon: Icons.add_circle_outline,
              title: 'Mulai dari Awal',
              subtitle: 'Buat akun baru dan atur profil Anda.',
              color: Colors.teal,
              onTap: _nextPage,
            ),
            const SizedBox(height: 24),
            _buildActionCard(
              icon: Icons.restore,
              title: 'Import Data Lama',
              subtitle: 'Pulihkan data dari file backup JSON.',
              color: Colors.orange,
              onTap: _importData,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActionCard({required IconData icon, required String title, required String subtitle, required MaterialColor color, required VoidCallback onTap}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          border: Border.all(color: color.shade200, width: 2),
          borderRadius: BorderRadius.circular(16),
          color: color.shade50,
        ),
        child: Row(
          children: [
            Icon(icon, size: 40, color: color.shade700),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: color.shade900)),
                  const SizedBox(height: 4),
                  Text(subtitle, style: TextStyle(fontSize: 14, color: color.shade700)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildProfilePage() {
    return SingleChildScrollView(
      child: Padding(
        padding: const EdgeInsets.all(32.0),
        child: Column(
          children: [
            const SizedBox(height: 60),
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: Colors.blue.shade50,
                shape: BoxShape.circle,
              ),
              child: Icon(Icons.person_outline, size: 80, color: Colors.blue.shade600),
            ),
            const SizedBox(height: 32),
            const Text(
              'Siapa nama Anda?',
              style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
            ),
            const SizedBox(height: 8),
            Text(
              'Nama ini akan digunakan untuk menyapa Anda.',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey.shade600),
            ),
            const SizedBox(height: 48),
            TextField(
              controller: _nameController,
              onChanged: (val) => _userName = val,
              onSubmitted: (_) => FocusScope.of(context).unfocus(),
              textInputAction: TextInputAction.done,
              textCapitalization: TextCapitalization.words,
              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w500),
              decoration: InputDecoration(
                hintText: 'Masukkan nama',
                hintStyle: TextStyle(color: Colors.grey.withOpacity(0.4)),
                filled: true,
                fillColor: Colors.grey.shade50,
                contentPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(16),
                  borderSide: BorderSide(color: Colors.grey.shade300),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(16),
                  borderSide: const BorderSide(color: Colors.teal, width: 2),
                ),
              ),
            ),
            const SizedBox(height: 100), // Space for keyboard
          ],
        ),
      ),
    );
  }

  Widget _buildWalletSetupPage() {
    final formatter = NumberFormat('#,###', 'id_ID');
    final formattedBalance = _initialBalanceStr.isEmpty ? '0' : formatter.format(int.parse(_initialBalanceStr));

    return Column(
      children: [
        Expanded(
          child: SingleChildScrollView(
            child: Padding(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                children: [
                  const Text(
                    'Rekening Utama',
                    style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Masukkan saldo awal Anda saat ini.',
                    style: TextStyle(color: Colors.grey.shade600),
                  ),
                  const SizedBox(height: 32),
                  Container(
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      color: Colors.teal.shade50,
                      borderRadius: BorderRadius.circular(24),
                    ),
                    child: Column(
                      children: [
                        TextField(
                          onChanged: (val) => _walletName = val,
                          textAlign: TextAlign.center,
                          textCapitalization: TextCapitalization.words,
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                          decoration: InputDecoration(
                            border: InputBorder.none,
                            hintText: 'Nama Rekening (Misal: BCA / Tunai)',
                            hintStyle: TextStyle(color: Colors.grey.withOpacity(0.4)),
                          ),
                          controller: _walletController,
                        ),
                        const Divider(),
                        const SizedBox(height: 8),
                        Text(
                          'Rp $formattedBalance',
                          style: TextStyle(fontSize: 36, fontWeight: FontWeight.bold, color: Colors.teal.shade700),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
        SizedBox(
          height: 250,
          child: _buildNumpad(),
        ),
      ],
    );
  }

  Widget _buildNumpad() {
    return Container(
      color: Colors.grey.shade50,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: GridView.builder(
        physics: const NeverScrollableScrollPhysics(),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 3,
          childAspectRatio: 2.2,
          crossAxisSpacing: 8,
          mainAxisSpacing: 8,
        ),
        itemCount: 12,
        itemBuilder: (context, index) {
          if (index == 9) return const SizedBox.shrink(); // Empty slot
          if (index == 11) {
            return IconButton(
              icon: const Icon(Icons.backspace_outlined, color: Colors.redAccent),
              onPressed: () {
                setState(() {
                  if (_initialBalanceStr.isNotEmpty) {
                    _initialBalanceStr = _initialBalanceStr.substring(0, _initialBalanceStr.length - 1);
                    if (_initialBalanceStr.isEmpty) _initialBalanceStr = '0';
                  }
                });
              },
            );
          }
          final number = index == 10 ? 0 : index + 1;
          return TextButton(
            onPressed: () {
              setState(() {
                if (_initialBalanceStr == '0') {
                  _initialBalanceStr = number.toString();
                } else if (_initialBalanceStr.length < 12) {
                  _initialBalanceStr += number.toString();
                }
              });
            },
            style: TextButton.styleFrom(
              backgroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              elevation: 0,
            ),
            child: Text(
              number.toString(),
              style: const TextStyle(fontSize: 22, color: Color(0xFF1E293B), fontWeight: FontWeight.bold),
            ),
          );
        },
      ),
    );
  }

  Widget _buildBottomControls() {
    return Container(
      padding: const EdgeInsets.all(24.0),
      decoration: BoxDecoration(
        color: Colors.white,
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          // Back Button or Indicator
          if (_currentPage > 0)
            TextButton.icon(
              onPressed: _prevPage,
              icon: const Icon(Icons.arrow_back, size: 20),
              label: const Text('Kembali'),
              style: TextButton.styleFrom(foregroundColor: Colors.grey.shade600),
            )
          else
            const SizedBox(width: 100), // Placeholder for symmetry

          // Dots indicator
          Row(
            children: List.generate(4, (index) {
              return AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                margin: const EdgeInsets.only(right: 8),
                height: 6,
                width: _currentPage == index ? 20 : 6,
                decoration: BoxDecoration(
                  color: _currentPage == index ? Colors.orange : Colors.grey.shade300,
                  borderRadius: BorderRadius.circular(4),
                ),
              );
            }),
          ),

          // Next/Finish Button
          if (_currentPage != 1)
            ElevatedButton(
              onPressed: (_isLoading || (_currentPage == 0 && !_termsAccepted)) ? null : _nextPage,
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.orange,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                elevation: 0,
              ),
              child: _isLoading
                  ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                  : Text(
                      _currentPage == 3 ? 'Selesai' : 'Lanjut',
                      style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                    ),
            )
          else
            const SizedBox(width: 100),
        ],
      ),
    );
  }
}
