<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Services\TaskProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectGanttLeafProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed basic permissions and superadmin
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);
    }

    public function test_parent_and_child_task_progress_only_counts_leaf_tasks(): void
    {
        $superadmin = User::where('email', 'admin@wirodev.com')->first();
        $this->assertNotNull($superadmin);

        $project = Project::create([
            'client_id'      => 1,
            'title'          => 'Test Gantt Leaf Project',
            'contract_value' => 10000000,
            'status'         => 'in_progress',
        ]);

        // 1. Create Parent Task
        $parentTask = ProjectTask::create([
            'project_id'    => $project->id,
            'parent_id'     => null,
            'code'          => 'TSK-PARENT1',
            'title'         => 'Parent Induk Task',
            'start_date'    => now()->format('Y-m-d'),
            'due_date'      => now()->addDays(10)->format('Y-m-d'),
            'duration_days' => 10,
            'progress'      => 0,
            'status'        => 'todo',
        ]);

        // 2. Create Child Task under Parent Task with 50% progress
        $childTask = ProjectTask::create([
            'project_id'    => $project->id,
            'parent_id'     => $parentTask->id,
            'code'          => 'TSK-CHILD1',
            'title'         => 'Child Anak Task 1',
            'start_date'    => now()->format('Y-m-d'),
            'due_date'      => now()->addDays(5)->format('Y-m-d'),
            'duration_days' => 5,
            'progress'      => 50.00,
            'status'        => 'in_progress',
        ]);

        // Check overall project progress via TaskProgressService
        $progressService = app(TaskProgressService::class);
        $progressService->recalculateParent($childTask);
        $project->refresh();

        // Project progress should be 50% (from single leaf task)
        $this->assertEquals(50, $project->progress_percentage);

        // Parent task progress auto-rolled up to 50%
        $parentTask->refresh();
        $this->assertEquals(50, (float) $parentTask->progress);

        // Hit gantt data endpoint
        $response = $this->actingAs($superadmin)->getJson(route('projects.gantt.data', $project));
        $response->assertStatus(200);

        $data = $response->json();
        $tasks = collect($data['tasks']);

        $parentInJson = $tasks->firstWhere('db_id', $parentTask->id);
        $childInJson = $tasks->firstWhere('db_id', $childTask->id);

        // Verify parent is marked as parent, not leaf, and status is parent
        $this->assertTrue($parentInJson['is_parent']);
        $this->assertFalse($parentInJson['is_leaf']);
        $this->assertEquals('parent', $parentInJson['status']);

        // Verify child is leaf, and status is in_progress
        $this->assertFalse($childInJson['is_parent']);
        $this->assertTrue($childInJson['is_leaf']);
        $this->assertEquals('in_progress', $childInJson['status']);

        // Only 1 leaf task exists
        $leafCount = $tasks->where('is_leaf', true)->count();
        $this->assertEquals(1, $leafCount);

        // Only 1 in-progress leaf task
        $inProgressLeafCount = $tasks->where('is_leaf', true)
            ->filter(fn($t) => $t['progress'] > 0 && $t['progress'] < 100)
            ->count();
        $this->assertEquals(1, $inProgressLeafCount);

        // 3. Add a standalone root task without children (should be considered a leaf task)
        $standaloneTask = ProjectTask::create([
            'project_id'    => $project->id,
            'parent_id'     => null,
            'code'          => 'TSK-STANDALONE',
            'title'         => 'Standalone Root Task',
            'start_date'    => now()->format('Y-m-d'),
            'due_date'      => now()->addDays(3)->format('Y-m-d'),
            'duration_days' => 3,
            'progress'      => 100.00,
            'status'        => 'completed',
        ]);

        $progressService->recalculateProjectProgress($project->id);
        $project->refresh();

        // Project progress: (50% child + 100% standalone) / 2 = 75%
        $this->assertEquals(75, $project->progress_percentage);

        // Fetch Gantt API again
        $response = $this->actingAs($superadmin)->getJson(route('projects.gantt.data', $project));
        $data = $response->json();
        $tasks = collect($data['tasks']);

        // Standalone task should be a leaf task
        $standaloneInJson = $tasks->firstWhere('db_id', $standaloneTask->id);
        $this->assertFalse($standaloneInJson['is_parent']);
        $this->assertTrue($standaloneInJson['is_leaf']);
        $this->assertEquals('completed', $standaloneInJson['status']);

        // Total leaf tasks should now be 2
        $this->assertEquals(2, $tasks->where('is_leaf', true)->count());

        // Completed leaf tasks should be 1
        $completedLeafCount = $tasks->where('is_leaf', true)
            ->filter(fn($t) => $t['progress'] >= 100)
            ->count();
        $this->assertEquals(1, $completedLeafCount);

        // In-progress leaf tasks should still be 1
        $inProgressLeafCount = $tasks->where('is_leaf', true)
            ->filter(fn($t) => $t['progress'] > 0 && $t['progress'] < 100)
            ->count();
        $this->assertEquals(1, $inProgressLeafCount);
    }
}
