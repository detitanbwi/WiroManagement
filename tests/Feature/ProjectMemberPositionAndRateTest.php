<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ManHourRate;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectMemberRate;
use App\Models\ProjectWorkLog;
use App\Models\Role;
use App\Models\User;
use App\Services\Finance\LaborCostCalculatorService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMemberPositionAndRateTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $financeUser;
    protected User $budi;
    protected Project $projectA;
    protected Project $projectB;
    protected LaborCostCalculatorService $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->calculator = app(LaborCostCalculatorService::class);

        // Admin & Finance users
        $this->adminUser = User::factory()->create([
            'name' => 'Project Manager Lead',
            'email' => 'pm@wirodev.test',
            'is_active' => true,
        ]);
        $this->adminUser->syncRoles(['admin', 'pm']);

        $this->financeUser = User::factory()->create([
            'name' => 'Finance Manager',
            'email' => 'finance@wirodev.test',
            'is_active' => true,
        ]);
        $this->financeUser->syncRoles(['finance']);

        // Budi with global system role 'staff'
        $this->budi = User::factory()->create([
            'name' => 'Budi Staff',
            'email' => 'budi@wirodev.test',
            'is_active' => true,
        ]);
        $this->budi->syncRoles(['staff']);

        // Clients and Projects
        $client = Client::create([
            'name' => 'PT Mitra Global',
            'email' => 'client@mitra.test',
            'phone' => '0812999888',
        ]);

        $this->projectA = Project::create([
            'client_id' => $client->id,
            'title' => 'Proyek A - E-Commerce',
            'project_code' => 'PRJ-A',
            'status' => 'in_progress',
            'contract_value' => 50000000,
            'start_date' => '2026-10-01',
        ]);

        $this->projectB = Project::create([
            'client_id' => $client->id,
            'title' => 'Proyek B - Core Banking',
            'project_code' => 'PRJ-B',
            'status' => 'in_progress',
            'contract_value' => 80000000,
            'start_date' => '2026-10-01',
        ]);

        // Seed dynamic Master Rate benchmarks
        ManHourRate::create([
            'role_slug' => 'Programmer',
            'skill_level' => 'Mid Engineer',
            'base_rate_per_hour' => 85000,
            'weekend_multiplier' => 1.10,
            'overtime_multiplier' => 1.20,
            'description' => 'Programmer benchmark',
        ]);

        ManHourRate::create([
            'role_slug' => 'System Analyst',
            'skill_level' => 'Senior Specialist',
            'base_rate_per_hour' => 120000,
            'weekend_multiplier' => 1.10,
            'overtime_multiplier' => 1.20,
            'description' => 'System Analyst benchmark',
        ]);
    }

    public function test_budi_can_have_different_project_positions_and_rates_across_projects(): void
    {
        // 1. Assign Budi to Project A as Programmer @ Rp 85.000/hr
        $responseA = $this->actingAs($this->adminUser)->post(route('projects.members.store', $this->projectA), [
            'user_id' => $this->budi->id,
            'role_in_project' => 'Programmer',
            'rate_per_hour' => 85000,
            'roles' => ['staff'],
        ]);
        $responseA->assertSessionHasNoErrors();

        // 2. Assign Budi to Project B as System Analyst @ Rp 120.000/hr
        $responseB = $this->actingAs($this->adminUser)->post(route('projects.members.store', $this->projectB), [
            'user_id' => $this->budi->id,
            'role_in_project' => 'System Analyst',
            'rate_per_hour' => 120000,
            'roles' => ['staff'],
        ]);
        $responseB->assertSessionHasNoErrors();

        // 3. Verify Budi's global system role remains 'staff'
        $this->budi->refresh();
        $this->assertEquals('staff', $this->budi->role);
        $this->assertTrue($this->budi->hasRole('staff'));

        // 4. Verify Project A membership position and rate
        $memberA = ProjectMember::where('project_id', $this->projectA->id)
            ->where('user_id', $this->budi->id)
            ->first();
        $this->assertNotNull($memberA);
        $this->assertEquals('Programmer', $memberA->role_in_project);
        $this->assertEquals(85000.0, $memberA->rate_per_hour);

        // 5. Verify Project B membership position and rate
        $memberB = ProjectMember::where('project_id', $this->projectB->id)
            ->where('user_id', $this->budi->id)
            ->first();
        $this->assertNotNull($memberB);
        $this->assertEquals('System Analyst', $memberB->role_in_project);
        $this->assertEquals(120000.0, $memberB->rate_per_hour);
    }

    public function test_labor_cost_calculation_uses_project_scoped_positions_and_rates(): void
    {
        // Assign Budi to both projects
        $this->projectA->assignMember(
            user: $this->budi,
            roles: ['staff'],
            roleInProject: 'Programmer',
            ratePerHour: 85000
        );

        $this->projectB->assignMember(
            user: $this->budi,
            roles: ['staff'],
            roleInProject: 'System Analyst',
            ratePerHour: 120000
        );

        // Work date on a weekday: 2026-10-07 (Wednesday)
        $date = '2026-10-07';

        // Calculation for Project A: 2 hours as Programmer @ 85.000
        $calcA = $this->calculator->calculate(
            user: $this->budi,
            project: $this->projectA,
            workDate: $date,
            hoursWorked: 2.0
        );
        $this->assertEquals('Programmer', $calcA['resolved_role']);
        $this->assertEquals(85000.0, $calcA['base_rate']);
        $this->assertEquals(170000.0, $calcA['calculated_nominal']);

        // Calculation for Project B: 2 hours as System Analyst @ 120.000
        $calcB = $this->calculator->calculate(
            user: $this->budi,
            project: $this->projectB,
            workDate: $date,
            hoursWorked: 2.0
        );
        $this->assertEquals('System Analyst', $calcB['resolved_role']);
        $this->assertEquals(120000.0, $calcB['base_rate']);
        $this->assertEquals(240000.0, $calcB['calculated_nominal']);
    }

    public function test_logging_work_via_controller_persists_project_position_and_rates(): void
    {
        $this->projectA->assignMember(
            user: $this->budi,
            roles: ['staff'],
            roleInProject: 'Programmer',
            ratePerHour: 85000
        );

        $this->projectB->assignMember(
            user: $this->budi,
            roles: ['staff'],
            roleInProject: 'System Analyst',
            ratePerHour: 120000
        );

        // Wednesday
        $workDate = '2026-10-07';

        // Log 2 hours in Project A
        $resA = $this->actingAs($this->adminUser)->post(route('finance.work-logs.store'), [
            'project_id' => $this->projectA->id,
            'user_id' => $this->budi->id,
            'work_date' => $workDate,
            'hours_worked' => 2.0,
            'source_account_type' => 'office_account',
            'payment_status' => 'unpaid',
            'notes' => 'Developing cart checkout module',
        ]);
        $resA->assertRedirect();

        // Log 2 hours in Project B
        $resB = $this->actingAs($this->adminUser)->post(route('finance.work-logs.store'), [
            'project_id' => $this->projectB->id,
            'user_id' => $this->budi->id,
            'work_date' => $workDate,
            'hours_worked' => 2.0,
            'source_account_type' => 'personal_reimbursement',
            'payment_status' => 'pending_approval',
            'notes' => 'Analyzing bank settlement protocol',
        ]);
        $resB->assertRedirect();

        // Verify database records
        $logA = ProjectWorkLog::where('project_id', $this->projectA->id)->first();
        $this->assertNotNull($logA);
        $this->assertEquals('Programmer', $logA->role_in_project);
        $this->assertEquals(85000.0, (float)$logA->base_rate);
        $this->assertEquals(170000.0, (float)$logA->calculated_nominal);
        $this->assertEquals('unpaid', $logA->payment_status);

        $logB = ProjectWorkLog::where('project_id', $this->projectB->id)->first();
        $this->assertNotNull($logB);
        $this->assertEquals('System Analyst', $logB->role_in_project);
        $this->assertEquals(120000.0, (float)$logB->base_rate);
        $this->assertEquals(240000.0, (float)$logB->calculated_nominal);
        $this->assertEquals('pending_approval', $logB->payment_status);

        // Verify Project model labor aggregations
        $this->assertEquals(170000.0, $this->projectA->totalLaborCost(false));
        $this->assertEquals(240000.0, $this->projectB->totalLaborCost(false));
    }

    public function test_projects_show_view_displays_functional_position_and_agreed_rate(): void
    {
        $this->projectA->assignMember(
            user: $this->budi,
            roles: ['staff'],
            roleInProject: 'Programmer',
            ratePerHour: 85000
        );

        ProjectWorkLog::create([
            'project_id' => $this->projectA->id,
            'user_id' => $this->budi->id,
            'work_date' => '2026-10-07',
            'hours_worked' => 2.0,
            'role_in_project' => 'Programmer',
            'source_account_type' => 'office_account',
            'base_rate' => 85000,
            'calculated_nominal' => 170000,
            'is_weekend' => false,
            'is_overtime' => false,
            'payment_status' => 'unpaid',
            'notes' => 'Feature cart',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('projects.show', $this->projectA));

        $response->assertStatus(200);
        $response->assertSee('Programmer');
        $response->assertSee('85.000');
        $response->assertSee('170.000');
        $response->assertSee('Log Kerja & Upah Tim', false);
    }

    public function test_finance_work_logs_dashboard_recapitulation_and_batch_approval(): void
    {
        $this->projectA->assignMember(
            user: $this->budi,
            roles: ['staff'],
            roleInProject: 'Programmer',
            ratePerHour: 85000
        );

        $this->projectB->assignMember(
            user: $this->budi,
            roles: ['staff'],
            roleInProject: 'System Analyst',
            ratePerHour: 120000
        );

        $logA = ProjectWorkLog::create([
            'project_id' => $this->projectA->id,
            'user_id' => $this->budi->id,
            'work_date' => '2026-10-07',
            'hours_worked' => 2.0,
            'role_in_project' => 'Programmer',
            'source_account_type' => 'office_account',
            'base_rate' => 85000,
            'calculated_nominal' => 170000,
            'payment_status' => 'unpaid',
        ]);

        $logB = ProjectWorkLog::create([
            'project_id' => $this->projectB->id,
            'user_id' => $this->budi->id,
            'work_date' => '2026-10-08',
            'hours_worked' => 2.0,
            'role_in_project' => 'System Analyst',
            'source_account_type' => 'personal_reimbursement',
            'base_rate' => 120000,
            'calculated_nominal' => 240000,
            'payment_status' => 'pending_approval',
        ]);

        // Access Finance Index as Finance Officer
        $response = $this->actingAs($this->financeUser)->get(route('finance.work-logs.index'));
        $response->assertStatus(200);
        $response->assertSee('Rekapitulasi & Monitoring Biaya Tenaga Kerja', false);
        $response->assertSee('Proyek A - E-Commerce');
        $response->assertSee('Proyek B - Core Banking');
        $response->assertSee('Programmer');
        $response->assertSee('System Analyst');
        $response->assertSee('410.000'); // Grand total nominal

        // Batch approval: set both to PAID
        $batchResponse = $this->actingAs($this->financeUser)->post(route('finance.work-logs.batch-status'), [
            'log_ids' => [$logA->id, $logB->id],
            'payment_status' => 'paid',
            'payment_date' => '2026-10-09',
        ]);
        $batchResponse->assertRedirect();

        $this->assertEquals('paid', $logA->fresh()->payment_status);
        $this->assertEquals('paid', $logB->fresh()->payment_status);
        $this->assertEquals('2026-10-09', $logA->fresh()->payment_date->toDateString());
        $this->assertEquals('2026-10-09', $logB->fresh()->payment_date->toDateString());
    }

    public function test_dynamic_master_rate_card_allows_flexible_text_input(): void
    {
        $response = $this->actingAs($this->financeUser)->post(route('finance.rates.master.store'), [
            'role_slug' => 'Cloud Solutions Architect Lead',
            'skill_level' => 'Principal Consultant Level IV',
            'base_rate_per_hour' => 250000,
            'weekend_multiplier' => 1.15,
            'overtime_multiplier' => 1.25,
            'description' => 'Custom dynamic enterprise role',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('man_hour_rates', [
            'role_slug' => 'Cloud Solutions Architect Lead',
            'skill_level' => 'Principal Consultant Level IV',
            'base_rate_per_hour' => 250000,
        ]);
    }
}
