import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'l10n/app_localizations.dart';
import 'widgets/top_toast.dart';
import 'widgets/nlp_input_dialog.dart';
import 'screens/transaction_list_screen.dart';
import 'screens/master_data_screen.dart';
import 'widgets/transaction_bottom_sheet.dart';
import 'widgets/expense_statistics_card.dart';
import 'services/database_helper.dart';
import 'services/sync_service.dart';
import 'core/config/app_config.dart';
import 'core/di/injection.dart';
import 'domain/repositories/expense_repository.dart';
import 'core/services/preference_service.dart';
import 'screens/onboarding_screen.dart';
import 'screens/about_screen.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:url_launcher/url_launcher.dart';
import 'theme/theme_notifier.dart';
import 'services/widget_service.dart';
import 'package:home_widget/home_widget.dart';



void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await PreferenceService.instance.init();
  runApp(const WiroFinApp());
}

class WiroFinApp extends StatefulWidget {
  const WiroFinApp({super.key});

  @override
  State<WiroFinApp> createState() => _WiroFinAppState();
}

class _WiroFinAppState extends State<WiroFinApp> {
  bool _isFirstLaunch = PreferenceService.instance.isFirstLaunch;

  @override
  void initState() {
    super.initState();
    _checkUpdate();
  }

  Future<void> _checkUpdate() async {
    try {
      final packageInfo = await PackageInfo.fromPlatform();
      final localVersion = packageInfo.version;
      
      // Mocking remote version check
      // In real scenario, you would fetch this from an API or Play Store scrape
      const remoteVersion = "1.1.0"; // Simulate a newer version available
      
      if (_isVersionGreater(remoteVersion, localVersion)) {
        if (mounted) {
          _showUpdateDialog();
        }
      }
    } catch (e) {
      debugPrint('Error checking update: $e');
    }
  }

  bool _isVersionGreater(String remote, String local) {
    try {
      List<int> remoteParts = remote.split('.').map(int.parse).toList();
      List<int> localParts = local.split('.').map(int.parse).toList();
      for (int i = 0; i < 3; i++) {
        if (remoteParts[i] > localParts[i]) return true;
        if (remoteParts[i] < localParts[i]) return false;
      }
    } catch (_) {}
    return false;
  }

