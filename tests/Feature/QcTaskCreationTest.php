<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QcTaskCreationTest extends TestCase
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
            'title' => 'Project Standalone Task',
            'project_code' => 'PRJ-ST-01',
            'status' => 'in_progress',
        ]);
    }

    public function test_pm_or_qc_can_create_standalone_task_without_bug(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $assignee = User::factory()->create(['is_active' => true, 'name' => 'Developer One']);
        $project = $this->createProject();

        $response = $this->actingAs($qcUser)->postJson("/api/projects/{$project->id}/qc/tasks", [
            'title' => 'Perbaikan alignment header navbar',
            'description' => 'Minor CSS fix untuk header navbar pada tampilan mobile.',
            'assignee_id' => $assignee->id,
            'column_id' => 'todo',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('task.title', 'Perbaikan alignment header navbar')
            ->assertJsonPath('task.column_id', 'todo')
            ->assertJsonPath('task.assignee_id', $assignee->id);

        $this->assertDatabaseHas('project_tasks', [
            'project_id' => $project->id,
            'title' => 'Perbaikan alignment header navbar',
            'column_id' => 'todo',
            'assignee_id' => $assignee->id,
        ]);
    }

    public function test_can_create_task_with_file_attachment(): void
    {
        Storage::fake('public');

        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $project = $this->createProject();
        $file = UploadedFile::fake()->image('screenshot.png');

        $response = $this->actingAs($qcUser)->post("/api/projects/{$project->id}/qc/tasks", [
            'title' => 'Task dengan lampiran screenshot',
            'description' => 'Perlu pengecekan UI sesuai screenshot.',
            'column_id' => 'in_progress',
            'attachment' => $file,
        ], ['Accept' => 'application/json']);

        $response->assertOk()->assertJsonPath('success', true);

        $task = ProjectTask::where('title', 'Task dengan lampiran screenshot')->first();
        $this->assertNotNull($task);
        $this->assertNotNull($task->attachment_path);
        Storage::disk('public')->assertExists($task->attachment_path);
    }

    public function test_staff_can_create_standalone_task_in_allowed_columns(): void
    {
        $staffUser = User::factory()->create(['is_active' => true]);
        $staffUser->syncRoles(['staff']);

        $project = $this->createProject();

        foreach (['todo', 'in_progress', 'ready_for_qc'] as $col) {
            $response = $this->actingAs($staffUser)->postJson("/api/projects/{$project->id}/qc/tasks", [
                'title' => "Staff task in {$col}",
                'column_id' => $col,
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('task.column_id', $col);

            $this->assertDatabaseHas('project_tasks', [
                'project_id' => $project->id,
                'title' => "Staff task in {$col}",
                'column_id' => $col,
            ]);
        }
    }

    public function test_staff_cannot_create_task_in_qc_in_progress_or_done(): void
    {
        $staffUser = User::factory()->create(['is_active' => true]);
        $staffUser->syncRoles(['staff']);

        $project = $this->createProject();

        // Attempt qc_in_progress
        $responseQc = $this->actingAs($staffUser)->postJson("/api/projects/{$project->id}/qc/tasks", [
            'title' => 'Staff trying qc_in_progress',
            'column_id' => 'qc_in_progress',
        ]);
        $responseQc->assertStatus(403)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Akses ditolak. Role Staff hanya dapat membuat task pada kolom To Do, In Progress, atau Ready for QC.');

        // Attempt done
        $responseDone = $this->actingAs($staffUser)->postJson("/api/projects/{$project->id}/qc/tasks", [
            'title' => 'Staff trying done',
            'column_id' => 'done',
        ]);
        $responseDone->assertStatus(403)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Akses ditolak. Role Staff hanya dapat membuat task pada kolom To Do, In Progress, atau Ready for QC.');

        $this->assertDatabaseMissing('project_tasks', [
            'title' => 'Staff trying qc_in_progress',
        ]);
        $this->assertDatabaseMissing('project_tasks', [
            'title' => 'Staff trying done',
        ]);
    }

    public function test_cannot_create_task_without_title(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $project = $this->createProject();

        $response = $this->actingAs($qcUser)->postJson("/api/projects/{$project->id}/qc/tasks", [
            'title' => '',
            'column_id' => 'todo',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_user_without_permission_cannot_create_task(): void
    {
        $userWithoutPermission = User::factory()->create(['is_active' => true]);
        // Do not assign qc.manage_tasks

        $project = $this->createProject();

        $response = $this->actingAs($userWithoutPermission)->postJson("/api/projects/{$project->id}/qc/tasks", [
            'title' => 'Unauthorized task',
            'column_id' => 'todo',
        ]);

        $response->assertStatus(403);
    }

    public function test_qc_task_is_typed_qc_and_does_not_appear_in_gantt_nor_affect_progress(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->syncRoles(['superadmin']);

        $project = $this->createProject();

        // 1. Create a 100% completed Gantt task
        $ganttTask = ProjectTask::create([
            'project_id'    => $project->id,
            'type'          => 'gantt',
            'code'          => 'TSK-GANTT-01',
            'title'         => 'Gantt Root Work Package',
            'start_date'    => now()->format('Y-m-d'),
            'due_date'      => now()->addDays(5)->format('Y-m-d'),
            'duration_days' => 5,
            'progress'      => 100.0,
            'status'        => 'completed',
        ]);

        $project->refresh();
        $this->assertEquals(100, $project->progress_percentage);

        // 2. Create QC task via QC API endpoint
        $response = $this->actingAs($admin)->postJson("/api/projects/{$project->id}/qc/tasks", [
            'title'       => 'QC Bug Kanban Task',
            'description' => 'Defect found during testing.',
            'column_id'   => 'todo',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('task.type', 'qc');

        $qcTask = ProjectTask::where('title', 'QC Bug Kanban Task')->first();
        $this->assertNotNull($qcTask);
        $this->assertEquals('qc', $qcTask->type);
        $this->assertNull($qcTask->wbs_code);

        // 3. Project progress remains 100% (QC task with 0% progress does not drag it down)
        $project->refresh();
        $this->assertEquals(100, $project->progress_percentage);

        // 4. QC task does not appear in Gantt chart data endpoint
        $ganttResponse = $this->actingAs($admin)->getJson("/projects/{$project->id}/gantt/data");
        $ganttResponse->assertOk();

        $tasksInGantt = collect($ganttResponse->json('tasks'));
        $this->assertTrue($tasksInGantt->contains('db_id', $ganttTask->id));
        $this->assertFalse($tasksInGantt->contains('db_id', $qcTask->id));
        $this->assertCount(1, $tasksInGantt);
    }

    public function test_gantt_task_does_not_appear_in_qc_tasks_endpoint(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->syncRoles(['superadmin']);

        $project = $this->createProject();

        // 1. Create a Gantt task
        $ganttTask = ProjectTask::create([
            'project_id'    => $project->id,
            'type'          => 'gantt',
            'code'          => 'TSK-GANTT-02',
            'title'         => 'Gantt Engineering Task',
            'start_date'    => now()->format('Y-m-d'),
            'due_date'      => now()->addDays(5)->format('Y-m-d'),
            'duration_days' => 5,
            'progress'      => 50.0,
            'status'        => 'in_progress',
        ]);

        // 2. Create a QC task
        $this->actingAs($admin)->postJson("/api/projects/{$project->id}/qc/tasks", [
            'title'     => 'QC Only Issue',
            'column_id' => 'todo',
        ])->assertOk();

        $qcTask = ProjectTask::where('title', 'QC Only Issue')->first();
        $this->assertNotNull($qcTask);

        // 3. Query QC tasks API
        $qcResponse = $this->actingAs($admin)->getJson("/api/projects/{$project->id}/qc/tasks");
        $qcResponse->assertOk();

        $tasksInQc = collect($qcResponse->json());
        $this->assertTrue($tasksInQc->contains('id', $qcTask->id));
        $this->assertFalse($tasksInQc->contains('id', $ganttTask->id));
        $this->assertCount(1, $tasksInQc);
    }
}
