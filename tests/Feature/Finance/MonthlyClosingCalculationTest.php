<?php

namespace Tests\Feature\Finance;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\MarketingCampaign;
use App\Models\MonthlyClosing;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\ProjectWorkLog;
use App\Models\TrackerAccount;
use App\Models\TrackerCategory;
use App\Models\TrackerExpense;
use App\Models\User;
use App\Services\Finance\MonthlyClosingService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyClosingCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected User $financeUser;
    protected Project $project;
    protected User $developer;
    protected MonthlyClosingService $closingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->closingService = app(MonthlyClosingService::class);

        $this->financeUser = User::factory()->create([
            'name' => 'Finance Specialist',
            'email' => 'fin@wirodev.test',
            'is_active' => true,
        ]);
        $this->financeUser->syncRoles(['finance']);

        $this->developer = User::factory()->create([
            'name' => 'Backend Dev',
            'email' => 'dev2@wirodev.test',
            'is_active' => true,
        ]);
        $this->developer->syncRoles(['staff']);

        $client = Client::create([
            'name' => 'PT Solusi Finansial',
            'email' => 'contact@solusi.test',
            'phone' => '08234567890',
        ]);

        $this->project = Project::create([
            'client_id' => $client->id,
            'title' => 'Portal Finansial Core',
            'project_code' => 'PRJ-2026-002',
            'status' => 'in_progress',
            'start_date' => '2026-10-01',
        ]);
    }

    public function test_monthly_closing_financial_formula_and_reconciliation(): void
    {
        $period = '2026-10';

        // 1. REVENUE: Paid invoice payment of Rp 50,000,000 on 2026-10-15
        $invoice = Invoice::create([
            'project_id' => $this->project->id,
            'invoice_number' => 'INV/WIRODEV/2026/001/01',
            'type' => 'project',
            'subtotal' => 50000000,
            'tax' => 0,
            'total_amount' => 50000000,
            'issued_date' => '2026-10-10',
            'status' => 'paid',
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'payment_date' => '2026-10-15',
            'amount' => 50000000,
            'payment_method' => 'BCA Transfer',
        ]);

        // 2. COGS
        // A. Direct Project Labor (Paid): 40 hours @ 200,000 = Rp 8,000,000
        ProjectWorkLog::create([
            'project_id' => $this->project->id,
            'user_id' => $this->developer->id,
            'work_date' => '2026-10-12',
            'hours_worked' => 40.0,
            'base_rate' => 200000,
            'calculated_nominal' => 8000000,
            'is_weekend' => false,
            'is_overtime' => false,
            'payment_status' => 'paid',
            'payment_date' => '2026-10-25',
        ]);

        // B. Infrastructure & Server Expense: Rp 2,000,000 (Paid)
        ProjectExpense::create([
            'project_id' => $this->project->id,
            'description' => 'AWS Cloud & Dedicated Server',
            'amount' => 2000000,
            'is_paid' => true,
            'date' => '2026-10-05',
        ]);

        // Total COGS = 8,000,000 (labor) + 2,000,000 (infra) = 10,000,000

        // 3. OPEX
        // A. Marketing ad spend: Rp 5,000,000
        MarketingCampaign::create([
            'platform' => 'meta_ads',
            'campaign_name' => 'Corporate Branding October',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'ad_spend' => 5000000,
            'messages_count' => 100,
            'deals_count' => 5,
            'total_revenue_generated' => 50000000,
        ]);

        // B. Operational tracker expense: Rp 3,000,000 (Utilities)
        $account = TrackerAccount::create(['name' => 'Company Main Bank', 'type' => 'company', 'balance' => 100000000]);
        $category = TrackerCategory::create(['name' => 'Utilities & Office', 'type' => 'company']);
        TrackerExpense::create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'company',
            'amount' => 3000000,
            'description' => 'PLN Electricity & Internet Fiber',
            'expense_date' => '2026-10-18 10:00:00',
        ]);

        // Total OPEX = 5,000,000 (marketing) + 3,000,000 (operational) = 8,000,000

        // 4. Run Monthly Closing Engine with Opening Cash Balance of Rp 20,000,000
        $openingCash = 20000000.00;
        $closing = $this->closingService->generateClosing(
            periodMonth: $period,
            openingBalance: $openingCash,
            auditorNotes: 'Audit bulanan Oktober 2026 berjalan normal.',
            auditedBy: $this->financeUser->id
        );

        // Assertions:
        // Revenue = 50,000,000
        $this->assertEquals(50000000.00, (float)$closing->total_revenue);

        // COGS = 10,000,000
        $this->assertEquals(10000000.00, (float)$closing->total_cogs);

        // Gross Profit = Revenue - COGS = 50,000,000 - 10,000,000 = 40,000,000
        $this->assertEquals(40000000.00, (float)$closing->gross_profit);

        // OPEX = 8,000,000
        $this->assertEquals(8000000.00, (float)$closing->total_opex);

        // Net Profit = Gross Profit - OPEX = 40,000,000 - 8,000,000 = 32,000,000
        $this->assertEquals(32000000.00, (float)$closing->net_profit);

        // Net Profit Margin = (32,000,000 / 50,000,000) * 100 = 64.0%
        $this->assertEquals(64.0, (float)$closing->net_profit_margin);

        // Cash Inflow = 50,000,000, Cash Outflow = 10,000,000 (cogs) + 8,000,000 (opex) = 18,000,000
        $this->assertEquals(50000000.00, (float)$closing->cash_inflow);
        $this->assertEquals(18000000.00, (float)$closing->cash_outflow);

        // Ending Balance = Opening + Inflows - Outflows = 20,000,000 + 50,000,000 - 18,000,000 = 52,000,000
        $this->assertEquals(52000000.00, (float)$closing->ending_cash_balance);

        // Assert Cash Flow reconciles
        $this->assertTrue($closing->isReconciled());

        // Check Monthly Closing Items ledger generation
        $this->assertTrue($closing->items()->where('account_code', '4000-01-01')->exists()); // Revenue
        $this->assertTrue($closing->items()->where('account_code', '5000-01-01')->exists()); // Labor COGS
        $this->assertTrue($closing->items()->where('account_code', '5000-02-01')->exists()); // Infra COGS
        $this->assertTrue($closing->items()->where('account_code', '6000-01-01')->exists()); // Marketing OPEX
        $this->assertTrue($closing->items()->where('account_code', '6000-02-01')->exists()); // Utility OPEX
        $this->assertTrue($closing->items()->where('account_code', '1000-03-01')->exists()); // Ending Cash Mutation
    }

    public function test_audit_closing_workflow_and_approval(): void
    {
        $closing = MonthlyClosing::create([
            'period_month' => '2026-11',
            'opening_cash_balance' => 52000000,
            'total_revenue' => 20000000,
            'total_cogs' => 5000000,
            'gross_profit' => 15000000,
            'total_opex' => 3000000,
            'net_profit' => 12000000,
            'net_profit_margin' => 60.0,
            'cash_inflow' => 20000000,
            'cash_outflow' => 8000000,
            'ending_cash_balance' => 64000000,
            'audit_status' => 'draft',
        ]);

        $response = $this->actingAs($this->financeUser)->post(route('finance.closings.audit', $closing), [
            'audit_status' => 'approved',
            'auditor_notes' => 'Disetujui penuh oleh finance dan manajemen.',
        ]);

        $response->assertRedirect();
        $this->assertEquals('approved', $closing->fresh()->audit_status);
        $this->assertEquals($this->financeUser->id, $closing->fresh()->approved_by);
    }
}
