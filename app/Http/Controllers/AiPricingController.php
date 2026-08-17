<?php

namespace App\Http\Controllers;

use App\Models\AiPricingSession;
use App\Models\AiPricingMessage;
use App\Services\GeminiPricingService;
use App\Services\PricingCalculatorEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AiPricingController extends Controller
{
    protected PricingCalculatorEngine $calculator;
    protected GeminiPricingService $gemini;

    public function __construct(PricingCalculatorEngine $calculator, GeminiPricingService $gemini)
    {
        $this->calculator = $calculator;
        $this->gemini = $gemini;
    }

    /**
     * Display AI Pricing workspace.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        // Get latest session or create a new one
        $activeSession = AiPricingSession::where('user_id', $userId)
            ->latest()
            ->first();

        if (!$activeSession) {
            $activeSession = $this->createNewSessionRecord($userId);
        }

        return $this->renderWorkspace($activeSession);
    }

    /**
     * Show a specific session.
     */
    public function show($id)
    {
        $userId = Auth::id();
        $session = AiPricingSession::where('id', $id)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            })
            ->firstOrFail();

        return $this->renderWorkspace($session);
    }

    /**
     * Create a new session and redirect.
     */
    public function newSession(Request $request)
    {
        $session = $this->createNewSessionRecord(Auth::id());

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'session_id' => $session->id, 'redirect_url' => route('ai-pricing.show', $session->id)]);
        }

        return redirect()->route('ai-pricing.show', $session->id);
    }

    /**
     * Helper to render the workspace view.
     */
    protected function renderWorkspace(AiPricingSession $session)
    {
        $userId = Auth::id();
        $recentSessions = AiPricingSession::where('user_id', $userId)
            ->latest()
            ->limit(10)
            ->get();

        $session->load('messages');

        // Ensure session has welcome message if empty
        if ($session->messages->isEmpty()) {
            $welcomeMsg = AiPricingMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => "Tuliskan ringkasan kebutuhan atau *copy-paste* brief proyek calon klien Anda di bawah ini.\n\nSistem akan langsung memetakan modul yang dibutuhkan, mengklarifikasi detail teknis satu per satu, dan menghitung estimasi anggarannya di panel kanan.",
                'extracted_params' => null,
            ]);
            $session->load('messages');
        }

        $rules = $this->calculator->getRules();
        $selectedModules = $session->selected_modules ?: ['CR-001', 'CR-006'];
        $unlistedFeatures = $session->unlisted_features ?: [];
        
        $calculation = $this->calculator->calculate(
            $selectedModules,
            $session->platform ?: 'web',
            (int)$session->risk_buffer_percent,
            (int)$session->rush_fee_percent,
            $session->client_segment ?: 'umkm',
            $unlistedFeatures
        );

        $isApiConfigured = $this->gemini->isConfigured();

        return view('ai-pricing.index', [
            'session' => $session,
            'messages' => $session->messages,
            'recentSessions' => $recentSessions,
            'rules' => $rules,
            'calculation' => $calculation,
            'isApiConfigured' => $isApiConfigured,
        ]);
    }

    /**
     * Handle chat message from user.
     */
    public function chat(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $session = AiPricingSession::findOrFail($id);
        $userMessageText = trim($request->input('message'));

        // Save user message
        AiPricingMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => $userMessageText,
        ]);

        // Auto-update title if default
        if ($session->title === 'Estimasi Proyek Baru' || str_starts_with($session->title, 'Estimasi #')) {
            $shortTitle = mb_substr($userMessageText, 0, 40);
            $session->title = ucfirst($shortTitle) . (mb_strlen($userMessageText) > 40 ? '...' : '');
            $session->save();
        }

        // Prepare conversation history
        $messages = $session->messages()->orderBy('created_at', 'asc')->get();
        $chatHistory = [];
        foreach ($messages as $msg) {
            $chatHistory[] = [
                'role' => $msg->role,
                'content' => $msg->content,
            ];
        }

        $currentState = [
            'client_name' => $session->client_name,
            'client_segment' => $session->client_segment,
            'platform' => $session->platform,
            'risk_buffer_percent' => $session->risk_buffer_percent,
            'rush_fee_percent' => $session->rush_fee_percent,
            'selected_modules' => $session->selected_modules ?: [],
            'unlisted_features' => $session->unlisted_features ?: [],
        ];

        // Call Gemini
        $aiResult = $this->gemini->generatePricingAdvice($chatHistory, $currentState);

        // Save AI message
        $aiMessage = AiPricingMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => $aiResult['message'],
            'extracted_params' => $aiResult['extracted_params'] ?? null,
        ]);

        // Merge detected modules with quantity & sub_items support
        $params = $aiResult['extracted_params'] ?? [];
        $currentModules = is_array($session->selected_modules) ? $session->selected_modules : [];
        $moduleMap = [];
        foreach ($currentModules as $k => $v) {
            if (is_array($v) && isset($v['code'])) {
                $subItems = isset($v['sub_items']) && is_array($v['sub_items']) ? array_values(array_filter($v['sub_items'])) : [];
                $qty = !empty($subItems) ? count($subItems) : max(1, (int)($v['qty'] ?? 1));
                $customPrice = isset($v['custom_price']) && $v['custom_price'] !== null ? (int)$v['custom_price'] : null;
                $moduleMap[$v['code']] = [
                    'qty' => $qty,
                    'sub_items' => $subItems,
                    'custom_price' => $customPrice,
                ];
            } elseif (is_string($k)) {
                $moduleMap[$k] = [
                    'qty' => max(1, (int)$v),
                    'sub_items' => [],
                    'custom_price' => null,
                ];
            } elseif (is_string($v)) {
                $moduleMap[$v] = [
                    'qty' => 1,
                    'sub_items' => [],
                    'custom_price' => null,
                ];
            }
        }

        $newModules = $params['detected_modules'] ?? [];
        foreach ($newModules as $k => $v) {
            if (is_array($v) && isset($v['code'])) {
                $code = $v['code'];
                $newSub = isset($v['sub_items']) && is_array($v['sub_items']) ? array_values(array_filter($v['sub_items'])) : [];
                $existingSub = $moduleMap[$code]['sub_items'] ?? [];
                $mergedSub = array_values(array_unique(array_merge($existingSub, $newSub)));
                $qty = !empty($mergedSub) ? count($mergedSub) : max($moduleMap[$code]['qty'] ?? 1, (int)($v['qty'] ?? 1));
                $moduleMap[$code] = [
                    'qty' => $qty,
                    'sub_items' => $mergedSub,
                    'custom_price' => $moduleMap[$code]['custom_price'] ?? null,
                ];
            } elseif (is_string($k)) {
                $moduleMap[$k] = [
                    'qty' => max($moduleMap[$k]['qty'] ?? 1, (int)$v),
                    'sub_items' => $moduleMap[$k]['sub_items'] ?? [],
                    'custom_price' => $moduleMap[$k]['custom_price'] ?? null,
                ];
            } elseif (is_string($v)) {
                if (!isset($moduleMap[$v])) {
                    $moduleMap[$v] = [
                        'qty' => 1,
                        'sub_items' => [],
                        'custom_price' => null,
                    ];
                }
            }
        }

        $mergedModules = [];
        foreach ($moduleMap as $c => $data) {
            $mergedModules[] = [
                'code' => $c,
                'qty' => $data['qty'],
                'sub_items' => $data['sub_items'],
                'custom_price' => $data['custom_price'] ?? null,
            ];
        }

        // Merge unlisted features
        $currentUnlisted = is_array($session->unlisted_features) ? $session->unlisted_features : [];
        $newUnlisted = $params['unlisted_features'] ?? [];
        foreach ($newUnlisted as $nu) {
            $name = is_array($nu) ? ($nu['name'] ?? '') : (string)$nu;
            if (!empty($name)) {
                $exists = false;
                foreach ($currentUnlisted as $cu) {
                    $cuName = is_array($cu) ? ($cu['name'] ?? '') : (string)$cu;
                    if (strcasecmp($cuName, $name) === 0) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $currentUnlisted[] = is_array($nu) ? $nu : ['name' => $name, 'description' => ''];
                }
            }
        }
        $session->unlisted_features = $currentUnlisted;

        if (!empty($params['client_name'])) {
            $session->client_name = $params['client_name'];
        }
        if (!empty($params['client_segment'])) {
            $session->client_segment = $params['client_segment'];
        }
        if (!empty($params['platform'])) {
            $session->platform = $params['platform'];
        }
        if (isset($params['risk_buffer_percent'])) {
            $session->risk_buffer_percent = (int)$params['risk_buffer_percent'];
        }
        if (isset($params['rush_fee_percent'])) {
            $session->rush_fee_percent = (int)$params['rush_fee_percent'];
        }

        $session->selected_modules = $mergedModules;

        // Recalculate
        $calculation = $this->calculator->calculate(
            $mergedModules,
            $session->platform ?: 'web',
            (int)$session->risk_buffer_percent,
            (int)$session->rush_fee_percent,
            $session->client_segment ?: 'umkm',
            $currentUnlisted
        );

        $session->calculation_result = $calculation;
        $session->save();

        return response()->json([
            'status' => 'success',
            'reply' => $aiResult['message'],
            'session' => $session,
            'calculation' => $calculation,
            'extracted_params' => $params,
        ]);
    }

    /**
     * Direct update of modules/settings from right canvas.
     */
    public function updateModules(Request $request, $id)
    {
        $session = AiPricingSession::findOrFail($id);

        $selectedModules = $request->input('selected_modules', $session->selected_modules ?: []);
        $unlistedFeatures = $request->input('unlisted_features', $session->unlisted_features ?: []);
        $platform = $request->input('platform', $session->platform ?: 'web');
        $riskBuffer = (int)$request->input('risk_buffer_percent', $session->risk_buffer_percent);
        $rushFee = (int)$request->input('rush_fee_percent', $session->rush_fee_percent);
        $segment = $request->input('client_segment', $session->client_segment ?: 'umkm');
        $clientName = $request->input('client_name', $session->client_name);

        $session->selected_modules = is_array($selectedModules) ? array_values($selectedModules) : [];
        $session->unlisted_features = is_array($unlistedFeatures) ? array_values($unlistedFeatures) : [];
        $session->platform = $platform;
        $session->risk_buffer_percent = $riskBuffer;
        $session->rush_fee_percent = $rushFee;
        $session->client_segment = $segment;
        $session->client_name = $clientName;

        $calculation = $this->calculator->calculate(
            $session->selected_modules,
            $platform,
            $riskBuffer,
            $rushFee,
            $segment,
            $session->unlisted_features
        );

        $session->calculation_result = $calculation;
        $session->save();

        return response()->json([
            'status' => 'success',
            'session' => $session,
            'calculation' => $calculation,
        ]);
    }

    /**
     * Delete session.
     */
    public function deleteSession($id)
    {
        $session = AiPricingSession::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $session->delete();

        return redirect()->route('ai-pricing.index')->with('success', 'Sesi estimasi berhasil dihapus.');
    }

    /**
     * Test Gemini API connection for localhost/XAMPP debugging.
     */
    public function testConnection()
    {
        if (!$this->gemini->isConfigured()) {
            return response()->json([
                'status' => 'warning',
                'message' => 'GEMINI_API_KEY belum disetel di file .env. Sistem saat ini berjalan dengan fallback deteksi kata kunci lokal.',
                'configured' => false
            ]);
        }

        try {
            $testRes = $this->gemini->generatePricingAdvice([
                ['role' => 'user', 'content' => 'Ping test. Tolong jawab "API Gemini aktif dan siap digunakan."']
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Koneksi ke Google Gemini API berhasil!',
                'configured' => true,
                'response' => $testRes['message']
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal terhubung ke Gemini API: ' . $e->getMessage(),
                'configured' => true
            ], 500);
        }
    }

    /**
     * Create a new session record with defaults.
     */
    protected function createNewSessionRecord(?int $userId): AiPricingSession
    {
        $sessionCount = AiPricingSession::where('user_id', $userId)->count();
        $title = 'Estimasi Proyek #' . ($sessionCount + 1);

        $defaultModules = ['CR-001', 'CR-006']; // Basic CRUD & Static Auth
        $calculation = $this->calculator->calculate($defaultModules, 'web', 0, 0, 'umkm');

        return AiPricingSession::create([
            'user_id' => $userId,
            'title' => $title,
            'client_name' => null,
            'client_segment' => 'umkm',
            'platform' => 'web',
            'risk_buffer_percent' => 0,
            'rush_fee_percent' => 0,
            'selected_modules' => $defaultModules,
            'calculation_result' => $calculation,
            'status' => 'draft',
        ]);
    }
}
