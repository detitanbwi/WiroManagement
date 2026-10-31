<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectGanttInteractiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);
        Client::firstOrCreate(
            ['id' => 1],
            ['name' => 'Test Client', 'email' => 'client@test.com', 'phone' => '08123456789']
        );
    }

    public function test_interactive_update_dates_and_progress_with_ancestors_and_project_sync(): void
    {
        $superadmin = User::where('email', 'admin@wirodev.com')->first();
        $this->assertNotNull($superadmin);

        $project = Project::create([
            'client_id'      => 1,
            'title'          => 'Interactive Test Project',
            'contract_value' => 10000000,
            'status'         => 'in_progress',
        ]);

        $parent = ProjectTask::create([
            'project_id'    => $project->id,
            'parent_id'     => null,
            'code'          => 'TSK-P1',
            'title'         => 'Modul 1 (Parent)',
            'start_date'    => '2026-11-01',
            'due_date'      => '2026-11-05',
            'duration_days' => 5,
            'progress'      => 0,
            'status'        => 'todo',
        ]);

        $child = ProjectTask::create([
            'project_id'    => $project->id,
            'parent_id'     => $parent->id,
            'code'          => 'TSK-C1',
            'title'         => 'Subtask 1 (Child)',
            'start_date'    => '2026-11-01',
            'due_date'      => '2026-11-05',
            'duration_days' => 5,
            'progress'      => 0,
            'status'        => 'todo',
        ]);

        $newStart = '2026-11-03';
        $newDue = '2026-11-12';

        $response = $this->actingAs($superadmin)->postJson(route('projects.gantt.tasks.interactive', $child), [
            'start_date' => $newStart,
            'due_date'   => $newDue,
            'progress'   => 100,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'task' => [
                'id',
                'db_id',
                'start',
                'end',
                'duration_days',
                'progress',
                'status',
                'custom_class',
            ],
            'ancestors',
            'project' => [
                'progress_percentage',
            ]
        ]);

        $response->assertJson([
            'success' => true,
            'task'    => [
                'db_id'         => $child->id,
                'start'         => $newStart,
                'end'           => $newDue,
                'duration_days' => 10,
                'progress'      => 100.0,
                'status'        => 'completed',
                'custom_class'  => 'bar-completed',
            ],
            'project' => [
                'progress_percentage' => 100,
            ]
        ]);

        // Ancestor should reflect updated rolled up dates and progress
        $ancestors = $response->json('ancestors');
        $this->assertNotEmpty($ancestors);
        $this->assertEquals($parent->id, $ancestors[0]['db_id']);
        $this->assertEquals($newStart, $ancestors[0]['start']);
        $this->assertEquals($newDue, $ancestors[0]['end']);
        $this->assertEquals(100.0, $ancestors[0]['progress']);
    }
}
