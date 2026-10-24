<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPersonalEmailTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

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
    }

    public function test_superadmin_can_create_user_with_personal_email(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('users.store'), [
            'name' => 'Budi Santoso',
            'email' => 'budi@wirodev.test',
            'personal_email' => 'budi.personal@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['staff'],
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'budi@wirodev.test',
            'personal_email' => 'budi.personal@gmail.com',
        ]);

        // Verify it shows in users index view
        $indexResponse = $this->actingAs($this->superadmin)->get(route('users.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('budi.personal@gmail.com');
    }

    public function test_superadmin_can_update_user_personal_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Siti Staff',
            'email' => 'siti@wirodev.test',
            'personal_email' => 'siti.old@yahoo.com',
            'is_active' => true,
        ]);
        $user->syncRoles(['staff']);

        $response = $this->actingAs($this->superadmin)->put(route('users.update', $user), [
            'name' => 'Siti Staff Updated',
            'email' => 'siti@wirodev.test',
            'personal_email' => 'siti.new@gmail.com',
            'roles' => ['staff'],
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'personal_email' => 'siti.new@gmail.com',
        ]);
    }

    public function test_user_can_update_own_personal_email_via_profile(): void
    {
        $staff = User::factory()->create([
            'name' => 'Rian Programmer',
            'email' => 'rian@wirodev.test',
            'personal_email' => null,
            'is_active' => true,
        ]);
        $staff->syncRoles(['staff']);

        $response = $this->actingAs($staff)->post(route('profile.update'), [
            'name' => 'Rian Programmer',
            'email' => 'rian@wirodev.test',
            'personal_email' => 'rian.pribadi@gmail.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'personal_email' => 'rian.pribadi@gmail.com',
        ]);
    }

    public function test_personal_email_is_optional(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('users.store'), [
            'name' => 'No Personal Email',
            'email' => 'nopersonal@wirodev.test',
            'personal_email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['staff'],
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'nopersonal@wirodev.test',
            'personal_email' => null,
        ]);
    }

    public function test_invalid_personal_email_format_fails_validation(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('users.store'), [
            'name' => 'Invalid Email User',
            'email' => 'invalid@wirodev.test',
            'personal_email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['staff'],
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('personal_email');
    }
}
