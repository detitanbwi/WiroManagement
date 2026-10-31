<?php

namespace Tests\Feature\Finance;

use App\Models\FixedExpense;
use App\Models\FixedExpenseRealization;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FixedExpenseWebUiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected User $finance;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

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
            'name' => 'Junior Staff',
            'email' => 'staff@wirodev.test',
            'is_active' => true,
        ]);
        $this->staff->syncRoles(['staff']);
    }

    public function test_finance_user_can_access_fixed_expenses_web_index(): void
    {
        FixedExpense::create([
            'name' => 'Office Internet Fiber 1 Gbps',
            'category' => 'UTILITY',
            'amount' => 1500000,
            'due_day_of_month' => 15,
            'payment_method' => 'Bank Transfer',
            'vendor' => 'Biznet',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->finance)->get(route('finance.fixed-expenses.index'));

        $response->assertStatus(200);
        $response->assertSee('Pengeluaran Tetap Bulanan');
        $response->assertSee('Office Internet Fiber 1 Gbps');
        $response->assertSee('Checklist Realisasi Tagihan Bulan Ini');
        $response->assertSee('Master Pos Biaya Tetap');
    }

    public function test_can_create_master_fixed_expense_via_web_form(): void
    {
        $payload = [
            'name' => 'Google Workspace Business Plus',
            'category' => 'SUBSCRIPTION',
            'amount' => 3200000,
            'due_day_of_month' => 8,
            'payment_method' => 'Credit Card',
            'vendor' => 'Google Cloud',
            'is_active' => '1',
            'notes' => 'Langganan 25 akun tim.',
        ];

        $response = $this->actingAs($this->finance)->post(route('finance.fixed-expenses.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('fixed_expenses', [
            'name' => 'Google Workspace Business Plus',
            'category' => 'SUBSCRIPTION',
            'due_day_of_month' => 8,
            'vendor' => 'Google Cloud',
        ]);
    }

    public function test_can_update_and_delete_fixed_expense_via_web(): void
    {
        $expense = FixedExpense::create([
            'name' => 'Meeting Room Coworking',
            'category' => 'OPEX',
            'amount' => 1000000,
            'due_day_of_month' => 5,
            'payment_method' => 'Corporate Cash',
            'is_active' => true,
        ]);

        // Update
        $updateResponse = $this->actingAs($this->finance)->put(
            route('finance.fixed-expenses.update', $expense),
            [
                'name' => 'Meeting Room Coworking Premium',
                'category' => 'OPEX',
                'amount' => 1250000,
                'due_day_of_month' => 10,
                'payment_method' => 'Corporate Cash',
                'is_active' => '1',
            ]
        );

        $updateResponse->assertRedirect();
        $updateResponse->assertSessionHas('success');

        $this->assertDatabaseHas('fixed_expenses', [
            'id' => $expense->id,
            'name' => 'Meeting Room Coworking Premium',
            'due_day_of_month' => 10,
        ]);

        // Delete
        $deleteResponse = $this->actingAs($this->finance)->delete(
            route('finance.fixed-expenses.destroy', $expense)
        );

        $deleteResponse->assertRedirect();
        $deleteResponse->assertSessionHas('success');

        $this->assertSoftDeleted('fixed_expenses', ['id' => $expense->id]);
    }

    public function test_can_trigger_monthly_cycle_generation_from_web(): void
    {
        FixedExpense::create([
            'name' => 'Jira & Confluence Cloud',
            'category' => 'SUBSCRIPTION',
            'amount' => 2000000,
            'due_day_of_month' => 12,
            'payment_method' => 'Credit Card',
            'is_active' => true,
        ]);

        $month = '2026-11';

        $response = $this->actingAs($this->finance)->post(route('finance.fixed-expenses.generate-cycle'), [
            'month' => $month,
        ]);

        $response->assertRedirect(route('finance.fixed-expenses.index', ['month' => $month]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('fixed_expense_realizations', [
            'month_period' => $month,
            'payment_status' => 'PENDING',
        ]);
    }

    public function test_can_mark_realization_as_paid_with_receipt_upload_from_web(): void
    {
        Storage::fake('public');

        $expense = FixedExpense::create([
            'name' => 'Office Electricity PLN',
            'category' => 'UTILITY',
            'amount' => 2500000,
            'due_day_of_month' => 20,
            'payment_method' => 'Bank Transfer',
            'is_active' => true,
        ]);

        $realization = FixedExpenseRealization::create([
            'fixed_expense_id' => $expense->id,
            'month_period' => '2026-10',
            'paid_amount' => 0,
            'payment_status' => 'PENDING',
        ]);

        $fakeFile = UploadedFile::fake()->create('bukti-bayar-pln.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->finance)->post(
            route('finance.fixed-expenses.mark-paid', $realization),
            [
                'amount' => 2500000,
                'payment_date' => '2026-10-18',
                'receipt_file' => $fakeFile,
                'notes' => 'Dibayar via m-Banking BCA Corporate',
            ]
        );

        $response->assertRedirect(route('finance.fixed-expenses.index', ['month' => '2026-10']));
        $response->assertSessionHas('success');

        $realization->refresh();
        $this->assertEquals('PAID', $realization->payment_status);
        $this->assertEquals(2500000, (float)$realization->paid_amount);
        $this->assertNotNull($realization->receipt_url);
        $this->assertStringContainsString('/storage/receipts/', $realization->receipt_url);
    }

    public function test_rbac_permissions_prevent_unauthorized_access_to_fixed_expenses_web(): void
    {
        // 1. Guest redirected to login
        $resGuest = $this->get(route('finance.fixed-expenses.index'));
        $resGuest->assertRedirect(route('login'));

        // 2. Staff without permission denied with 403
        $resStaff = $this->actingAs($this->staff)->get(route('finance.fixed-expenses.index'));
        $resStaff->assertStatus(403);

        $resStaffPost = $this->actingAs($this->staff)->post(route('finance.fixed-expenses.store'), [
            'name' => 'Unauthorized Expense',
            'category' => 'OPEX',
            'amount' => 100000,
            'due_day_of_month' => 1,
            'payment_method' => 'Cash',
        ]);
        $resStaffPost->assertStatus(403);

        // 3. Superadmin has full access
        $resSuper = $this->actingAs($this->superadmin)->get(route('finance.fixed-expenses.index'));
        $resSuper->assertStatus(200);
    }
}
