<?php

namespace App\Services\Finance;

use App\Models\DividendPayout;
use App\Models\MonthlyClosing;
use App\Models\ProfitDistribution;
use App\Models\QuarterlyClosing;
use App\Models\Shareholder;
use App\Models\ShareholderCapitalHistory;
use Illuminate\Support\Facades\DB;

class QuarterlyClosingService
{
    public function __construct(
        protected MonthlyClosingService $monthlyClosingService,
        protected ProfitAllocationEngine $allocationEngine
    ) {}

    /**
     * Consolidate 3 monthly closings into a QuarterlyClosing statement.
     *
     * @param int $year
     * @param int $quarter 1, 2, 3, or 4
     * @return QuarterlyClosing
     */
    public function generateQuarterlyClosing(int $year, int $quarter): QuarterlyClosing
    {
        return DB::transaction(function () use ($year, $quarter) {
            $startMonthNum = ($quarter - 1) * 3 + 1;
            $months = [
                sprintf('%04d-%02d', $year, $startMonthNum),
                sprintf('%04d-%02d', $year, $startMonthNum + 1),
                sprintf('%04d-%02d', $year, $startMonthNum + 2),
            ];

            // Ensure all 3 monthly closing records exist and are up to date
            $monthlyClosings = collect();
            foreach ($months as $monthPeriod) {
                $mClosing = MonthlyClosing::where('period_month', $monthPeriod)->first();
                if (!$mClosing) {
                    $mClosing = $this->monthlyClosingService->generateClosing($monthPeriod);
                }
                $monthlyClosings->push($mClosing);
            }

            // Aggregate Financial Statement Metrics
            $totalRevenue = (float)$monthlyClosings->sum('total_revenue');
            $totalCogs = (float)$monthlyClosings->sum('total_cogs');
            $grossProfit = $totalRevenue - $totalCogs;
            $totalOpex = (float)$monthlyClosings->sum('total_opex');
            $netProfit = $grossProfit - $totalOpex;
            $netProfitMargin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 2) : 0.0;

            // Cash Balances (Month 1 opening, Month 3 ending)
            $openingCash = (float)$monthlyClosings->first()->opening_cash_balance;
            $endingCash = (float)$monthlyClosings->last()->ending_cash_balance;

            $cashFloorTarget = ProfitAllocationEngine::DEFAULT_CASH_FLOOR;
            $cashSurplusAboveFloor = max(0.0, round($endingCash - $cashFloorTarget, 2));

            $periodLabel = sprintf('%04d-Q%d', $year, $quarter);

            // Update or Create Quarterly Closing Record
            return QuarterlyClosing::updateOrCreate(
                [
                    'year' => $year,
                    'quarter' => $quarter,
                ],
                [
                    'period_label' => $periodLabel,
                    'total_revenue' => round($totalRevenue, 2),
                    'total_cogs' => round($totalCogs, 2),
                    'gross_profit' => round($grossProfit, 2),
                    'total_opex' => round($totalOpex, 2),
                    'net_profit' => round($netProfit, 2),
                    'net_profit_margin' => $netProfitMargin,
                    'opening_cash_balance' => round($openingCash, 2),
                    'ending_cash_balance' => round($endingCash, 2),
                    'cash_floor_target' => $cashFloorTarget,
                    'cash_surplus_above_floor' => $cashSurplusAboveFloor,
                ]
            );
        });
    }

    /**
     * Calculate RUPS profit sharing allocation with static safety cash floor protection.
     *
     * @param QuarterlyClosing $closing
     * @param float $retainedEarningsPercent e.g. 0.20
     * @return array
     */
    public function calculateAllocation(
        QuarterlyClosing $closing,
        float $retainedEarningsPercent = ProfitAllocationEngine::DEFAULT_RETAINED_EARNINGS_PERCENT
    ): array {
        $calc = $this->allocationEngine->calculate(
            netProfit: (float)$closing->net_profit,
            endingCash: (float)$closing->ending_cash_balance,
            retainedEarningsPercent: $retainedEarningsPercent,
            cashFloorTarget: (float)$closing->cash_floor_target
        );

        $shareholderAllocations = $this->allocationEngine->allocateShareholders(
            distributableProfit: $calc['distributable_profit']
        );

        return array_merge(['closing' => $closing], $calc, $shareholderAllocations);
    }

    /**
     * Get previous quarterly closing if it exists.
     */
    public function getPreviousQuarterClosing(int $year, int $quarter): ?QuarterlyClosing
    {
        $prevQuarter = $quarter === 1 ? 4 : $quarter - 1;
        $prevYear = $quarter === 1 ? $year - 1 : $year;
        return QuarterlyClosing::where('year', $prevYear)->where('quarter', $prevQuarter)->first();
    }

    /**
     * Finalize RUPS decision, store ProfitDistribution and generate individual founder DividendPayouts.
     * Also handles dividend reinvestment roll-forward into shareholder capital ledger.
     *
     * @param QuarterlyClosing $closing
     * @param array $data
     * @return ProfitDistribution
     */
    public function finalizeDistribution(QuarterlyClosing $closing, array $data): ProfitDistribution
    {
        return DB::transaction(function () use ($closing, $data) {
            $retainedPercent = isset($data['retained_earnings_percentage'])
                ? (float)$data['retained_earnings_percentage']
                : 20.0;

            // Recalculate accurately based on exact percentage
            $allocation = $this->calculateAllocation($closing, $retainedPercent / 100);

            // Document number
            $documentNumber = $data['document_number'] ?? sprintf('BA-RUPS/WD/%04d/Q%d/%03d', $closing->year, $closing->quarter, $closing->id);

            $distribution = ProfitDistribution::updateOrCreate(
                ['quarterly_closing_id' => $closing->id],
                [
                    'document_number' => $documentNumber,
                    'total_net_profit' => $closing->net_profit,
                    'retained_earnings_amount' => $allocation['retained_earnings_amount'],
                    'retained_earnings_percentage' => $allocation['retained_earnings_percent'],
                    'distributable_profit' => $allocation['distributable_profit'],
                    'dividend_per_share' => $allocation['dividend_per_share'],
                    'distribution_date' => $data['distribution_date'] ?? now()->toDateString(),
                    'status' => $data['status'] ?? 'approved',
                    'notes' => $data['notes'] ?? null,
                ]
            );

            // Re-generate individual payout records
            $distribution->payouts()->delete();

            $reinvestPercentages = $data['reinvest_percentages'] ?? [];
            $reinvestAllocations = $data['reinvest_allocations'] ?? [];
            $globalReinvestPct = isset($data['reinvest_percentage']) ? (float)$data['reinvest_percentage'] : null;

            $totalCashPayout = 0.0;
            $totalReinvestedCapital = 0.0;
            $shareholderReinvestMap = [];

            foreach ($allocation['shareholders'] as $shData) {
                $shId = $shData['shareholder_id'];
                $payoutAmount = (float)$shData['payout_amount'];

                // Determine reinvest percentage
                $reinvestPct = 0.0;
                if (isset($reinvestPercentages[$shId])) {
                    $reinvestPct = (float)$reinvestPercentages[$shId];
                } elseif (!empty($reinvestAllocations)) {
                    foreach ($reinvestAllocations as $alloc) {
                        if (is_array($alloc) && isset($alloc['shareholder_id']) && $alloc['shareholder_id'] == $shId) {
                            $reinvestPct = (float)($alloc['reinvest_percentage'] ?? 0.0);
                            break;
                        }
                    }
                } elseif ($globalReinvestPct !== null) {
                    $reinvestPct = $globalReinvestPct;
                }
                $reinvestPct = max(0.0, min(100.0, $reinvestPct));

                $reinvestAmount = round($payoutAmount * ($reinvestPct / 100), 2);
                $cashAmount = round($payoutAmount - $reinvestAmount, 2);

                $totalCashPayout += $cashAmount;
                $totalReinvestedCapital += $reinvestAmount;
                $shareholderReinvestMap[$shId] = [
                    'reinvest_percentage' => $reinvestPct,
                    'reinvest_amount' => $reinvestAmount,
                    'cash_amount' => $cashAmount,
                ];

                DividendPayout::create([
                    'profit_distribution_id' => $distribution->id,
                    'shareholder_id' => $shId,
                    'shares_count' => $shData['shares_count'],
                    'ownership_percentage' => $shData['ownership_percentage'],
                    'payout_amount' => $payoutAmount,
                    'cash_amount' => $cashAmount,
                    'reinvest_amount' => $reinvestAmount,
                    'reinvest_percentage' => $reinvestPct,
                    'payment_status' => 'pending',
                    'payment_date' => null,
                    'transfer_receipt_url' => null,
                    'notes' => null,
                ]);
            }

            // Effective quarter label (e.g. 2026-Q1 -> 2026-Q2)
            $nextYear = $closing->quarter === 4 ? $closing->year + 1 : $closing->year;
            $nextQuarter = $closing->quarter === 4 ? 1 : $closing->quarter + 1;
            $effectiveQuarterLabel = sprintf('%04d-Q%d', $nextYear, $nextQuarter);

            // Roll-forward capital ledger
            $activeShareholders = Shareholder::where('is_active', true)->orderBy('id')->get();
            $totalCurrentCapital = (float)$activeShareholders->sum('capital_amount');

            // If no shareholder has capital_amount recorded yet, establish baseline from shares count
            if ($totalCurrentCapital <= 0 && $activeShareholders->sum('shares_count') > 0) {
                foreach ($activeShareholders as $ash) {
                    $baseNominal = (float)($ash->shares_count > 0 ? $ash->shares_count * 1000 : ($ash->ownership_percentage * 1000000));
                    $ash->capital_amount = $baseNominal;
                    $ash->save();
                }
                $activeShareholders = Shareholder::where('is_active', true)->orderBy('id')->get();
            }

            // Calculate new capital per shareholder
            $nextQuarterTotalCapital = 0.0;
            $shNewCapitals = [];
            foreach ($activeShareholders as $ash) {
                $reinvest = $shareholderReinvestMap[$ash->id]['reinvest_amount'] ?? 0.0;
                $newCap = round((float)$ash->capital_amount + $reinvest, 2);
                $shNewCapitals[$ash->id] = $newCap;
                $nextQuarterTotalCapital += $newCap;
            }
            $nextQuarterTotalCapital = round($nextQuarterTotalCapital, 2);

            // Calculate new ownership percentages and record capital mutations
            $sumPercentages = 0.0;
            $shCount = $activeShareholders->count();
            $idx = 0;

            // Delete previous capital histories for this distribution to prevent duplication on re-finalization
            ShareholderCapitalHistory::where('profit_distribution_id', $distribution->id)->delete();

            foreach ($activeShareholders as $ash) {
                $idx++;
                $openingCap = (float)$ash->capital_amount;
                $openingShares = (int)$ash->shares_count;
                $openingPct = (float)$ash->ownership_percentage;

                $newCap = $shNewCapitals[$ash->id];
                $reinvest = $shareholderReinvestMap[$ash->id]['reinvest_amount'] ?? 0.0;

                if ($nextQuarterTotalCapital > 0) {
                    if ($idx === $shCount) {
                        $newPct = round(100.0 - $sumPercentages, 2);
                    } else {
                        $newPct = round(($newCap / $nextQuarterTotalCapital) * 100, 2);
                        $sumPercentages += $newPct;
                    }
                } else {
                    $newPct = $openingPct;
                }

                if ($reinvest > 0) {
                    ShareholderCapitalHistory::create([
                        'shareholder_id' => $ash->id,
                        'quarterly_closing_id' => $closing->id,
                        'profit_distribution_id' => $distribution->id,
                        'transaction_type' => 'dividend_reinvestment',
                        'effective_quarter_label' => $effectiveQuarterLabel,
                        'amount' => $reinvest,
                        'opening_capital' => $openingCap,
                        'closing_capital' => $newCap,
                        'opening_shares_count' => $openingShares,
                        'closing_shares_count' => $openingShares,
                        'opening_ownership_percentage' => $openingPct,
                        'closing_ownership_percentage' => $newPct,
                        'notes' => "Reinvestasi dividen RUPS kuartal {$closing->period_label}",
                    ]);
                }

                // Update shareholder table
                $ash->update([
                    'capital_amount' => $newCap,
                    'ownership_percentage' => $newPct,
                ]);
            }

            // Update distribution recap
            $distribution->update([
                'total_cash_payout' => round($totalCashPayout, 2),
                'total_reinvested_capital' => round($totalReinvestedCapital, 2),
                'next_quarter_total_capital' => $nextQuarterTotalCapital,
            ]);

            // Update closing audit status to approved
            $closing->update([
                'audit_status' => 'approved',
                'approved_by' => auth()->id() ?? $closing->approved_by,
                'auditor_notes' => $data['notes'] ?? $closing->auditor_notes,
            ]);

            return $distribution->fresh(['payouts.shareholder', 'quarterlyClosing', 'capitalHistories']);
        });
    }
}
