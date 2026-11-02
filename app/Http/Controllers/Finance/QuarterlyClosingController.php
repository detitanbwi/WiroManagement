<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\DividendPayout;
use App\Models\MonthlyClosing;
use App\Models\QuarterlyClosing;
use App\Models\Shareholder;
use App\Services\Finance\FinancialExportService;
use App\Services\Finance\QuarterlyClosingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class QuarterlyClosingController extends Controller
{
    public function __construct(
        protected QuarterlyClosingService $quarterlyClosingService,
        protected FinancialExportService $exportService
    ) {}

    /**
     * Display listing of quarterly closings, RUPS summary, and KPI indicators.
     */
    public function index(): View
    {
        $closings = QuarterlyClosing::with(['profitDistribution.payouts.shareholder', 'approver'])
            ->orderBy('year', 'desc')
            ->orderBy('quarter', 'desc')
            ->paginate(12);

        $allClosings = QuarterlyClosing::all();
        $totalNetProfitAll = (float)$allClosings->sum('net_profit');
        $totalRevenueAll = (float)$allClosings->sum('total_revenue');

        $totalPaidDividends = (float)DividendPayout::where('payment_status', 'transferred')->sum('payout_amount');
        $totalPendingDividends = (float)DividendPayout::where('payment_status', 'pending')->sum('payout_amount');

        $shareholders = Shareholder::where('is_active', true)->orderBy('ownership_percentage', 'desc')->get();
        $latestClosing = $closings->first();

        return view('finance.rups.index', compact(
            'closings',
            'latestClosing',
            'totalNetProfitAll',
            'totalRevenueAll',
            'totalPaidDividends',
            'totalPendingDividends',
            'shareholders'
        ));
    }

    /**
     * Generate or recalculate quarterly consolidation statement.
     */
    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2050',
            'quarter' => 'required|integer|in:1,2,3,4',
        ]);

        $closing = $this->quarterlyClosingService->generateQuarterlyClosing(
            year: (int)$validated['year'],
            quarter: (int)$validated['quarter']
        );

        return redirect()->route('finance.rups.show', $closing->id)
            ->with('success', "Konsolidasi Kuartal {$closing->period_label} berhasil dikompilasi.");
    }

    /**
     * Display quarterly evaluation, cash protection meter, and interactive dividend simulator.
     */
    public function show(Request $request, int|string $id): View
    {
        $closing = QuarterlyClosing::with(['profitDistribution.payouts.shareholder', 'approver', 'capitalHistories.shareholder'])->findOrFail($id);

        // Fetch underlying monthly closings
        $monthlyClosings = MonthlyClosing::whereIn('period_month', $closing->quarter_months)
            ->orderBy('period_month', 'asc')
            ->get();

        $defaultPercent = $closing->profitDistribution
            ? (float)$closing->profitDistribution->retained_earnings_percentage
            : 20.0;

        $retainedPercent = (float)$request->query('retained_percent', $defaultPercent);

        $allocation = $this->quarterlyClosingService->calculateAllocation(
            closing: $closing,
            retainedEarningsPercent: $retainedPercent / 100
        );

        $shareholders = Shareholder::where('is_active', true)->orderBy('ownership_percentage', 'desc')->get();

        return view('finance.rups.show', compact(
            'closing',
            'monthlyClosings',
            'allocation',
            'retainedPercent',
            'shareholders'
        ));
    }

    /**
     * Finalize RUPS decision and create founder dividend payouts.
     */
    public function distribute(Request $request, int|string $id): RedirectResponse
    {
        $closing = QuarterlyClosing::findOrFail($id);

        $validated = $request->validate([
            'retained_earnings_percentage' => 'required|numeric|min:0|max:100',
            'distribution_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'document_number' => 'nullable|string|max:50',
            'reinvest_percentages' => 'nullable|array',
            'reinvest_percentages.*' => 'nullable|numeric|between:0,100',
            'reinvest_allocations' => 'nullable|array',
            'reinvest_percentage' => 'nullable|numeric|between:0,100',
        ]);

        $distribution = $this->quarterlyClosingService->finalizeDistribution($closing, $validated);

        return redirect()->route('finance.rups.show', $closing->id)
            ->with('success', "Keputusan RUPS {$closing->period_label} berhasil disahkan! Dividen sebesar Rp " . number_format($distribution->distributable_profit, 0, ',', '.') . " siap ditransfer.");
    }

    /**
     * Stream or download Berita Acara RUPS (PDF).
     */
    public function exportPdf(Request $request, int|string $id): Response
    {
        $closing = QuarterlyClosing::with(['profitDistribution.payouts.shareholder', 'approver', 'capitalHistories.shareholder'])->findOrFail($id);

        if ($request->query('download') == 1) {
            return $this->exportService->downloadRupsPdf($closing);
        }

        return $this->exportService->streamRupsPdf($closing);
    }

    /**
     * Mark founder dividend payout as transferred and attach payment receipt.
     */
    public function markPaid(Request $request, int|string $id): RedirectResponse
    {
        $payout = DividendPayout::with(['shareholder', 'profitDistribution.quarterlyClosing'])->findOrFail($id);

        $request->validate([
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'transfer_receipt' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:10240',
        ]);

        if ($request->hasFile('transfer_receipt')) {
            $path = $request->file('transfer_receipt')->store('receipts/dividends', 'public');
            $payout->transfer_receipt_url = '/storage/' . $path;
        }

        $payout->payment_status = 'transferred';
        $payout->payment_date = $request->payment_date ?: now()->toDateString();
        if ($request->filled('notes')) {
            $payout->notes = $request->notes;
        }
        $payout->save();

        // Check if all payouts under this distribution are completed
        $distribution = $payout->profitDistribution;
        if ($distribution && $distribution->isFullyTransferred()) {
            $distribution->update(['status' => 'distributed']);
        }

        $closingId = $distribution?->quarterly_closing_id;

        return redirect()->route('finance.rups.show', $closingId)
            ->with('success', "Dividen sebesar Rp " . number_format($payout->payout_amount, 0, ',', '.') . " untuk {$payout->shareholder->name} berhasil ditandai telah ditransfer.");
    }
}
