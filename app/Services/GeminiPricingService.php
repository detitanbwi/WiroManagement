<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiPricingService
{
    protected PricingCalculatorEngine $calculator;
    protected ?string $apiKey;
    protected string $model;

    public function __construct(PricingCalculatorEngine $calculator)
    {
        $this->calculator = $calculator;
        $this->apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));
        $this->model = config('services.gemini.model', env('GEMINI_MODEL', 'gemini-3.5-flash-lite'));
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Send conversation history to Gemini API and parse response.
     *
     * @param array $chatHistory Array of ['role' => 'user'|'assistant', 'content' => '...']
     * @param array $currentSessionState
     * @return array
     */
    public function generatePricingAdvice(array $chatHistory, array $currentSessionState = []): array
    {
        if (!$this->isConfigured()) {
            return $this->fallbackRuleBasedResponse($chatHistory, $currentSessionState, "API Key Gemini belum disetel di .env (GEMINI_API_KEY). Sistem menggunakan deteksi kata kunci lokal bawaan.");
        }

        try {
            $systemInstruction = $this->buildSystemInstruction();
            $contents = $this->formatHistoryForGemini($chatHistory);

            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->timeout(30)
                ->post($endpoint, [
                    'system_instruction' => [
                        'parts' => [
                            ['text' => $systemInstruction]
                        ]
                    ],
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'topP' => 0.95,
                        'maxOutputTokens' => 2048,
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                return $this->parseGeminiResponse($rawText, $currentSessionState);
            }

            Log::warning("Gemini API request failed: " . $response->status() . " " . $response->body());
            
            // If model fails, try fallback to gemini-3.1-flash-lite
            if ($this->model !== 'gemini-3.1-flash-lite') {
                $this->model = 'gemini-3.1-flash-lite';
                return $this->generatePricingAdvice($chatHistory, $currentSessionState);
            }

            return $this->fallbackRuleBasedResponse($chatHistory, $currentSessionState, "Koneksi API Gemini mengalami kendala (" . $response->status() . "). Beralih ke deteksi kata kunci lokal.");

        } catch (\Throwable $e) {
            Log::error("Gemini Pricing Error: " . $e->getMessage());
            return $this->fallbackRuleBasedResponse($chatHistory, $currentSessionState, "Terjadi kesalahan saat memanggil Gemini API: " . $e->getMessage());
        }
    }

    /**
     * Build comprehensive system instruction from dataset rules.
     */
    protected function buildSystemInstruction(): string
    {
        $rules = $this->calculator->getRules();
        $modules = $rules['modules'] ?? [];
        $platforms = $rules['platforms'] ?? [];
        $segments = $rules['segments'] ?? [];

        $modulesJson = json_encode($modules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $platformsJson = json_encode($platforms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $segmentsJson = json_encode($segments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Anda adalah AI Solution Architect & Pricing Engine dari Wirodayan Digital (Wiro App).

GAYA DAN ATURAN KOMUNIKASI (SANGAT KETAT):
1. **JANGAN BERTELE-TELE & HINDARI BASA-BASI**: 
   - DILARANG menulis kalimat pembuka klise seperti "Halo! Terima kasih atas penjelasannya...", "Brief Anda sangat terstruktur...", "Senang bisa membantu...".
   - LANGSUNG ke poin analisis: sebutkan modul yang teridentifikasi secara ringkas.
2. **SATU PERTANYAAN PER GILIRAN (ONE QUESTION AT A TIME)**:
   - DILARANG menanyakan 2 atau 3 pertanyaan sekaligus dalam satu balasan.
   - JIKA ada yang perlu diklarifikasi, TULISKAN HANYA 1 PERTANYAAN TERPENTING di akhir pesan Anda (misal: "❓ **Pertanyaan:** Apakah butuh ...?").
   - Sertakan pilihan jawaban cepat (`quick_options`) di dalam JSON output agar pengguna bisa langsung klik opsi jawabannya.
   - Setelah pengguna menjawab di chat berikutnya, barulah ajukan 1 pertanyaan berikutnya jika masih ada.
   - JIKA SEMUA SUDAH JELAS: Jangan ajukan pertanyaan lagi, cukup simpulkan modul dan kalkulasi siap ditinjau.
3. **JANGAN SEBUT MERK SPESIFIK**:
   - Jangan sebut merk seperti Olsera, Fonnte, Midtrans kecuali jika pengguna yang menyebutkannya duluan. Gunakan istilah generik seperti "integrasi API pihak ketiga (POS/ERP eksternal)", "notifikasi WhatsApp otomatis", "payment gateway online".
4. **BREAKDOWN FITUR KOMPLEKS & ESTIMASI SUB-ITEM (ENTITAS / RINCIAN)**:
   - JIKA klien menyebutkan kebutuhan sistem/fitur yang luas (misal: 'Sistem Inventaris Toko', 'Sistem Penggajian & HR', 'Sistem Manajemen Pergudangan'):
     PECAH / BREAKDOWN menjadi kombinasi modul Rate Card yang ada dan daftarkan sub-item / entitas yang teridentifikasi ke dalam array `sub_items`.
     - `CR-001` (Core CRUD): Isi `sub_items` dengan nama-nama entitas master data yang teridentifikasi (contoh: `["Barang", "Supplier", "Kategori", "Pelanggan"]`, sehingga Qty otomatis 4).
     - `CR-002` (Relasional): Isi `sub_items` dengan nama-nama transaksi relasional (contoh: `["Purchase Order (PO)", "Surat Jalan / Pengiriman"]`, sehingga Qty otomatis 2).
     - `CR-008` (Export Excel) / `CR-009` (Export PDF): Isi `sub_items` dengan nama-nama laporan/dokumen yang perlu diekspor (contoh: `["Laporan Omset Penjualan", "Rekapitulasi Stok Gudang"]`).
   - Kuantitas (`qty`) adalah jumlah elemen dalam `sub_items`.
   - HANYA kebutuhan yang benar-benar kustom/eksternal yang tidak dapat dibangun dari kombinasi 19 modul Rate Card (misal: IoT Sensor fisik, Timbangan Truk Serial Port, Face Recognition kamera khusus) yang dicatat ke `unlisted_features`.
5. **MENCATAT FITUR KHUSUS / UNLISTED FEATURES**:
   - Jika ada fitur yang benar-benar kustom dan tidak tercakup di Rate Card, catat ke array `unlisted_features`.
   - Di teks pesan, sebutkan: "📌 *Catatan Fitur Khusus: [Nama Fitur] (Perlu estimasi custom R&D / add-on terpisah)*".
6. **JANGAN HITUNG RUPIAH MANUAL DI CHAT**:
   - Biarkan backend engine kami yang menghitung angka rupiah deterministik di panel kanan.
7. **PAGAR KONTEKS — TOLAK TOPIK DI LUAR PERENCANAAN PROYEK**:
   - Chat ini HANYA untuk perencanaan dan pengembangan proyek software/website/aplikasi.
   - JIKA pengguna mengirim pesan yang TIDAK RELEVAN dengan perencanaan/pengembangan proyek (misalnya: meminta menulis kode program, membahas makanan, cuaca, olahraga, politik, AI/ChatGPT prompt, curhat, pertanyaan umum yang tidak menyangkut requirement proyek, meminta debug/solving bug, meminta generate source code), TOLAK DENGAN SOPAN.
   - Contoh penolakan: "⚠️ Maaf, chat ini khusus untuk diskusi perencanaan dan estimasi kebutuhan proyek software. Silakan ketik brief/kebutuhan proyek yang ingin diestimasi."
   - Yang DIPERBOLEHKAN: diskusi kebutuhan klien, spesifikasi fitur, pemilihan platform, pertanyaan seputar arsitektur proyek, timeline, dan semua hal yang berkaitan dengan perencanaan pengembangan aplikasi/website.

DATASET ACUAN RESMI:
--- DAFTAR MODUL TERSEDIA ---
{$modulesJson}

--- PLATFORM & MULTIPLIER ---
{$platformsJson}

--- SEGMEN KLIEN ---
{$segmentsJson}

ATURAN WAJIB OUTPUT JSON:
Di akhir setiap respon, sertakan blok JSON berikut:
```json
{
  "client_name": "Nama klien atau null",
  "client_segment": "umkm|sme|enterprise",
  "platform": "web|android|hybrid|offline_first",
  "risk_buffer_percent": 0,
  "rush_fee_percent": 0,
  "detected_modules": [
    { 
      "code": "CR-001", 
      "qty": 3,
      "sub_items": ["Produk", "Kategori", "Supplier"] 
    },
    { 
      "code": "CR-002", 
      "qty": 1,
      "sub_items": ["Pesanan & Detail Item"] 
    },
    { 
      "code": "CR-006", 
      "qty": 1,
      "sub_items": ["Admin & Operator"] 
    }
  ],
  "unlisted_features": [
    {
      "name": "Nama Fitur Khusus yang Tidak Ada di Menu",
      "description": "Keterangan singkat kebutuhan custom klien"
    }
  ],
  "has_clarification_questions": true,
  "next_question": "Teks 1 pertanyaan klarifikasi singkat",
  "quick_options": ["Pilihan Jawaban 1", "Pilihan Jawaban 2"]
}
```
Jika tidak ada unlisted feature, isi `unlisted_features: []`.
Jika tidak ada pertanyaan lagi, set `has_clarification_questions: false`, `next_question: null`, dan `quick_options: []`.
PROMPT;
    }

    /**
     * Format conversation history for Gemini API.
     */
    protected function formatHistoryForGemini(array $chatHistory): array
    {
        $contents = [];
        foreach ($chatHistory as $msg) {
            $role = ($msg['role'] === 'assistant' || $msg['role'] === 'model') ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => (string)$msg['content']]
                ]
            ];
        }

        // Gemini requires user role first
        if (empty($contents) || $contents[0]['role'] !== 'user') {
            array_unshift($contents, [
                'role' => 'user',
                'parts' => [['text' => 'Halo, saya ingin konsultasi estimasi harga proyek baru.']]
            ]);
        }

        return $contents;
    }

    /**
     * Parse text response from Gemini and extract JSON parameters.
     */
    public function parseGeminiResponse(string $rawText, array $currentSessionState = []): array
    {
        $cleanText = $rawText;
        $extractedParams = [
            'client_name' => $currentSessionState['client_name'] ?? null,
            'client_segment' => $currentSessionState['client_segment'] ?? 'umkm',
            'platform' => $currentSessionState['platform'] ?? 'web',
            'risk_buffer_percent' => $currentSessionState['risk_buffer_percent'] ?? 0,
            'rush_fee_percent' => $currentSessionState['rush_fee_percent'] ?? 0,
            'detected_modules' => $currentSessionState['selected_modules'] ?? [],
            'unlisted_features' => $currentSessionState['unlisted_features'] ?? [],
            'has_clarification_questions' => false,
            'next_question' => null,
            'quick_options' => [],
        ];

        // Extract JSON code block
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $rawText, $matches)) {
            $jsonString = trim($matches[1]);
            $parsedJson = json_decode($jsonString, true);
            if (is_array($parsedJson)) {
                // Normalize detected_modules
                if (isset($parsedJson['detected_modules'])) {
                    $norm = [];
                    foreach ($parsedJson['detected_modules'] as $key => $val) {
                        if (is_array($val) && isset($val['code'])) {
                            $subItems = isset($val['sub_items']) && is_array($val['sub_items']) ? array_values(array_filter($val['sub_items'])) : [];
                            $qty = !empty($subItems) ? count($subItems) : max(1, (int)($val['qty'] ?? 1));
                            $norm[] = [
                                'code' => $val['code'],
                                'qty' => $qty,
                                'sub_items' => $subItems
                            ];
                        } elseif (is_string($key)) {
                            $norm[] = [
                                'code' => $key,
                                'qty' => max(1, (int)$val),
                                'sub_items' => []
                            ];
                        } elseif (is_string($val)) {
                            $norm[] = [
                                'code' => $val,
                                'qty' => 1,
                                'sub_items' => []
                            ];
                        }
                    }
                    $parsedJson['detected_modules'] = $norm;
                }

                $extractedParams = array_merge($extractedParams, $parsedJson);
                // Remove the json block from visible text for cleaner chat display
                $cleanText = trim(str_replace($matches[0], '', $rawText));
            }
        }

        return [
            'message' => $cleanText,
            'extracted_params' => $extractedParams,
            'raw_response' => $rawText
        ];
    }

    /**
     * Fallback heuristic parser using keyword matching if API is offline.
     */
    public function fallbackRuleBasedResponse(array $chatHistory, array $currentSessionState = [], string $notice = ''): array
    {
        $lastUserMessage = '';
        foreach (array_reverse($chatHistory) as $msg) {
            if ($msg['role'] === 'user') {
                $lastUserMessage = strtolower($msg['content']);
                break;
            }
        }

        $rules = $this->calculator->getRules();
        $detectedModules = $currentSessionState['selected_modules'] ?? [];
        if (!is_array($detectedModules)) {
            $detectedModules = [];
        }

        $detectedPlatform = $currentSessionState['platform'] ?? 'web';
        if (str_contains($lastUserMessage, 'android') || str_contains($lastUserMessage, 'apk') || str_contains($lastUserMessage, 'flutter')) {
            $detectedPlatform = 'android';
        } elseif (str_contains($lastUserMessage, 'offline') || str_contains($lastUserMessage, 'tanpa internet')) {
            $detectedPlatform = 'offline_first';
        }

        // Match keywords for each module
        foreach ($rules['modules'] ?? [] as $module) {
            $code = $module['code'];
            $keywords = $module['keywords'] ?? [];
            $match = false;
            foreach ($keywords as $kw) {
                if (!empty($kw) && str_contains($lastUserMessage, strtolower($kw))) {
                    $match = true;
                    break;
                }
            }
            if ($match && !in_array($code, $detectedModules)) {
                $detectedModules[] = $code;
            }
        }

        // Always include basic CRUD and Auth if empty and mentioned
        if (empty($detectedModules)) {
            $detectedModules = ['CR-001', 'CR-006'];
        }

        $reply = "Halo! Saya telah menganalisis kebutuhan proyek Anda. " .
                 (!empty($notice) ? "\n\n> *ℹ️ Catatan: {$notice}*\n\n" : "") .
                 "Berdasarkan brief yang Anda sampaikan, modul yang teridentifikasi:\n";

        foreach ($rules['modules'] ?? [] as $m) {
            if (in_array($m['code'], $detectedModules)) {
                $reply .= "- **{$m['name']}** ({$m['code']}): {$m['spec']}\n";
            }
        }

        $reply .= "\nApakah ada kebutuhan tambahan seperti cetak printer thermal struk, integrasi WhatsApp notifikasi, atau export Excel/PDF?";

        $extractedParams = [
            'client_name' => $currentSessionState['client_name'] ?? null,
            'client_segment' => $currentSessionState['client_segment'] ?? 'umkm',
            'platform' => $detectedPlatform,
            'risk_buffer_percent' => $currentSessionState['risk_buffer_percent'] ?? 0,
            'rush_fee_percent' => $currentSessionState['rush_fee_percent'] ?? 0,
            'detected_modules' => array_values(array_unique($detectedModules)),
            'has_clarification_questions' => true,
        ];

        return [
            'message' => $reply,
            'extracted_params' => $extractedParams,
            'raw_response' => null
        ];
    }
}
