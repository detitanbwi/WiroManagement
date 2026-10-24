<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CompanyTask;
use App\Models\Permission;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GanttAndCompanyTaskRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_permissions_exist_in_matrix_and_seeded_properly(): void
    {
        $this->assertDatabaseHas('permissions', ['name' => 'projects.gantt']);
        $this->assertDatabaseHas('permissions', ['name' => 'projects.gantt_manage']);
        $this->assertDatabaseHas('permissions', ['name' => 'company_tasks.view']);
        $this->assertDatabaseHas('permissions', ['name' => 'company_tasks.manage']);

        $pmRole = Role::where('slug', 'pm')->first();
        $this->assertTrue($pmRole->hasPermission('projects.gantt'));
        $this->assertTrue($pmRole->hasPermission('projects.gantt_manage'));
        $this->assertTrue($pmRole->hasPermission('company_tasks.view'));
        $this->assertTrue($pmRole->hasPermission('company_tasks.manage'));

        $staffRole = Role::where('slug', 'staff')->first();
        $this->assertTrue($staffRole->hasPermission('projects.gantt'));
        $this->assertFalse($staffRole->hasPermission('projects.gantt_manage'));
        $this->assertTrue($staffRole->hasPermission('company_tasks.view'));
        $this->assertFalse($staffRole->hasPermission('company_tasks.manage'));
    }

    public function test_staff_can_view_gantt_but_cannot_manage_tasks(): void
    {
        $client = Client::create(['name' => 'Client Gantt', 'email' => 'client@gantt.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Gantt RBAC Test',
            'contract_value' => 10000000,
            'status' => 'in_progress',
        ]);

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-1',
            'title' => 'Task 1',
            'start_date' => '2026-11-01',
            'due_date' => '2026-11-05',
            'duration_days' => 5,
            'progress' => 0,
            'status' => 'todo',
        ]);

        $staff = User::factory()->create(['is_active' => true]);
        $staff->syncRoles(['staff']);

        // Staff can view gantt view and data
        $this->actingAs($staff)->get(route('projects.gantt', $project))->assertStatus(200);
        $this->actingAs($staff)->getJson(route('projects.gantt.data', $project))->assertStatus(200);

        // Staff is forbidden from creating tasks
        $this->actingAs($staff)->postJson(route('projects.gantt.tasks.store', $project), [
            'title' => 'Unauthorized Task',
            'start_date' => '2026-11-01',
            'due_date' => '2026-11-05',
        ])->assertStatus(403);

        // Staff is forbidden from interactive updating
        $this->actingAs($staff)->postJson(route('projects.gantt.tasks.interactive', $task), [
            'start_date' => '2026-11-02',
            'due_date' => '2026-11-06',
        ])->assertStatus(403);

        // Staff is forbidden from deleting tasks
        $this->actingAs($staff)->deleteJson(route('projects.gantt.tasks.destroy', $task))->assertStatus(403);
    }

    public function test_pm_can_manage_gantt_chart_tasks(): void
    {
        $client = Client::create(['name' => 'Client Gantt PM', 'email' => 'client2@gantt.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Gantt PM Test',
            'contract_value' => 10000000,
            'status' => 'in_progress',
        ]);

        $pm = User::factory()->create(['is_active' => true]);
        $pm->syncRoles(['pm']);

        $response = $this->actingAs($pm)->postJson(route('projects.gantt.tasks.store', $project), [
            'title' => 'New Task By PM',
            'start_date' => '2026-11-01',
            'due_date' => '2026-11-05',
            'status' => 'todo',
            'progress' => 0,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('project_tasks', ['project_id' => $project->id, 'title' => 'New Task By PM']);
    }

    public function test_company_tasks_access_and_manage_permissions(): void
    {
        $staff = User::factory()->create(['is_active' => true]);
        $staff->syncRoles(['staff']);

        // Staff has company_tasks.view, can access index
        $this->actingAs($staff)->get(route('company-tasks.index'))->assertStatus(200);

        // Staff doesn't have company_tasks.manage, cannot create
        $this->actingAs($staff)->post(route('company-tasks.store'), [
            'title' => 'Meeting Klien Staff',
            'type' => 'meeting',
            'status' => 'todo',
            'priority' => 'high',
        ])->assertStatus(403);

        // PM has company_tasks.manage, can create
        $pm = User::factory()->create(['is_active' => true]);
        $pm->syncRoles(['pm']);

        $response = $this->actingAs($pm)->post(route('company-tasks.store'), [
            'title' => 'Meeting Klien PM',
            'type' => 'meeting',
            'status' => 'todo',
            'priority' => 'high',
        ]);

        $response->assertRedirect(route('company-tasks.index'));
        $this->assertDatabaseHas('company_tasks', ['title' => 'Meeting Klien PM']);
    }

    public function test_unauthorized_user_without_gantt_permission_is_forbidden(): void
    {
        $client = Client::create(['name' => 'Client Portal', 'email' => 'client@portal.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Secret Project',
            'contract_value' => 5000000,
            'status' => 'in_progress',
        ]);

        $staffUser = User::factory()->create(['is_active' => true]);
        $staffUser->syncRoles(['staff']);

        $staffRole = Role::where('slug', 'staff')->first();
        $permsWithoutGantt = array_diff($staffRole->permissions->pluck('name')->toArray(), ['projects.gantt']);
        $staffRole->syncPermissions($permsWithoutGantt);
        $staffUser->clearPermissionCache();

        $this->actingAs($staffUser)->get(route('projects.gantt', $project))->assertStatus(403);
        $this->actingAs($staffUser)->getJson(route('projects.gantt.data', $project))->assertStatus(403);
    }
}
