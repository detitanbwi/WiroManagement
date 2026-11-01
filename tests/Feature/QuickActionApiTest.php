<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QuickActionApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an internal staff/admin user
        $this->staffUser = User::factory()->create([
            'name' => 'PM Internal',
            'email' => 'pm@wirodev.test',
            'password' => Hash::make('secret123'),
            'role' => UserRole::PM->value,
            'client_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_staff_can_login_and_retrieve_token(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'pm@wirodev.test',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email', 'role', 'roles'],
                ],
            ]);

        $token = $response->json('data.token');

        // Test /auth/me
        $meResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.email', 'pm@wirodev.test');
    }

    public function test_client_portal_user_cannot_login_to_mobile_companion_app(): void
    {
        $client = Client::create([
            'name' => 'External Client',
            'company_name' => 'External Corp',
        ]);

        $clientUser = User::factory()->create([
            'email' => 'client@external.test',
            'password' => Hash::make('secret123'),
            'role' => UserRole::CLIENT->value,
            'client_id' => $client->id,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'client@external.test',
            'password' => 'secret123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('status', 'error');
    }

    public function test_quick_client_crud_via_api(): void
    {
        $token = $this->staffUser->createToken('test_token')->plainTextToken;

        // 1. Create client
        $createResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/clients', [
                'name' => 'Pak Bambang',
                'company_name' => 'PT Maju Bersama',
                'email' => 'bambang@majubersama.com',
                'phone' => '08123456789',
                'address' => 'Jl. Sudirman No. 12 Jakarta',
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Pak Bambang')
            ->assertJsonPath('data.company_name', 'PT Maju Bersama');

        $clientId = $createResponse->json('data.id');
        $this->assertDatabaseHas('clients', ['id' => $clientId, 'name' => 'Pak Bambang']);

        // 2. List clients
        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/clients?search=Bambang');

        $listResponse->assertStatus(200)
            ->assertJsonPath('status', 'success');
        $this->assertCount(1, $listResponse->json('data.data'));

        // 3. Show client
        $showResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/clients/{$clientId}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('data.id', $clientId);
    }

    public function test_quick_project_creation_via_api(): void
    {
        $token = $this->staffUser->createToken('test_token')->plainTextToken;

        $client = Client::create([
            'name' => 'Bu Ratna',
            'company_name' => 'CV Karya Indah',
        ]);

        // Create Project
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/projects', [
                'client_id' => $client->id,
                'title' => 'Redesign Website Company Profile',
                'status' => 'in_progress',
                'start_date' => '2026-11-01',
                'end_date' => '2026-12-01',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', 'Redesign Website Company Profile');

        $projectId = $response->json('data.id');
        $this->assertDatabaseHas('projects', [
            'id' => $projectId,
            'client_id' => $client->id,
            'title' => 'Redesign Website Company Profile',
        ]);

        // List projects
        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/projects?status=in_progress');

        $listResponse->assertStatus(200)
            ->assertJsonPath('status', 'success');
        $this->assertCount(1, $listResponse->json('data.data'));
    }

    public function test_quick_invoice_creation_with_auto_generated_number_and_calculation(): void
    {
        $token = $this->staffUser->createToken('test_token')->plainTextToken;

        $client = Client::create([
            'name' => 'Pak Joko',
            'company_name' => 'PT Nusantara Sejahtera',
        ]);

        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Aplikasi Kasir Mobile',
            'status' => 'in_progress',
        ]);

        // 1. Get next invoice number recommendation
        $nextNumRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/projects/{$project->id}/next-invoice-number");

        $nextNumRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['invoice_number']]);

        $recommendedNumber = $nextNumRes->json('data.invoice_number');
        $this->assertStringContainsString('INV/WIRODEV/', $recommendedNumber);

        // 2. Create invoice (auto-generate invoice number if omitted)
        $invoiceRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/invoices', [
                'project_id' => $project->id,
                'issued_date' => '2026-11-01',
                'due_date' => '2026-11-15',
                'tax' => 110000,
                'discount' => 50000,
                'notes' => 'Pembayaran DP 50%',
                'items' => [
                    [
                        'description' => 'Development Phase 1 (UI & DB)',
                        'qty' => 1,
                        'price' => 1000000,
                    ],
                ],
            ]);

        $invoiceRes->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $invoiceData = $invoiceRes->json('data');
        $this->assertEquals($recommendedNumber, $invoiceData['invoice_number']);
        $this->assertEquals(1000000, $invoiceData['subtotal']);
        $this->assertEquals(1060000, $invoiceData['total_amount']); // 1.000.000 + 110.000 - 50.000 = 1.060.000
        $this->assertEquals(1060000, $invoiceData['balance_due']);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceData['id'],
            'invoice_number' => $recommendedNumber,
            'total_amount' => 1060000,
        ]);
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoiceData['id'],
            'description' => 'Development Phase 1 (UI & DB)',
            'price' => 1000000,
        ]);

        // 3. List Invoices
        $listInvoicesRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/invoices');

        $listInvoicesRes->assertStatus(200)
            ->assertJsonPath('status', 'success');
        $this->assertCount(1, $listInvoicesRes->json('data.data'));

        // 4. Test Dashboard Summary
        $dashRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/dashboard/summary');

        $dashRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.counts.clients', 1)
            ->assertJsonPath('data.counts.active_projects', 1)
            ->assertJsonPath('data.counts.unpaid_invoices', 1);
    }
}

