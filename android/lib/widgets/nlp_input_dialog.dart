import 'package:flutter/material.dart';
import '../services/nlp_service.dart';
import 'top_toast.dart';

class NlpInputDialog extends StatefulWidget {
  final String activeMode;

  const NlpInputDialog({super.key, required this.activeMode});

  static Future<NlpParseResult?> show(BuildContext context, {required String activeMode}) {
    return showModalBottomSheet<NlpParseResult>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => Padding(
        padding: EdgeInsets.only(
          top: MediaQuery.of(context).padding.top + 24,
          bottom: MediaQuery.of(context).viewInsets.bottom,
        ),
        child: NlpInputDialog(activeMode: activeMode),
      ),
    );
  }

  @override
  State<NlpInputDialog> createState() => _NlpInputDialogState();
}

class _NlpInputDialogState extends State<NlpInputDialog> {
  final TextEditingController _controller = TextEditingController();
  bool _isProcessing = false;

  final List<String> _suggestions = [
    "Ngopi kopi kenangan 28k",
    "Gaji bulanan masuk 5jt",
    "Beli bensin pertamax 50rb kemaren",
    "Makan siang warteg 20000",
  ];

  Future<void> _processText(String text) async {
    if (text.trim().isEmpty) return;

    setState(() => _isProcessing = true);

    try {
      final res = await NlpService.instance.parseSentence(text);
      if (mounted) {
        Navigator.pop(context, res); // Close NLP dialog and return result
      }
    } catch (e) {
      if (mounted) {
        String msg = "Gagal memproses kalimat";
        if (e is Exception) {
          msg = e.toString().replaceAll('Exception: ', '');
        }
        TopToast.show(context, msg, isError: true);
      }
    } finally {
      if (mounted) {
        setState(() => _isProcessing = false);
      }
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(24),
      constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.85),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        boxShadow: [
          BoxShadow(color: Colors.black12, blurRadius: 20, spreadRadius: 5),
        ],
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.deepPurple.shade50,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Icon(Icons.auto_awesome, color: Colors.deepPurple.shade700),
              ),
              const SizedBox(width: 16),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      "Pencatatan Cerdas (NLP)",
                      style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
                    ),
                    Text(
                      "Ketik kalimat kasual, WiroFin otomatis mengisinya",
                      style: TextStyle(fontSize: 14, color: Colors.grey),
                    ),
                  ],
                ),
              ),
              IconButton(
                icon: const Icon(Icons.close, color: Colors.grey),
                onPressed: () => Navigator.pop(context),
              ),
            ],
          ),
          const SizedBox(height: 24),
          TextField(
            controller: _controller,
            autofocus: true,
            maxLines: 2,
            style: const TextStyle(fontSize: 18),
            decoration: InputDecoration(
              hintText: "Contoh: Beli bensin 50rb kemaren...",
              hintStyle: TextStyle(color: Colors.grey.shade400),
              filled: true,
              fillColor: Colors.grey.shade50,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(20),
                borderSide: BorderSide(color: Colors.grey.shade200),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(20),
                borderSide: BorderSide(color: Colors.deepPurple.shade400, width: 2),
              ),
              suffixIcon: _isProcessing
                  ? const Padding(
                      padding: EdgeInsets.all(12.0),
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : IconButton(
                      icon: Icon(Icons.send, color: Colors.deepPurple.shade600),
                      onPressed: () => _processText(_controller.text),
                    ),
            ),
            onSubmitted: _processText,
          ),
          const SizedBox(height: 20),
          Text(
            "Coba kalimat berikut:",
            style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: Colors.grey.shade700),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _suggestions.map((sug) => ActionChip(
              label: Text(sug, style: TextStyle(color: Colors.deepPurple.shade700, fontSize: 13)),
              backgroundColor: Colors.deepPurple.shade50,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              side: BorderSide.none,
              onPressed: () {
                _controller.text = sug;
                _processText(sug);
              },
            )).toList(),
          ),
          const SizedBox(height: 16),
        ],
      ),
      ),
    );
  }
}