  void _showUpdateDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => PopScope(
        canPop: false,
        onPopInvoked: (_) => false,
        child: AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: const Text('Update Tersedia!', style: TextStyle(fontWeight: FontWeight.bold)),
          content: const Text(
            'Versi terbaru WiroFin sudah tersedia di Play Store. Silakan update untuk melanjutkan menggunakan aplikasi.',
          ),
          actions: [
            TextButton(
              onPressed: () => SystemNavigator.pop(),
              child: Text('Keluar', style: TextStyle(color: Colors.grey.shade600)),
            ),
            ElevatedButton(
              onPressed: () async {
                final url = Uri.parse('https://play.google.com/store/apps/details?id=com.wirodev.wirofin');
                if (await canLaunchUrl(url)) {
                  await launchUrl(url, mode: LaunchMode.externalApplication);
                }
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: Theme.of(context).primaryColor,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              child: const Text('Update Sekarang', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<Locale>(
      valueListenable: PreferenceService.instance.localeNotifier,
      builder: (context, locale, child) {
        return ListenableBuilder(
          listenable: ThemeNotifier.instance,
          builder: (context, child) {
            return MaterialApp(
              title: 'WiroFin',
              debugShowCheckedModeBanner: false,
              localizationsDelegates: const [
                AppLocalizations.delegate,
                GlobalMaterialLocalizations.delegate,
                GlobalWidgetsLocalizations.delegate,
                GlobalCupertinoLocalizations.delegate,
              ],
              supportedLocales: const [
                Locale('id'),
                Locale('en'),
              ],
              locale: locale,
              theme: ThemeNotifier.instance.currentTheme,
              home: _isFirstLaunch
                  ? OnboardingScreen(
                      onFinish: () {
                        setState(() {
                          _isFirstLaunch = false;
                        });
                      },
                    )
                  : const DashboardScreen(),
            );
          },
        );
      },
    );
  }
}

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  late String _activeMode;
  List<Map<String, dynamic>> _transactions = [];
  List<Map<String, dynamic>> _categoryData = [];
  List<Map<String, dynamic>> _trendData = [];
  bool _isLoading = true;
  String _userName = 'User';
  DateTime? _startDate;
  DateTime? _endDate;

  @override
  void initState() {
    super.initState();
    _activeMode = PreferenceService.instance.activeMode;
    _userName = PreferenceService.instance.userName;
    _loadTransactions();
    _initWidgetListener();
    // Refresh UI otomatis saat sinkronisasi background selesai
    if (!AppConfig.instance.isOfflineMode) {
      SyncService.instance.onSyncComplete = _loadTransactions;
      SyncService.instance.init();
    }
  }

  void _initWidgetListener() {
    HomeWidget.initiallyLaunchedFromHomeWidget().then((Uri? uri) {
      if (uri != null && uri.host == 'add_transaction') {
        _triggerNlpFromWidget();
      }
    });

    HomeWidget.widgetClicked.listen((Uri? uri) {
      if (uri != null && uri.host == 'add_transaction') {
        _triggerNlpFromWidget();
      }
    });
  }

  void _triggerNlpFromWidget() async {
    final res = await NlpInputDialog.show(context, activeMode: _activeMode);
    if (res != null && mounted) {
      _openTransactionSheet(initialTransaction: {
        'amount': res.amount,
        'description': res.description,
        'category_id': res.categoryId,
        'transaction_type': res.transactionType,
        'date': res.date.toIso8601String(),
      });
    }
  }

  Future<void> _loadTransactions({bool silent = false}) async {
    if (!silent) setState(() => _isLoading = true);
    final data = await getIt<ExpenseRepository>().getExpenses(
      type: _activeMode,
      startDate: _startDate,
      endDate: _endDate,
    );
    final catData = await DatabaseHelper.instance.getCategoryExpenses(
      _activeMode,
      transactionType: 'expense',
      startDate: _startDate,
      endDate: _endDate,
    );
    final trendData = await DatabaseHelper.instance.getDailyExpensesTrend(
      _activeMode,
      transactionType: 'expense',
      startDate: _startDate,
      endDate: _endDate,
    );

    setState(() {
      _transactions = List<Map<String, dynamic>>.from(data).map((item) {
        return {
          ...item,
          'date': DateTime.parse(item['date']),
        };
      }).toList();
      _categoryData = catData;
      _trendData = trendData;
      _isLoading = false;
    });

    WidgetService.syncDataToWidget(_activeMode);
  }

  void _showDateRangePicker() async {
    final DateTimeRange? picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime(2101),
      initialDateRange: _startDate != null && _endDate != null
          ? DateTimeRange(start: _startDate!, end: _endDate!)
          : null,
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: ColorScheme.light(
              primary: const Color(0xFF00367D),
              onPrimary: Colors.white,
              onSurface: const Color(0xFF1E293B),
            ),
          ),
          child: child!,
        );
      },
    );

