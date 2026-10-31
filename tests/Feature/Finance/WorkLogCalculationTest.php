<?php

namespace Tests\Feature\Finance;

use App\Models\Client;
use App\Models\ManHourRate;
use App\Models\Project;
use App\Models\ProjectMemberRate;
use App\Models\ProjectWorkLog;
use App\Models\Role;
use App\Models\User;
use App\Services\Finance\LaborCostCalculatorService;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkLogCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected User $financeUser;
    protected Project $project;
    protected User $developer;
    protected LaborCostCalculatorService $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->calculator = app(LaborCostCalculatorService::class);

        $client = Client::create([
            'name' => 'PT Maju Bersama',
            'email' => 'client@maju.test',
            'phone' => '08123456789',
        ]);

        $this->financeUser = User::factory()->create([
            'name' => 'Finance Officer',
            'email' => 'finance@wirodev.test',
            'is_active' => true,
        ]);
        $this->financeUser->syncRoles(['finance']);

        $this->developer = User::factory()->create([
            'name' => 'Backend Developer',
            'email' => 'dev@wirodev.test',
            'is_active' => true,
        ]);
        $this->developer->syncRoles(['staff']);

        $this->project = Project::create([
            'client_id' => $client->id,
            'title' => 'E-Commerce Enterprise',
            'project_code' => 'PRJ-2026-001',
            'status' => 'in_progress',
            'start_date' => '2026-10-01',
        ]);

        // Seed master rate for developer
        ManHourRate::create([
            'role_slug' => 'developer',
            'skill_level' => 'middle',
            'base_rate_per_hour' => 100000,
            'weekend_multiplier' => 1.10,
            'overtime_multiplier' => 1.20,
            'description' => 'Middle Developer rate',
        ]);
    }

    public function test_normal_weekday_rate_calculation(): void
    {
        // 2026-10-07 is a Wednesday (Weekday)
        $date = '2026-10-07';
        $calc = $this->calculator->calculate(
            user: $this->developer,
            project: $this->project,
            workDate: $date,
            hoursWorked: 2.0,
            roleSlug: 'developer'
        );

        $this->assertEquals(100000.0, $calc['base_rate']);
        $this->assertFalse($calc['is_weekend']);
        $this->assertFalse($calc['is_overtime']);
        $this->assertEquals(200000.0, $calc['calculated_nominal']);
    }

    public function test_weekend_rate_multiplier_applies_10_percent_bonus(): void
    {
        // 2026-10-10 is a Saturday (Weekend)
        $date = '2026-10-10';
        $calc = $this->calculator->calculate(
            user: $this->developer,
            project: $this->project,
            workDate: $date,
            hoursWorked: 2.0,
            roleSlug: 'developer'
        );

        $this->assertTrue($calc['is_weekend']);
        $this->assertFalse($calc['is_overtime']);
        $this->assertEquals(1.10, $calc['weekend_multiplier']);
        // 2 hours * 100,000 * 1.10 = 220,000
        $this->assertEquals(220000.0, $calc['calculated_nominal']);
    }

    public function test_overtime_rate_multiplier_triggers_above_two_hours(): void
    {
        // 2026-10-07 Wednesday, 3.5 hours worked (> 2.0 hours threshold)
        $date = '2026-10-07';
        $calc = $this->calculator->calculate(
            user: $this->developer,
            project: $this->project,
            workDate: $date,
            hoursWorked: 3.5,
            roleSlug: 'developer'
        );

        $this->assertFalse($calc['is_weekend']);
        $this->assertTrue($calc['is_overtime']);
        $this->assertEquals(1.20, $calc['overtime_multiplier']);
        // 3.5 hours * 100,000 * 1.20 = 420,000
        $this->assertEquals(420000.0, $calc['calculated_nominal']);
    }

    public function test_weekend_and_overtime_multipliers_compound_properly(): void
    {
        // 2026-10-11 Sunday, 4 hours worked
        $date = '2026-10-11';
        $calc = $this->calculator->calculate(
            user: $this->developer,
            project: $this->project,
            workDate: $date,
            hoursWorked: 4.0,
            roleSlug: 'developer'
        );

        $this->assertTrue($calc['is_weekend']);
        $this->assertTrue($calc['is_overtime']);
        // 4 hours * (100,000 * 1.10 * 1.20) = 4 * 132,000 = 528,000
        $this->assertEquals(528000.0, $calc['calculated_nominal']);
    }

    public function test_custom_project_member_rate_overrides_master_rate_card(): void
    {
        // Set custom override: 150,000/hr, 1.15 weekend, 1.25 overtime
        ProjectMemberRate::create([
            'project_id' => $this->project->id,
            'user_id' => $this->developer->id,
            'rate_per_hour' => 150000,
            'weekend_multiplier' => 1.15,
            'overtime_multiplier' => 1.25,
            'notes' => 'Senior architect assignment override',
        ]);

        $date = '2026-10-07'; // Wednesday, 1 hour
        $calc = $this->calculator->calculate(
            user: $this->developer,
            project: $this->project,
            workDate: $date,
            hoursWorked: 1.0,
            roleSlug: 'developer'
        );

        $this->assertEquals(150000.0, $calc['base_rate']);
        $this->assertEquals(150000.0, $calc['calculated_nominal']);

        // Overtime check with custom 1.25x
        $calcOt = $this->calculator->calculate(
            user: $this->developer,
            project: $this->project,
            workDate: $date,
            hoursWorked: 3.0,
            roleSlug: 'developer'
        );
        // 3 hours * 150,000 * 1.25 = 562,500
        $this->assertEquals(562500.0, $calcOt['calculated_nominal']);
    }

    public function test_store_work_log_and_batch_status_update(): void
    {
        $log1 = ProjectWorkLog::create([
            'project_id' => $this->project->id,
            'user_id' => $this->developer->id,
            'work_date' => '2026-10-05',
            'hours_worked' => 2.0,
            'role_in_project' => 'developer',
            'source_account_type' => 'office_account',
            'base_rate' => 100000,
            'calculated_nominal' => 200000,
            'is_weekend' => false,
            'is_overtime' => false,
            'payment_status' => 'unpaid',
        ]);

        $log2 = ProjectWorkLog::create([
            'project_id' => $this->project->id,
            'user_id' => $this->developer->id,
            'work_date' => '2026-10-06',
            'hours_worked' => 2.0,
            'role_in_project' => 'developer',
            'source_account_type' => 'office_account',
            'base_rate' => 100000,
            'calculated_nominal' => 200000,
            'is_weekend' => false,
            'is_overtime' => false,
            'payment_status' => 'unpaid',
        ]);

        $this->assertEquals('unpaid', $log1->payment_status);
        $this->assertNull($log1->payment_date);

        // Batch update to paid
        $response = $this->actingAs($this->financeUser)->post(route('finance.work-logs.batch-status'), [
            'log_ids' => [$log1->id, $log2->id],
            'payment_status' => 'paid',
            'payment_date' => '2026-10-20',
        ]);

        $response->assertRedirect();
        $this->assertEquals('paid', $log1->fresh()->payment_status);
        $this->assertEquals('2026-10-20', $log1->fresh()->payment_date->toDateString());
        $this->assertEquals('paid', $log2->fresh()->payment_status);
    }

    public function test_store_work_log_with_multiple_date_ranges(): void
    {
        $payload = [
            'project_id' => $this->project->id,
            'user_id' => $this->developer->id,
            'role_in_project' => 'developer',
            'skill_level' => 'middle',
            'source_account_type' => 'office_account',
            'base_rate' => 100000,
            'payment_status' => 'unpaid',
            'date_ranges' => [
                [
                    'start_date' => '2026-10-05',
                    'end_date' => '2026-10-07',
                    'hours_per_day' => 8,
                    'is_weekend' => false,
                    'is_overtime' => false,
                ],
                [
                    'start_date' => '2026-10-12',
                    'end_date' => '2026-10-15',
                    'hours_per_day' => 8,
                    'is_weekend' => false,
                    'is_overtime' => true,
                ],
            ],
        ];

        $response = $this->actingAs($this->financeUser)->post(route('finance.work-logs.store'), $payload);

        $response->assertRedirect(route('projects.show', $this->project->id));
        $response->assertSessionHas('success', 'Log kerja berhasil dicatat (7 hari kerja, 56 jam).');

        // Exactly ONE single consolidated record created
        $logs = ProjectWorkLog::where('project_id', $this->project->id)
            ->where('user_id', $this->developer->id)
            ->get();

        $this->assertCount(1, $logs);

        $log = $logs->first();
        $this->assertEquals('2026-10-05', $log->work_date->toDateString());
        $this->assertEquals('2026-10-15', $log->end_date->toDateString());
        $this->assertEquals(7, $log->days_count);
        $this->assertEquals(8.0, (float)$log->hours_per_day);
        $this->assertEquals(56.0, (float)$log->hours_worked);
        $this->assertFalse($log->is_weekend);
        $this->assertTrue($log->is_overtime);

        // Range 1: 3 days * 8h = 24h * 100k = 2,400,000
        // Range 2: 4 days * 8h = 32h * 100k * 1.2 (OT) = 3,840,000
        // Total = 6,240,000
        $this->assertEquals(6240000.0, (float)$log->calculated_nominal);

        $this->assertIsArray($log->date_ranges_data);
        $this->assertCount(2, $log->date_ranges_data);
        $this->assertEquals('2026-10-05', $log->date_ranges_data[0]['start_date']);
        $this->assertEquals('2026-10-07', $log->date_ranges_data[0]['end_date']);
        $this->assertEquals(2400000, $log->date_ranges_data[0]['nominal']);
        $this->assertEquals('2026-10-12', $log->date_ranges_data[1]['start_date']);
        $this->assertEquals('2026-10-15', $log->date_ranges_data[1]['end_date']);
        $this->assertEquals(3840000, $log->date_ranges_data[1]['nominal']);
    }

    public function test_store_work_log_single_range_five_days_creates_one_record_and_calculates_rate(): void
    {
        // Monday 2026-10-26 to Friday 2026-10-30 (5 days, 8 hrs/day = 40 hours)
        $payload = [
            'project_id' => $this->project->id,
            'user_id' => $this->developer->id,
            'role_in_project' => 'developer',
            'skill_level' => 'middle',
            'source_account_type' => 'office_account',
            'base_rate' => 100000,
            'is_weekend' => false,
            'is_overtime' => false,
            'payment_status' => 'paid',
            'date_ranges' => [
                [
                    'start_date' => '2026-10-26',
                    'end_date' => '2026-10-30',
                    'hours_per_day' => 8,
                ],
            ],
        ];

        $response = $this->actingAs($this->financeUser)->post(route('finance.work-logs.store'), $payload);

        $response->assertRedirect(route('projects.show', $this->project->id));

        $logs = ProjectWorkLog::where('project_id', $this->project->id)->where('user_id', $this->developer->id)->get();
        $this->assertCount(1, $logs);

        $log = $logs->first();
        $this->assertEquals('2026-10-26', $log->work_date->toDateString());
        $this->assertEquals('2026-10-30', $log->end_date->toDateString());
        $this->assertEquals(5, $log->days_count);
        $this->assertEquals(8.0, (float)$log->hours_per_day);
        $this->assertEquals(40.0, (float)$log->hours_worked);
        // Nominal = 40 * 100,000 = 4,000,000
        $this->assertEquals(4000000.0, (float)$log->calculated_nominal);
        $this->assertEquals('26/10/2026 - 30/10/2026', $log->formatted_date_range);

        // Check project labor cost aggregation
        $this->assertEquals(4000000.0, $this->project->fresh()->totalLaborCost(true));
    }

    public function test_update_work_log_with_date_range_and_hours_per_day(): void
    {
        $log = ProjectWorkLog::create([
            'project_id' => $this->project->id,
            'user_id' => $this->developer->id,
            'work_date' => '2026-10-05',
            'end_date' => '2026-10-05',
            'days_count' => 1,
            'hours_per_day' => 8.0,
            'hours_worked' => 8.0,
            'role_in_project' => 'developer',
            'source_account_type' => 'office_account',
            'base_rate' => 100000,
            'calculated_nominal' => 800000,
            'is_weekend' => false,
            'is_overtime' => false,
            'payment_status' => 'unpaid',
        ]);

        // Update to 3 days: 2026-10-05 to 2026-10-07, 8 hrs/day
        $response = $this->actingAs($this->financeUser)->put(route('finance.work-logs.update', $log), [
            'project_id' => $this->project->id,
            'user_id' => $this->developer->id,
            'work_date' => '2026-10-05',
            'end_date' => '2026-10-07',
            'hours_per_day' => 8,
            'role_in_project' => 'developer',
            'source_account_type' => 'office_account',
            'base_rate' => 100000,
            'is_weekend' => false,
            'is_overtime' => false,
            'payment_status' => 'unpaid',
        ]);

        $response->assertRedirect();
        $updated = $log->fresh();
        $this->assertEquals('2026-10-05', $updated->work_date->toDateString());
        $this->assertEquals('2026-10-07', $updated->end_date->toDateString());
        $this->assertEquals(3, $updated->days_count);
        $this->assertEquals(8.0, (float)$updated->hours_per_day);
        $this->assertEquals(24.0, (float)$updated->hours_worked);
        $this->assertEquals(2400000.0, (float)$updated->calculated_nominal);
    }

    public function test_date_ranges_validation_fails_when_end_date_before_start_date(): void
    {
        $payload = [
            'project_id' => $this->project->id,
            'user_id' => $this->developer->id,
            'role_in_project' => 'developer',
            'source_account_type' => 'office_account',
            'payment_status' => 'unpaid',
            'date_ranges' => [
                [
                    'start_date' => '2026-10-10',
                    'end_date' => '2026-10-05',
                    'hours_per_day' => 8,
                ],
            ],
        ];

        $response = $this->actingAs($this->financeUser)->post(route('finance.work-logs.store'), $payload);

        $response->assertSessionHasErrors(['date_ranges.0.end_date']);
    }

    public function test_backward_compatible_single_work_date_submission(): void
    {
        $payload = [
            'project_id' => $this->project->id,
            'user_id' => $this->developer->id,
            'work_date' => '2026-10-05',
            'hours_worked' => 4.0,
            'role_in_project' => 'developer',
            'source_account_type' => 'office_account',
            'base_rate' => 100000,
            'payment_status' => 'unpaid',
        ];

        $response = $this->actingAs($this->financeUser)->post(route('finance.work-logs.store'), $payload);

        $response->assertRedirect(route('projects.show', $this->project->id));
        $log = ProjectWorkLog::where('project_id', $this->project->id)
            ->where('user_id', $this->developer->id)
            ->whereDate('work_date', '2026-10-05')
            ->first();
        $this->assertNotNull($log);
        $this->assertEquals(4.0, (float)$log->hours_worked);
    }

    public function test_update_work_log_with_multiple_date_ranges(): void
    {
        $log = ProjectWorkLog::create([
            'project_id' => $this->project->id,
            'user_id' => $this->developer->id,
            'work_date' => '2026-10-01',
            'end_date' => '2026-10-01',
            'days_count' => 1,
            'hours_per_day' => 8.0,
            'hours_worked' => 8.0,
            'role_in_project' => 'developer',
            'source_account_type' => 'office_account',
            'base_rate' => 100000,
            'calculated_nominal' => 800000,
            'is_weekend' => false,
            'is_overtime' => false,
            'payment_status' => 'unpaid',
        ]);

        $payload = [
            'project_id' => $this->project->id,
            'user_id' => $this->developer->id,
            'role_in_project' => 'developer',
            'source_account_type' => 'office_account',
            'base_rate' => 100000,
            'payment_status' => 'unpaid',
            'date_ranges' => [
                [
                    'start_date' => '2026-10-01',
                    'end_date' => '2026-10-03',
                    'hours_per_day' => 8,
                    'is_weekend' => true,
                    'is_overtime' => false,
                ],
                [
                    'start_date' => '2026-10-08',
                    'end_date' => '2026-10-11',
                    'hours_per_day' => 8,
                    'is_weekend' => false,
                    'is_overtime' => true,
                ],
            ],
        ];

        $response = $this->actingAs($this->financeUser)->put(route('finance.work-logs.update', $log), $payload);

        $response->assertRedirect();
        $fresh = $log->fresh();

        $this->assertEquals('2026-10-01', $fresh->work_date->toDateString());
        $this->assertEquals('2026-10-11', $fresh->end_date->toDateString());
        $this->assertEquals(7, $fresh->days_count);
        $this->assertEquals(56.0, (float)$fresh->hours_worked);
        $this->assertTrue($fresh->is_weekend);
        $this->assertTrue($fresh->is_overtime);
        $this->assertCount(2, $fresh->date_ranges_data);

        // Range 1: 3 days * 8h = 24h * 100k * 1.1 (weekend) = 2,640,000
        // Range 2: 4 days * 8h = 32h * 100k * 1.2 (overtime) = 3,840,000
        // Total = 6,480,000
        $this->assertEquals(6480000.0, (float)$fresh->calculated_nominal);
    }

    public function test_project_show_view_renders_consolidated_work_log_with_period_breakdown(): void
    {
        ProjectWorkLog::create([
            'project_id' => $this->project->id,
            'user_id' => $this->developer->id,
            'work_date' => '2026-10-01',
            'end_date' => '2026-10-11',
            'days_count' => 7,
            'hours_per_day' => 8.0,
            'hours_worked' => 56.0,
            'role_in_project' => 'developer',
            'source_account_type' => 'office_account',
            'base_rate' => 100000,
            'calculated_nominal' => 6480000,
            'is_weekend' => true,
            'is_overtime' => true,
            'payment_status' => 'unpaid',
            'date_ranges_data' => [
                [
                    'start_date' => '2026-10-01',
                    'end_date' => '2026-10-03',
                    'days_count' => 3,
                    'hours_per_day' => 8,
                    'hours_worked' => 24,
                    'is_weekend' => true,
                    'is_overtime' => false,
                    'nominal' => 2640000,
                ],
                [
                    'start_date' => '2026-10-08',
                    'end_date' => '2026-10-11',
                    'days_count' => 4,
                    'hours_per_day' => 8,
                    'hours_worked' => 32,
                    'is_weekend' => false,
                    'is_overtime' => true,
                    'nominal' => 3840000,
                ],
            ],
        ]);

        $response = $this->actingAs($this->financeUser)->get(route('projects.show', $this->project));

        $response->assertOk();
        $response->assertSee('01/10/2026 - 11/10/2026');
        $response->assertSee('7 hari kerja (2 periode kerja)');
        $response->assertSee('01/10 - 03/10 (3 hari, 24 jam - Weekend)');
        $response->assertSee('08/10 - 11/10 (4 hari, 32 jam - Lembur)');
        $response->assertSee('56,0 jam');
        $response->assertSee('6.480.000');
    }

    public function test_finance_work_logs_index_renders_multi_period_breakdown(): void
    {
        ProjectWorkLog::create([
            'project_id' => $this->project->id,
            'user_id' => $this->developer->id,
            'work_date' => '2026-10-01',
            'end_date' => '2026-10-11',
            'days_count' => 7,
            'hours_per_day' => 8.0,
            'hours_worked' => 56.0,
            'role_in_project' => 'developer',
            'source_account_type' => 'office_account',
            'base_rate' => 100000,
            'calculated_nominal' => 6480000,
            'is_weekend' => true,
            'is_overtime' => true,
            'payment_status' => 'unpaid',
            'date_ranges_data' => [
                [
                    'start_date' => '2026-10-01',
                    'end_date' => '2026-10-03',
                    'days_count' => 3,
                    'hours_per_day' => 8,
                    'hours_worked' => 24,
                    'is_weekend' => true,
                    'is_overtime' => false,
                    'nominal' => 2640000,
                ],
                [
                    'start_date' => '2026-10-08',
                    'end_date' => '2026-10-11',
                    'days_count' => 4,
                    'hours_per_day' => 8,
                    'hours_worked' => 32,
                    'is_weekend' => false,
                    'is_overtime' => true,
                    'nominal' => 3840000,
                ],
            ],
        ]);

        $response = $this->actingAs($this->financeUser)->get(route('finance.work-logs.index'));

        $response->assertOk();
        $response->assertViewHas('masterRates');
        $response->assertSee('01/10/2026 - 11/10/2026');
        $response->assertSee('(7 hari, 2 periode)');
        $response->assertSee('01/10 - 03/10 (3h @ 8 jam - Weekend)');
        $response->assertSee('08/10 - 11/10 (4h @ 8 jam - Lembur)');
        $response->assertSee('PRJ-2026-001');
    }

    public function test_store_work_log_from_finance_with_redirect_to(): void
    {
        $payload = [
            'project_id' => $this->project->id,
            'user_id' => $this->developer->id,
            'role_in_project' => 'developer',
            'skill_level' => 'middle',
            'source_account_type' => 'office_account',
            'base_rate' => 100000,
            'payment_status' => 'unpaid',
            'redirect_to' => route('finance.work-logs.index'),
            'date_ranges' => [
                [
                    'start_date' => '2026-10-05',
                    'end_date' => '2026-10-07',
                    'hours_per_day' => 8,
                    'is_weekend' => false,
                    'is_overtime' => false,
                ],
            ],
        ];

        $response = $this->actingAs($this->financeUser)->post(route('finance.work-logs.store'), $payload);

        $response->assertRedirect(route('finance.work-logs.index'));
        $this->assertDatabaseHas('project_work_logs', [
            'project_id' => $this->project->id,
            'user_id' => $this->developer->id,
            'days_count' => 3,
            'hours_worked' => 24,
            'calculated_nominal' => 2400000,
        ]);
    }
}
