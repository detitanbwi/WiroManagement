<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TestCase as ProjectTestCase;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QcTaskEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    private function createProject(): Project
    {
        $client = Client::create(['name' => 'PT Test Client', 'email' => 'client@test.com']);
        return Project::create([
            'client_id' => $client->id,
            'title' => 'Project Task Edit',
            'project_code' => 'PRJ-ED-01',
            'status' => 'in_progress',
        ]);
    }

    public function test_pm_or_qc_can_update_task_content_and_assignee(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $developer1 = User::factory()->create(['is_active' => true, 'name' => 'Dev One']);
        $developer2 = User::factory()->create(['is_active' => true, 'name' => 'Dev Two']);
        $project = $this->createProject();

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-100',
            'title' => 'Initial Title',
            'description' => 'Initial Description',
            'assignee_id' => $developer1->id,
            'column_id' => 'todo',
        ]);

        $response = $this->actingAs($qcUser)->post("/api/qc/tasks/{$task->id}", [
            'title' => 'Updated Task Title',
            'description' => 'Updated Detailed Description',
            'assignee_id' => $developer2->id,
            'column_id' => 'in_progress',
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('task.title', 'Updated Task Title')
            ->assertJsonPath('task.description', 'Updated Detailed Description')
            ->assertJsonPath('task.column_id', 'in_progress')
            ->assertJsonPath('task.assignee_id', $developer2->id);

        $this->assertDatabaseHas('project_tasks', [
            'id' => $task->id,
            'title' => 'Updated Task Title',
            'description' => 'Updated Detailed Description',
            'assignee_id' => $developer2->id,
            'column_id' => 'in_progress',
        ]);
    }

    public function test_can_update_task_with_new_attachment(): void
    {
        Storage::fake('public');

        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $project = $this->createProject();
        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-101',
            'title' => 'Task with Attachment',
            'column_id' => 'todo',
        ]);

        $file = UploadedFile::fake()->create('spec.pdf', 500, 'application/pdf');

        $response = $this->actingAs($qcUser)->post("/api/qc/tasks/{$task->id}", [
            'title' => 'Task with Updated Attachment',
            'column_id' => 'todo',
            'attachment' => $file,
        ], ['Accept' => 'application/json']);

        $response->assertOk()->assertJsonPath('success', true);

        $task->refresh();
        $this->assertNotNull($task->attachment_path);
        Storage::disk('public')->assertExists($task->attachment_path);
    }

    public function test_staff_can_update_task_within_allowed_columns(): void
    {
        $staffUser = User::factory()->create(['is_active' => true]);
        $staffUser->syncRoles(['staff']);

        $project = $this->createProject();
        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-102',
            'title' => 'Staff Task Original',
            'column_id' => 'todo',
        ]);

        $response = $this->actingAs($staffUser)->postJson("/api/qc/tasks/{$task->id}", [
            'title' => 'Staff Task Edited Title',
            'description' => 'New notes by staff',
            'column_id' => 'in_progress',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('project_tasks', [
            'id' => $task->id,
            'title' => 'Staff Task Edited Title',
            'column_id' => 'in_progress',
        ]);
    }

    public function test_staff_cannot_update_task_to_or_from_restricted_columns(): void
    {
        $staffUser = User::factory()->create(['is_active' => true]);
        $staffUser->syncRoles(['staff']);

        $project = $this->createProject();

        // 1. Task in todo -> Staff tries to set column to qc_in_progress
        $task1 = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-103',
            'title' => 'Task 103',
            'column_id' => 'todo',
        ]);

        $res1 = $this->actingAs($staffUser)->postJson("/api/qc/tasks/{$task1->id}", [
            'title' => 'Task 103',
            'column_id' => 'qc_in_progress',
        ]);
        $res1->assertStatus(403)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Akses ditolak. Role Staff hanya dapat mengedit task pada kolom To Do, In Progress, atau Ready for QC.');

        // 2. Task already in qc_in_progress -> Staff cannot edit it
        $task2 = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-104',
            'title' => 'Task 104 in QC',
            'column_id' => 'qc_in_progress',
        ]);

        $res2 = $this->actingAs($staffUser)->postJson("/api/qc/tasks/{$task2->id}", [
            'title' => 'Staff attempting edit in QC',
            'column_id' => 'qc_in_progress',
        ]);
        $res2->assertStatus(403)
            ->assertJsonPath('status', 'error');
    }

    public function test_updating_task_to_done_requires_execute_tests_permission(): void
    {
        $userWithoutExec = User::factory()->create(['is_active' => true]);
        $userWithoutExec->syncRoles(['pm']); // has qc.manage_tasks but not qc.execute_tests

        $project = $this->createProject();
        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-105',
            'title' => 'Task 105',
            'column_id' => 'ready_for_qc',
        ]);

        $response = $this->actingAs($userWithoutExec)->postJson("/api/qc/tasks/{$task->id}", [
            'title' => 'Task 105',
            'column_id' => 'done',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Akses ditolak. Anda tidak memiliki izin untuk menandai Pass QC.');
    }

    public function test_get_tasks_includes_assignee_id(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $dev = User::factory()->create(['is_active' => true, 'name' => 'Assigned Developer']);
        $project = $this->createProject();

        ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-106',
            'title' => 'Task with Assignee ID',
            'assignee_id' => $dev->id,
            'column_id' => 'todo',
        ]);

        $response = $this->actingAs($qcUser)->getJson("/api/projects/{$project->id}/qc/tasks");

        $response->assertOk();
        $tasks = $response->json();
        $this->assertNotEmpty($tasks);
        $this->assertEquals($dev->id, $tasks[0]['assignee_id']);
        $this->assertEquals('Assigned Developer', $tasks[0]['assignee']);
    }
}
