<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Permission;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Role;
use App\Models\TestCase as ProjectTestCase;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QcRunTestingPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_qc_execute_tests_permission_definition_and_default_assignments(): void
    {
        $perm = Permission::where('name', 'qc.execute_tests')->first();
        $this->assertNotNull($perm);
        $this->assertEquals('Kontrol Run Testing (Pass / Fail)', $perm->label);
        $this->assertEquals('Quality Control (QA)', $perm->group);

        $qcRole = Role::where('slug', 'qc')->first();
        $this->assertTrue($qcRole->hasPermission('qc.execute_tests'));

        $pmRole = Role::where('slug', 'pm')->first();
        $this->assertFalse($pmRole->hasPermission('qc.execute_tests'));

        $adminRole = Role::where('slug', 'admin')->first();
        $this->assertFalse($adminRole->hasPermission('qc.execute_tests'));
    }

    public function test_qc_user_can_execute_pass_fail_and_mark_task_as_done(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $client = Client::create(['name' => 'PT Client Test', 'email' => 'client@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Test QC',
            'project_code' => 'PRJ-QC-01',
            'status' => 'in_progress',
        ]);

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-01',
            'title' => 'Task QC Test',
            'column_id' => 'qc_in_progress',
        ]);

        $testCase = ProjectTestCase::create([
            'project_id' => $project->id,
            'code' => 'TC-01',
            'title' => 'Test Login Skenario',
            'status' => 'pending',
            'project_task_id' => $task->id,
        ]);

        // 1. Submit test result (pass)
        $resResult = $this->actingAs($qcUser)->postJson(route('api.qc.test-cases.result', $testCase->id), [
            'status' => 'passed',
        ]);
        $resResult->assertStatus(200);
        $this->assertEquals('passed', $testCase->fresh()->status);

        // 2. Mark task as done (Pass QC)
        $resMove = $this->actingAs($qcUser)->postJson(route('api.qc.tasks.move', $task->id), [
            'column_id' => 'done',
        ]);
        $resMove->assertStatus(200);
        $this->assertEquals('done', $task->fresh()->column_id);
    }

    public function test_user_without_execute_permission_cannot_mark_task_as_done_or_submit_result(): void
    {
        $pmUser = User::factory()->create(['is_active' => true]);
        $pmUser->syncRoles(['pm']); // By default PM has no qc.execute_tests

        $client = Client::create(['name' => 'PT Client Test', 'email' => 'client2@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Test PM',
            'project_code' => 'PRJ-PM-01',
            'status' => 'in_progress',
        ]);

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-02',
            'title' => 'Task PM Test',
            'column_id' => 'qc_in_progress',
        ]);

        $testCase = ProjectTestCase::create([
            'project_id' => $project->id,
            'code' => 'TC-02',
            'title' => 'Test Case Skenario',
            'status' => 'pending',
            'project_task_id' => $task->id,
        ]);

        // Attempt to submit test result: Forbidden 403
        $resResult = $this->actingAs($pmUser)->postJson(route('api.qc.test-cases.result', $testCase->id), [
            'status' => 'passed',
        ]);
        $resResult->assertStatus(403);

        // Attempt to mark task as done (Pass QC): Forbidden 403
        $resMove = $this->actingAs($pmUser)->postJson(route('api.qc.tasks.move', $task->id), [
            'column_id' => 'done',
        ]);
        $resMove->assertStatus(403);
        $this->assertNotEquals('done', $task->fresh()->column_id);
    }
}
