<?php

namespace Tests\Feature\Finance;

use App\Models\Client;
use App\Models\MonthlyClosing;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialRolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected User $finance;
    protected User $pm;
    protected User $staff;
    protected User $clientUser;
    protected MonthlyClosing $closing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->superadmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'super@wirodev.test',
            'is_active' => true,
        ]);
        $this->superadmin->syncRoles(['superadmin']);

        $this->finance = User::factory()->create([
            'name' => 'Finance Staff',
            'email' => 'finance_user@wirodev.test',
            'is_active' => true,
        ]);
        $this->finance->syncRoles(['finance']);

        $this->pm = User::factory()->create([
            'name' => 'Project Manager',
            'email' => 'pm_user@wirodev.test',
            'is_active' => true,
        ]);
        $this->pm->syncRoles(['pm']);

        $this->staff = User::factory()->create([
            'name' => 'Staff Developer',
            'email' => 'staff_user@wirodev.test',
            'is_active' => true,
        ]);
        $this->staff->syncRoles(['staff']);

        $this->clientUser = User::factory()->create([
            'name' => 'External Client',
            'email' => 'client@external.test',
            'is_active' => true,
        ]);
        $this->clientUser->syncRoles(['client']);

        $this->closing = MonthlyClosing::create([
            'period_month' => '2026-10',
            'opening_cash_balance' => 10000000,
            'total_revenue' => 30000000,
            'total_cogs' => 5000000,
            'gross_profit' => 25000000,
            'total_opex' => 5000000,
            'net_profit' => 20000000,
            'net_profit_margin' => 66.67,
            'cash_inflow' => 30000000,
            'cash_outflow' => 10000000,
            'ending_cash_balance' => 30000000,
            'audit_status' => 'approved',
        ]);
    }

    public function test_superadmin_can_access_all_financial_modules(): void
    {
        $this->actingAs($this->superadmin)->get(route('finance.work-logs.index'))->assertStatus(200);
        $this->actingAs($this->superadmin)->get(route('finance.rates.index'))->assertStatus(200);
        $this->actingAs($this->superadmin)->get(route('finance.marketing.index'))->assertStatus(200);
        $this->actingAs($this->superadmin)->get(route('finance.closings.index'))->assertStatus(200);
        $this->actingAs($this->superadmin)->get(route('finance.closings.show', $this->closing))->assertStatus(200);
        $this->actingAs($this->superadmin)->get(route('finance.closings.pdf', $this->closing))->assertStatus(200);
    }

    public function test_finance_role_can_access_work_logs_rates_closings_and_pdf(): void
    {
        $this->actingAs($this->finance)->get(route('finance.work-logs.index'))->assertStatus(200);
        $this->actingAs($this->finance)->get(route('finance.rates.index'))->assertStatus(200);
        $this->actingAs($this->finance)->get(route('finance.marketing.index'))->assertStatus(200);
        $this->actingAs($this->finance)->get(route('finance.closings.index'))->assertStatus(200);
        $this->actingAs($this->finance)->get(route('finance.closings.show', $this->closing))->assertStatus(200);
        $this->actingAs($this->finance)->get(route('finance.closings.pdf', $this->closing))->assertStatus(200);
    }

    public function test_pm_can_access_work_logs_but_cannot_access_closings(): void
    {
        // PM has finance.work_logs.view and finance.work_logs.manage
        $this->actingAs($this->pm)->get(route('finance.work-logs.index'))->assertStatus(200);
        $this->actingAs($this->pm)->get(route('finance.rates.index'))->assertStatus(200);

        // PM cannot access closings or marketing
        $this->actingAs($this->pm)->get(route('finance.closings.index'))->assertStatus(403);
        $this->actingAs($this->pm)->get(route('finance.closings.show', $this->closing))->assertStatus(403);
        $this->actingAs($this->pm)->get(route('finance.marketing.index'))->assertStatus(403);
    }

    public function test_unauthorized_staff_cannot_view_financial_closings_or_rates(): void
    {
        // Regular staff developer cannot access financial closings or rates
        $this->actingAs($this->staff)->get(route('finance.closings.index'))->assertStatus(403);
        $this->actingAs($this->staff)->get(route('finance.closings.show', $this->closing))->assertStatus(403);
        $this->actingAs($this->staff)->get(route('finance.rates.index'))->assertStatus(403);
        $this->actingAs($this->staff)->get(route('finance.closings.pdf', $this->closing))->assertStatus(403);
    }

    public function test_client_role_is_strictly_prohibited_from_financial_engine(): void
    {
        // External client cannot access any internal finance module via JSON API (returns 403)
        $this->actingAs($this->clientUser)->getJson(route('finance.closings.index'))->assertStatus(403);
        $this->actingAs($this->clientUser)->getJson(route('finance.work-logs.index'))->assertStatus(403);
        $this->actingAs($this->clientUser)->getJson(route('finance.rates.index'))->assertStatus(403);
        $this->actingAs($this->clientUser)->getJson(route('finance.marketing.index'))->assertStatus(403);

        // Via standard web requests, EnsureInternalUser logs out client and redirects to login
        $response = $this->actingAs($this->clientUser)->get(route('finance.closings.index'));
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
