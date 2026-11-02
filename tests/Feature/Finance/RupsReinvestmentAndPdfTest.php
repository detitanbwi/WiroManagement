<?php

namespace Tests\Feature\Finance;

use App\Models\DividendPayout;
use App\Models\MonthlyClosing;
use App\Models\ProfitDistribution;
use App\Models\QuarterlyClosing;
use App\Models\Shareholder;
use App\Models\ShareholderCapitalHistory;
use App\Models\User;
use App\Services\Finance\QuarterlyClosingService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RupsReinvestmentAndPdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $financeUser;
    protected Shareholder $titan;
    protected Shareholder $bima;
    protected QuarterlyClosingService $quarterlyClosingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->quarterlyClosingService = app(QuarterlyClosingService::class);

        $this->financeUser = User::factory()->create([
            'name' => 'Finance Specialist',
            'email' => 'finance@wirodev.test',
            'is_active' => true,
        ]);
        $this->financeUser->syncRoles(['finance']);

        // 2 Founders with defined capital amounts
        $this->titan = Shareholder::create([
            'name' => 'Titan Anggaraksa',
            'email' => 'titan@wirodev.test',
            'shares_count' => 95000,
            'capital_amount' => 95000000.00,
            'ownership_percentage' => 95.00,
            'bank_name' => 'BCA',
            'bank_account_number' => '8830123456',
            'bank_account_holder' => 'Titan Anggaraksa',
            'is_active' => true,
        ]);

        $this->bima = Shareholder::create([
            'name' => 'Bima Jati',
            'email' => 'bima@wirodev.test',
            'shares_count' => 5000,
            'capital_amount' => 5000000.00,
            'ownership_percentage' => 5.00,
            'bank_name' => 'BCA',
            'bank_account_number' => '8830998877',
            'bank_account_holder' => 'Bima Jati',
            'is_active' => true,
        ]);

        // Monthly closings for 2026 Q1
        MonthlyClosing::create([
            'period_month' => '2026-01',
            'opening_cash_balance' => 20000000,
            'total_revenue' => 30000000,
            'total_cogs' => 10000000,
            'gross_profit' => 20000000,
            'total_opex' => 5000000,
            'net_profit' => 15000000,
            'net_profit_margin' => 50.0,
            'cash_inflow' => 30000000,
            'cash_outflow' => 15000000,
            'ending_cash_balance' => 35000000,
            'audit_status' => 'approved',
        ]);

        MonthlyClosing::create([
            'period_month' => '2026-02',
            'opening_cash_balance' => 35000000,
            'total_revenue' => 40000000,
            'total_cogs' => 12000000,
            'gross_profit' => 28000000,
            'total_opex' => 8000000,
            'net_profit' => 20000000,
            'net_profit_margin' => 50.0,
            'cash_inflow' => 40000000,
            'cash_outflow' => 20000000,
            'ending_cash_balance' => 55000000,
            'audit_status' => 'approved',
        ]);

        MonthlyClosing::create([
            'period_month' => '2026-03',
            'opening_cash_balance' => 55000000,
            'total_revenue' => 30000000,
            'total_cogs' => 8000000,
            'gross_profit' => 22000000,
            'total_opex' => 7000000,
            'net_profit' => 15000000,
            'net_profit_margin' => 50.0,
            'cash_inflow' => 30000000,
            'cash_outflow' => 15000000,
            'ending_cash_balance' => 70000000,
            'audit_status' => 'approved',
        ]);
    }

    /**
     * 1. Test bahwa alokasi dividen dengan reinvestasi 50% kas & 50% reinvest berhasil terbagi dengan benar di dividend_payouts.
     */
    public function test_dividend_allocation_with_50_percent_cash_and_50_percent_reinvestment(): void
    {
        $closing = $this->quarterlyClosingService->generateQuarterlyClosing(2026, 1);

        // Distributable profit is 40,000,000:
        // Titan (95%) = 38,000,000
        // Bima (5%) = 2,000,000
        // Both choose 50% reinvest and 50% cash
        $response = $this->actingAs($this->financeUser)->post(route('finance.rups.distribute', $closing->id), [
            'retained_earnings_percentage' => 20,
            'distribution_date' => '2026-04-05',
            'notes' => 'Keputusan RUPS Q1 dengan reinvestasi 50%.',
            'document_number' => 'BA-RUPS/WD/2026/Q1/001',
            'reinvest_percentages' => [
                $this->titan->id => 50,
                $this->bima->id => 50,
            ],
        ]);

        $response->assertRedirect(route('finance.rups.show', $closing->id));

        $distribution = ProfitDistribution::where('quarterly_closing_id', $closing->id)->first();
        $this->assertNotNull($distribution);
        $this->assertEquals('BA-RUPS/WD/2026/Q1/001', $distribution->document_number);
        $this->assertEquals(40000000.00, (float)$distribution->distributable_profit);
        $this->assertEquals(20000000.00, (float)$distribution->total_cash_payout);
        $this->assertEquals(20000000.00, (float)$distribution->total_reinvested_capital);

        // Payout Titan
        $titanPayout = DividendPayout::where('profit_distribution_id', $distribution->id)
            ->where('shareholder_id', $this->titan->id)
            ->first();

        $this->assertNotNull($titanPayout);
        $this->assertEquals(38000000.00, (float)$titanPayout->payout_amount);
        $this->assertEquals(19000000.00, (float)$titanPayout->cash_amount);
        $this->assertEquals(19000000.00, (float)$titanPayout->reinvest_amount);
        $this->assertEquals(50.00, (float)$titanPayout->reinvest_percentage);

        // Payout Bima
        $bimaPayout = DividendPayout::where('profit_distribution_id', $distribution->id)
            ->where('shareholder_id', $this->bima->id)
            ->first();

        $this->assertNotNull($bimaPayout);
        $this->assertEquals(2000000.00, (float)$bimaPayout->payout_amount);
        $this->assertEquals(1000000.00, (float)$bimaPayout->cash_amount);
        $this->assertEquals(1000000.00, (float)$bimaPayout->reinvest_amount);
        $this->assertEquals(50.00, (float)$bimaPayout->reinvest_percentage);
    }

    /**
     * 2. Test bahwa mutasi modal tersimpan di shareholder_capital_histories dan saldo modal founder bertambah.
     */
    public function test_capital_mutations_are_recorded_in_ledger_and_founder_capital_increases(): void
    {
        $closing = $this->quarterlyClosingService->generateQuarterlyClosing(2026, 1);

        $distribution = $this->quarterlyClosingService->finalizeDistribution($closing, [
            'retained_earnings_percentage' => 20,
            'distribution_date' => '2026-04-05',
            'reinvest_percentages' => [
                $this->titan->id => 50,
                $this->bima->id => 50,
            ],
        ]);

        // Assert capital histories created
        $this->assertDatabaseHas('shareholder_capital_histories', [
            'shareholder_id' => $this->titan->id,
            'quarterly_closing_id' => $closing->id,
            'profit_distribution_id' => $distribution->id,
            'transaction_type' => 'dividend_reinvestment',
            'effective_quarter_label' => '2026-Q2',
            'amount' => 19000000.00,
            'opening_capital' => 95000000.00,
            'closing_capital' => 114000000.00,
            'closing_ownership_percentage' => 95.00,
        ]);

        $this->assertDatabaseHas('shareholder_capital_histories', [
            'shareholder_id' => $this->bima->id,
            'quarterly_closing_id' => $closing->id,
            'profit_distribution_id' => $distribution->id,
            'transaction_type' => 'dividend_reinvestment',
            'effective_quarter_label' => '2026-Q2',
            'amount' => 1000000.00,
            'opening_capital' => 5000000.00,
            'closing_capital' => 6000000.00,
            'closing_ownership_percentage' => 5.00,
        ]);

        // Reload shareholders and verify increased capital
        $this->titan->refresh();
        $this->bima->refresh();

        $this->assertEquals(114000000.00, (float)$this->titan->capital_amount);
        $this->assertEquals(6000000.00, (float)$this->bima->capital_amount);
        $this->assertEquals(120000000.00, (float)$distribution->next_quarter_total_capital);
    }

    /**
     * 3. Test bahwa kuartal berikutnya membaca proporsi modal terbaru.
     */
    public function test_subsequent_quarter_reads_updated_capital_proportions(): void
    {
        $closingQ1 = $this->quarterlyClosingService->generateQuarterlyClosing(2026, 1);

        // In Q1: Titan takes 100% cash (0% reinvest), Bima reinvests 100% of his dividend
        // Titan payout: 38,000,000 -> 0 reinvest -> capital remains 95,000,000
        // Bima payout: 2,000,000 -> 2,000,000 reinvest -> capital becomes 5,000,000 + 2,000,000 = 7,000,000
        // Total new capital = 95M + 7M = 102M
        // Titan new % = 95 / 102 * 100 = 93.14%
        // Bima new % = 7 / 102 * 100 = 6.86%
        $this->quarterlyClosingService->finalizeDistribution($closingQ1, [
            'retained_earnings_percentage' => 20,
            'distribution_date' => '2026-04-05',
            'reinvest_percentages' => [
                $this->titan->id => 0,
                $this->bima->id => 100,
            ],
        ]);

        $this->titan->refresh();
        $this->bima->refresh();

        $this->assertEquals(93.14, (float)$this->titan->ownership_percentage);
        $this->assertEquals(6.86, (float)$this->bima->ownership_percentage);

        // Setup 3 monthly closings for Q2 (2026-04, 2026-05, 2026-06)
        MonthlyClosing::create([
            'period_month' => '2026-04',
            'opening_cash_balance' => 70000000,
            'total_revenue' => 30000000,
            'total_cogs' => 10000000,
            'gross_profit' => 20000000,
            'total_opex' => 5000000,
            'net_profit' => 15000000,
            'net_profit_margin' => 50.0,
            'cash_inflow' => 30000000,
            'cash_outflow' => 15000000,
            'ending_cash_balance' => 85000000,
            'audit_status' => 'approved',
        ]);
        MonthlyClosing::create([
            'period_month' => '2026-05',
            'opening_cash_balance' => 85000000,
            'total_revenue' => 30000000,
            'total_cogs' => 10000000,
            'gross_profit' => 20000000,
            'total_opex' => 5000000,
            'net_profit' => 15000000,
            'net_profit_margin' => 50.0,
            'cash_inflow' => 30000000,
            'cash_outflow' => 15000000,
            'ending_cash_balance' => 100000000,
            'audit_status' => 'approved',
        ]);
        MonthlyClosing::create([
            'period_month' => '2026-06',
            'opening_cash_balance' => 100000000,
            'total_revenue' => 40000000,
            'total_cogs' => 10000000,
            'gross_profit' => 30000000,
            'total_opex' => 10000000,
            'net_profit' => 20000000,
            'net_profit_margin' => 50.0,
            'cash_inflow' => 40000000,
            'cash_outflow' => 20000000,
            'ending_cash_balance' => 120000000,
            'audit_status' => 'approved',
        ]);

        // Generate Q2 closing
        $closingQ2 = $this->quarterlyClosingService->generateQuarterlyClosing(2026, 2);

        // Net profit Q2: 15M + 15M + 20M = 50M.
        // Retained earnings 20% = 10M -> Distributable profit = 40M.
        $allocationQ2 = $this->quarterlyClosingService->calculateAllocation($closingQ2, 0.20);

        $shAllocations = collect($allocationQ2['shareholders']);
        $titanAlloc = $shAllocations->firstWhere('shareholder_id', $this->titan->id);
        $bimaAlloc = $shAllocations->firstWhere('shareholder_id', $this->bima->id);

        // Q2 allocation must reflect new percentages: 93.14% and 6.86%
        $this->assertEquals(93.14, (float)$titanAlloc['ownership_percentage']);
        $this->assertEquals(6.86, (float)$bimaAlloc['ownership_percentage']);

        // Check payouts: 93.14% of 40M = 37,256,000, 6.86% of 40M = 2,744,000
        $this->assertEquals(37256000.00, (float)$titanAlloc['payout_amount']);
        $this->assertEquals(2744000.00, (float)$bimaAlloc['payout_amount']);
    }

    /**
     * 4. Test bahwa rute GET finance/rups/{id}/pdf mengembalikan response status 200 dengan header application/pdf.
     */
    public function test_rups_pdf_stream_returns_status_200_with_pdf_header(): void
    {
        $closing = $this->quarterlyClosingService->generateQuarterlyClosing(2026, 1);

        $this->quarterlyClosingService->finalizeDistribution($closing, [
            'retained_earnings_percentage' => 20,
            'distribution_date' => '2026-04-05',
            'notes' => 'Persetujuan resmi RUPS Q1 Wirodev.',
            'document_number' => 'BA-RUPS/WD/2026/Q1/001',
            'reinvest_percentages' => [
                $this->titan->id => 50,
                $this->bima->id => 50,
            ],
        ]);

        $response = $this->actingAs($this->financeUser)->get(route('finance.rups.pdf', $closing->id));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }
}
