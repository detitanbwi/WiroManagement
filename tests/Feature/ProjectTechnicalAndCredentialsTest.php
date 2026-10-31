<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectTechnicalAndCredentialsTest extends TestCase
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
            'name' => 'PT Mitra Sejahtera',
            'company_name' => 'Mitra Corp',
            'email' => 'client@mitracorp.com',
        ]);
    }

    public function test_project_model_encrypts_hosting_credentials_and_casts_boolean(): void
    {
        $credentials = [
            'host' => '192.168.100.1',
            'username' => 'wiro_root',
            'password' => 'SuperSecretPass123!',
            'port' => '22',
            'notes' => 'SSH on port 22 only via VPN',
        ];

        $project = Project::create([
            'client_id' => $this->client->id,
            'title' => 'E-Commerce Platform',
            'status' => 'in_progress',
            'demo_url' => 'https://staging.ecommerce.test',
            'repository_url' => 'https://github.com/wirodev/ecommerce-app',
            'production_url' => 'https://ecommerce.test',
            'hosting_provider' => 'DigitalOcean',
            'hosting_credentials' => $credentials,
            'is_credentials_visible_to_client' => true,
        ]);

        $this->assertTrue($project->is_credentials_visible_to_client);
        $this->assertEquals($credentials, $project->hosting_credentials);
        $this->assertEquals('SuperSecretPass123!', $project->hosting_credentials['password']);

        // Check raw database row to verify encryption
        $rawRow = DB::table('projects')->where('id', $project->id)->first();
        $this->assertNotNull($rawRow->hosting_credentials);
        // Plaintext password must NOT exist in the raw database column
        $this->assertStringNotContainsString('SuperSecretPass123!', $rawRow->hosting_credentials);
    }

    public function test_superadmin_can_store_project_with_technical_and_hosting_credentials(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('projects.store'), [
            'client_id' => $this->client->id,
            'title' => 'Portal Layanan Publik',
            'status' => 'draft',
            'demo_url' => 'https://staging.layanan.go.id',
            'repository_url' => 'https://gitlab.com/wiro/public-service',
            'production_url' => 'https://layanan.go.id',
            'hosting_provider' => 'AWS EC2',
            'hosting_credentials' => [
                'host' => 'ec2-12-34-56-78.compute-1.amazonaws.com',
                'username' => 'ubuntu',
                'password' => 'Ec2Secret2026',
                'port' => '22',
                'notes' => 'Requires pem key',
            ],
            'is_credentials_visible_to_client' => '1',
        ]);

        $response->assertRedirect(route('projects.index'));

        $project = Project::where('title', 'Portal Layanan Publik')->first();
        $this->assertNotNull($project);
        $this->assertEquals('https://staging.layanan.go.id', $project->demo_url);
        $this->assertEquals('https://gitlab.com/wiro/public-service', $project->repository_url);
        $this->assertEquals('https://layanan.go.id', $project->production_url);
        $this->assertEquals('AWS EC2', $project->hosting_provider);
        $this->assertTrue($project->is_credentials_visible_to_client);
        $this->assertEquals('ubuntu', $project->hosting_credentials['username']);
        $this->assertEquals('Ec2Secret2026', $project->hosting_credentials['password']);
    }

    public function test_superadmin_can_update_project_technical_and_hosting_credentials(): void
    {
        $project = Project::create([
            'client_id' => $this->client->id,
            'title' => 'ERP Internal',
            'status' => 'approved',
            'demo_url' => 'https://staging.erp.test',
            'hosting_provider' => 'DomaiNesia',
            'hosting_credentials' => [
                'host' => 'cpanel.erp.test',
                'username' => 'old_user',
                'password' => 'old_pass',
            ],
            'is_credentials_visible_to_client' => true,
        ]);

        $response = $this->actingAs($this->superadmin)->put(route('projects.update', $project), [
            'client_id' => $this->client->id,
            'title' => 'ERP Internal Updated',
            'status' => 'in_progress',
            'demo_url' => 'https://new-staging.erp.test',
            'repository_url' => 'https://github.com/wiro/erp-system',
            'production_url' => 'https://erp.test',
            'hosting_provider' => 'AWS',
            'hosting_credentials' => [
                'host' => 'aws.erp.test',
                'username' => 'new_user',
                'password' => 'new_secure_pass',
                'port' => '2222',
                'notes' => 'Production server',
            ],
            // Uncheck visibility: not sent or sent as 0
        ]);

        $response->assertRedirect(route('projects.index'));

        $project->refresh();
        $this->assertEquals('ERP Internal Updated', $project->title);
        $this->assertEquals('https://new-staging.erp.test', $project->demo_url);
        $this->assertEquals('https://github.com/wiro/erp-system', $project->repository_url);
        $this->assertEquals('https://erp.test', $project->production_url);
        $this->assertEquals('AWS', $project->hosting_provider);
        $this->assertEquals('new_user', $project->hosting_credentials['username']);
        $this->assertEquals('new_secure_pass', $project->hosting_credentials['password']);
        $this->assertFalse($project->is_credentials_visible_to_client);
    }

    public function test_validation_fails_for_invalid_urls(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('projects.store'), [
            'client_id' => $this->client->id,
            'title' => 'Invalid URLs App',
            'status' => 'draft',
            'demo_url' => 'not-a-valid-url',
            'repository_url' => 'ftp://invalid repo',
        ]);

        $response->assertSessionHasErrors(['demo_url', 'repository_url']);
    }

    public function test_mitra_api_returns_urls_and_hides_credentials_when_visibility_is_false(): void
    {
        $clientUser = User::factory()->create([
            'name' => 'Klien Test',
            'email' => 'client.test@mitracorp.com',
            'client_id' => $this->client->id,
            'is_active' => true,
        ]);
        $clientUser->syncRoles(['client']);

        $project = Project::create([
            'client_id' => $this->client->id,
            'title' => 'Private Client App',
            'status' => 'in_progress',
            'demo_url' => 'https://staging.private.test',
            'repository_url' => 'https://github.com/wiro/private-repo',
            'production_url' => 'https://private.test',
            'hosting_provider' => 'DomaiNesia',
            'hosting_credentials' => [
                'host' => 'private-host.test',
                'username' => 'private_admin',
                'password' => 'HiddenTopSecretPassword!',
                'port' => '22',
            ],
            'is_credentials_visible_to_client' => false, // Hidden from client!
        ]);

        $token = $clientUser->createToken('test-mitra-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/mitra/projects/' . $project->id);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.demo_url', 'https://staging.private.test')
            ->assertJsonPath('data.repository_url', 'https://github.com/wiro/private-repo')
            ->assertJsonPath('data.production_url', 'https://private.test')
            ->assertJsonPath('data.hosting', null);

        // Ensure sensitive credentials are NEVER leaked into json
        $response->assertJsonMissing(['HiddenTopSecretPassword!']);
        $response->assertJsonMissing(['private_admin']);
    }

    public function test_mitra_api_returns_credentials_when_visibility_is_true(): void
    {
        $clientUser = User::factory()->create([
            'name' => 'Klien Handover',
            'email' => 'handover@mitracorp.com',
            'client_id' => $this->client->id,
            'is_active' => true,
        ]);
        $clientUser->syncRoles(['client']);

        $project = Project::create([
            'client_id' => $this->client->id,
            'title' => 'Handover Project',
            'status' => 'completed',
            'demo_url' => 'https://staging.handover.test',
            'repository_url' => 'https://github.com/wiro/handover-repo',
            'production_url' => 'https://handover.test',
            'hosting_provider' => 'Niagahoster cPanel',
            'hosting_credentials' => [
                'host' => 'server.handover.test',
                'username' => 'cpanel_client',
                'password' => 'HandoverPass2026!',
                'port' => '2083',
                'notes' => 'cPanel login URL: https://server.handover.test:2083',
            ],
            'is_credentials_visible_to_client' => true, // Visible to client
        ]);

        $token = $clientUser->createToken('test-mitra-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/mitra/projects/' . $project->id);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.demo_url', 'https://staging.handover.test')
            ->assertJsonPath('data.repository_url', 'https://github.com/wiro/handover-repo')
            ->assertJsonPath('data.production_url', 'https://handover.test')
            ->assertJsonPath('data.hosting.provider', 'Niagahoster cPanel')
            ->assertJsonPath('data.hosting.host', 'server.handover.test')
            ->assertJsonPath('data.hosting.username', 'cpanel_client')
            ->assertJsonPath('data.hosting.password', 'HandoverPass2026!')
            ->assertJsonPath('data.hosting.port', '2083')
            ->assertJsonPath('data.hosting.notes', 'cPanel login URL: https://server.handover.test:2083');
    }

    public function test_blade_views_render_technical_credentials_section(): void
    {
        $project = Project::create([
            'client_id' => $this->client->id,
            'title' => 'Full View Test Project',
            'status' => 'in_progress',
            'demo_url' => 'https://demo.viewtest.com',
            'repository_url' => 'https://github.com/view/test',
            'production_url' => 'https://viewtest.com',
            'hosting_provider' => 'Cloudways',
            'hosting_credentials' => [
                'host' => '104.21.3.4',
                'username' => 'cloud_user',
                'password' => 'CloudPass123',
                'port' => '22',
                'notes' => 'Master SSH User',
            ],
            'is_credentials_visible_to_client' => true,
        ]);

        // 1. Create page
        $createRes = $this->actingAs($this->superadmin)->get(route('projects.create'));
        $createRes->assertStatus(200)
            ->assertSee('Informasi Teknis &amp; Kredensial Server', false)
            ->assertSee('Demo / Staging URL')
            ->assertSee('Izinkan Klien Melihat Kredensial Ini di WiroMitra');

        // 2. Edit page
        $editRes = $this->actingAs($this->superadmin)->get(route('projects.edit', $project));
        $editRes->assertStatus(200)
            ->assertSee('Informasi Teknis &amp; Kredensial Server', false)
            ->assertSee('https://demo.viewtest.com')
            ->assertSee('Cloudways')
            ->assertSee('104.21.3.4')
            ->assertSee('cloud_user')
            ->assertSee('CloudPass123');

        // 3. Show page
        $showRes = $this->actingAs($this->superadmin)->get(route('projects.show', $project));
        $showRes->assertStatus(200)
            ->assertSee('Technical Access &amp; Deployment', false)
            ->assertSee('Buka Demo (Staging)')
            ->assertSee('GitHub Repo')
            ->assertSee('Live Site')
            ->assertSee('Visible to Client')
            ->assertSee('Cloudways')
            ->assertSee('104.21.3.4')
            ->assertSee('cloud_user')
            ->assertSee('CloudPass123');
    }
}
