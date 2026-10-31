<?php

namespace Tests\Feature\Finance;

use App\Models\Client;
use App\Models\FixedExpense;
use App\Models\FixedExpenseRealization;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Services\Finance\FixedExpenseService;
use App\Services\Finance\MonthlyClosingService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected User $finance;
    protected User $staff;
    protected FixedExpenseService $expenseService;
    protected MonthlyClosingService $closingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->expenseService = app(FixedExpenseService::class);
        $this->closingService = app(MonthlyClosingService::class);

        $this->superadmin = User::factory()->create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@wirodev.test',
            'is_active' => true,
        ]);
        $this->superadmin->syncRoles(['superadmin']);

        $this->finance = User::factory()->create([
            'name' => 'Finance Specialist',
            'email' => 'finance@wirodev.test',
            'is_active' => true,
        ]);
        $this->finance->syncRoles(['finance']);

        $this->staff = User::factory()->create([
            'name' => 'Junior Developer',
            'email' => 'staff@wirodev.test',
            'is_active' => true,
        ]);
        $this->staff->syncRoles(['staff']);
    }

    public function test_can_list_fixed_expenses_with_category_and_active_filters(): void
    {
        FixedExpense::create([
            'name' => 'PLN Office Electricity',
            'category' => 'UTILITY',
            'amount' => 1500000,
            'due_day_of_month' => 20,
            'payment_method' => 'Bank Transfer',
            'is_active' => true,
        ]);

        FixedExpense::create([
            'name' => 'GitHub Enterprise & Copilot',
            'category' => 'SUBSCRIPTION',
            'amount' => 2500000,
            'due_day_of_month' => 5,
            'payment_method' => 'Credit Card',
            'is_active' => true,
        ]);

        FixedExpense::create([
            'name' => 'Old Legacy Office Rent',
            'category' => 'OPEX',
            'amount' => 5000000,
            'due_day_of_month' => 1,
            'payment_method' => 'Bank Transfer',
            'is_active' => false,
        ]);

        // 1. List all
        $resAll = $this->actingAs($this->finance)->getJson('/api/v1/fixed-expenses');
        $resAll->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');

        // 2. Filter by category
        $resCat = $this->actingAs($this->finance)->getJson('/api/v1/fixed-expenses?category=UTILITY');
        $resCat->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'PLN Office Electricity');

        // 3. Filter by active
        $resActive = $this->actingAs($this->finance)->getJson('/api/v1/fixed-expenses?is_active=1');
        $resActive->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $resInactive = $this->actingAs($this->finance)->getJson('/api/v1/fixed-expenses?is_active=0');
        $resInactive->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Old Legacy Office Rent');
    }

    public function test_can_create_fixed_expense_with_valid_data(): void
    {
        $payload = [
            'name' => 'AWS Cloud Infrastructure',
            'category' => 'INFRASTRUCTURE',
            'amount' => 7500000,
            'due_day_of_month' => 12,
            'payment_method' => 'Credit Card',
            'vendor' => 'Amazon Web Services Inc.',
            'is_active' => true,
            'notes' => 'Dedicated production cluster & RDS PostgreSQL.',
        ];

        $response = $this->actingAs($this->finance)->postJson('/api/v1/fixed-expenses', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'AWS Cloud Infrastructure')
            ->assertJsonPath('data.category', 'INFRASTRUCTURE')
            ->assertJsonPath('data.due_day_of_month', 12);

        $this->assertDatabaseHas('fixed_expenses', [
            'name' => 'AWS Cloud Infrastructure',
            'category' => 'INFRASTRUCTURE',
            'due_day_of_month' => 12,
        ]);
    }

    public function test_validation_errors_for_amount_and_due_day_of_month(): void
    {
        // Amount <= 0 and due_day_of_month outside 1-31
        $payload = [
            'name' => 'Invalid Expense',
            'category' => 'OPEX',
            'amount' => -5000,
            'due_day_of_month' => 32,
            'payment_method' => 'Bank Transfer',
        ];

        $resInvalid = $this->actingAs($this->finance)->postJson('/api/v1/fixed-expenses', $payload);
        $resInvalid->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'due_day_of_month']);

        // Amount equal to 0
        $resZeroAmount = $this->actingAs($this->finance)->postJson('/api/v1/fixed-expenses', [
            'name' => 'Zero Amount Expense',
            'category' => 'OPEX',
            'amount' => 0,
            'due_day_of_month' => 0,
            'payment_method' => 'Bank Transfer',
        ]);
        $resZeroAmount->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'due_day_of_month']);
    }

    public function test_can_show_update_and_delete_fixed_expense(): void
    {
        $expense = FixedExpense::create([
            'name' => 'Internet Dedicated Office',
            'category' => 'UTILITY',
            'amount' => 2000000,
            'due_day_of_month' => 10,
            'payment_method' => 'Bank Transfer',
            'vendor' => 'Biznet',
        ]);

        // Show
        $showRes = $this->actingAs($this->finance)->getJson("/api/v1/fixed-expenses/{$expense->id}");
        $showRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $expense->id);

        // Update
        $updateRes = $this->actingAs($this->finance)->putJson("/api/v1/fixed-expenses/{$expense->id}", [
            'name' => 'Internet Dedicated Office 300 Mbps',
            'amount' => 2500000,
            'due_day_of_month' => 15,
        ]);
        $updateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Internet Dedicated Office 300 Mbps')
            ->assertJsonPath('data.amount', '2500000.00');

        $this->assertDatabaseHas('fixed_expenses', [
            'id' => $expense->id,
            'name' => 'Internet Dedicated Office 300 Mbps',
            'due_day_of_month' => 15,
        ]);

        // Delete (Soft Delete)
        $deleteRes = $this->actingAs($this->finance)->deleteJson("/api/v1/fixed-expenses/{$expense->id}");
        $deleteRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('fixed_expenses', ['id' => $expense->id]);
    }

    public function test_monthly_generator_does_not_create_duplicate_records_for_same_period(): void
    {
        $exp1 = FixedExpense::create([
            'name' => 'SaaS Project Management',
            'category' => 'SUBSCRIPTION',
            'amount' => 1200000,
            'due_day_of_month' => 10,
            'payment_method' => 'Credit Card',
            'is_active' => true,
        ]);

        $exp2 = FixedExpense::create([
            'name' => 'Office Drinking Water & Cleaning',
            'category' => 'OPEX',
            'amount' => 800000,
            'due_day_of_month' => 20,
            'payment_method' => 'Corporate Cash',
            'is_active' => true,
        ]);

        FixedExpense::create([
            'name' => 'Inactive Historical Expense',
            'category' => 'OPEX',
            'amount' => 500000,
            'due_day_of_month' => 1,
            'payment_method' => 'Corporate Cash',
            'is_active' => false,
        ]);

        $month = '2026-11';

        // 1. First cycle generation
        $resCycle1 = $this->actingAs($this->finance)->postJson("/api/v1/fixed-expenses/generate-cycle?month={$month}");
        $resCycle1->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        $this->assertDatabaseCount('fixed_expense_realizations', 2);

        // 2. Second cycle generation for same month period
        $resCycle2 = $this->actingAs($this->finance)->postJson("/api/v1/fixed-expenses/generate-cycle?month={$month}");
        $resCycle2->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        // Must still be 2, NO duplicates
        $this->assertDatabaseCount('fixed_expense_realizations', 2);
    }

    public function test_overdue_status_evaluated_for_past_due_date(): void
    {
        $exp = FixedExpense::create([
            'name' => 'Past Month Utility',
            'category' => 'UTILITY',
            'amount' => 1000000,
            'due_day_of_month' => 5,
            'payment_method' => 'Bank Transfer',
            'is_active' => true,
        ]);

        // Generate for past month
        $realizationsPast = $this->expenseService->generateMonthlyRealizations('2026-01');
        $this->assertEquals('OVERDUE', $realizationsPast->first()->payment_status);

        // Generate for distant future month
        $realizationsFuture = $this->expenseService->generateMonthlyRealizations('2030-12');
        $this->assertEquals('PENDING', $realizationsFuture->first()->payment_status);
    }

    public function test_mark_as_paid_and_monthly_summary_calculation(): void
    {
        $exp1 = FixedExpense::create([
            'name' => 'Server Cloud Hosting',
            'category' => 'INFRASTRUCTURE',
            'amount' => 3000000,
            'due_day_of_month' => 10,
            'payment_method' => 'Credit Card',
            'is_active' => true,
        ]);

        $exp2 = FixedExpense::create([
            'name' => 'Office Hub Rent',
            'category' => 'OPEX',
            'amount' => 7000000,
            'due_day_of_month' => 25,
            'payment_method' => 'Bank Transfer',
            'is_active' => true,
        ]);

        $month = '2026-10';

        // Generate realizations
        $this->expenseService->generateMonthlyRealizations($month);

        $realization1 = FixedExpenseRealization::where('fixed_expense_id', $exp1->id)
            ->where('month_period', $month)
            ->first();

        // Mark exp1 as PAID
        $markPaidRes = $this->actingAs($this->finance)->postJson(
            "/api/v1/fixed-expenses/realizations/{$realization1->id}/mark-paid",
            [
                'amount' => 3000000,
                'payment_date' => '2026-10-10 14:00:00',
                'receipt_url' => 'https://storage.wirodev.test/proofs/server-october.pdf',
                'notes' => 'Lunas via CC Perusahaan BCA',
            ]
        );

        $markPaidRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_status', 'PAID')
            ->assertJsonPath('data.paid_amount', '3000000.00')
            ->assertJsonPath('data.receipt_url', 'https://storage.wirodev.test/proofs/server-october.pdf');

        // Monthly Summary
        $summaryRes = $this->actingAs($this->finance)->getJson("/api/v1/fixed-expenses/monthly-summary?month={$month}");
        $summaryRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.projected_budget', 10000000)
            ->assertJsonPath('data.realized_expenditure', 3000000)
            ->assertJsonPath('data.pending_or_overdue_amount', 7000000)
            ->assertJsonPath('data.completion_rate', 30)
            ->assertJsonPath('data.paid_count', 1);
    }

    public function test_fixed_expenses_integrate_with_monthly_closing_service(): void
    {
        $period = '2026-10';

        // 1. Create Fixed Expenses & Mark Paid Realization
        $fixedExp = FixedExpense::create([
            'name' => 'Figma & JetBrains Enterprise Licenses',
            'category' => 'SUBSCRIPTION',
            'amount' => 4000000,
            'due_day_of_month' => 5,
            'payment_method' => 'Credit Card',
            'is_active' => true,
        ]);

        $realization = FixedExpenseRealization::create([
            'fixed_expense_id' => $fixedExp->id,
            'month_period' => $period,
            'paid_amount' => 4000000,
            'payment_date' => '2026-10-05 10:00:00',
            'payment_status' => 'PAID',
            'receipt_url' => 'https://storage.wirodev.test/receipts/jetbrains-oct.pdf',
        ]);

        // 2. Create Revenue (Paid Invoice)
        $client = Client::create([
            'name' => 'PT Wiro Mitra Mandiri',
            'email' => 'finance@mandiri.test',
        ]);

        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Sistem Rekonsiliasi Keuangan',
            'project_code' => 'PRJ-2026-099',
            'status' => 'in_progress',
        ]);

        $invoice = Invoice::create([
            'project_id' => $project->id,
            'invoice_number' => 'INV/2026/10/001',
            'type' => 'project',
            'subtotal' => 20000000,
            'tax' => 0,
            'total_amount' => 20000000,
            'issued_date' => '2026-10-01',
            'status' => 'paid',
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'payment_date' => '2026-10-08',
            'amount' => 20000000,
            'payment_method' => 'Mandiri Virtual Account',
        ]);

        // 3. Generate Monthly Closing
        $closing = $this->closingService->generateClosing(
            periodMonth: $period,
            openingBalance: 15000000,
            auditorNotes: 'Closing dengan integrasi pengeluaran tetap bulanan.',
            auditedBy: $this->finance->id
        );

        // 4. Assertions
        // Total OPEX must include the 4,000,000 fixed expense
        $this->assertEquals(4000000.00, (float)$closing->total_opex);
        $this->assertEquals(16000000.00, (float)$closing->net_profit); // 20M revenue - 0 cogs - 4M opex = 16M
        $this->assertEquals(31000000.00, (float)$closing->ending_cash_balance); // 15M opening + 20M inflow - 4M outflow = 31M

        // Verify ledger item exists for fixed expense
        $this->assertDatabaseHas('monthly_closing_items', [
            'closing_id' => $closing->id,
            'category' => 'opex',
            'account_code' => '6000-04-01',
            'debit' => 4000000.00,
        ]);
    }

    public function test_rbac_permissions_enforced_for_fixed_expenses(): void
    {
        // 1. Unauthenticated request -> 401
        $resUnauth = $this->getJson('/api/v1/fixed-expenses');
        $resUnauth->assertStatus(401);

        // 2. Staff without permission -> 403
        $resStaff = $this->actingAs($this->staff)->getJson('/api/v1/fixed-expenses');
        $resStaff->assertStatus(403);

        $resStaffPost = $this->actingAs($this->staff)->postJson('/api/v1/fixed-expenses', [
            'name' => 'Forbidden Expense',
            'category' => 'OPEX',
            'amount' => 1000000,
            'due_day_of_month' => 10,
            'payment_method' => 'Bank Transfer',
        ]);
        $resStaffPost->assertStatus(403);

        // 3. Superadmin bypasses permission checks -> 200
        $resSuper = $this->actingAs($this->superadmin)->getJson('/api/v1/fixed-expenses');
        $resSuper->assertStatus(200);

        // 4. Finance specialist with permission -> 200
        $resFinance = $this->actingAs($this->finance)->getJson('/api/v1/fixed-expenses');
        $resFinance->assertStatus(200);
    }
}
