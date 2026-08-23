<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class PricingCalculatorEngine
{
    protected ?array $rules = null;

    /**
     * Backward-compatibility mapping from legacy CR-xxx rule IDs to official Rate Card Module codes.
     */
    protected array $codeAliases = [
        'CR-001' => 'MOD-01',
        'CR-002' => 'MOD-02',
        'CR-003' => 'MOD-03',
        'CR-004' => 'MOD-04',
        'CR-005' => 'MOD-05',
        'CR-006' => 'AUTH-01',
        'CR-007' => 'AUTH-02',
        'CR-008' => 'UI-01',
        'CR-009' => 'EXP-01',
        'CR-010' => 'EXP-02',
        'CR-011' => 'EXP-03',
        'CR-012' => 'IMP-01',
        'CR-013' => 'BND-01',
        'CR-014' => 'SEC-01',
        'CR-015' => 'BCK-01',
        'CR-016' => 'INT-PAY-01',
        'CR-017' => 'INT-WA-01',
        'CR-018' => 'INT-API-01',
        'CR-019' => 'INT-API-02',
        'CR-020' => 'INT-EML-01',
        'CR-021' => 'HDW-POS-01',
        'CR-022' => 'HDW-BIO-01',
        'CR-023' => 'HDW-CAM-01',
        'CR-026' => 'DES-02',
    ];

    public function __construct()
    {
        $this->loadRules();
    }

    public function loadRules(): array
    {
        if ($this->rules !== null) {
            return $this->rules;
        }

        try {
            $path = (function_exists('app') && app()->bound('path.storage')) 
                ? storage_path('app/pricing_rules.json') 
                : (dirname(__DIR__, 2) . '/storage/app/pricing_rules.json');
        } catch (\Throwable $e) {
            $path = dirname(__DIR__, 2) . '/storage/app/pricing_rules.json';
        }
        if (file_exists($path)) {
            $this->rules = json_decode(file_get_contents($path), true) ?: [];
        } else {
            $this->rules = [
                'modules' => [],
                'platforms' => [],
                'novelty_options' => [],
                'risk_buffer_options' => [],
                'rush_fee_options' => [],
                'segments' => [],
                'dp_rules' => ['threshold' => 5000000, 'below_threshold_dp_pct' => 30, 'above_threshold_dp_pct' => 20],
                'operational' => [],
                'ai_training_corpus' => []
            ];
        }

        return $this->rules;
    }

    public function getRules(): array
    {
        return $this->loadRules();
    }

    public function saveRules(array $rules): bool
    {
        $this->rules = $rules;
        try {
            $path = (function_exists('app') && app()->bound('path.storage')) 
                ? storage_path('app/pricing_rules.json') 
                : (dirname(__DIR__, 2) . '/storage/app/pricing_rules.json');
        } catch (\Throwable $e) {
            $path = dirname(__DIR__, 2) . '/storage/app/pricing_rules.json';
        }
        return file_put_contents($path, json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }

    public function getModules(): array
    {
        return $this->loadRules()['modules'] ?? [];
    }

    public function getPlatforms(): array
    {
        return $this->loadRules()['platforms'] ?? [];
    }

    public function getSegments(): array
    {
        return $this->loadRules()['segments'] ?? [];
    }

    /**
     * Resolve module code (supports legacy aliases like CR-001 -> MOD-01).
     */
    public function resolveModuleCode(string $code): string
    {
        $code = trim($code);
        return $this->codeAliases[$code] ?? $code;
    }

    /**
     * Calculate pricing estimation deterministically matching Sheet 7 (Simulasi_Kalkulator).
     *
     * @param array $selectedModules Array of module codes ['MOD-01', 'AUTH-01'] or with qty/custom_price
     * @param string $platform 'web', 'android', 'offline_first'
     * @param float|int $riskBufferPct e.g. -7.5 (matang), 0 (normal), 7.5/10/20 (mentah)
     * @param int $rushFeePct 0, 25, 50
     * @param string $segment 'umkm', 'organization', 'sme', 'enterprise'
     * @param array $unlistedFeatures
     * @param string|float $novelty 'from_scratch' (1.0x) | 'existing_project' (1.2x) | 'NVT-01' | 'NVT-02'
     * @return array
     */
    public function calculate(
        array $selectedModules = [],
        string $platform = 'web',
        float|int $riskBufferPct = 0,
        int $rushFeePct = 0,
        string $segment = 'umkm',
        array $unlistedFeatures = [],
        string|float $novelty = 'from_scratch'
    ): array {
        $rules = $this->loadRules();
        $allModulesMap = [];
        foreach ($rules['modules'] ?? [] as $m) {
            $allModulesMap[$m['code']] = $m;
        }

        // Normalize selected modules into [code => ['qty' => N, 'sub_items' => [...], 'custom_price' => null|int]]
        $normalizedModules = [];
        foreach ($selectedModules as $key => $val) {
            if (is_array($val) && isset($val['code'])) {
                $code = $this->resolveModuleCode($val['code']);
                $subItems = isset($val['sub_items']) && is_array($val['sub_items']) ? array_values(array_filter($val['sub_items'])) : [];
                $qty = !empty($subItems) ? count($subItems) : (isset($val['qty']) ? max(1, (int)$val['qty']) : 1);
                $customPrice = isset($val['custom_price']) && $val['custom_price'] !== null ? (int)$val['custom_price'] : null;
                $normalizedModules[$code] = [
                    'qty' => $qty,
                    'sub_items' => $subItems,
                    'custom_price' => $customPrice,
                ];
            } elseif (is_string($key)) {
                $code = $this->resolveModuleCode($key);
                $qty = max(1, (int)$val);
                $normalizedModules[$code] = [
                    'qty' => $qty,
                    'sub_items' => [],
                    'custom_price' => null,
                ];
            } elseif (is_string($val)) {
                $code = $this->resolveModuleCode($val);
                $normalizedModules[$code] = [
                    'qty' => 1,
                    'sub_items' => [],
                    'custom_price' => null,
                ];
            }
        }

        $items = [];
        $subtotalBaseRec = 0;
        $subtotalBaseMin = 0;
        $subtotalBaseMax = 0;

        foreach ($normalizedModules as $code => $info) {
            $qty = $info['qty'];
            $subItems = $info['sub_items'];
            $customPrice = $info['custom_price'];
            if (isset($allModulesMap[$code])) {
                $m = $allModulesMap[$code];
                $unitPrice = ($customPrice !== null) ? $customPrice : $m['price_recommended'];
                $unitPriceMin = ($customPrice !== null) ? $customPrice : $m['price_min'];
                $unitPriceMax = ($customPrice !== null) ? $customPrice : $m['price_max'];
                $priceMin = $unitPriceMin * $qty;
                $priceMax = $unitPriceMax * $qty;
                $priceRec = $unitPrice * $qty;

                $subtotalBaseMin += $priceMin;
                $subtotalBaseMax += $priceMax;
                $subtotalBaseRec += $priceRec;

                $items[] = [
                    'code' => $code,
                    'name' => $m['name'],
                    'category' => $m['category'] ?? 'Core System',
                    'spec' => $m['spec'] ?? '',
                    'unit' => $m['unit'] ?? 'Per Entitas',
                    'complexity' => $m['complexity'] ?? 'Sedang',
                    'qty' => $qty,
                    'sub_items' => $subItems,
                    'unit_price' => $unitPrice,
                    'custom_price' => $customPrice,
                    'default_price' => $m['price_recommended'],
                    'subtotal' => $priceRec,
                    'price_min' => $priceMin,
                    'price_max' => $priceMax,
                    'is_free' => ($unitPrice === 0 && $m['price_recommended'] === 0),
                    'is_hardware' => !empty($m['is_hardware']),
                ];
            }
        }

        // Platform Multiplier (Web: 1.0x, Android Flutter: 1.4x, Offline First: 2.2x)
        $platformInfo = $rules['platforms'][$platform] ?? ($rules['platforms']['web'] ?? ['name' => 'Web App Standar', 'multiplier' => 1.0]);
        $platformMultiplier = (float)($platformInfo['multiplier'] ?? 1.0);

        // Subtotal after platform multiplier
        $subtotalAfterPlatform = round($subtotalBaseRec * $platformMultiplier);
        $subtotalMinAfterPlatform = round($subtotalBaseMin * $platformMultiplier);
        $subtotalMaxAfterPlatform = round($subtotalBaseMax * $platformMultiplier);

        // Novelty Factor: Existing Project (+20%) vs From Scratch (1.0x / 0%)
        $isExistingProject = ($novelty === 'existing_project' || $novelty === 'NVT-02' || $novelty === 1.2 || $novelty === '1.2');
        $noveltyMultiplier = $isExistingProject ? 1.2 : 1.0;
        $noveltyAmount = $isExistingProject ? round($subtotalAfterPlatform * 0.20) : 0;

        // Brief Risk Buffer / Discount (e.g. -7.5%, 0%, +7.5%, +10%, +20%)
        $riskBufferPct = (float)$riskBufferPct;
        $riskBufferAmount = round($subtotalAfterPlatform * ($riskBufferPct / 100));

        // Rush Fee (0%, 25%, 50%)
        $rushFeePct = in_array($rushFeePct, [0, 25, 50]) ? $rushFeePct : 0;
        $rushFeeAmount = round($subtotalAfterPlatform * ($rushFeePct / 100));

        // Total Estimasi Nilai Proyek (F28 = F24 + F25 + F26 + F27)
        $totalEstimated = $subtotalAfterPlatform + $noveltyAmount + $riskBufferAmount + $rushFeeAmount;
        $totalMinEstimated = $subtotalMinAfterPlatform + ($isExistingProject ? round($subtotalMinAfterPlatform * 0.2) : 0) + round($subtotalMinAfterPlatform * ($riskBufferPct / 100)) + round($subtotalMinAfterPlatform * ($rushFeePct / 100));
        $totalMaxEstimated = $subtotalMaxAfterPlatform + ($isExistingProject ? round($subtotalMaxAfterPlatform * 0.2) : 0) + round($subtotalMaxAfterPlatform * ($riskBufferPct / 100)) + round($subtotalMaxAfterPlatform * ($rushFeePct / 100));

        // Segment floor price check
        $segmentInfo = $rules['segments'][$segment] ?? ($rules['segments']['umkm'] ?? [
            'name' => 'UMKM / Solo Entrepreneur',
            'floor_price_system' => 1200000,
            'warranty' => '30 Hari Garansi Bug Bebas Biaya',
            'sla' => '24-48 Jam Respon'
        ]);

        $floorPrice = (int)($segmentInfo['floor_price_system'] ?? 1200000);
        $isFloorAdjusted = false;
        if ($totalEstimated < $floorPrice && count($items) > 0) {
            $isFloorAdjusted = true;
        }

        // Termin Rules from Excel Sheet 5 & 7: Threshold Rp 5.000.000
        $threshold = $rules['dp_rules']['threshold'] ?? 5000000;
        $dpPct = ($totalEstimated < $threshold) ? ($rules['dp_rules']['below_threshold_dp_pct'] ?? 30) : ($rules['dp_rules']['above_threshold_dp_pct'] ?? 20);
        $pelunasanPct = 100 - $dpPct;

        $dpAmount = round($totalEstimated * ($dpPct / 100));
        $pelunasanAmount = $totalEstimated - $dpAmount;

        // Estimated Duration based on module count & complexity
        $moduleCount = count($items);
        $estDaysMin = max(5, $moduleCount * 2);
        $estDaysMax = max(10, $moduleCount * 3 + 4);
        if ($rushFeePct > 0) {
            $estDaysMin = max(3, (int)round($estDaysMin * 0.7));
            $estDaysMax = max(7, (int)round($estDaysMax * 0.7));
        }

        return [
            'items' => $items,
            'item_count' => count($items),
            'subtotal_base' => $subtotalBaseRec,
            'subtotal_base_min' => $subtotalBaseMin,
            'subtotal_base_max' => $subtotalBaseMax,
            'platform' => [
                'code' => $platform,
                'name' => $platformInfo['name'] ?? 'Web App Standar',
                'multiplier' => $platformMultiplier,
            ],
            'subtotal_after_platform' => $subtotalAfterPlatform,
            'novelty' => [
                'code' => $isExistingProject ? 'NVT-02' : 'NVT-01',
                'value' => $isExistingProject ? 'existing_project' : 'from_scratch',
                'name' => $isExistingProject ? 'Melanjutkan Proyek Existing (+20%)' : 'Bangun dari Nol (1.0x)',
                'multiplier' => $noveltyMultiplier,
                'amount' => $noveltyAmount,
            ],
            'risk_buffer' => [
                'percent' => $riskBufferPct,
                'amount' => $riskBufferAmount,
                'label' => $riskBufferPct < 0 ? "Diskon Brief Matang ({$riskBufferPct}%)" : ($riskBufferPct > 0 ? "Risk Buffer Brief Mentah (+{$riskBufferPct}%)" : "Brief Normal (0%)")
            ],
            'rush_fee' => [
                'percent' => $rushFeePct,
                'amount' => $rushFeeAmount,
            ],
            'total_estimated' => $totalEstimated,
            'total_min' => $totalMinEstimated,
            'total_max' => $totalMaxEstimated,
            'floor_price' => $floorPrice,
            'is_floor_adjusted' => $isFloorAdjusted,
            'payment_terms' => [
                'dp_percent' => $dpPct,
                'dp_amount' => $dpAmount,
                'pelunasan_percent' => $pelunasanPct,
                'pelunasan_amount' => $pelunasanAmount,
                'description' => "Termin 1: Uang Muka (DP {$dpPct}%), Termin 2: Pelunasan ({$pelunasanPct}%) pasca UAT & Go-Live"
            ],
            'timeline' => [
                'estimated_days_range' => "{$estDaysMin} - {$estDaysMax} Hari Kerja",
                'is_rush' => ($rushFeePct > 0),
            ],
            'warranty' => $segmentInfo['warranty'] ?? '30 Hari Garansi Bug Bebas Biaya',
            'sla' => $segmentInfo['sla'] ?? '24-48 Jam Respon',
            'segment' => [
                'code' => $segment,
                'name' => $segmentInfo['name'] ?? 'UMKM / Solo Entrepreneur',
                'floor_price_system' => $floorPrice,
                'strategy' => $segmentInfo['strategy'] ?? '',
                'payment_scheme' => $segmentInfo['payment_scheme'] ?? ''
            ],
            'operational' => $rules['operational'] ?? [],
            'unlisted_features' => $unlistedFeatures,
        ];
    }
}
