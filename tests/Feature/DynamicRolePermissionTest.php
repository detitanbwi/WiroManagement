<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicRolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_superadmin_can_access_role_management(): void
    {
        $superadmin = User::factory()->create(['is_active' => true]);
        $superadmin->syncRoles(['superadmin']);

        $response = $this->actingAs($superadmin)->get(route('roles.index'));
        $response->assertStatus(200);
        $response->assertSee('Matriks Hak Akses');
    }

    public function test_non_superadmin_is_forbidden_from_role_management(): void
    {
        $pm = User::factory()->create(['is_active' => true]);
        $pm->syncRoles(['pm']);

        $response = $this->actingAs($pm)->get(route('roles.index'));
        $response->assertStatus(403);
    }

    public function test_superadmin_can_create_new_dynamic_role_with_permissions(): void
    {
        $superadmin = User::factory()->create(['is_active' => true]);
        $superadmin->syncRoles(['superadmin']);

        $response = $this->actingAs($superadmin)->post(route('roles.store'), [
            'name' => 'Technical Lead',
            'slug' => 'tech-lead',
            'color' => 'indigo',
            'description' => 'Memimpin arsitektur teknis dan review kode.',
            'permissions' => ['projects.view', 'projects.create', 'qc.manage_tasks'],
        ]);

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', ['slug' => 'tech-lead', 'name' => 'Technical Lead']);

        $role = Role::where('slug', 'tech-lead')->first();
        $this->assertTrue($role->hasPermission('projects.view'));
        $this->assertTrue($role->hasPermission('projects.create'));
        $this->assertTrue($role->hasPermission('qc.manage_tasks'));
        $this->assertFalse($role->hasPermission('finance.bank_accounts'));

        // Assign to a new user and test user permissions
        $user = User::factory()->create(['is_active' => true]);
        $user->syncRoles(['tech-lead']);

        $this->assertTrue($user->hasPermission('projects.create'));
        $this->assertTrue($user->can('qc.manage_tasks'));
        $this->assertFalse($user->hasPermission('finance.bank_accounts'));
        $this->assertFalse($user->can('finance.bank_accounts'));
    }

    public function test_superadmin_can_modify_permissions_of_existing_predefined_role(): void
    {
        $superadmin = User::factory()->create(['is_active' => true]);
        $superadmin->syncRoles(['superadmin']);

        $pmRole = Role::where('slug', 'pm')->first();
        $this->assertFalse($pmRole->hasPermission('finance.view'));

        $pmUser = User::factory()->create(['is_active' => true]);
        $pmUser->syncRoles(['pm']);
        $this->assertFalse($pmUser->hasPermission('finance.view'));

        // Add 'finance.view' to PM role via update
        $currentPerms = $pmRole->permissions->pluck('name')->toArray();
        $newPerms = array_merge($currentPerms, ['finance.view']);

        $response = $this->actingAs($superadmin)->put(route('roles.update', $pmRole), [
            'name' => $pmRole->name,
            'color' => $pmRole->color,
            'description' => 'Updated PM role with finance view',
            'permissions' => $newPerms,
        ]);

        $response->assertRedirect(route('roles.index'));

        // Verify PM role now has finance.view
        $pmRole->refresh();
        $this->assertTrue($pmRole->hasPermission('finance.view'));

        // Verify user with PM role now has finance.view permission
        $pmUser->refresh();
        $this->assertTrue($pmUser->hasPermission('finance.view'));
        $this->assertTrue($pmUser->can('finance.view'));
    }

    public function test_superadmin_cannot_delete_system_role(): void
    {
        $superadmin = User::factory()->create(['is_active' => true]);
        $superadmin->syncRoles(['superadmin']);

        $superadminRole = Role::where('slug', 'superadmin')->first();

        $response = $this->actingAs($superadmin)->delete(route('roles.destroy', $superadminRole));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['slug' => 'superadmin']);
    }

    public function test_superadmin_can_delete_unused_custom_role(): void
    {
        $superadmin = User::factory()->create(['is_active' => true]);
        $superadmin->syncRoles(['superadmin']);

        $customRole = Role::create([
            'name' => 'Temporary Role',
            'slug' => 'temp-role',
            'color' => 'slate',
            'is_system' => false,
        ]);

        $response = $this->actingAs($superadmin)->delete(route('roles.destroy', $customRole));
        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseMissing('roles', ['slug' => 'temp-role']);
    }
}
