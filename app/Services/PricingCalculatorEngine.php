<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class PricingCalculatorEngine
{
    protected ?array $rules = null;

    public function __construct()
    {
        $this->loadRules();
    }

    public function loadRules(): array
    {
        if ($this->rules !== null) {
            return $this->rules;
        }

        $path = storage_path('app/pricing_rules.json');
        if (File::exists($path)) {
            $this->rules = json_decode(File::get($path), true) ?: [];
        } else {
            $this->rules = [
                'modules' => [],
                'platforms' => [],
                'segments' => [],
                'dp_rules' => ['threshold' => 5000000, 'below_threshold_dp_pct' => 30, 'above_threshold_dp_pct' => 20],
                'risk_buffer_options' => [],
                'rush_fee_options' => []
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
        $path = storage_path('app/pricing_rules.json');
        return File::put($path, json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
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
     * Calculate pricing estimation deterministically.
     *
     * @param array $selectedModules Array of module codes ['CR-001', 'CR-004'] or ['CR-001' => 1, 'CR-004' => 2] or objects [['code' => 'CR-001', 'qty' => 1]]
     * @param string $platform 'web', 'android', 'hybrid', 'offline_first'
     * @param int $riskBufferPct 0, 10, 20
     * @param int $rushFeePct 0, 25, 50
     * @param string $segment 'umkm', 'sme', 'enterprise'
     * @return array
     */
    public function calculate(
        array $selectedModules = [],
        string $platform = 'web',
        int $riskBufferPct = 0,
        int $rushFeePct = 0,
        string $segment = 'umkm',
        array $unlistedFeatures = []
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
                $code = $val['code'];
                $subItems = isset($val['sub_items']) && is_array($val['sub_items']) ? array_values(array_filter($val['sub_items'])) : [];
                $qty = !empty($subItems) ? count($subItems) : (isset($val['qty']) ? max(1, (int)$val['qty']) : 1);
                $customPrice = isset($val['custom_price']) && $val['custom_price'] !== null ? (int)$val['custom_price'] : null;
                $normalizedModules[$code] = [
                    'qty' => $qty,
                    'sub_items' => $subItems,
                    'custom_price' => $customPrice,
                ];
            } elseif (is_string($key)) {
                $code = $key;
                $qty = max(1, (int)$val);
                $normalizedModules[$code] = [
                    'qty' => $qty,
                    'sub_items' => [],
                    'custom_price' => null,
                ];
            } elseif (is_string($val)) {
                $code = $val;
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

        // Platform Multiplier
        $platformInfo = $rules['platforms'][$platform] ?? ($rules['platforms']['web'] ?? ['name' => 'Web App Saja', 'multiplier' => 1.0]);
        $platformMultiplier = (float)($platformInfo['multiplier'] ?? 1.0);

        // Subtotal after platform multiplier
        $subtotalAfterPlatform = round($subtotalBaseRec * $platformMultiplier);
        $subtotalMinAfterPlatform = round($subtotalBaseMin * $platformMultiplier);
        $subtotalMaxAfterPlatform = round($subtotalBaseMax * $platformMultiplier);

        // Risk Buffer & Rush Fee
        $riskBufferPct = in_array($riskBufferPct, [0, 10, 20]) ? $riskBufferPct : 0;
        $rushFeePct = in_array($rushFeePct, [0, 25, 50]) ? $rushFeePct : 0;

        $riskBufferAmount = round($subtotalAfterPlatform * ($riskBufferPct / 100));
        $rushFeeAmount = round($subtotalAfterPlatform * ($rushFeePct / 100));

        $totalEstimated = $subtotalAfterPlatform + $riskBufferAmount + $rushFeeAmount;
        $totalMinEstimated = $subtotalMinAfterPlatform + round($subtotalMinAfterPlatform * ($riskBufferPct / 100)) + round($subtotalMinAfterPlatform * ($rushFeePct / 100));
        $totalMaxEstimated = $subtotalMaxAfterPlatform + round($subtotalMaxAfterPlatform * ($riskBufferPct / 100)) + round($subtotalMaxAfterPlatform * ($rushFeePct / 100));

        // Termin Rules from Excel: Threshold Rp 5.000.000
        $threshold = $rules['dp_rules']['threshold'] ?? 5000000;
        $dpPct = ($totalEstimated < $threshold) ? 30 : 20;
        $pelunasanPct = 100 - $dpPct;

        $dpAmount = round($totalEstimated * ($dpPct / 100));
        $pelunasanAmount = $totalEstimated - $dpAmount;

        // Estimated Duration based on module count
        $moduleCount = count($items);
        $estDaysMin = max(5, $moduleCount * 2);
        $estDaysMax = max(10, $moduleCount * 3 + 4);
        if ($rushFeePct > 0) {
            $estDaysMin = max(3, round($estDaysMin * 0.7));
            $estDaysMax = max(7, round($estDaysMax * 0.7));
        }

        $segmentInfo = $rules['segments'][$segment] ?? ($rules['segments']['umkm'] ?? [
            'name' => 'UMKM / Solo Entrepreneur',
            'warranty' => '30 Hari Garansi Bug Bebas Biaya',
            'sla' => '24-48 Jam Respon'
        ]);

        return [
            'items' => $items,
            'item_count' => count($items),
            'subtotal_base' => $subtotalBaseRec,
            'subtotal_base_min' => $subtotalBaseMin,
            'subtotal_base_max' => $subtotalBaseMax,
            'platform' => [
                'code' => $platform,
                'name' => $platformInfo['name'] ?? 'Web App Saja',
                'multiplier' => $platformMultiplier,
            ],
            'subtotal_after_platform' => $subtotalAfterPlatform,
            'risk_buffer' => [
                'percent' => $riskBufferPct,
                'amount' => $riskBufferAmount,
            ],
            'rush_fee' => [
                'percent' => $rushFeePct,
                'amount' => $rushFeeAmount,
            ],
            'total_estimated' => $totalEstimated,
            'total_min' => $totalMinEstimated,
            'total_max' => $totalMaxEstimated,
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
                'name' => $segmentInfo['name'] ?? 'UMKM',
            ],
            'unlisted_features' => $unlistedFeatures,
        ];
    }
}
