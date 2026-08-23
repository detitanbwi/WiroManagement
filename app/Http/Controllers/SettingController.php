<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\PricingCalculatorEngine;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    protected PricingCalculatorEngine $calculator;

    public function __construct(PricingCalculatorEngine $calculator)
    {
        $this->calculator = $calculator;
    }

    public function index(Request $request)
    {
        $settings = Setting::all()->pluck('value', 'key');
        $rules = $this->calculator->getRules();
        $activeTab = $request->query('tab', 'rate-card');

        return view('settings.index', compact('settings', 'rules', 'activeTab'));
    }

    public function update(Request $request)
    {
        $activeTab = $request->input('active_tab', 'rate-card');

        // Handle Document / Terms settings
        if ($request->has('settings')) {
            foreach ($request->settings as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }

        // Handle Rate Card & Module Pricing settings
        if ($request->has('rate_card_modules')) {
            $rules = $this->calculator->getRules();
            $modulesInput = $request->input('rate_card_modules', []);

            // Update module prices
            if (isset($rules['modules']) && is_array($rules['modules'])) {
                foreach ($rules['modules'] as &$mod) {
                    $code = $mod['code'];
                    if (isset($modulesInput[$code])) {
                        $in = $modulesInput[$code];
                        // Strip dots or non-digits for clean integer storage
                        $priceMin = (int)preg_replace('/[^\d]/', '', (string)($in['price_min'] ?? '0'));
                        $priceMax = (int)preg_replace('/[^\d]/', '', (string)($in['price_max'] ?? '0'));
                        $priceRec = (int)preg_replace('/[^\d]/', '', (string)($in['price_recommended'] ?? '0'));

                        $mod['price_min'] = $priceMin;
                        $mod['price_max'] = $priceMax;
                        $mod['price_recommended'] = $priceRec;
                        $mod['is_free'] = ($priceRec === 0 && $priceMin === 0 && $priceMax === 0);
                    }
                }
            }

            // Update platform multipliers
            if ($request->has('platforms') && isset($rules['platforms'])) {
                foreach ($request->input('platforms') as $pKey => $pVal) {
                    if (isset($rules['platforms'][$pKey])) {
                        $rules['platforms'][$pKey]['multiplier'] = (float)$pVal;
                    }
                }
            }

            // Update novelty options
            if ($request->has('novelty_options') && isset($rules['novelty_options'])) {
                $noveltyIn = $request->input('novelty_options', []);
                foreach ($rules['novelty_options'] as &$nov) {
                    $nCode = $nov['code'] ?? '';
                    if (isset($noveltyIn[$nCode])) {
                        $nov['multiplier'] = (float)$noveltyIn[$nCode];
                    }
                }
            }

            // Update segments floor price
            if ($request->has('segments') && isset($rules['segments'])) {
                $segmentsIn = $request->input('segments', []);
                foreach ($rules['segments'] as $sKey => &$sVal) {
                    if (isset($segmentsIn[$sKey]['floor_price_system'])) {
                        $sVal['floor_price_system'] = (int)preg_replace('/[^\d]/', '', (string)$segmentsIn[$sKey]['floor_price_system']);
                    }
                }
            }

            // Update DP rules
            if ($request->has('dp_rules') && isset($rules['dp_rules'])) {
                $dpIn = $request->input('dp_rules');
                $threshold = (int)preg_replace('/[^\d]/', '', (string)($dpIn['threshold'] ?? '5000000'));
                $belowPct = (int)($dpIn['below_threshold_dp_pct'] ?? 30);
                $abovePct = (int)($dpIn['above_threshold_dp_pct'] ?? 20);

                $rules['dp_rules']['threshold'] = $threshold;
                $rules['dp_rules']['below_threshold_dp_pct'] = $belowPct;
                $rules['dp_rules']['above_threshold_dp_pct'] = $abovePct;
            }

            $this->calculator->saveRules($rules);
        }

        return redirect()->route('settings.index', ['tab' => $activeTab])->with('success', 'Pengaturan berhasil diperbarui.');
    }
}

