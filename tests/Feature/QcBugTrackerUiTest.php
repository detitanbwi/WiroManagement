<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Permission;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Role;
use App\Models\TaskBug;
use App\Models\TestCase as ProjectTestCase;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QcBugTrackerUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_role_edit_view_contains_run_testing_control_permission(): void
    {
        $superadmin = User::factory()->create(['is_active' => true]);
        $superadmin->syncRoles(['superadmin']);

        $pmRole = Role::where('slug', 'pm')->first();

        $response = $this->actingAs($superadmin)->get(route('roles.edit', $pmRole->id));
        $response->assertStatus(200);
        $response->assertSee('Kontrol Run Testing (Pass / Fail)');
        $response->assertSee('qc.execute_tests');
    }

    public function test_qc_dashboard_view_renders_bug_tracker_tabs_and_columns(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $client = Client::create(['name' => 'PT Client Test', 'email' => 'client@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Test QC UI',
            'project_code' => 'PRJ-UI-01',
            'status' => 'in_progress',
        ]);

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-UI-01',
            'title' => 'Task QC UI',
            'column_id' => 'qc_in_progress',
        ]);

        $tc = ProjectTestCase::create([
            'project_id' => $project->id,
            'code' => 'TC-UI-01',
            'title' => 'TC Scenario 1',
            'status' => 'failed',
            'project_task_id' => $task->id,
        ]);

        $bug = TaskBug::create([
            'project_task_id' => $task->id,
            'test_case_id' => $tc->id,
            'code' => 'BUG-UI-01',
            'description' => 'Bug UI description test',
            'severity' => 'High',
            'status' => 'open',
        ]);

        $response = $this->actingAs($qcUser)->get(route('projects.qc', $project->id));
        $response->assertStatus(200);

        // Verify Alpine state default tab is active
        $response->assertSee("bugFilterTab: 'active'", false);
        $response->assertSee("details: ''", false);
        $response->assertSee("severity: ''", false);
        $response->assertSee("status: ''", false);
        $response->assertSee("testCase: ''", false);
        $response->assertSee("task: ''", false);

        // Verify Tab order
        $content = $response->getContent();
        $posActiveTab = strpos($content, 'Bug Aktif (Open)');
        $posSolvedTab = strpos($content, 'Bug Solved / Resolved');
        $posAllTab = strpos($content, 'Semua Bug');

        $this->assertNotFalse($posActiveTab);
        $this->assertNotFalse($posSolvedTab);
        $this->assertNotFalse($posAllTab);
        $this->assertTrue($posActiveTab < $posSolvedTab, 'Bug Aktif (Open) must precede Bug Solved / Resolved');
        $this->assertTrue($posSolvedTab < $posAllTab, 'Bug Solved / Resolved must precede Semua Bug');

        // Verify separate Severity and Status headers
        $this->assertStringContainsString('Severity</th>', $content);
        $this->assertStringContainsString('Status</th>', $content);

        // Verify column filter row inputs
        $this->assertStringContainsString('x-model="bugFilters.details"', $content);
        $this->assertStringContainsString('x-model="bugFilters.severity"', $content);
        $this->assertStringContainsString('x-model="bugFilters.status"', $content);
        $this->assertStringContainsString('x-model="bugFilters.testCase"', $content);
        $this->assertStringContainsString('x-model="bugFilters.task"', $content);

        // Verify test case code click modal trigger
        $this->assertStringContainsString('openViewTestCaseModal(bug.test_case)', $content);
    }
}
