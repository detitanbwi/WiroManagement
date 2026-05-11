import 'package:flutter/material.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:intl/intl.dart';

class ExpenseStatisticsCard extends StatelessWidget {
  final List<Map<String, dynamic>> categoryData;
  final List<Map<String, dynamic>> trendData;
  final Color themeColor;

  const ExpenseStatisticsCard({
    super.key,
    required this.categoryData,
    required this.trendData,
    required this.themeColor,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(child: _buildPieChart(context)),
        const SizedBox(width: 12),
        Expanded(child: _buildBarChart(context)),
      ],
    );
  }

  Widget _buildPieChart(BuildContext context) {
    if (categoryData.isEmpty) return _buildEmptyState('Distribusi');

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardTitle('Distribusi'),
          const SizedBox(height: 16),
          SizedBox(
            height: 120,
            child: PieChart(
              PieChartData(
                sectionsSpace: 2,
                centerSpaceRadius: 20,
                sections: _generatePieSections(),
              ),
            ),
          ),
          const SizedBox(height: 12),
          _buildCompactLegend(),
        ],
      ),
    );
  }

  BoxDecoration _cardDecoration() {
    return BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(20),
      boxShadow: [
        BoxShadow(
          color: Colors.black.withOpacity(0.04),
          blurRadius: 10,
          offset: const Offset(0, 4),
        ),
      ],
    );
  }

  Widget _cardTitle(String title) {
    return Text(
      title,
      maxLines: 1,
      overflow: TextOverflow.ellipsis,
      style: const TextStyle(
        fontSize: 13,
        fontWeight: FontWeight.bold,
        color: Color(0xFF1E293B),
      ),
    );
  }

  Widget _buildEmptyState(String title) {
    return Container(
      height: 200,
      padding: const EdgeInsets.all(16),
      decoration: _cardDecoration(),
      child: Center(child: Text(title, style: const TextStyle(fontSize: 10, color: Colors.grey))),
    );
  }

  List<PieChartSectionData> _generatePieSections() {
    final List<Color> colors = _getPalette();
    return List.generate(categoryData.length, (i) {
      final data = categoryData[i];
      final total = data['total'] as int;
      return PieChartSectionData(
        color: colors[i % colors.length],
        value: total.toDouble(),
        title: '',
        radius: 35,
      );
    });
  }

  List<Color> _getPalette() {
    return [
      themeColor,
      themeColor.withOpacity(0.7),
      themeColor.withOpacity(0.4),
      Colors.orange,
      Colors.teal,
      Colors.indigo,
    ];
  }

  Widget _buildCompactLegend() {
    final List<Color> colors = _getPalette();
    // Only show top 3 to keep it compact
    final displayData = categoryData.take(3).toList();

    return Column(
      children: List.generate(displayData.length, (i) {
        final data = displayData[i];
        return Padding(
          padding: const EdgeInsets.only(bottom: 4),
          child: Row(
            children: [
              Container(
                width: 8,
                height: 8,
                decoration: BoxDecoration(
                  color: colors[i % colors.length],
                  shape: BoxShape.circle,
                ),
              ),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  data['category'] ?? '...',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 10, color: Colors.grey),
                ),
              ),
            ],
          ),
        );
      }),
    );
  }

  Widget _buildBarChart(BuildContext context) {
    if (trendData.isEmpty) return _buildEmptyState('Tren');

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardTitle('Tren'),
          const SizedBox(height: 24),
          SizedBox(
            height: 120,
            child: BarChart(
              BarChartData(
                alignment: BarChartAlignment.spaceAround,
                maxY: _getMaxY(),
                barTouchData: BarTouchData(enabled: false),
                titlesData: FlTitlesData(
                  show: true,
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      getTitlesWidget: (value, meta) {
                        final index = value.toInt();
                        if (index < 0 || index >= trendData.length) return const SizedBox.shrink();
                        final dateStr = trendData[index]['day'] as String;
                        final date = DateTime.parse(dateStr);
                        return Text(
                          DateFormat('dd').format(date),
                          style: const TextStyle(color: Colors.grey, fontSize: 9),
                        );
                      },
                    ),
                  ),
                  leftTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                  topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                  rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                ),
                gridData: const FlGridData(show: false),
                borderData: FlBorderData(show: false),
                barGroups: _generateBarGroups(),
              ),
            ),
          ),
          const SizedBox(height: 16),
          const Text('7 Hari Terakhir', style: TextStyle(fontSize: 9, color: Colors.grey)),
        ],
      ),
    );
  }

  double _getMaxY() {
    double max = 0;
    for (var data in trendData) {
      final total = (data['total'] as int).toDouble();
      if (total > max) max = total;
    }
    return max == 0 ? 10 : max * 1.2;
  }

  List<BarChartGroupData> _generateBarGroups() {
    return List.generate(trendData.length, (i) {
      final data = trendData[i];
      final total = (data['total'] as int).toDouble();
      return BarChartGroupData(
        x: i,
        barRods: [
          BarChartRodData(
            toY: total,
            color: themeColor,
            width: 10,
            borderRadius: BorderRadius.circular(2),
          ),
        ],
      );
    });
  }
}
