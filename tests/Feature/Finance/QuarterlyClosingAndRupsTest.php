<?php

namespace Tests\Feature\Finance;

use App\Models\DividendPayout;
use App\Models\MarketingCampaign;
use App\Models\MonthlyClosing;
use App\Models\MonthlyClosingItem;
use App\Models\ProfitDistribution;
use App\Models\QuarterlyClosing;
use App\Models\Shareholder;
use App\Models\User;
use App\Services\Finance\MonthlyClosingService;
use App\Services\Finance\QuarterlyClosingService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuarterlyClosingAndRupsTest extends TestCase
{
    use RefreshDatabase;

    protected User $financeUser;
    protected User $staffUser;
    protected MonthlyClosingService $monthlyClosingService;
    protected QuarterlyClosingService $quarterlyClosingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->monthlyClosingService = app(MonthlyClosingService::class);
        $this->quarterlyClosingService = app(QuarterlyClosingService::class);

        $this->financeUser = User::factory()->create([
            'name' => 'Finance Specialist',
            'email' => 'finance@wirodev.test',
            'is_active' => true,
        ]);
        $this->financeUser->syncRoles(['finance']);

        $this->staffUser = User::factory()->create([
            'name' => 'Staff Member',
            'email' => 'staff@wirodev.test',
            'is_active' => true,
        ]);
        $this->staffUser->syncRoles(['staff']);

        // Create 2 active shareholders (Founders: Titan Anggaraksa 95%, Bima Jati 5%)
        Shareholder::create([
            'name' => 'Titan Anggaraksa',
            'email' => 'titan@wirodev.test',
            'shares_count' => 95000,
            'ownership_percentage' => 95.00,
            'bank_name' => 'BCA',
            'bank_account_number' => '8830123456',
            'bank_account_holder' => 'Titan Anggaraksa',
            'is_active' => true,
        ]);

        Shareholder::create([
            'name' => 'Bima Jati',
            'email' => 'bima@wirodev.test',
            'shares_count' => 5000,
            'ownership_percentage' => 5.00,
            'bank_name' => 'BCA',
            'bank_account_number' => '8830998877',
            'bank_account_holder' => 'Bima Jati',
            'is_active' => true,
        ]);
    }

    /**
     * Test Auto-Roll Forward Saldo Kas Awal:
     * When openingBalance is 0 or not passed, it retrieves ending_cash_balance from M-1 with approved/audited status.
     */
    public function test_auto_roll_forward_opening_cash_balance(): void
    {
        // Setup M-1 (2026-09) with approved status and ending cash Rp 45,000,000
        MonthlyClosing::create([
            'period_month' => '2026-09',
            'opening_cash_balance' => 30000000,
            'total_revenue' => 25000000,
            'total_cogs' => 5000000,
            'gross_profit' => 20000000,
            'total_opex' => 5000000,
            'net_profit' => 15000000,
            'net_profit_margin' => 60.0,
            'cash_inflow' => 25000000,
            'cash_outflow' => 10000000,
            'ending_cash_balance' => 45000000,
            'audit_status' => 'approved',
        ]);

        // Generate closing for M (2026-10) with openingBalance = 0
        $closing = $this->monthlyClosingService->generateClosing(
            periodMonth: '2026-10',
            openingBalance: 0
        );

        // It should automatically pull Rp 45,000,000 from 2026-09
        $this->assertEquals(45000000.00, (float)$closing->opening_cash_balance);
    }

    /**
     * Test multi-month marketing campaign proportional daily rate calculation (no spend duplication).
     */
    public function test_marketing_campaign_proportional_daily_rate_across_months(): void
    {
        // Campaign running across 2 months: 2026-10-15 to 2026-11-14 (31 days total: 17 days in Oct, 14 days in Nov)
        // ad_spend = Rp 3,100,000 (Daily rate = Rp 100,000 / day)
        MarketingCampaign::create([
            'platform' => 'meta_ads',
            'campaign_name' => 'Q4 Lead Gen Ads',
            'start_date' => '2026-10-15',
            'end_date' => '2026-11-14',
            'ad_spend' => 3100000,
            'messages_count' => 50,
            'deals_count' => 2,
        ]);

        // 1. Generate closing for October 2026 (17 days) -> expected spend: 1,700,000
        $closingOct = $this->monthlyClosingService->generateClosing('2026-10', 10000000);
        $this->assertEquals(1700000.00, (float)$closingOct->total_opex);
        $octItem = $closingOct->items()->where('account_code', '6000-01-01')->first();
        $this->assertNotNull($octItem);
        $this->assertEquals(1700000.00, (float)$octItem->debit);

        // 2. Generate closing for November 2026 (14 days) -> expected spend: 1,400,000
        $closingNov = $this->monthlyClosingService->generateClosing('2026-11', 10000000);
        $this->assertEquals(1400000.00, (float)$closingNov->total_opex);
        $novItem = $closingNov->items()->where('account_code', '6000-01-01')->first();
        $this->assertNotNull($novItem);
        $this->assertEquals(1400000.00, (float)$novItem->debit);

        // Sum of both months equals total ad_spend exactly, no double counting
        $this->assertEquals(3100000.00, (float)$octItem->debit + (float)$novItem->debit);
    }

    /**
     * Test Quarterly Closing consolidation and Safety Cash Floor (Rp 15.000.000) formula.
     */
    public function test_quarterly_closing_generation_and_cash_floor_rules(): void
    {
        // Create 3 months for 2026-Q1
        // Month 1 (Jan): Revenue 30M, COGS 10M, OPEX 5M, Net 15M, Ending Cash 35M
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

        // Month 2 (Feb): Revenue 40M, COGS 12M, OPEX 8M, Net 20M, Ending Cash 55M
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

        // Month 3 (Mar): Revenue 30M, COGS 8M, OPEX 7M, Net 15M, Ending Cash 70M
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

        // Generate Quarterly Closing for 2026 Q1
        $quarterly = $this->quarterlyClosingService->generateQuarterlyClosing(2026, 1);

        // Assertions:
        $this->assertEquals('2026-Q1', $quarterly->period_label);
        $this->assertEquals(100000000.00, (float)$quarterly->total_revenue); // 30M + 40M + 30M
        $this->assertEquals(30000000.00, (float)$quarterly->total_cogs);     // 10M + 12M + 8M
        $this->assertEquals(70000000.00, (float)$quarterly->gross_profit);  // 100M - 30M
        $this->assertEquals(20000000.00, (float)$quarterly->total_opex);     // 5M + 8M + 7M
        $this->assertEquals(50000000.00, (float)$quarterly->net_profit);    // 70M - 20M
        $this->assertEquals(20000000.00, (float)$quarterly->opening_cash_balance); // Month 1 opening
        $this->assertEquals(70000000.00, (float)$quarterly->ending_cash_balance);  // Month 3 ending
        $this->assertEquals(15000000.00, (float)$quarterly->cash_floor_target);    // Safety Cash Floor

        // Plafon Kas Aman = Ending Cash (70M) - Floor (15M) = 55M
        $this->assertEquals(55000000.00, (float)$quarterly->cash_surplus_above_floor);

        // Calculate Allocation with 20% Retained Earnings:
        // Net Profit = 50,000,000
        // Retained Earnings (20%) = 10,000,000
        // Available Profit = 40,000,000
        // Safe Cash Ceiling = 55,000,000
        // Distributable Profit = min(40M, 55M) = 40,000,000
        $allocation = $this->quarterlyClosingService->calculateAllocation($quarterly, 0.20);
        $this->assertEquals(10000000.00, $allocation['retained_earnings_amount']);
        $this->assertEquals(40000000.00, $allocation['distributable_profit']);

        // Check Founder Payouts:
        // Founder 1 (Titan Anggaraksa): 95% of 40M = 38,000,000
        // Founder 2 (Bima Jati): 5% of 40M = 2,000,000
        $shAlloc = collect($allocation['shareholders']);
        $titan = $shAlloc->firstWhere('name', 'Titan Anggaraksa');
        $bima = $shAlloc->firstWhere('name', 'Bima Jati');

        $this->assertEquals(38000000.00, $titan['payout_amount']);
        $this->assertEquals(2000000.00, $bima['payout_amount']);
    }

    /**
     * Test Cash Floor protection caps dividend when ending cash is insufficient.
     */
    public function test_cash_floor_caps_distributable_profit_when_cash_is_low(): void
    {
        // Quarterly net profit is high (40M), but ending cash is only 25M
        // Cash Floor = 15M -> Safe Cash Ceiling = 25M - 15M = 10M
        // Even though available profit (80% of 40M) = 32M, distributable profit is capped at 10M
        $closing = QuarterlyClosing::create([
            'year' => 2026,
            'quarter' => 2,
            'period_label' => '2026-Q2',
            'total_revenue' => 50000000,
            'total_cogs' => 5000000,
            'gross_profit' => 45000000,
            'total_opex' => 5000000,
            'net_profit' => 40000000,
            'net_profit_margin' => 80.0,
            'opening_cash_balance' => 10000000,
            'ending_cash_balance' => 25000000,
            'cash_floor_target' => 15000000,
            'cash_surplus_above_floor' => 10000000,
            'audit_status' => 'draft',
        ]);

        $allocation = $this->quarterlyClosingService->calculateAllocation($closing, 0.20);

        $this->assertEquals(10000000.00, $allocation['safe_cash_ceiling']);
        $this->assertEquals(32000000.00, $allocation['available_profit']);
        $this->assertEquals(10000000.00, $allocation['distributable_profit']); // strictly capped to 10M!
        $this->assertTrue($allocation['is_distribution_capped_by_cash']);
    }

    /**
     * Test full web workflow: RUPS index, generate, show, distribute, and mark payout as transferred with file upload.
     */
    public function test_web_rups_workflow_and_payout_receipt(): void
    {
        Storage::fake('public');

        // Prepare monthly closings for Q1
        MonthlyClosing::create([
            'period_month' => '2026-01',
            'opening_cash_balance' => 10000000,
            'total_revenue' => 20000000,
            'total_cogs' => 5000000,
            'gross_profit' => 15000000,
            'total_opex' => 3000000,
            'net_profit' => 12000000,
            'net_profit_margin' => 60.0,
            'cash_inflow' => 20000000,
            'cash_outflow' => 8000000,
            'ending_cash_balance' => 22000000,
            'audit_status' => 'approved',
        ]);
        MonthlyClosing::create([
            'period_month' => '2026-02',
            'opening_cash_balance' => 22000000,
            'total_revenue' => 20000000,
            'total_cogs' => 5000000,
            'gross_profit' => 15000000,
            'total_opex' => 3000000,
            'net_profit' => 12000000,
            'net_profit_margin' => 60.0,
            'cash_inflow' => 20000000,
            'cash_outflow' => 8000000,
            'ending_cash_balance' => 34000000,
            'audit_status' => 'approved',
        ]);
        MonthlyClosing::create([
            'period_month' => '2026-03',
            'opening_cash_balance' => 34000000,
            'total_revenue' => 20000000,
            'total_cogs' => 5000000,
            'gross_profit' => 15000000,
            'total_opex' => 3000000,
            'net_profit' => 12000000,
            'net_profit_margin' => 60.0,
            'cash_inflow' => 20000000,
            'cash_outflow' => 8000000,
            'ending_cash_balance' => 46000000,
            'audit_status' => 'approved',
        ]);

        // 1. Visit RUPS index
        $indexRes = $this->actingAs($this->financeUser)->get(route('finance.rups.index'));
        $indexRes->assertStatus(200);
        $indexRes->assertSee('RUPS Triwulanan');
        $indexRes->assertSee('Dividen Pendiri');
        $indexRes->assertSee('Rp 15.000.000');

        // 2. Generate Q1 consolidation via POST
        $generateRes = $this->actingAs($this->financeUser)->post(route('finance.rups.generate'), [
            'year' => 2026,
            'quarter' => 1,
        ]);
        $generateRes->assertRedirect();

        $closing = QuarterlyClosing::where('period_label', '2026-Q1')->first();
        $this->assertNotNull($closing);

        // 3. View detail & simulator page
        $showRes = $this->actingAs($this->financeUser)->get(route('finance.rups.show', $closing->id));
        $showRes->assertStatus(200);
        $showRes->assertSee('Evaluasi Kuartal 2026-Q1');
        $showRes->assertSee('Safety Cash Floor');
        $showRes->assertSee('Titan Anggaraksa');
        $showRes->assertSee('Bima Jati');

        // 4. Distribute profit (RUPS Decision) with 20% retained earnings
        $distributeRes = $this->actingAs($this->financeUser)->post(route('finance.rups.distribute', $closing->id), [
            'retained_earnings_percentage' => 20,
            'distribution_date' => '2026-04-05',
            'notes' => 'Disahkan dalam RUPS Triwulanan 1 Wirodev.',
        ]);
        $distributeRes->assertRedirect(route('finance.rups.show', $closing->id));

        $this->assertDatabaseHas('profit_distributions', [
            'quarterly_closing_id' => $closing->id,
            'status' => 'approved',
        ]);

        // 2 payouts should be created (one for each of the 2 founders)
        $this->assertEquals(2, DividendPayout::count());

        $firstPayout = DividendPayout::first();
        $this->assertEquals('pending', $firstPayout->payment_status);

        // 5. Mark payout as transferred with transfer receipt file upload
        $fakeFile = UploadedFile::fake()->create('bukti-transfer-bima.pdf', 500, 'application/pdf');

        $payoutRes = $this->actingAs($this->financeUser)->post(route('finance.rups.payouts.mark-paid', $firstPayout->id), [
            'payment_date' => '2026-04-06',
            'notes' => 'Transfer via BCA KlikBisnis ref #998822',
            'transfer_receipt' => $fakeFile,
        ]);
        $payoutRes->assertRedirect(route('finance.rups.show', $closing->id));

        $firstPayout->refresh();
        $this->assertEquals('transferred', $firstPayout->payment_status);
        $this->assertEquals('2026-04-06', $firstPayout->payment_date->toDateString());
        $this->assertNotNull($firstPayout->transfer_receipt_url);
        $this->assertStringContainsString('receipts/dividends', $firstPayout->transfer_receipt_url);
    }

    /**
     * Test clear ledger account labels for dual-role compensation (6000-05-01 and 5000-01-01).
     */
    public function test_dual_role_compensation_ledger_account_labels(): void
    {
        // 1. Labor log (COGS 5000-01-01)
        $client = \App\Models\Client::create(['name' => 'Client Alpha', 'email' => 'client@alpha.test']);
        $project = \App\Models\Project::create([
            'client_id' => $client->id,
            'title' => 'Alpha Core Platform',
            'project_code' => 'PRJ-ALPHA',
            'status' => 'in_progress',
            'start_date' => '2026-10-01',
        ]);

        \App\Models\ProjectWorkLog::create([
            'project_id' => $project->id,
            'user_id' => $this->financeUser->id,
            'work_date' => '2026-10-10',
            'hours_worked' => 20.0,
            'base_rate' => 150000,
            'calculated_nominal' => 3000000,
            'payment_status' => 'paid',
            'payment_date' => '2026-10-20',
        ]);

        // 2. Fixed Expense Payroll (OPEX 6000-05-01)
        $payrollExpense = \App\Models\FixedExpense::create([
            'name' => 'Gaji Pokok Direksi',
            'category' => 'PAYROLL',
            'default_amount' => 10000000,
            'due_day_of_month' => 25,
            'payment_method' => 'BCA KlikBisnis',
            'is_active' => true,
        ]);

        \App\Models\FixedExpenseRealization::create([
            'fixed_expense_id' => $payrollExpense->id,
            'month_period' => '2026-10',
            'amount_due' => 10000000,
            'paid_amount' => 10000000,
            'payment_status' => 'PAID',
            'payment_date' => '2026-10-25',
        ]);

        $closing = $this->monthlyClosingService->generateClosing('2026-10', 50000000);

        // Assert 5000-01-01 label
        $laborItem = $closing->items()->where('account_code', '5000-01-01')->first();
        $this->assertNotNull($laborItem);
        $this->assertEquals('HPP Tenaga Kerja Langsung: Honor Eksekusi Teknis Proyek', $laborItem->account_name);

        // Assert 6000-05-01 label
        $payrollItem = $closing->items()->where('account_code', '6000-05-01')->first();
        $this->assertNotNull($payrollItem);
        $this->assertEquals('Beban Tetap (PAYROLL): Gaji Pokok Struktural / Manajerial', $payrollItem->account_name);
    }

    /**
     * Test Quarterly Closings route alias (/finance/quarterly-closings).
     */
    public function test_quarterly_closings_alias_routes(): void
    {
        $closing = QuarterlyClosing::create([
            'year' => 2026,
            'quarter' => 4,
            'period_label' => '2026-Q4',
            'total_revenue' => 80000000,
            'total_cogs' => 20000000,
            'gross_profit' => 60000000,
            'total_opex' => 15000000,
            'net_profit' => 45000000,
            'net_profit_margin' => 56.25,
            'opening_cash_balance' => 20000000,
            'ending_cash_balance' => 65000000,
            'cash_floor_target' => 15000000,
            'cash_surplus_above_floor' => 50000000,
            'audit_status' => 'draft',
        ]);

        $indexRes = $this->actingAs($this->financeUser)->get(route('finance.quarterly-closings.index'));
        $indexRes->assertStatus(200);

        $showRes = $this->actingAs($this->financeUser)->get(route('finance.quarterly-closings.show', $closing->id));
        $showRes->assertStatus(200);
        $showRes->assertSee('2026-Q4');
    }

    /**
     * Test full CRUD workflow for Shareholders (Pemegang Saham).
     */
    public function test_shareholders_crud_workflow(): void
    {
        // 1. Index page lists shareholders
        $res = $this->actingAs($this->financeUser)->get(route('finance.shareholders.index'));
        $res->assertStatus(200);
        $res->assertSee('Titan Anggaraksa');
        $res->assertSee('Bima Jati');
        $res->assertSee('95.00%');
        $res->assertSee('5.00%');

        // 2. Create new shareholder
        $createRes = $this->actingAs($this->financeUser)->post(route('finance.shareholders.store'), [
            'name' => 'Investor Baru',
            'email' => 'investor@wirodev.test',
            'phone' => '089988776655',
            'shares_count' => 10000,
            'ownership_percentage' => 10.00,
            'bank_name' => 'Permata Bank',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Investor Baru',
            'is_active' => '1',
        ]);
        $createRes->assertRedirect();
        $this->assertDatabaseHas('shareholders', [
            'name' => 'Investor Baru',
            'email' => 'investor@wirodev.test',
            'ownership_percentage' => 10.00,
        ]);

        $created = Shareholder::where('email', 'investor@wirodev.test')->first();

        // 3. Update existing shareholder
        $updateRes = $this->actingAs($this->financeUser)->put(route('finance.shareholders.update', $created->id), [
            'name' => 'Investor Strategis',
            'email' => 'investor@wirodev.test',
            'phone' => '089988776699',
            'shares_count' => 15000,
            'ownership_percentage' => 12.50,
            'bank_name' => 'CIMB Niaga',
            'bank_account_number' => '9876543210',
            'bank_account_holder' => 'Investor Strategis',
            'is_active' => '1',
        ]);
        $updateRes->assertRedirect();
        $this->assertDatabaseHas('shareholders', [
            'id' => $created->id,
            'name' => 'Investor Strategis',
            'bank_name' => 'CIMB Niaga',
            'ownership_percentage' => 12.50,
        ]);

        // 4. Delete shareholder
        $deleteRes = $this->actingAs($this->financeUser)->delete(route('finance.shareholders.destroy', $created->id));
        $deleteRes->assertRedirect();
        $this->assertDatabaseMissing('shareholders', [
            'id' => $created->id,
        ]);
    }

    /**
     * Test validation rules for creating/updating shareholders.
     */
    public function test_shareholder_validation_rules(): void
    {
        // Missing required fields
        $res = $this->actingAs($this->financeUser)->post(route('finance.shareholders.store'), []);
        $res->assertSessionHasErrors(['name', 'email', 'shares_count', 'ownership_percentage', 'bank_name', 'bank_account_number', 'bank_account_holder']);

        // Percentage out of bounds (> 100)
        $invalidPctRes = $this->actingAs($this->financeUser)->post(route('finance.shareholders.store'), [
            'name' => 'Invalid Shareholder',
            'email' => 'invalid@wirodev.test',
            'shares_count' => 5000,
            'ownership_percentage' => 150.00,
            'bank_name' => 'BCA',
            'bank_account_number' => '12345',
            'bank_account_holder' => 'Invalid',
        ]);
        $invalidPctRes->assertSessionHasErrors(['ownership_percentage']);
    }

    /**
     * Test RBAC protection on quarterly rups.
     */
    public function test_rbac_protection_on_quarterly_rups(): void
    {
        $closing = QuarterlyClosing::create([
            'year' => 2026,
            'quarter' => 3,
            'period_label' => '2026-Q3',
            'total_revenue' => 10000000,
            'total_cogs' => 2000000,
            'gross_profit' => 8000000,
            'total_opex' => 2000000,
            'net_profit' => 6000000,
            'net_profit_margin' => 60.0,
            'opening_cash_balance' => 10000000,
            'ending_cash_balance' => 16000000,
            'cash_floor_target' => 15000000,
            'cash_surplus_above_floor' => 1000000,
            'audit_status' => 'draft',
        ]);

        // Staff user without finance.rups.view or finance.rups.manage
        $response = $this->actingAs($this->staffUser)->get(route('finance.rups.index'));
        $response->assertStatus(403);

        $generateResponse = $this->actingAs($this->staffUser)->post(route('finance.rups.generate'), [
            'year' => 2026,
            'quarter' => 3,
        ]);
        $generateResponse->assertStatus(403);
    }
}
