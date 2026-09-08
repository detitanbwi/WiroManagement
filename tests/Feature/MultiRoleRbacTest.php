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
        $this->actingAs($superadmin)->get(route('users.index'))->assertStatus(200);

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

        // Allowed: Projects (from PM)
        $indexResponse = $this->actingAs($user)->get(route('projects.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee(route('projects.qc', $project));
        $indexResponse->assertSee('Fitur QC');

        // Allowed: Project QC (from QC & PM)
        $this->actingAs($user)->get(route('projects.qc', $project))->assertStatus(200);

        // Project detail page (Fitur QC button has been moved to index)
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

    public function test_superadmin_can_create_user_with_multiple_roles(): void
    {
        $superadmin = User::factory()->create(['is_active' => true]);
        $superadmin->syncRoles(['superadmin']);

        $response = $this->actingAs($superadmin)->post(route('users.store'), [
            'name' => 'Multi-Role User 03',
            'email' => 'user03@wirodev.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['pm', 'qc'],
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'user03@wirodev.test']);

        $created = User::where('email', 'user03@wirodev.test')->first();
        $this->assertTrue($created->hasRole('pm'));
        $this->assertTrue($created->hasRole('qc'));
        $this->assertTrue($created->hasAllRoles(['pm', 'qc']));
        $this->assertEquals(['pm', 'qc'], $created->getRoleSlugs());
    }

    public function test_superadmin_can_update_user_roles(): void
    {
        $superadmin = User::factory()->create(['is_active' => true]);
        $superadmin->syncRoles(['superadmin']);

        $user = User::factory()->create(['is_active' => true]);
        $user->syncRoles(['staff']);

        $response = $this->actingAs($superadmin)->put(route('users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'roles' => ['pm', 'finance', 'qc'],
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertTrue($user->hasRole('pm'));
        $this->assertTrue($user->hasRole('finance'));
        $this->assertTrue($user->hasRole('qc'));
    }

    public function test_superadmin_cannot_revoke_superadmin_or_deactivate_self(): void
    {
        $superadmin = User::factory()->create(['is_active' => true]);
        $superadmin->syncRoles(['superadmin']);

        // Try removing superadmin role from self
        $response = $this->actingAs($superadmin)->put(route('users.update', $superadmin), [
            'name' => $superadmin->name,
            'email' => $superadmin->email,
            'roles' => ['staff'],
            'is_active' => '1',
        ]);

        $response->assertSessionHas('error');
        $superadmin->refresh();
        $this->assertTrue($superadmin->hasRole('superadmin'));
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

        // 1. Default QC User: ONLY Fitur QC button is displayed
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $qcResponse = $this->actingAs($qcUser)->get(route('projects.index'));
        $qcResponse->assertStatus(200);
        // Sees Fitur QC
        $qcResponse->assertSee('Fitur QC');
        $qcResponse->assertSee(route('projects.qc', $project));
        // Does NOT see other buttons
        $qcResponse->assertDontSee('>Kelola<', false);
        $qcResponse->assertDontSee('>Edit<', false);
        $qcResponse->assertDontSee('Ya, Hapus Proyek');
        $qcResponse->assertDontSee('Buat Proyek Baru');
        // Does NOT see financial column
        $qcResponse->assertDontSee('<th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-indigo-50 uppercase tracking-wider">Financial</th>', false);
        $qcResponse->assertDontSee('Due: Rp');

        // QC user is forbidden from direct access to show, edit, create, and destroy
        $this->actingAs($qcUser)->get(route('projects.show', $project))->assertStatus(403);
        $this->actingAs($qcUser)->get(route('projects.edit', $project))->assertStatus(403);
        $this->actingAs($qcUser)->get(route('projects.create'))->assertStatus(403);
        $this->actingAs($qcUser)->delete(route('projects.destroy', $project))->assertStatus(403);

        // 2. Default Finance User: Sees Financial & Kelola, but NOT Fitur QC
        $financeUser = User::factory()->create(['is_active' => true]);
        $financeUser->syncRoles(['finance']);

        $financeResponse = $this->actingAs($financeUser)->get(route('projects.index'));
        $financeResponse->assertStatus(200);
        $financeResponse->assertSee('Financial');
        $financeResponse->assertSee('Kelola');
        $financeResponse->assertDontSee('Fitur QC');

        // 3. Superadmin: Full visibility (Financial, Kelola, Fitur QC, Edit, Hapus, Buat Proyek Baru)
        $superadmin = User::factory()->create(['is_active' => true]);
        $superadmin->syncRoles(['superadmin']);

        $saResponse = $this->actingAs($superadmin)->get(route('projects.index'));
        $saResponse->assertStatus(200);
        $saResponse->assertSee('Financial');
        $saResponse->assertSee('Kelola');
        $saResponse->assertSee('Fitur QC');
        $saResponse->assertSee('Edit');
        $saResponse->assertSee('Hapus');
        $saResponse->assertSee('Buat Proyek Baru');
    }
}
