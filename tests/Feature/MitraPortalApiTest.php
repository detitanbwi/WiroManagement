<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MitraPortalApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mitra_client_can_login_and_access_dashboard()
    {
        $this->seed(\Database\Seeders\MitraPortalTestSeeder::class);

        $response = $this->postJson('/api/v1/mitra/auth/login', [
            'email' => 'budi@sinergidigital.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email', 'role', 'client_id', 'company_name']
                ]
            ]);

        $token = $response->json('data.token');

        // Test dashboard endpoint
        $dashResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/mitra/dashboard');

        $dashResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.summary.total_projects', 2);

        // Test projects endpoint
        $projectsResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/mitra/projects');

        $projectsResponse->assertStatus(200)
            ->assertJsonPath('status', 'success');
        
        $this->assertCount(2, $projectsResponse->json('data'));
    }

    public function test_invitation_token_can_be_verified_and_set_password()
    {
        $this->seed(\Database\Seeders\MitraPortalTestSeeder::class);

        // 1. Verify token
        $verifyRes = $this->postJson('/api/v1/mitra/auth/verify-token', [
            'token' => 'test-onboarding-token-123456789abcdef'
        ]);

        $verifyRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.valid', true);

        // 2. Set password
        $setPassRes = $this->postJson('/api/v1/mitra/auth/set-password', [
            'token' => 'test-onboarding-token-123456789abcdef',
            'password' => 'newSecretPassword123!',
            'password_confirmation' => 'newSecretPassword123!',
        ]);

        $setPassRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_client_cannot_access_other_client_project_idor()
    {
        $this->seed(\Database\Seeders\MitraPortalTestSeeder::class);

        // Create another client and project
        $otherClient = Client::create([
            'name' => 'Other Client',
            'email' => 'other@client.com',
        ]);
        $otherProject = \App\Models\Project::create([
            'client_id' => $otherClient->id,
            'title' => 'Secret Project B',
            'status' => 'in_progress',
        ]);

        // Login as Budi (client 1)
        $loginRes = $this->postJson('/api/v1/mitra/auth/login', [
            'email' => 'budi@sinergidigital.com',
            'password' => 'password123',
        ]);
        $token = $loginRes->json('data.token');

        // Budi attempts to access $otherProject->id
        $forbiddenRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/mitra/projects/' . $otherProject->id);

        $forbiddenRes->assertStatus(404); // Scoped query results in 404 (not found in Budi's scope)
    }
}
