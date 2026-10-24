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

class QcTaskColumnTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_can_move_task_from_todo_to_in_progress_when_bug_is_resolved(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $client = Client::create(['name' => 'PT Client Test', 'email' => 'client@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project QC Transitions',
            'project_code' => 'PRJ-TR-01',
            'status' => 'in_progress',
        ]);

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-01',
            'title' => 'Task in To Do',
            'column_id' => 'todo',
        ]);

        $testCase = ProjectTestCase::create([
            'project_id' => $project->id,
            'code' => 'TC-01',
            'title' => 'Test Case with Bug',
            'status' => 'passed',
            'project_task_id' => $task->id,
        ]);

        $bug = TaskBug::create([
            'project_id' => $project->id,
            'project_task_id' => $task->id,
            'test_case_id' => $testCase->id,
            'code' => 'BUG-01',
            'description' => 'Resolved bug description',
            'severity' => 'Medium',
            'status' => 'resolved',
        ]);

        // 1. Move task to in_progress via POST api.qc.tasks.move
        $resMove = $this->actingAs($qcUser)->postJson(route('api.qc.tasks.move', $task->id), [
            'column_id' => 'in_progress',
        ]);
        $resMove->assertStatus(200);
        $resMove->assertJson(['success' => true]);
        $this->assertEquals('in_progress', $task->fresh()->column_id);
    }

    public function test_can_move_task_using_column_alias_endpoint_with_post_and_patch(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $client = Client::create(['name' => 'PT Client Test', 'email' => 'client2@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project QC Column Route',
            'project_code' => 'PRJ-TR-02',
            'status' => 'in_progress',
        ]);

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-02',
            'title' => 'Task for PATCH test',
            'column_id' => 'todo',
        ]);

        // PATCH /api/qc/tasks/{id}/column
        $resPatch = $this->actingAs($qcUser)->patchJson("/api/qc/tasks/{$task->id}/column", [
            'column_id' => 'in_progress',
        ]);
        $resPatch->assertStatus(200);
        $resPatch->assertJson(['success' => true]);
        $this->assertEquals('in_progress', $task->fresh()->column_id);

        // POST /api/qc/tasks/{id}/column
        $resPost = $this->actingAs($qcUser)->postJson("/api/qc/tasks/{$task->id}/column", [
            'column_id' => 'ready_for_qc',
        ]);
        $resPost->assertStatus(200);
        $this->assertEquals('ready_for_qc', $task->fresh()->column_id);
    }

    public function test_can_move_task_from_qc_in_progress_to_done_when_test_is_passed(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']); // Has qc.execute_tests & qc.manage_tasks

        $client = Client::create(['name' => 'PT Client Test', 'email' => 'client3@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project QC Pass Done',
            'project_code' => 'PRJ-TR-03',
            'status' => 'in_progress',
        ]);

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-03',
            'title' => 'Task in QC in Progress',
            'column_id' => 'qc_in_progress',
        ]);

        $testCase = ProjectTestCase::create([
            'project_id' => $project->id,
            'code' => 'TC-03',
            'title' => 'Test Case Passed',
            'status' => 'passed',
            'project_task_id' => $task->id,
        ]);

        // Move task to done
        $resDone = $this->actingAs($qcUser)->postJson(route('api.qc.tasks.move', $task->id), [
            'column_id' => 'done',
        ]);
        $resDone->assertStatus(200);
        $resDone->assertJson(['success' => true]);
        $this->assertEquals('done', $task->fresh()->column_id);
        $this->assertEquals('passed', $testCase->fresh()->status);
    }

    public function test_user_without_execute_tests_permission_cannot_move_task_to_done(): void
    {
        $pmUser = User::factory()->create(['is_active' => true]);
        $pmUser->syncRoles(['pm']); // Has qc.manage_tasks but NOT qc.execute_tests

        $client = Client::create(['name' => 'PT Client Test', 'email' => 'client4@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project PM QC Attempt',
            'project_code' => 'PRJ-TR-04',
            'status' => 'in_progress',
        ]);

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-04',
            'title' => 'Task QC in Progress',
            'column_id' => 'qc_in_progress',
        ]);

        // PM can move to ready_for_qc or in_progress
        $resDev = $this->actingAs($pmUser)->postJson(route('api.qc.tasks.move', $task->id), [
            'column_id' => 'in_progress',
        ]);
        $resDev->assertStatus(200);
        $this->assertEquals('in_progress', $task->fresh()->column_id);

        // PM CANNOT move to done (requires qc.execute_tests)
        $resDone = $this->actingAs($pmUser)->postJson(route('api.qc.tasks.move', $task->id), [
            'column_id' => 'done',
        ]);
        $resDone->assertStatus(403);
        $this->assertNotEquals('done', $task->fresh()->column_id);
    }

    public function test_can_return_task_from_qc_in_progress_to_todo(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $client = Client::create(['name' => 'PT Client Test', 'email' => 'client_return@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Return to Developer',
            'project_code' => 'PRJ-RTD-01',
            'status' => 'in_progress',
        ]);

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-RTD-01',
            'title' => 'Task returned to developer',
            'column_id' => 'qc_in_progress',
        ]);

        $response = $this->actingAs($qcUser)->postJson(route('api.qc.tasks.move', $task->id), [
            'column_id' => 'todo',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertEquals('todo', $task->fresh()->column_id);
    }

    public function test_staff_role_can_move_task_up_to_ready_for_qc(): void
    {
        $staffUser = User::factory()->create(['is_active' => true]);
        $staffUser->syncRoles(['staff']);

        $client = Client::create(['name' => 'PT Client Test', 'email' => 'staff_client@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Staff Movement',
            'project_code' => 'PRJ-ST-01',
            'status' => 'in_progress',
        ]);

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-ST-01',
            'title' => 'Task for Staff',
            'column_id' => 'todo',
        ]);

        // 1. Staff can move from todo to in_progress
        $res1 = $this->actingAs($staffUser)->postJson(route('api.qc.tasks.move', $task->id), [
            'column_id' => 'in_progress',
        ]);
        $res1->assertStatus(200);
        $this->assertEquals('in_progress', $task->fresh()->column_id);

        // 2. Staff can move from in_progress to ready_for_qc
        $res2 = $this->actingAs($staffUser)->postJson(route('api.qc.tasks.move', $task->id), [
            'column_id' => 'ready_for_qc',
        ]);
        $res2->assertStatus(200);
        $this->assertEquals('ready_for_qc', $task->fresh()->column_id);

        // 3. Staff can move from ready_for_qc back to in_progress
        $res3 = $this->actingAs($staffUser)->postJson(route('api.qc.tasks.move', $task->id), [
            'column_id' => 'in_progress',
        ]);
        $res3->assertStatus(200);
        $this->assertEquals('in_progress', $task->fresh()->column_id);
    }

    public function test_staff_role_cannot_move_task_to_qc_in_progress_or_done(): void
    {
        $staffUser = User::factory()->create(['is_active' => true]);
        $staffUser->syncRoles(['staff']);

        $client = Client::create(['name' => 'PT Client Test', 'email' => 'staff_client2@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Staff Bounds',
            'project_code' => 'PRJ-ST-02',
            'status' => 'in_progress',
        ]);

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-ST-02',
            'title' => 'Task at Ready for QC',
            'column_id' => 'ready_for_qc',
        ]);

        // 1. Attempt to move to qc_in_progress: 403 Forbidden
        $resQc = $this->actingAs($staffUser)->postJson(route('api.qc.tasks.move', $task->id), [
            'column_id' => 'qc_in_progress',
        ]);
        $resQc->assertStatus(403);
        $resQc->assertJsonFragment(['message' => 'Akses ditolak. Role Staff hanya dapat mengubah posisi task hingga Ready for QC.']);
        $this->assertEquals('ready_for_qc', $task->fresh()->column_id);

        // 2. Attempt to move to done: 403 Forbidden
        $resDone = $this->actingAs($staffUser)->postJson(route('api.qc.tasks.move', $task->id), [
            'column_id' => 'done',
        ]);
        $resDone->assertStatus(403);
        $this->assertEquals('ready_for_qc', $task->fresh()->column_id);

        // 3. If task is already in qc_in_progress, staff cannot touch/move it
        $qcTask = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-ST-03',
            'title' => 'Task already in QC',
            'column_id' => 'qc_in_progress',
        ]);
        $resMoveQc = $this->actingAs($staffUser)->postJson(route('api.qc.tasks.move', $qcTask->id), [
            'column_id' => 'in_progress',
        ]);
        $resMoveQc->assertStatus(403);
        $this->assertEquals('qc_in_progress', $qcTask->fresh()->column_id);
    }

    public function test_staff_role_cannot_delete_task(): void
    {
        $staffUser = User::factory()->create(['is_active' => true]);
        $staffUser->syncRoles(['staff']);

        $client = Client::create(['name' => 'PT Client Test', 'email' => 'staff_client3@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Staff Delete Attempt',
            'project_code' => 'PRJ-ST-03',
            'status' => 'in_progress',
        ]);

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-ST-04',
            'title' => 'Task to delete',
            'column_id' => 'todo',
        ]);

        $res = $this->actingAs($staffUser)->deleteJson(route('api.qc.tasks.destroy', $task->id));
        $res->assertStatus(403);
        $this->assertNotNull(ProjectTask::find($task->id));
    }
}

