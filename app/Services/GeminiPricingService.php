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
        $this->apiKey = function_exists('config') ? config('services.gemini.api_key', function_exists('env') ? env('GEMINI_API_KEY') : (getenv('GEMINI_API_KEY') ?: null)) : (getenv('GEMINI_API_KEY') ?: null);
        $this->model = function_exists('config') ? config('services.gemini.model', function_exists('env') ? env('GEMINI_MODEL', 'gemini-3.5-flash-lite') : 'gemini-3.5-flash-lite') : 'gemini-3.5-flash-lite';
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
        $corpus = $rules['ai_training_corpus'] ?? [];
        $noveltyOptions = $rules['novelty_options'] ?? [];

        $modulesJson = json_encode($modules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $platformsJson = json_encode($platforms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $segmentsJson = json_encode($segments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $corpusJson = json_encode($corpus, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $noveltyJson = json_encode($noveltyOptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Anda adalah AI Solution Architect & Pricing Engine dari Wirodayan Digital (Wiro App).

GAYA DAN ATURAN KOMUNIKASI (SANGAT KETAT):
1. **JANGAN BERTELE-TELE & HINDARI BASA-BASI**: 
   - DILARANG menulis kalimat pembuka klise seperti "Halo! Terima kasih atas penjelasannya...", "Brief Anda sangat terstruktur...", "Senang bisa membantu...".
   - LANGSUNG ke poin analisis: sebutkan modul yang teridentifikasi secara ringkas dan profesional.
2. **SATU PERTANYAAN PER GILIRAN (ONE QUESTION AT A TIME)**:
   - DILARANG menanyakan 2 atau 3 pertanyaan sekaligus dalam satu balasan.
   - JIKA ada yang perlu diklarifikasi, TULISKAN HANYA 1 PERTANYAAN TERPENTING di akhir pesan Anda (misal: "❓ **Pertanyaan:** Apakah butuh ...?").
   - Sertakan pilihan jawaban cepat (`quick_options`) di dalam JSON output agar pengguna bisa langsung klik opsi jawabannya.
   - Setelah pengguna menjawab di chat berikutnya, barulah ajukan 1 pertanyaan berikutnya jika masih ada.
   - JIKA SEMUA SUDAH JELAS: Jangan ajukan pertanyaan lagi, cukup simpulkan modul dan kalkulasi siap ditinjau.
3. **JANGAN SEBUT MERK SPESIFIK**:
   - Jangan sebut merk seperti Olsera, Fonnte, Midtrans kecuali jika pengguna yang menyebutkannya duluan. Gunakan istilah generik seperti "integrasi API pihak ketiga (POS/ERP eksternal)", "notifikasi WhatsApp otomatis", "payment gateway online".
4. **BREAKDOWN FITUR & EKSTRAKSI SUB-ITEM (ENTITAS / RINCIAN)**:
   - JIKA klien menyebutkan kebutuhan sistem/fitur yang luas (misal: 'Sistem Inventaris Toko', 'Sistem Penggajian & HR', 'Sistem Manajemen Pergudangan'):
     PECAH / BREAKDOWN menjadi kombinasi modul Rate Card resmi:
     - `MOD-01` (Core CRUD): Masukkan nama-nama entitas master data ke `sub_items` (contoh: `["Barang", "Supplier", "Kategori", "Pelanggan"]`, sehingga Qty = 4).
     - `MOD-02` (Relasional): Masukkan nama transaksi relasional ke `sub_items` (contoh: `["Purchase Order (PO)", "Surat Jalan / Pengiriman"]`, sehingga Qty = 2).
     - `EXP-01` (Export CSV) / `EXP-02` (Export Excel) / `EXP-03` (Export PDF): Masukkan nama dokumen laporan ke `sub_items` (contoh: `["Laporan Omset", "Rekapitulasi Stok"]`).
     - `BND-01` (Bundling Impor+Ekspor): Gunakan jika suatu entitas membutuhkan sepasang impor & ekspor data terintegrasi.
     - `AUTH-01` (Static 3 Role): Default untuk sistem login standar (Owner, Manager, Staff).
     - `AUTH-02` (Dynamic RBAC): Khusus jika klien meminta UI matriks pengaturan izin peran dinamis.
     - `HDW-POS-01` (Printer Thermal) & `HDW-BIO-01` (Mesin Presensi Fingerprint/RFID): Khusus jika ada hardware fisik kasir/absensi.
     - `HDW-CAM-01` (Barcode / QR Scanner Kamera): GRATIS (Rp 0).
     - `DES-02` (Custom Tailored UI Design): Jika klien meminta desain visual khusus/eksklusif brand.
     - `NVT-02` (Melanjutkan Proyek Existing): Jika klien ingin melanjutkan atau menambah fitur pada kodingan/sistem lama (+20%).
   - Kuantitas (`qty`) adalah jumlah elemen dalam `sub_items` (minimal 1).
   - HANYA kebutuhan yang benar-benar kustom/eksternal yang tidak dapat dibangun dari katalog modul Rate Card yang dicatat ke `unlisted_features`.
5. **MENCATAT FITUR KHUSUS / UNLISTED FEATURES**:
   - Jika ada fitur yang benar-benar kustom dan tidak tercakup di Rate Card (misal: IoT Sensor timbangan truk, AI Computer Vision khusus), catat ke array `unlisted_features`.
   - Di teks pesan, sebutkan: "📌 *Catatan Fitur Khusus: [Nama Fitur] (Perlu estimasi custom R&D / add-on terpisah)*".
6. **JANGAN HITUNG RUPIAH MANUAL DI CHAT**:
   - Biarkan backend engine kami yang menghitung angka rupiah deterministik di panel kanan.
7. **PAGAR KONTEKS — TOLAK TOPIK DI LUAR PERENCANAAN PROYEK**:
   - Chat ini HANYA untuk perencanaan dan estimasi proyek software/website/aplikasi.
   - JIKA pengguna mengirim pesan yang TIDAK RELEVAN dengan perencanaan/pengembangan proyek, TOLAK DENGAN SOPAN.

DATASET ACUAN RESMI:
--- DAFTAR MODUL TERSEDIA ---
{$modulesJson}

--- 27 ATURAN TRAINING INTENT & SCOPE BOUNDARY ---
{$corpusJson}

--- PLATFORM & MULTIPLIER ---
{$platformsJson}

--- NOVELTY (STATUS PROYEK) ---
{$noveltyJson}

--- SEGMEN KLIEN ---
{$segmentsJson}

ATURAN WAJIB OUTPUT JSON:
Di akhir setiap respon, sertakan blok JSON berikut:
```json
{
  "client_name": "Nama klien atau null",
  "client_segment": "umkm|organization|sme|enterprise",
  "platform": "web|android|offline_first",
  "novelty": "from_scratch|existing_project",
  "risk_buffer_percent": 0,
  "rush_fee_percent": 0,
  "detected_modules": [
    { 
      "code": "MOD-01", 
      "qty": 3,
      "sub_items": ["Produk", "Kategori", "Supplier"] 
    },
    { 
      "code": "MOD-02", 
      "qty": 1,
      "sub_items": ["Pesanan & Detail Item"] 
    },
    { 
      "code": "AUTH-01", 
      "qty": 1,
      "sub_items": ["Owner, Manager, Staff"] 
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
            'novelty' => $currentSessionState['novelty'] ?? 'from_scratch',
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
                                'code' => $this->calculator->resolveModuleCode($val['code']),
                                'qty' => $qty,
                                'sub_items' => $subItems
                            ];
                        } elseif (is_string($key)) {
                            $norm[] = [
                                'code' => $this->calculator->resolveModuleCode($key),
                                'qty' => max(1, (int)$val),
                                'sub_items' => []
                            ];
                        } elseif (is_string($val)) {
                            $norm[] = [
                                'code' => $this->calculator->resolveModuleCode($val),
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

        $detectedNovelty = $currentSessionState['novelty'] ?? 'from_scratch';
        if (str_contains($lastUserMessage, 'existing') || str_contains($lastUserMessage, 'kodingan lama') || str_contains($lastUserMessage, 'lanjutin') || str_contains($lastUserMessage, 'refactor')) {
            $detectedNovelty = 'existing_project';
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

        // Default to MOD-01 and AUTH-01 if empty
        if (empty($detectedModules)) {
            $detectedModules = ['MOD-01', 'AUTH-01'];
        }

        $reply = "Halo! Saya telah menganalisis kebutuhan proyek Anda.\n" .
                 (!empty($notice) ? "\n> *ℹ️ Catatan: {$notice}*\n\n" : "\n") .
                 "Berdasarkan brief yang Anda sampaikan, modul yang teridentifikasi:\n";

        foreach ($rules['modules'] ?? [] as $m) {
            if (in_array($m['code'], $detectedModules)) {
                $reply .= "- **{$m['name']}** (`{$m['code']}`): {$m['spec']}\n";
            }
        }

        $reply .= "\nApakah ada kebutuhan tambahan seperti cetak printer thermal struk, integrasi WhatsApp notifikasi, atau export Excel/PDF?";

        $extractedParams = [
            'client_name' => $currentSessionState['client_name'] ?? null,
            'client_segment' => $currentSessionState['client_segment'] ?? 'umkm',
            'platform' => $detectedPlatform,
            'novelty' => $detectedNovelty,
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
