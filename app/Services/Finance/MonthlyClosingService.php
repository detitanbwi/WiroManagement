<?php

namespace App\Services\Finance;

use App\Models\FixedExpenseRealization;
use App\Models\MarketingCampaign;
use App\Models\MonthlyClosing;
use App\Models\MonthlyClosingItem;
use App\Models\Payment;
use App\Models\ProjectExpense;
use App\Models\ProjectWorkLog;
use App\Models\TrackerExpense;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyClosingService
{
    /**
     * Generate or regenerate monthly closing financial statements and ledger items.
     *
     * @param string $periodMonth YYYY-MM
     * @param float $openingBalance
     * @param string|null $auditorNotes
     * @param int|null $auditedBy
     * @return MonthlyClosing
     */
    public function generateClosing(
        string $periodMonth,
        float $openingBalance = 0,
        ?string $auditorNotes = null,
        ?int $auditedBy = null
    ): MonthlyClosing {
        return DB::transaction(function () use ($periodMonth, $openingBalance, $auditorNotes, $auditedBy) {
            $startDate = Carbon::parse($periodMonth . '-01')->startOfMonth();
            $endDate = Carbon::parse($periodMonth . '-01')->endOfMonth();

            // 1. REVENUE: Client payments received during the month
            $payments = Payment::whereBetween('payment_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->with(['invoice.project'])
                ->get();
            $totalRevenue = (float)$payments->sum('amount');

            // 2. COGS (Infrastructure + Project Labor)
            // A. Infrastructure & direct project expenses
            $expenses = ProjectExpense::where('is_paid', true)
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->with('project')
                ->get();
            $totalInfraCost = (float)$expenses->sum('amount');

            // B. Paid direct project labor work logs
            $workLogs = ProjectWorkLog::where('payment_status', 'paid')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('payment_date', [$startDate->toDateString(), $endDate->toDateString()])
                        ->orWhere(function ($sub) use ($startDate, $endDate) {
                            $sub->whereNull('payment_date')
                                ->whereBetween('work_date', [$startDate->toDateString(), $endDate->toDateString()]);
                        });
                })
                ->with(['project', 'user'])
                ->get();
            $totalLaborCost = (float)$workLogs->sum('calculated_nominal');

            $totalCogs = $totalInfraCost + $totalLaborCost;

            // 3. OPEX (Marketing Spend + Office Operational / Utilities / Salary)
            // A. Marketing ad spend
            $marketingCampaigns = MarketingCampaign::where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()]);
            })->get();
            $totalMarketingSpend = (float)$marketingCampaigns->sum('ad_spend');

            // B. Operational tracker expenses
            $trackerExpenses = TrackerExpense::where('type', 'company')
                ->whereBetween('expense_date', [$startDate->toDateTimeString(), $endDate->toDateTimeString()])
                ->with('category')
                ->get();
            $totalTrackerOpex = (float)$trackerExpenses->sum('amount');

            // C. Realized Fixed Expenses (PAID in periodMonth)
            $fixedExpenseRealizations = FixedExpenseRealization::where('month_period', $periodMonth)
                ->where('payment_status', 'PAID')
                ->with('fixedExpense')
                ->get();
            $totalFixedExpenses = (float)$fixedExpenseRealizations->sum('paid_amount');

            $totalOpex = $totalMarketingSpend + $totalTrackerOpex + $totalFixedExpenses;

            // 4. Financial Calculations
            $grossProfit = $totalRevenue - $totalCogs;
            $netProfit = $grossProfit - $totalOpex;
            $netProfitMargin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 2) : 0.0;

            // 5. Cash Flow Reconciliation
            $cashInflow = $totalRevenue;
            $cashOutflow = $totalCogs + $totalOpex;
            $endingCashBalance = $openingBalance + $cashInflow - $cashOutflow;

            // 6. Create or Update MonthlyClosing Record
            $closing = MonthlyClosing::updateOrCreate(
                ['period_month' => $periodMonth],
                [
                    'opening_cash_balance' => round($openingBalance, 2),
                    'total_revenue' => round($totalRevenue, 2),
                    'total_cogs' => round($totalCogs, 2),
                    'gross_profit' => round($grossProfit, 2),
                    'total_opex' => round($totalOpex, 2),
                    'net_profit' => round($netProfit, 2),
                    'net_profit_margin' => $netProfitMargin,
                    'cash_inflow' => round($cashInflow, 2),
                    'cash_outflow' => round($cashOutflow, 2),
                    'ending_cash_balance' => round($endingCashBalance, 2),
                    'audit_status' => $closingExists = MonthlyClosing::where('period_month', $periodMonth)->first()?->audit_status ?: 'draft',
                    'auditor_notes' => $auditorNotes ?? MonthlyClosing::where('period_month', $periodMonth)->first()?->auditor_notes,
                    'audited_by' => $auditedBy ?? MonthlyClosing::where('period_month', $periodMonth)->first()?->audited_by,
                ]
            );

            // 7. Re-generate Ledger Items
            $closing->items()->delete();

            $items = [];

            // Cash mutation summary entries
            $items[] = [
                'closing_id' => $closing->id,
                'account_code' => '1000-00-01',
                'account_name' => 'Saldo Kas Awal Periode',
                'category' => 'cash_mutation',
                'debit' => round($openingBalance, 2),
                'credit' => 0,
                'project_id' => null,
                'notes' => 'Saldo awal buku kas perusahaan per tanggal ' . $startDate->format('d/m/Y'),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Revenue Items
            if ($payments->isNotEmpty()) {
                foreach ($payments as $payment) {
                    $proj = $payment->invoice?->project;
                    $items[] = [
                        'closing_id' => $closing->id,
                        'account_code' => '4000-01-01',
                        'account_name' => 'Pendapatan Proyek: ' . ($proj ? $proj->title : 'Umum'),
                        'category' => 'revenue',
                        'debit' => 0,
                        'credit' => round((float)$payment->amount, 2),
                        'project_id' => $proj?->id,
                        'notes' => 'Pembayaran Invoice ' . ($payment->invoice?->invoice_number ?? '-') . ' via ' . ($payment->payment_method ?? 'Transfer Bank'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            } else {
                $items[] = [
                    'closing_id' => $closing->id,
                    'account_code' => '4000-01-01',
                    'account_name' => 'Pendapatan Proyek Nihil',
                    'category' => 'revenue',
                    'debit' => 0,
                    'credit' => 0,
                    'project_id' => null,
                    'notes' => 'Tidak ada penerimaan pembayaran pada periode ini.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // COGS: Labor
            if ($workLogs->isNotEmpty()) {
                // Group by project for clean ledger presentation
                $laborByProject = $workLogs->groupBy('project_id');
                foreach ($laborByProject as $projectId => $logs) {
                    $proj = $logs->first()?->project;
                    $subLabor = (float)$logs->sum('calculated_nominal');
                    $hours = (float)$logs->sum('hours_worked');
                    $items[] = [
                        'closing_id' => $closing->id,
                        'account_code' => '5000-01-01',
                        'account_name' => 'HPP Tenaga Kerja Langsung: ' . ($proj ? $proj->title : 'Proyek #' . $projectId),
                        'category' => 'cogs',
                        'debit' => round($subLabor, 2),
                        'credit' => 0,
                        'project_id' => $projectId,
                        'notes' => 'Total jam kerja terealisasi: ' . $hours . ' jam (' . $logs->count() . ' log kerja terbayar)',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // COGS: Infrastructure & Direct Expenses
            if ($expenses->isNotEmpty()) {
                $expensesByProject = $expenses->groupBy('project_id');
                foreach ($expensesByProject as $projectId => $projeExpenses) {
                    $proj = $projeExpenses->first()?->project;
                    $subInfra = (float)$projeExpenses->sum('amount');
                    $items[] = [
                        'closing_id' => $closing->id,
                        'account_code' => '5000-02-01',
                        'account_name' => 'HPP Infrastruktur & Server: ' . ($proj ? $proj->title : 'Proyek #' . $projectId),
                        'category' => 'cogs',
                        'debit' => round($subInfra, 2),
                        'credit' => 0,
                        'project_id' => $projectId,
                        'notes' => 'Biaya server/domain/lisensi langsung proyek (' . $projeExpenses->count() . ' transaksi)',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // OPEX: Marketing
            if ($marketingCampaigns->isNotEmpty()) {
                foreach ($marketingCampaigns as $camp) {
                    $items[] = [
                        'closing_id' => $closing->id,
                        'account_code' => '6000-01-01',
                        'account_name' => 'Beban Iklan & Pemasaran: ' . $camp->campaign_name,
                        'category' => 'opex',
                        'debit' => round((float)$camp->ad_spend, 2),
                        'credit' => 0,
                        'project_id' => null,
                        'notes' => 'Platform: ' . strtoupper($camp->platform) . ', Leads: ' . $camp->messages_count . ', Deals: ' . $camp->deals_count,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // OPEX: Operational & Utilities
            if ($trackerExpenses->isNotEmpty()) {
                $opexByCategory = $trackerExpenses->groupBy('category_id');
                foreach ($opexByCategory as $catId => $expGroup) {
                    $catName = $expGroup->first()?->category?->name ?? 'Operasional Umum';
                    $subOpex = (float)$expGroup->sum('amount');
                    $items[] = [
                        'closing_id' => $closing->id,
                        'account_code' => '6000-02-01',
                        'account_name' => 'Beban Kantor: ' . $catName,
                        'category' => 'opex',
                        'debit' => round($subOpex, 2),
                        'credit' => 0,
                        'project_id' => null,
                        'notes' => 'Pengeluaran operasional perusahaan ' . $catName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // OPEX: Realized Fixed Expenses (Pengeluaran Tetap Bulanan)
            if ($fixedExpenseRealizations->isNotEmpty()) {
                foreach ($fixedExpenseRealizations as $realization) {
                    $expense = $realization->fixedExpense;
                    $expenseName = $expense?->name ?? 'Pengeluaran Tetap';
                    $category = $expense ? strtoupper($expense->category) : 'OPEX';
                    $accountCode = match ($category) {
                        'UTILITY' => '6000-03-01',
                        'SUBSCRIPTION' => '6000-04-01',
                        'PAYROLL' => '6000-05-01',
                        'INFRASTRUCTURE' => '6000-06-01',
                        default => '6000-02-02',
                    };

                    $paymentDateFormatted = $realization->payment_date
                        ? Carbon::parse($realization->payment_date)->format('d/m/Y')
                        : $periodMonth;

                    $items[] = [
                        'closing_id' => $closing->id,
                        'account_code' => $accountCode,
                        'account_name' => 'Beban Tetap (' . $category . '): ' . $expenseName,
                        'category' => 'opex',
                        'debit' => round((float)$realization->paid_amount, 2),
                        'credit' => 0,
                        'project_id' => null,
                        'notes' => 'Pembayaran ' . $expenseName . ' (' . $paymentDateFormatted . ') via ' . ($expense?->payment_method ?? 'Transfer Bank'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Cash Mutation entries
            $items[] = [
                'closing_id' => $closing->id,
                'account_code' => '1000-01-01',
                'account_name' => 'Total Arus Kas Masuk (Inflow)',
                'category' => 'cash_mutation',
                'debit' => round($cashInflow, 2),
                'credit' => 0,
                'project_id' => null,
                'notes' => 'Penerimaan pembayaran dari klien pada periode ' . $periodMonth,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $items[] = [
                'closing_id' => $closing->id,
                'account_code' => '1000-02-01',
                'account_name' => 'Total Arus Kas Keluar (Outflow)',
                'category' => 'cash_mutation',
                'debit' => 0,
                'credit' => round($cashOutflow, 2),
                'project_id' => null,
                'notes' => 'Realisasi biaya HPP dan OPEX pada periode ' . $periodMonth,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $items[] = [
                'closing_id' => $closing->id,
                'account_code' => '1000-03-01',
                'account_name' => 'Saldo Kas Akhir Rekonsiliasi',
                'category' => 'cash_mutation',
                'debit' => round($endingCashBalance, 2),
                'credit' => 0,
                'project_id' => null,
                'notes' => 'Saldo akhir buku kas perusahaan per tanggal ' . $endDate->format('d/m/Y'),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            MonthlyClosingItem::insert($items);

            return $closing->fresh(['items.project', 'auditor', 'approver']);
        });
    }

    /**
     * Audit and transition status of a monthly closing.
     */
    public function auditClosing(
        MonthlyClosing $closing,
        string $status,
        ?string $notes,
        int $userId
    ): MonthlyClosing {
        $closing->audit_status = $status;
        $closing->auditor_notes = $notes;

        if ($status === 'audited') {
            $closing->audited_by = $userId;
        } elseif ($status === 'approved') {
            if (!$closing->audited_by) {
                $closing->audited_by = $userId;
            }
            $closing->approved_by = $userId;
        }

        $closing->save();
        return $closing;
    }
}
