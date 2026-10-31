<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\AuditMonthlyClosingRequest;
use App\Http\Requests\Finance\GenerateMonthlyClosingRequest;
use App\Models\MonthlyClosing;
use App\Services\Finance\FinancialExportService;
use App\Services\Finance\MonthlyClosingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class MonthlyClosingController extends Controller
{
    public function __construct(
        protected MonthlyClosingService $closingService,
        protected FinancialExportService $exportService
    ) {}

    /**
     * Display a listing of monthly closing statements and financial KPI overview.
     */
    public function index(): View
    {
        $closings = MonthlyClosing::with(['auditor', 'approver'])
            ->orderBy('period_month', 'desc')
            ->paginate(12);

        // All-time / aggregated metrics
        $allClosings = MonthlyClosing::all();
        $totalRevenueAll = (float)$allClosings->sum('total_revenue');
        $totalCogsAll = (float)$allClosings->sum('total_cogs');
        $totalGrossProfitAll = (float)$allClosings->sum('gross_profit');
        $totalNetProfitAll = (float)$allClosings->sum('net_profit');
        $latestClosing = $closings->first();

        return view('finance.closings.index', compact(
            'closings',
            'latestClosing',
            'totalRevenueAll',
            'totalCogsAll',
            'totalGrossProfitAll',
            'totalNetProfitAll'
        ));
    }

    /**
     * Display the detailed interactive closing auditor view.
     */
    public function show(MonthlyClosing $closing): View
    {
        $closing->load(['items.project', 'auditor', 'approver']);

        $revenueItems = $closing->items->where('category', 'revenue');
        $cogsItems = $closing->items->where('category', 'cogs');
        $opexItems = $closing->items->where('category', 'opex');
        $cashItems = $closing->items->where('category', 'cash_mutation');

        return view('finance.closings.show', compact(
            'closing',
            'revenueItems',
            'cogsItems',
            'opexItems',
            'cashItems'
        ));
    }

    /**
     * Generate or recalculate monthly financial closing statement.
     */
    public function generate(GenerateMonthlyClosingRequest $request): RedirectResponse
    {
        $closing = $this->closingService->generateClosing(
            periodMonth: $request->period_month,
            openingBalance: (float)$request->input('opening_cash_balance', 0),
            auditorNotes: $request->auditor_notes,
            auditedBy: auth()->id()
        );

        return redirect()->route('finance.closings.show', $closing)
            ->with('success', "Laporan Closing Bulanan {$closing->period_month} berhasil dikompilasi.");
    }

    /**
     * Audit and update approval status of a monthly closing statement.
     */
    public function audit(AuditMonthlyClosingRequest $request, MonthlyClosing $closing): RedirectResponse
    {
        $this->closingService->auditClosing(
            closing: $closing,
            status: $request->audit_status,
            notes: $request->auditor_notes,
            userId: auth()->id()
        );

        return redirect()->route('finance.closings.show', $closing)
            ->with('success', "Status audit closing {$closing->period_month} berhasil diperbarui ke " . strtoupper($request->audit_status) . '.');
    }

    /**
     * Stream PDF monthly closing report.
     */
    public function exportPdf(MonthlyClosing $closing): Response
    {
        return $this->exportService->streamPdf($closing);
    }
}
