<?php

namespace App\Services\Finance;

use App\Models\FixedExpense;
use App\Models\FixedExpenseRealization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FixedExpenseService
{
    /**
     * Create a new recurring fixed expense.
     *
     * @param array $data
     * @return FixedExpense
     */
    public function createFixedExpense(array $data): FixedExpense
    {
        if (isset($data['category'])) {
            $data['category'] = strtoupper($data['category']);
        }

        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        return FixedExpense::create($data);
    }

    /**
     * Update an existing fixed expense.
     *
     * @param FixedExpense $expense
     * @param array $data
     * @return FixedExpense
     */
    public function updateFixedExpense(FixedExpense $expense, array $data): FixedExpense
    {
        if (isset($data['category'])) {
            $data['category'] = strtoupper($data['category']);
        }

        $expense->update($data);

        return $expense->fresh();
    }

    /**
     * Delete (soft delete) a fixed expense.
     *
     * @param FixedExpense $expense
     * @return bool
     */
    public function deleteFixedExpense(FixedExpense $expense): bool
    {
        return (bool) $expense->delete();
    }

    /**
     * Generate realization checklist for active fixed expenses for a given month period.
     * Evaluates OVERDUE status automatically if current date has passed due_day_of_month.
     *
     * @param string $monthPeriod Format: YYYY-MM
     * @return Collection
     */
    public function generateMonthlyRealizations(string $monthPeriod): Collection
    {
        return DB::transaction(function () use ($monthPeriod) {
            $today = Carbon::today();
            $currentMonthPeriod = $today->format('Y-m');

            $activeExpenses = FixedExpense::where('is_active', true)->get();

            foreach ($activeExpenses as $expense) {
                $isOverdue = ($monthPeriod < $currentMonthPeriod) ||
                    ($monthPeriod === $currentMonthPeriod && $today->day > $expense->due_day_of_month);

                $existing = FixedExpenseRealization::where('fixed_expense_id', $expense->id)
                    ->where('month_period', $monthPeriod)
                    ->first();

                if (!$existing) {
                    FixedExpenseRealization::create([
                        'fixed_expense_id' => $expense->id,
                        'month_period' => $monthPeriod,
                        'paid_amount' => 0,
                        'payment_date' => null,
                        'payment_status' => $isOverdue ? 'OVERDUE' : 'PENDING',
                        'receipt_url' => null,
                        'proof_of_payment' => null,
                        'notes' => null,
                    ]);
                } elseif ($existing->payment_status === 'PENDING' && $isOverdue) {
                    $existing->update(['payment_status' => 'OVERDUE']);
                }
            }

            // Also re-evaluate any existing PENDING realizations for this cycle
            $pendingRealizations = FixedExpenseRealization::where('month_period', $monthPeriod)
                ->where('payment_status', 'PENDING')
                ->with('fixedExpense')
                ->get();

            foreach ($pendingRealizations as $pending) {
                if ($pending->fixedExpense) {
                    $isOverdue = ($monthPeriod < $currentMonthPeriod) ||
                        ($monthPeriod === $currentMonthPeriod && $today->day > $pending->fixedExpense->due_day_of_month);

                    if ($isOverdue) {
                        $pending->update(['payment_status' => 'OVERDUE']);
                    }
                }
            }

            return FixedExpenseRealization::where('month_period', $monthPeriod)
                ->with('fixedExpense')
                ->get();
        });
    }

    /**
     * Mark a fixed expense realization as PAID.
     *
     * @param FixedExpenseRealization $realization
     * @param float $amount
     * @param string|null $paymentDate
     * @param string|null $receiptUrl
     * @return FixedExpenseRealization
     */
    public function markAsPaid(
        FixedExpenseRealization $realization,
        float $amount,
        ?string $paymentDate = null,
        ?string $receiptUrl = null
    ): FixedExpenseRealization {
        $realization->update([
            'paid_amount' => $amount,
            'payment_date' => $paymentDate ? Carbon::parse($paymentDate) : Carbon::now(),
            'payment_status' => 'PAID',
            'receipt_url' => $receiptUrl,
            'proof_of_payment' => $receiptUrl,
        ]);

        return $realization->fresh(['fixedExpense']);
    }

    /**
     * Compute comprehensive financial monthly summary for fixed expenses.
     *
     * @param string $monthPeriod Format: YYYY-MM
     * @return array
     */
    public function getMonthlySummary(string $monthPeriod): array
    {
        $today = Carbon::today();
        $currentMonthPeriod = $today->format('Y-m');

        // Total projected budget from all currently active fixed expenses
        $activeExpenses = FixedExpense::where('is_active', true)->get();
        $totalProjectedBudget = (float) $activeExpenses->sum('amount');

        // Realizations for the period
        $realizations = FixedExpenseRealization::where('month_period', $monthPeriod)
            ->with('fixedExpense')
            ->get();

        // Check if any PENDING realizations are now overdue
        foreach ($realizations as $realization) {
            if ($realization->payment_status === 'PENDING' && $realization->fixedExpense) {
                $isOverdue = ($monthPeriod < $currentMonthPeriod) ||
                    ($monthPeriod === $currentMonthPeriod && $today->day > $realization->fixedExpense->due_day_of_month);

                if ($isOverdue) {
                    $realization->update(['payment_status' => 'OVERDUE']);
                    $realization->payment_status = 'OVERDUE';
                }
            }
        }

        $paidRealizations = $realizations->where('payment_status', 'PAID');
        $pendingRealizations = $realizations->where('payment_status', 'PENDING');
        $overdueRealizations = $realizations->where('payment_status', 'OVERDUE');
        $skippedRealizations = $realizations->where('payment_status', 'SKIPPED');

        $totalRealizedExpenditure = (float) $paidRealizations->sum('paid_amount');

        $totalPendingAmount = (float) $pendingRealizations->sum(function ($r) {
            return (float) ($r->fixedExpense?->amount ?? 0);
        });

        $totalOverdueAmount = (float) $overdueRealizations->sum(function ($r) {
            return (float) ($r->fixedExpense?->amount ?? 0);
        });

        $totalPendingOrOverdue = $totalPendingAmount + $totalOverdueAmount;

        $completionRate = $totalProjectedBudget > 0
            ? round(($totalRealizedExpenditure / $totalProjectedBudget) * 100, 2)
            : 0.0;

        return [
            'month_period' => $monthPeriod,
            'total_projected_budget' => round($totalProjectedBudget, 2),
            'projected_budget' => round($totalProjectedBudget, 2),
            'total_realized_expenditure' => round($totalRealizedExpenditure, 2),
            'realized_expenditure' => round($totalRealizedExpenditure, 2),
            'total_pending_amount' => round($totalPendingAmount, 2),
            'pending_amount' => round($totalPendingAmount, 2),
            'total_overdue_amount' => round($totalOverdueAmount, 2),
            'overdue_amount' => round($totalOverdueAmount, 2),
            'total_pending_or_overdue_amount' => round($totalPendingOrOverdue, 2),
            'pending_or_overdue_amount' => round($totalPendingOrOverdue, 2),
            'completion_rate' => $completionRate,
            'completion_rate_percentage' => $completionRate,
            'total_realizations' => $realizations->count(),
            'paid_count' => $paidRealizations->count(),
            'pending_count' => $pendingRealizations->count(),
            'overdue_count' => $overdueRealizations->count(),
            'skipped_count' => $skippedRealizations->count(),
            'realizations' => $realizations,
        ];
    }
}
