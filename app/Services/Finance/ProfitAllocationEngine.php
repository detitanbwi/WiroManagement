<?php

namespace App\Services\Finance;

use App\Models\Shareholder;
use Illuminate\Support\Collection;

class ProfitAllocationEngine
{
    public const DEFAULT_CASH_FLOOR = 15000000.00; // Rp 15.000.000
    public const DEFAULT_RETAINED_EARNINGS_PERCENT = 0.20; // 20%

    /**
     * Calculate profit sharing allocation adhering to static safety cash floor rules.
     *
     * @param float $netProfit
     * @param float $endingCash
     * @param float $retainedEarningsPercent e.g. 0.20 for 20%
     * @param float $cashFloorTarget e.g. 15000000
     * @return array
     */
    public function calculate(
        float $netProfit,
        float $endingCash,
        float $retainedEarningsPercent = self::DEFAULT_RETAINED_EARNINGS_PERCENT,
        float $cashFloorTarget = self::DEFAULT_CASH_FLOOR
    ): array {
        // 1. Plafon Kas Aman: max(0, Ending Cash - Cash Floor)
        $safeCashCeiling = max(0.0, round($endingCash - $cashFloorTarget, 2));

        // 2. Retained Earnings (Laba Ditahan): Net Profit * $retainedEarningsPercent
        // Retained earnings only applies to positive net profit.
        $retainedEarningsAmount = $netProfit > 0 ? round($netProfit * $retainedEarningsPercent, 2) : 0.0;

        // 3. Laba Bersih Tersedia (Available Profit): max(0, Net Profit - Retained Earnings)
        $availableProfit = max(0.0, round($netProfit - $retainedEarningsAmount, 2));

        // 4. Distributable Profit (Laba yang Dapat Dibagikan):
        // min(max(0, Net Profit - Retained Earnings), Plafon Kas Aman)
        $distributableProfit = min($availableProfit, $safeCashCeiling);

        return [
            'cash_floor' => $cashFloorTarget,
            'ending_cash' => round($endingCash, 2),
            'safe_cash_ceiling' => $safeCashCeiling,
            'is_cash_floor_met' => $endingCash >= $cashFloorTarget,
            'net_profit' => round($netProfit, 2),
            'retained_earnings_percent' => round($retainedEarningsPercent * 100, 2),
            'retained_earnings_amount' => $retainedEarningsAmount,
            'available_profit' => $availableProfit,
            'distributable_profit' => round($distributableProfit, 2),
            'is_distribution_capped_by_cash' => $distributableProfit < $availableProfit && $availableProfit > 0,
        ];
    }

    /**
     * Distribute total distributable profit to active shareholders proportionally.
     *
     * @param float $distributableProfit
     * @param Collection|null $shareholders
     * @return array
     */
    public function allocateShareholders(float $distributableProfit, ?Collection $shareholders = null): array
    {
        $shareholders = $shareholders ?? Shareholder::where('is_active', true)->orderBy('id')->get();
        $totalShares = (int)$shareholders->sum('shares_count');
        $dividendPerShare = ($totalShares > 0 && $distributableProfit > 0)
            ? round($distributableProfit / $totalShares, 4)
            : 0.0;

        $payoutList = [];
        $runningTotal = 0.0;
        $count = $shareholders->count();
        $index = 0;

        foreach ($shareholders as $sh) {
            $index++;
            $pct = (float)$sh->ownership_percentage;
            
            // Calculate payout based on ownership percentage
            $payout = ($distributableProfit > 0 && $pct > 0)
                ? round($distributableProfit * ($pct / 100), 2)
                : 0.0;

            // Handle micro-rounding difference on the last active shareholder
            if ($index === $count && $distributableProfit > 0) {
                $difference = round($distributableProfit - ($runningTotal + $payout), 2);
                if (abs($difference) <= 5.0) { // within small rounding tolerance
                    $payout = round($payout + $difference, 2);
                }
            }

            $runningTotal += $payout;

            $payoutList[] = [
                'shareholder' => $sh,
                'shareholder_id' => $sh->id,
                'name' => $sh->name,
                'bank_name' => $sh->bank_name,
                'bank_account_number' => $sh->bank_account_number,
                'bank_account_holder' => $sh->bank_account_holder,
                'shares_count' => (int)$sh->shares_count,
                'capital_amount' => (float)($sh->capital_amount ?? 0),
                'ownership_percentage' => $pct,
                'payout_amount' => $payout,
            ];
        }

        $totalCapital = (float)$shareholders->sum('capital_amount');

        return [
            'total_shares' => $totalShares,
            'total_capital' => $totalCapital,
            'dividend_per_share' => $dividendPerShare,
            'total_allocated_payout' => round($runningTotal, 2),
            'shareholders' => $payoutList,
        ];
    }
}
