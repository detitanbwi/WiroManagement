<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\TestCase as ProjectTestCase;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QcTestStepReorderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_can_create_test_case_with_ordered_steps(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $client = Client::create(['name' => 'PT Test Client', 'email' => 'client@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Test Steps',
            'project_code' => 'PRJ-STEP-01',
            'status' => 'in_progress',
        ]);

        $steps = [
            '1. Buka halaman login',
            '2. Masukkan email salah',
            '3. Masukkan password salah',
            '4. Klik tombol submit',
            '5. Verifikasi pesan validasi muncul',
        ];

        $response = $this->actingAs($qcUser)->postJson("/api/projects/{$project->id}/qc/test-cases", [
            'title' => 'Test Case Flow Login',
            'steps' => $steps,
            'priority' => 'High',
            'complexity' => 'Medium',
            'test_type' => 'Functional',
            'automation_status' => 'Manual',
        ]);

        $response->assertStatus(200);
        $testCaseId = $response->json('testCase.id');

        $testCase = ProjectTestCase::findOrFail($testCaseId);
        $this->assertEquals($steps, $testCase->steps);
    }

    public function test_can_update_and_reorder_test_case_steps(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $client = Client::create(['name' => 'PT Test Client', 'email' => 'client@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Test Steps Reorder',
            'project_code' => 'PRJ-STEP-02',
            'status' => 'in_progress',
        ]);

        $initialSteps = [
            'Langkah A: Masukkan username',
            'Langkah B: Buka aplikasi',
            'Langkah C: Klik tombol Masuk',
        ];

        $testCase = ProjectTestCase::create([
            'project_id' => $project->id,
            'code' => 'TC-REORDER01',
            'title' => 'Test Reorder Steps',
            'steps' => $initialSteps,
            'status' => 'pending',
        ]);

        // Simulating drag and drop reorder where "Langkah B: Buka aplikasi" was moved to first position
        $reorderedSteps = [
            'Langkah B: Buka aplikasi',
            'Langkah A: Masukkan username',
            'Langkah C: Klik tombol Masuk',
        ];

        $updateResponse = $this->actingAs($qcUser)->putJson("/api/qc/test-cases/{$testCase->id}", [
            'title' => 'Test Reorder Steps Updated',
            'steps' => $reorderedSteps,
        ]);

        $updateResponse->assertStatus(200);

        $testCase->refresh();
        $this->assertEquals($reorderedSteps, $testCase->steps);
        $this->assertEquals('Langkah B: Buka aplikasi', $testCase->steps[0]);
        $this->assertEquals('Langkah A: Masukkan username', $testCase->steps[1]);
        $this->assertEquals('Langkah C: Klik tombol Masuk', $testCase->steps[2]);

        // Verify API list returns the newly ordered steps
        $listResponse = $this->actingAs($qcUser)->getJson("/api/projects/{$project->id}/qc/test-cases");
        $listResponse->assertStatus(200);

        $listData = $listResponse->json();
        $this->assertNotEmpty($listData);
        $this->assertEquals($reorderedSteps, $listData[0]['steps']);
    }
}
