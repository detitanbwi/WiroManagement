<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TaskComment;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QcTaskCommentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    private function createProjectAndTask(): array
    {
        $client = Client::create(['name' => 'PT Test Client', 'email' => 'client@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Comment Test',
            'project_code' => 'PRJ-CM-01',
            'status' => 'in_progress',
        ]);

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-001',
            'title' => 'Task with discussion',
            'column_id' => 'todo',
        ]);

        return [$project, $task];
    }

    public function test_qc_user_and_staff_developer_can_fetch_comments(): void
    {
        [$project, $task] = $this->createProjectAndTask();

        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $staffUser = User::factory()->create(['is_active' => true]);
        $staffUser->syncRoles(['staff']);

        TaskComment::create([
            'project_task_id' => $task->id,
            'user_id' => $qcUser->id,
            'comment' => 'Initial comment from QC tester.',
        ]);

        // QC user can fetch comments
        $responseQc = $this->actingAs($qcUser)->getJson("/api/qc/tasks/{$task->id}/comments");
        $responseQc->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.comment', 'Initial comment from QC tester.');

        // Staff / Developer can fetch comments
        $responseStaff = $this->actingAs($staffUser)->getJson("/api/qc/tasks/{$task->id}/comments");
        $responseStaff->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.comment', 'Initial comment from QC tester.');
    }

    public function test_user_can_add_comment_to_task(): void
    {
        [$project, $task] = $this->createProjectAndTask();

        $staffUser = User::factory()->create(['is_active' => true]);
        $staffUser->syncRoles(['staff']);

        $response = $this->actingAs($staffUser)->postJson("/api/qc/tasks/{$task->id}/comments", [
            'comment' => 'Sudah diperbaiki pada commit terbaru, siap di-verify.',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('comment.comment', 'Sudah diperbaiki pada commit terbaru, siap di-verify.')
            ->assertJsonPath('comment.user.id', $staffUser->id);

        $this->assertDatabaseHas('task_comments', [
            'project_task_id' => $task->id,
            'user_id' => $staffUser->id,
            'comment' => 'Sudah diperbaiki pada commit terbaru, siap di-verify.',
        ]);
    }

    public function test_user_can_add_comment_with_attachment(): void
    {
        Storage::fake('public');
        [$project, $task] = $this->createProjectAndTask();

        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $file = UploadedFile::fake()->create('screenshot_evidence.png', 100, 'image/png');

        $response = $this->actingAs($qcUser)->post("/api/qc/tasks/{$task->id}/comments", [
            'comment' => 'Lampiran screenshot error',
            'attachment' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $comment = TaskComment::where('project_task_id', $task->id)->first();
        $this->assertNotNull($comment);
        $this->assertNotNull($comment->attachment_path);
        Storage::disk('public')->assertExists($comment->attachment_path);
    }

    public function test_author_can_delete_own_comment(): void
    {
        [$project, $task] = $this->createProjectAndTask();

        $staffUser = User::factory()->create(['is_active' => true]);
        $staffUser->syncRoles(['staff']);

        $comment = TaskComment::create([
            'project_task_id' => $task->id,
            'user_id' => $staffUser->id,
            'comment' => 'Comment to be deleted by author.',
        ]);

        $response = $this->actingAs($staffUser)->deleteJson("/api/qc/comments/{$comment->id}");
        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('task_comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_non_admin_cannot_delete_other_user_comment(): void
    {
        [$project, $task] = $this->createProjectAndTask();

        $author = User::factory()->create(['is_active' => true]);
        $author->syncRoles(['staff']);

        $otherUser = User::factory()->create(['is_active' => true]);
        $otherUser->syncRoles(['staff']);

        $comment = TaskComment::create([
            'project_task_id' => $task->id,
            'user_id' => $author->id,
            'comment' => 'Comment owned by author.',
        ]);

        $response = $this->actingAs($otherUser)->deleteJson("/api/qc/comments/{$comment->id}");
        $response->assertForbidden();

        $this->assertDatabaseHas('task_comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_admin_can_delete_any_comment(): void
    {
        [$project, $task] = $this->createProjectAndTask();

        $author = User::factory()->create(['is_active' => true]);
        $author->syncRoles(['staff']);

        $adminUser = User::factory()->create(['is_active' => true]);
        $adminUser->syncRoles(['admin']);

        $comment = TaskComment::create([
            'project_task_id' => $task->id,
            'user_id' => $author->id,
            'comment' => 'Comment to be deleted by admin.',
        ]);

        $response = $this->actingAs($adminUser)->deleteJson("/api/qc/comments/{$comment->id}");
        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('task_comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_qc_dashboard_view_renders_comment_ui_elements(): void
    {
        [$project, $task] = $this->createProjectAndTask();

        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $response = $this->actingAs($qcUser)->get("/projects/{$project->id}/qc");
        $response->assertOk();

        // Check comment button on Kanban card
        $response->assertSee('Diskusi & Komentar', false);
        $response->assertSee("openTaskModal(task, 'comments')", false);
        $response->assertSee('task.comments_count || 0', false);

        // Check Tab
        $response->assertSee("activeTab === 'comments'", false);
        $response->assertSee('newCommentText', false);
        $response->assertSee('submitTaskComment()', false);
    }
}
