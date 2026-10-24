<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectUserAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->superadmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@wirodev.test',
            'is_active' => true,
        ]);
        $this->superadmin->syncRoles(['superadmin']);

        $this->client = Client::create([
            'name' => 'PT Wiro Mitra',
            'email' => 'client@wiromitra.com',
        ]);
    }

    public function test_user_can_be_created_as_regular_user_without_roles(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('users.store'), [
            'name' => 'Budi Regular',
            'email' => 'budi@wirodev.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'is_active' => 1,
            // no roles passed!
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $user = User::where('email', 'budi@wirodev.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->roles->isEmpty());
        $this->assertTrue($user->isInternal());

        // Role badges should show Employee for a regular employee
        $badges = $user->role_badges;
        $this->assertCount(1, $badges);
        $this->assertEquals('Employee', $badges[0]['name']);

        // User can log in and view dashboard
        $this->actingAs($user)->get(route('dashboard'))->assertStatus(200);
    }

    public function test_assign_user_to_project_with_multiple_roles(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $project = Project::create([
            'client_id' => $this->client->id,
            'title' => 'Project Alpha',
            'status' => 'in_progress',
        ]);

        // Assign user to project Alpha with Developer (staff) and QA/QC (qc) roles
        $response = $this->actingAs($this->superadmin)->post(route('projects.members.store', $project), [
            'user_id' => $user->id,
            'roles' => ['staff', 'qc'],
        ]);

        $response->assertRedirect();
        $this->assertTrue($project->hasMember($user));

        $member = $project->getMember($user);
        $this->assertNotNull($member);
        $this->assertTrue($member->hasRole('staff'));
        $this->assertTrue($member->hasRole('qc'));

        // Check user helper methods
        $this->assertTrue($user->hasProjectRole($project, 'staff'));
        $this->assertTrue($user->hasProjectRole($project, 'qc'));
        $this->assertFalse($user->hasProjectRole($project, 'pm'));
    }

    public function test_user_has_different_roles_in_different_projects(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $project1 = Project::create([
            'client_id' => $this->client->id,
            'title' => 'Project Web E-Commerce',
            'status' => 'in_progress',
        ]);

        $project2 = Project::create([
            'client_id' => $this->client->id,
            'title' => 'Project Mobile Banking',
            'status' => 'in_progress',
        ]);

        // In Project 1: User is Developer (staff)
        $project1->assignMember($user, ['staff']);

        // In Project 2: User is QA/QC (qc)
        $project2->assignMember($user, ['qc']);

        // Verification for Project 1
        $this->assertTrue($user->hasProjectRole($project1, 'staff'));
        $this->assertFalse($user->hasProjectRole($project1, 'qc'));

        // Verification for Project 2
        $this->assertFalse($user->hasProjectRole($project2, 'staff'));
        $this->assertTrue($user->hasProjectRole($project2, 'qc'));
    }

    public function test_project_scoped_qc_permissions_and_staff_restrictions(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->syncRoles(['staff']); // Assign global structural role so they have base permissions

        $project1 = Project::create([
            'client_id' => $this->client->id,
            'title' => 'Dev Project',
            'status' => 'in_progress',
        ]);

        $project2 = Project::create([
            'client_id' => $this->client->id,
            'title' => 'QC Project',
            'status' => 'in_progress',
        ]);

        // Project 1: User is Developer (staff only)
        $project1->assignMember($user, ['staff']);

        // Project 2: User is QA/QC
        $project2->assignMember($user, ['qc']);

        // 1. In Project 1 (Staff): cannot create task in Done column
        $responseFail = $this->actingAs($user)->postJson(route('api.qc.tasks.store', $project1), [
            'title' => 'Done task',
            'column_id' => 'done',
        ]);
        
        $responseFail->assertStatus(403);

        // Can create task in todo column
        $responseSuccess = $this->actingAs($user)->postJson(route('api.qc.tasks.store', $project1), [
            'title' => 'To Do task',
            'column_id' => 'todo',
        ]);
        $responseSuccess->assertStatus(200);

        // 2. In Project 2 (QC): can create task in done column or pass QC
        $responseQc = $this->actingAs($user)->postJson(route('api.qc.tasks.store', $project2), [
            'title' => 'QC task in done',
            'column_id' => 'done',
        ]);
        $responseQc->assertStatus(200);
    }

    public function test_update_and_remove_project_member(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $project = Project::create([
            'client_id' => $this->client->id,
            'title' => 'Team Manage Project',
            'status' => 'in_progress',
        ]);

        $member = $project->assignMember($user, ['staff']);
        $this->assertTrue($member->hasRole('staff'));
        $this->assertFalse($member->hasRole('qc'));

        // Update role to QC and PM
        $responseUpdate = $this->actingAs($this->superadmin)->put(route('projects.members.update', [$project, $member]), [
            'roles' => ['qc', 'pm'],
        ]);
        $responseUpdate->assertRedirect();

        $member->refresh();
        $this->assertFalse($member->hasRole('staff'));
        $this->assertTrue($member->hasRole('qc'));
        $this->assertTrue($member->hasRole('pm'));

        // Remove member
        $responseDestroy = $this->actingAs($this->superadmin)->delete(route('projects.members.destroy', [$project, $member]));
        $responseDestroy->assertRedirect();

        $this->assertFalse($project->fresh()->hasMember($user));
    }

    public function test_create_project_with_initial_team_members(): void
    {
        $user1 = User::factory()->create(['name' => 'Dev User', 'is_active' => true]);
        $user2 = User::factory()->create(['name' => 'QC User', 'is_active' => true]);

        $response = $this->actingAs($this->superadmin)->post(route('projects.store'), [
            'client_id' => $this->client->id,
            'title' => 'Brand New App',
            'status' => 'in_progress',
            'members' => [
                [
                    'user_id' => $user1->id,
                    'roles' => ['staff'],
                ],
                [
                    'user_id' => $user2->id,
                    'roles' => ['qc', 'pm'],
                ],
            ],
        ]);

        $response->assertRedirect(route('projects.index'));

        $project = Project::where('title', 'Brand New App')->first();
        $this->assertNotNull($project);
        $this->assertEquals(2, $project->members()->count());

        $this->assertTrue($project->hasMember($user1));
        $this->assertTrue($project->getMember($user1)->hasRole('staff'));

        $this->assertTrue($project->hasMember($user2));
        $this->assertTrue($project->getMember($user2)->hasRole('qc'));
        $this->assertTrue($project->getMember($user2)->hasRole('pm'));
    }

    public function test_project_roles_dynamically_follow_all_roles_defined_in_rbac_matrix(): void
    {
        // 1. Initial count of assignable roles (non-client)
        $allRoles = Role::where('slug', '!=', 'client')->get();
        $this->assertGreaterThanOrEqual(3, $allRoles->count());

        // View create project page and assert roles count
        $responseCreate = $this->actingAs($this->superadmin)->get(route('projects.create'));
        $responseCreate->assertStatus(200);
        $rolesInCreateView = $responseCreate->viewData('roles');
        $this->assertCount($allRoles->count(), $rolesInCreateView);

        // 2. Create project and check show page
        $project = Project::create([
            'client_id' => $this->client->id,
            'title' => 'Dynamic Role Project',
            'status' => 'in_progress',
        ]);

        $responseShow = $this->actingAs($this->superadmin)->get(route('projects.show', $project));
        $responseShow->assertStatus(200);
        $rolesInShowView = $responseShow->viewData('assignableRoles');
        $this->assertCount($allRoles->count(), $rolesInShowView);

        // 3. Create a new custom role dynamically
        $customRole = Role::create([
            'name' => 'Lead Architect',
            'slug' => 'lead-architect',
            'color' => 'indigo',
            'description' => 'Memimpin arsitektur sistem proyek.',
            'is_system' => false,
        ]);

        // Now total roles increased by 1
        $updatedRoleCount = Role::where('slug', '!=', 'client')->count();
        $this->assertEquals($allRoles->count() + 1, $updatedRoleCount);

        // Check that projects.create and projects.show immediately reflect the new custom role
        $responseCreateAfter = $this->actingAs($this->superadmin)->get(route('projects.create'));
        $this->assertCount($updatedRoleCount, $responseCreateAfter->viewData('roles'));
        $responseCreateAfter->assertSee('Lead Architect');

        $responseShowAfter = $this->actingAs($this->superadmin)->get(route('projects.show', $project));
        $this->assertCount($updatedRoleCount, $responseShowAfter->viewData('assignableRoles'));
        $responseShowAfter->assertSee('Lead Architect');

        // 4. Assign user to project with this new dynamic role
        $user = User::factory()->create(['name' => 'Architect User', 'is_active' => true]);
        $responseAssign = $this->actingAs($this->superadmin)->post(route('projects.members.store', $project), [
            'user_id' => $user->id,
            'roles' => ['lead-architect', 'staff'],
        ]);
        $responseAssign->assertRedirect();

        $member = $project->getMember($user);
        $this->assertNotNull($member);
        $this->assertTrue($member->hasRole('lead-architect'));
        $this->assertTrue($member->hasRole('staff'));
        $this->assertTrue($user->hasProjectRole($project, 'lead-architect'));

        // Check badge reflects dynamic role
        $badges = $member->role_badges;
        $badgeNames = array_column($badges, 'name');
        $this->assertContains('Lead Architect', $badgeNames);
    }

    public function test_project_index_only_displays_assigned_projects_for_user_and_hides_unassigned_projects(): void
    {
        $projectA = Project::create([
            'client_id' => $this->client->id,
            'title' => 'Alpha Assigned Project',
            'status' => 'in_progress',
        ]);

        $projectB = Project::create([
            'client_id' => $this->client->id,
            'title' => 'Beta Assigned Project',
            'status' => 'in_progress',
        ]);

        $projectC = Project::create([
            'client_id' => $this->client->id,
            'title' => 'Gamma Unassigned Project',
            'status' => 'in_progress',
        ]);

        $user1 = User::factory()->create(['name' => 'User One', 'is_active' => true]);
        $user2 = User::factory()->create(['name' => 'User Two', 'is_active' => true]);
        $user3 = User::factory()->create(['name' => 'User Three Unassigned', 'is_active' => true]);

        // Assign User 1 only to Project Alpha
        $projectA->assignMember($user1, ['staff']);

        // Assign User 2 only to Project Beta
        $projectB->assignMember($user2, ['qc']);

        // 1. User 1 should only see Project Alpha
        $responseUser1 = $this->actingAs($user1)->get(route('projects.index'));
        $responseUser1->assertStatus(200);
        $responseUser1->assertSee('Alpha Assigned Project');
        $responseUser1->assertDontSee('Beta Assigned Project');
        $responseUser1->assertDontSee('Gamma Unassigned Project');

        // 2. User 2 should only see Project Beta
        $responseUser2 = $this->actingAs($user2)->get(route('projects.index'));
        $responseUser2->assertStatus(200);
        $responseUser2->assertDontSee('Alpha Assigned Project');
        $responseUser2->assertSee('Beta Assigned Project');
        $responseUser2->assertDontSee('Gamma Unassigned Project');

        // 3. User 3 (unassigned) should see no projects
        $responseUser3 = $this->actingAs($user3)->get(route('projects.index'));
        $responseUser3->assertStatus(200);
        $responseUser3->assertSee('No projects found.');
        $responseUser3->assertDontSee('Alpha Assigned Project');
        $responseUser3->assertDontSee('Beta Assigned Project');
        $responseUser3->assertDontSee('Gamma Unassigned Project');

        // 4. Super Admin should see all projects
        $responseAdmin = $this->actingAs($this->superadmin)->get(route('projects.index'));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Alpha Assigned Project');
        $responseAdmin->assertSee('Beta Assigned Project');
        $responseAdmin->assertSee('Gamma Unassigned Project');
    }

    public function test_dashboard_only_shows_assigned_projects_for_user_and_hides_other_projects(): void
    {
        $projectA = Project::create([
            'client_id' => $this->client->id,
            'title' => 'Project A Assigned',
            'status' => 'in_progress',
        ]);

        $projectB = Project::create([
            'client_id' => $this->client->id,
            'title' => 'Project B Unassigned',
            'status' => 'in_progress',
        ]);

        $user = User::factory()->create(['name' => 'Assigned User', 'is_active' => true]);
        $projectA->assignMember($user, ['qc', 'staff']);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Project A Assigned');
        $response->assertDontSee('Project B Unassigned');
    }

    public function test_qc_module_button_and_access_for_project_assigned_user(): void
    {
        $projectA = Project::create([
            'client_id' => $this->client->id,
            'title' => 'Project Alpha QC',
            'status' => 'in_progress',
        ]);

        $projectB = Project::create([
            'client_id' => $this->client->id,
            'title' => 'Project Beta Other',
            'status' => 'in_progress',
        ]);

        // Clean user with no global roles, assigned as qc & staff on Project A
        $user = User::factory()->create(['name' => 'QC Specialist', 'is_active' => true]);
        $this->assertTrue($user->roles->isEmpty());

        $projectA->assignMember($user, ['qc', 'staff']);

        // 1. In projects.index, user should see QC Module button for Project A
        $indexResponse = $this->actingAs($user)->get(route('projects.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Project Alpha QC');
        $indexResponse->assertSee(route('projects.qc', $projectA));
        $indexResponse->assertSee('QC Module');

        // 2. User can access QC module board of Project A
        $qcResponse = $this->actingAs($user)->get(route('projects.qc', $projectA));
        $qcResponse->assertStatus(200);
        $qcResponse->assertSee('Project Alpha QC');

        // 3. User cannot access QC module board of unassigned Project B (403)
        $qcForbiddenResponse = $this->actingAs($user)->get(route('projects.qc', $projectB));
        $qcForbiddenResponse->assertStatus(403);
    }
}