    if (picked != null) {
      setState(() {
        _startDate = picked.start;
        _endDate = picked.end;
      });
      _loadTransactions(silent: true);
    }
  }

  void _clearDateFilter() {
    setState(() {
      _startDate = null;
      _endDate = null;
    });
    _loadTransactions(silent: true);
  }

  void _openTransactionSheet({Map<String, dynamic>? initialTransaction}) async {
    final result = await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => Padding(
        padding: EdgeInsets.only(
          top: MediaQuery.of(context).padding.top + 24,
          bottom: MediaQuery.of(context).viewInsets.bottom,
        ),
        child: TransactionBottomSheet(
          activeMode: _activeMode,
          initialTransaction: initialTransaction,
        ),
      ),
    );

    if (result != null) {
      await getIt<ExpenseRepository>().saveExpense({
        'amount': result['amount'],
        'description': result['description'],
        'category_id': result['category_id'],
        'account_id': result['account_id'],
        'date': DateTime.now().toIso8601String(),
        'type': result['type'],
        'transaction_type': result['transaction_type'] ?? 'expense',
      });
      await _loadTransactions();
      if (mounted) {
        TopToast.show(context, 'Transaksi berhasil disimpan');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    // Filter transactions based on active mode
    final filteredTransactions = _transactions.where((t) => t['type'] == _activeMode).toList();
    
    // Calculate total income, expense, and net balance
    final totalIncome = filteredTransactions
        .where((t) => t['transaction_type'] == 'income')
        .fold<int>(0, (sum, item) => sum + (item['amount'] as int));
    final totalExpense = filteredTransactions
        .where((t) => (t['transaction_type'] ?? 'expense') == 'expense')
        .fold<int>(0, (sum, item) => sum + (item['amount'] as int));
    final netBalance = totalIncome - totalExpense;
    
    final formatter = NumberFormat('#,###', 'id_ID');
    final Color themeColor = _activeMode == 'personal' ? Colors.orange.shade600 : Colors.teal.shade600;

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light.copyWith(
        statusBarColor: Colors.transparent,
        systemNavigationBarColor: Colors.white,
        systemNavigationBarIconBrightness: Brightness.dark,
        systemNavigationBarDividerColor: Colors.grey.shade300,
      ),
      child: PopScope(
        canPop: false,
        onPopInvoked: (didPop) async {
          if (didPop) return;
          final bool shouldPop = await _showExitConfirmation(context) ?? false;
          if (shouldPop) {
            SystemNavigator.pop();
          }
        },
        child: Scaffold(
          backgroundColor: Colors.white,
          body: Stack(
            children: [
              Container(
                height: MediaQuery.of(context).size.height * 0.75,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [
                      Theme.of(context).primaryColorDark,
                      Theme.of(context).primaryColor,
                    ],
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                  ),
                ),
              ),
              SafeArea(
                child: ListView(
                  children: [
                    // Header & Toggle
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 16.0),
                  child: Column(
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Halo, $_userName',
                                style: const TextStyle(
                                  fontSize: 16,
                                  color: Colors.white70,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              const Text(
                                'Expense Tracker',
                                style: TextStyle(
                                  fontSize: 24,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                ),
                              ),
                            ],
                          ),
                          Row(
                            children: [
                              IconButton(
                                icon: const Icon(Icons.settings, color: Colors.white70),
                                onPressed: () {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (context) => MasterDataScreen(activeMode: _activeMode),
                                    ),
                                  ).then((_) {
                                    setState(() {
                                      _userName = PreferenceService.instance.userName;
                                    });
                                    _loadTransactions();
                                  });
                                },
                              ),
                              GestureDetector(
                                onTap: () {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(builder: (context) => const AboutScreen()),
                                  );
                                },
                                child: CircleAvatar(
                                  backgroundColor: Colors.white.withOpacity(0.2),
                                  child: const Icon(Icons.info_outline, color: Colors.white),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                      const SizedBox(height: 24),
                      // Custom Toggle
                      Container(
                        height: 50,
                        padding: const EdgeInsets.all(4),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.15),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: Colors.white.withOpacity(0.2), width: 1),
                        ),
                        child: Stack(
                          children: [
                            // Animated Background Pill
                            AnimatedAlign(
                              duration: const Duration(milliseconds: 300),
                              curve: Curves.easeInOut,
                              alignment: _activeMode == 'personal' ? Alignment.centerLeft : Alignment.centerRight,
                              child: FractionallySizedBox(
                                widthFactor: 0.5,
                                child: Container(
                                  decoration: BoxDecoration(
                                    color: Colors.white,
                                    borderRadius: BorderRadius.circular(12),
                                    boxShadow: [
                                      BoxShadow(
                                        color: Colors.black.withOpacity(0.1),
                                        blurRadius: 8,
                                        offset: const Offset(0, 2),
                                      )
                                    ],
                                  ),
                                ),
                              ),
                            ),
                            // Buttons
                            Row(
                              children: [
                                Expanded(
                                  child: GestureDetector(
                                    onTap: () {
                                      setState(() => _activeMode = 'personal');
                                      PreferenceService.instance.setActiveMode('personal');
                                      ThemeNotifier.instance.toggleMode('personal');
                                      _loadTransactions(silent: true);
                                    },
                                    behavior: HitTestBehavior.opaque,
                                    child: Center(
                                      child: AnimatedDefaultTextStyle(
                                        duration: const Duration(milliseconds: 300),
                                        style: TextStyle(
                                          fontFamily: 'Inter',
                                          fontWeight: FontWeight.bold,
                                          color: _activeMode == 'personal' ? Colors.orange.shade700 : Colors.white70,
                                          fontSize: 12,
                                        ),
                                        child: const Text('PERSONAL'),
                                      ),
                                    ),
                                  ),
                                ),
                                Expanded(
                                  child: GestureDetector(
                                    onTap: () {
                                      setState(() => _activeMode = 'company');
                                      PreferenceService.instance.setActiveMode('company');
                                      ThemeNotifier.instance.toggleMode('company');
                                      _loadTransactions(silent: true);
                                    },
                                    behavior: HitTestBehavior.opaque,
                                    child: Center(
                                      child: AnimatedDefaultTextStyle(
                                        duration: const Duration(milliseconds: 300),
                                        style: TextStyle(
                                          fontFamily: 'Inter',
                                          fontWeight: FontWeight.bold,
                                          color: _activeMode == 'company' ? Colors.teal.shade700 : Colors.white70,
                                          fontSize: 12,
                                        ),
                                        child: const Text('COMPANY'),
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                      // Date Filter Row
                      Row(
                        children: [
                          Expanded(
                            child: GestureDetector(
                              onTap: _showDateRangePicker,
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.1),
                                  borderRadius: BorderRadius.circular(12),
                                  border: Border.all(color: Colors.white.withOpacity(0.2)),
                                ),
                                child: Row(
                                  children: [
                                    const Icon(Icons.calendar_today, size: 16, color: Colors.white70),
                                    const SizedBox(width: 12),
                                    Text(
                                      _startDate == null 
                                        ? 'Semua Waktu' 
                                        : '${DateFormat('dd MMM').format(_startDate!)} - ${DateFormat('dd MMM').format(_endDate!)}',
                                      style: const TextStyle(color: Colors.white, fontSize: 13),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ),
                          if (_startDate != null) ...[
                            const SizedBox(width: 8),
                            IconButton(
                              icon: const Icon(Icons.close, color: Colors.white),
                              onPressed: _clearDateFilter,
                              tooltip: 'Hapus Filter',
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
                
                // Balance Card
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 24.0),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(24.0),
                    child: BackdropFilter(
                      filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
                      child: Container(
                        padding: const EdgeInsets.all(24.0),
                        decoration: BoxDecoration(
                          color: themeColor.withOpacity(0.2),
                          borderRadius: BorderRadius.circular(24.0),
                          border: Border.all(
                            color: themeColor.withOpacity(0.3),
                            width: 1.5,
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                const Text(
                                  'SALDO BERSIH',
                                  style: TextStyle(
                                    color: Colors.white70,
                                    fontSize: 11,
                                    letterSpacing: 1.2,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: Colors.white.withOpacity(0.15),
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  child: const Text(
                                    'Live Update',
                                    style: TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.w600),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 6),
                            Text(
                              '${netBalance < 0 ? '-' : ''}Rp ${formatter.format(netBalance.abs())}',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 32,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const SizedBox(height: 20),
                            Container(
                              height: 1,
                              width: double.infinity,
                              color: Colors.white12,
                            ),
                            const SizedBox(height: 16),
                            Row(
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        children: [
                                          Container(
                                            padding: const EdgeInsets.all(4),
                                            decoration: BoxDecoration(
                                              color: Colors.green.withOpacity(0.2),
                                              shape: BoxShape.circle,
                                            ),
                                            child: const Icon(Icons.arrow_downward, size: 12, color: Colors.greenAccent),
                                          ),
                                          const SizedBox(width: 6),
                                          const Text(
                                            'PEMASUKAN',
                                            style: TextStyle(color: Colors.white70, fontSize: 10, fontWeight: FontWeight.w600),
                                          ),
                                        ],
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        '+Rp ${formatter.format(totalIncome)}',
                                        style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
                                      ),
                                    ],
                                  ),
                                ),
                                Container(width: 1, height: 35, color: Colors.white12),
                                const SizedBox(width: 16),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        children: [
                                          Container(
                                            padding: const EdgeInsets.all(4),
                                            decoration: BoxDecoration(
                                              color: Colors.red.withOpacity(0.2),
                                              shape: BoxShape.circle,
                                            ),
                                            child: const Icon(Icons.arrow_upward, size: 12, color: Colors.redAccent),
                                          ),
                                          const SizedBox(width: 6),
                                          const Text(
                                            'PENGELUARAN',
                                            style: TextStyle(color: Colors.white70, fontSize: 10, fontWeight: FontWeight.w600),
                                          ),
                                        ],
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        '-Rp ${formatter.format(totalExpense)}',
                                        style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),

                const SizedBox(height: 16),

                // Statistics
                if (_categoryData.isNotEmpty || _trendData.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 24.0),
                    child: ExpenseStatisticsCard(
                      categoryData: _categoryData,
                      trendData: _trendData,
                      themeColor: themeColor,
                    ),
                  ),

                const SizedBox(height: 24),

                // Transaction List
                Container(
                  constraints: BoxConstraints(minHeight: MediaQuery.of(context).size.height * 0.5),
                  decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(32),
                        topRight: Radius.circular(32),
                      ),
                    ),
                    child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 24.0),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text(
                              'Riwayat Transaksi',
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF1E293B),
                              ),
                            ),
                            GestureDetector(
                              onTap: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (context) => TransactionListScreen(
                                      transactions: _transactions,
                                      activeMode: _activeMode,
                                      onRefresh: _loadTransactions,
                                    ),
                                  ),
                                ).then((_) => _loadTransactions());
                              },
                              behavior: HitTestBehavior.opaque,
                              child: Padding(
                                padding: const EdgeInsets.symmetric(vertical: 4.0),
                                child: Text(
                                  'Lihat Semua',
                                  style: TextStyle(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w600,
                                    color: themeColor,
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      if (filteredTransactions.isEmpty) 
                        Padding(
                          padding: const EdgeInsets.all(64.0),
                          child: Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.receipt_long, size: 64, color: Colors.grey.shade300),
                                const SizedBox(height: 16),
                                Text(
                                  'Belum ada transaksi',
                                  style: TextStyle(color: Colors.grey.shade500),
                                ),
                              ],
                            ),
                          ),
                        )
                      else
                        ListView.builder(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          padding: const EdgeInsets.symmetric(horizontal: 24.0),
                          itemCount: filteredTransactions.length > 5 ? 5 : filteredTransactions.length,
                          itemBuilder: (context, index) {
                            final item = filteredTransactions[index];
                            final isIncome = item['transaction_type'] == 'income';
                            return Container(
                              margin: const EdgeInsets.only(bottom: 16),
                              padding: const EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(color: Colors.grey.shade100),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withOpacity(0.02),
                                    blurRadius: 8,
                                    offset: const Offset(0, 4),
                                  ),
                                ],
                              ),
                              child: Row(
                                children: [
                                  Container(
                                    padding: const EdgeInsets.all(12),
                                    decoration: BoxDecoration(
                                      color: isIncome ? Colors.green.withOpacity(0.1) : Colors.red.withOpacity(0.1),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Icon(
                                      isIncome ? Icons.arrow_downward : Icons.arrow_upward,
                                      color: isIncome ? Colors.green.shade600 : Colors.red.shade600,
                                    ),
                                  ),
                                  const SizedBox(width: 16),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Row(
                                          children: [
                                            Expanded(
                                              child: Text(
                                                item['category'] ?? 'Kategori...',
                                                style: const TextStyle(
                                                  fontWeight: FontWeight.bold,
                                                  fontSize: 16,
                                                  color: Color(0xFF1E293B),
                                                ),
                                              ),
                                            ),
                                            if (!AppConfig.instance.isOfflineMode) ...[
                                              if (item['sync_status'] == 'syncing')
                                                const SizedBox(
                                                  width: 14,
                                                  height: 14,
                                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.blue),
                                                )
                                              else if (item['sync_status'] == 'pending')
                                                Icon(Icons.cloud_queue, size: 14, color: Colors.grey.shade400),
                                            ],
                                          ],
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          (item['description'] == null || item['description'].isEmpty) 
                                            ? (item['account'] ?? 'Pilih Akun...') 
                                            : item['description'],
                                          style: TextStyle(
                                            fontSize: 13,
                                            color: Colors.grey.shade500,
                                          ),
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ],
                                    ),
                                  ),
                                  Text(
                                    '${isIncome ? '+' : '-'} Rp ${formatter.format(item['amount'])}',
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 16,
                                      color: isIncome ? Colors.green.shade600 : Colors.red.shade600,
                                    ),
                                  ),
                                ],
                              ),
                            );
                          },
                        ),
                    ],
                  ),
                ),
                const SizedBox(height: 100), // padding for FAB so it doesn't overlap content
              ],
            ),
          ),
        ],
      ),
      floatingActionButton: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          FloatingActionButton.extended(
            heroTag: 'nlp_fab',
            onPressed: () async {
              final res = await NlpInputDialog.show(context, activeMode: _activeMode);
              if (res != null) {
                _openTransactionSheet(initialTransaction: {
                  'amount': res.amount,
                  'description': res.description,
                  'category_id': res.categoryId,
                  'transaction_type': res.transactionType,
                  'date': res.date.toIso8601String(),
                });
              }
            },
            backgroundColor: Colors.deepPurple.shade600,
            elevation: 4,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            icon: const Icon(Icons.auto_awesome, color: Colors.white),
            label: const Text('NLP', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
          const SizedBox(width: 12),
          FloatingActionButton(
            heroTag: 'add_fab',
            onPressed: _openTransactionSheet,
            backgroundColor: themeColor,
            elevation: 4,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: const Icon(Icons.add, color: Colors.white),
          ),
        ],
      ),
        ),
      ),
    );
  }

  Future<bool?> _showExitConfirmation(BuildContext context) {
    return showGeneralDialog<bool>(
      context: context,
      barrierDismissible: true,
      barrierLabel: 'Dismiss',
      transitionDuration: const Duration(milliseconds: 300),
      pageBuilder: (context, animation, secondaryAnimation) {
        return Center(
          child: Container(
            margin: const EdgeInsets.symmetric(horizontal: 32),
            padding: const EdgeInsets.all(32),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(24),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.1),
                  blurRadius: 20,
                  offset: const Offset(0, 10),
                )
              ],
            ),
            child: Material(
              color: Colors.transparent,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.orange.shade50,
                      shape: BoxShape.circle,
                    ),
                    child: Icon(Icons.exit_to_app, size: 40, color: Colors.orange.shade600),
                  ),
                  const SizedBox(height: 24),
                  const Text(
                    'Keluar Aplikasi',
                    style: TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF1E293B),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'Apakah Anda yakin ingin keluar dari aplikasi Wiro Expense Tracker?',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 15,
                      color: Colors.grey.shade600,
                      height: 1.5,
                    ),
                  ),
                  const SizedBox(height: 32),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: () => Navigator.of(context).pop(false),
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            side: BorderSide(color: Colors.grey.shade300),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          ),
                          child: Text(
                            'Batal',
                            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.grey.shade700),
                          ),
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: ElevatedButton(
                          onPressed: () => Navigator.of(context).pop(true),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.orange.shade600,
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            elevation: 0,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          ),
                          child: const Text(
                            'Keluar',
                            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                          ),
                        ),
                      ),
                    ],
                  )
                ],
              ),
            ),
          ),
        );
      },
      transitionBuilder: (context, animation, secondaryAnimation, child) {
        return ScaleTransition(
          scale: CurvedAnimation(parent: animation, curve: Curves.easeOutBack),
          child: FadeTransition(
            opacity: animation,
            child: child,
          ),
        );
      },
    );
  }
}
