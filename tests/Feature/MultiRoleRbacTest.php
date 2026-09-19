<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiRoleRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
    }

    public function test_superadmin_has_full_access_to_users_settings_and_finance(): void
    {
        $superadmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@wirodev.test',
            'is_active' => true,
        ]);
        $superadmin->syncRoles(['superadmin']);

        // Can access user management
        $this->actingAs($superadmin)->get(route('users.index'))->assertStatus(200)->assertDontSee('@section');

        // Can access settings
        $this->actingAs($superadmin)->get(route('settings.index'))->assertStatus(200);

        // Can access finance bank accounts
        $this->actingAs($superadmin)->get(route('finance.bank-accounts'))->assertStatus(200);

        // Can access dashboard
        $this->actingAs($superadmin)->get(route('dashboard'))->assertStatus(200);
    }

    public function test_pm_and_qc_multi_role_user_has_union_access(): void
    {
        $user = User::factory()->create([
            'name' => 'Aditya PM & QC',
            'email' => 'aditya@wirodev.test',
            'is_active' => true,
        ]);
        // Assigned both PM and QC roles
        $user->syncRoles(['pm', 'qc']);

        $client = Client::create([
            'name' => 'Test Client',
            'email' => 'client@test.com',
        ]);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Sample Project',
            'status' => 'in_progress',
        ]);
        $project->assignMember($user, ['pm', 'qc']);

        // Allowed: Projects (from PM)
        $indexResponse = $this->actingAs($user)->get(route('projects.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee(route('projects.qc', $project));
        $indexResponse->assertSee('QC Module');

        // Allowed: Project QC (from QC & PM)
        $this->actingAs($user)->get(route('projects.qc', $project))->assertStatus(200);

        // Project detail page
        $showResponse = $this->actingAs($user)->get(route('projects.show', $project));
        $showResponse->assertStatus(200);
        $showResponse->assertDontSee('Fitur QC');

        // Prohibited: User Management (Superadmin only) -> 403
        $this->actingAs($user)->get(route('users.index'))->assertStatus(403);

        // Prohibited: Settings (Superadmin only) -> 403
        $this->actingAs($user)->get(route('settings.index'))->assertStatus(403);

        // Prohibited: Finance Bank Accounts (Superadmin & Finance only) -> 403
        $this->actingAs($user)->get(route('finance.bank-accounts'))->assertStatus(403);
    }

    public function test_finance_and_staff_multi_role_user_has_union_access(): void
    {
        $user = User::factory()->create([
            'name' => 'Dewi Finance & Dev',
            'email' => 'dewi@wirodev.test',
            'is_active' => true,
        ]);
        $user->syncRoles(['finance', 'staff']);

        // Allowed: Finance Overview & Bank Accounts (from Finance)
        $this->actingAs($user)->get(route('finance.overview'))->assertStatus(200);
        $this->actingAs($user)->get(route('finance.bank-accounts'))->assertStatus(200);

        // Allowed: AI Pricing (from Staff)
        $this->actingAs($user)->get(route('ai-pricing.index'))->assertStatus(200);

        // Prohibited: User Management -> 403
        $this->actingAs($user)->get(route('users.index'))->assertStatus(403);

        // Prohibited: Settings -> 403
        $this->actingAs($user)->get(route('settings.index'))->assertStatus(403);
    }

    public function test_client_role_is_strictly_prohibited_from_internal_web_dashboard(): void
    {
        $clientUser = User::factory()->create([
            'name' => 'Budi Client',
            'email' => 'budi@sinergidigital.test',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);
        $clientUser->syncRoles(['client']);

        // 1. Attempting login on internal web app must be rejected
        $response = $this->post(route('login.post'), [
            'email' => 'budi@sinergidigital.test',
            'password' => 'secret123',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        // 2. Direct actingAs session must be blocked by EnsureInternalUser middleware
        $directResponse = $this->actingAs($clientUser)->get(route('dashboard'));
        $directResponse->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_superadmin_creates_user_without_roles_initially(): void
    {
        $superadmin = User::factory()->create(['is_active' => true]);
        $superadmin->syncRoles(['superadmin']);

        $response = $this->actingAs($superadmin)->post(route('users.store'), [
            'name' => 'Regular User 03',
            'email' => 'user03@wirodev.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'user03@wirodev.test']);

        $created = User::where('email', 'user03@wirodev.test')->first();
        $this->assertTrue($created->roles->isEmpty());
        $this->assertTrue($created->isInternal());
        $this->assertEquals(['Employee'], array_column($created->role_badges, 'name'));
    }

    public function test_superadmin_can_update_user_profile_without_roles(): void
    {
        $superadmin = User::factory()->create(['is_active' => true]);
        $superadmin->syncRoles(['superadmin']);

        $user = User::factory()->create(['name' => 'Original Name', 'is_active' => true]);

        // Edit view should not contain role checkboxes
        $editView = $this->actingAs($superadmin)->get(route('users.edit', $user));
        $editView->assertStatus(200);
        $editView->assertDontSee('Peran Sistem Global');
        $editView->assertDontSee('roles[]');

        $response = $this->actingAs($superadmin)->put(route('users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'personal_email' => 'personal@updated.test',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('personal@updated.test', $user->personal_email);
    }

    public function test_superadmin_cannot_deactivate_self(): void
    {
        $superadmin = User::factory()->create(['is_active' => true]);
        $superadmin->syncRoles(['superadmin']);

        // Cannot deactivate self
        $response = $this->actingAs($superadmin)->put(route('users.update', $superadmin), [
            'name' => $superadmin->name,
            'email' => $superadmin->email,
            'is_active' => '0',
        ]);

        $response->assertSessionHas('error');
        $superadmin->refresh();
        $this->assertTrue($superadmin->is_active);
    }

    public function test_project_list_granular_permissions_and_qc_default_view(): void
    {
        $client = Client::create([
            'name' => 'PT Solusi Bangun',
            'email' => 'solusi@test.com',
        ]);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'E-Commerce App',
            'status' => 'in_progress',
        ]);

        // 1. Default QC User: QC Module button is displayed
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);
        $project->assignMember($qcUser, ['qc']);

        $qcResponse = $this->actingAs($qcUser)->get(route('projects.index'));
        $qcResponse->assertStatus(200);
        // Sees QC Module
        $qcResponse->assertSee('QC Module');
        $qcResponse->assertSee(route('projects.qc', $project));
        // Does NOT see other buttons
        $qcResponse->assertDontSee('>Manage<', false);
        $qcResponse->assertDontSee('>Edit<', false);

        // QC user is forbidden from direct access to show, edit, create, and destroy
        $this->actingAs($qcUser)->get(route('projects.show', $project))->assertStatus(403);
        $this->actingAs($qcUser)->get(route('projects.edit', $project))->assertStatus(403);
        $this->actingAs($qcUser)->get(route('projects.create'))->assertStatus(403);
        $this->actingAs($qcUser)->delete(route('projects.destroy', $project))->assertStatus(403);

        // 2. Default Finance User: Sees Manage, but NOT QC Module
        $financeUser = User::factory()->create(['is_active' => true]);
        $financeUser->syncRoles(['finance']);
        $project->assignMember($financeUser, ['finance']);

        $financeResponse = $this->actingAs($financeUser)->get(route('projects.index'));
        $financeResponse->assertStatus(200);
        $financeResponse->assertSee('Manage');
        $financeResponse->assertDontSee('QC Module');

        // 3. Superadmin: Full visibility (Financial, Kelola, Fitur QC, Edit, Hapus, Buat Proyek Baru)
        $superadmin = User::factory()->create(['is_active' => true]);
        $superadmin->syncRoles(['superadmin']);

        $saResponse = $this->actingAs($superadmin)->get(route('projects.index'));
        $saResponse->assertStatus(200);
        $saResponse->assertSee('Project Value');
        $saResponse->assertSee('Manage');
        $saResponse->assertSee('QC Module');
        $saResponse->assertSee('Edit');
        $saResponse->assertSee('Delete');
        $saResponse->assertSee('New Project');
    }
}
