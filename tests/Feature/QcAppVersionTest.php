<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TaskBug;
use App\Models\TestCase as ProjectTestCase;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QcAppVersionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_can_create_and_update_test_case_with_app_version(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $client = Client::create(['name' => 'PT Client Version Test', 'email' => 'client@ver.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Version Test',
            'project_code' => 'PRJ-VER-01',
            'status' => 'in_progress',
        ]);

        // 1. Create Test Case with app_version
        $response = $this->actingAs($qcUser)->postJson("/api/projects/{$project->id}/qc/test-cases", [
            'title' => 'Test Scenario Auth Login',
            'preconditions' => 'User registered',
            'expected' => 'Login successful',
            'app_version' => 'v1.2.0-rc1',
            'test_type' => 'Functional',
            'complexity' => 'Medium',
            'priority' => 'High',
            'automation_status' => 'Manual'
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('testCase.app_version', 'v1.2.0-rc1');

        $this->assertDatabaseHas('test_cases', [
            'project_id' => $project->id,
            'title' => 'Test Scenario Auth Login',
            'app_version' => 'v1.2.0-rc1',
        ]);

        $tcId = $response->json('testCase.id');

        // 2. Update Test Case with new app_version
        $updateResponse = $this->actingAs($qcUser)->putJson("/api/qc/test-cases/{$tcId}", [
            'title' => 'Test Scenario Auth Login Updated',
            'preconditions' => 'User registered',
            'expected' => 'Login successful with redirect',
            'app_version' => 'v1.2.1',
            'test_type' => 'Functional',
            'complexity' => 'Medium',
            'priority' => 'High',
            'automation_status' => 'Manual'
        ]);

        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonPath('success', true);
        $updateResponse->assertJsonPath('testCase.app_version', 'v1.2.1');

        $this->assertDatabaseHas('test_cases', [
            'id' => $tcId,
            'app_version' => 'v1.2.1',
        ]);

        // 3. Verify getProjectTestCases returns app_version
        $listResponse = $this->actingAs($qcUser)->getJson("/api/projects/{$project->id}/qc/test-cases");
        $listResponse->assertStatus(200);
        $listResponse->assertJsonFragment(['app_version' => 'v1.2.1']);
    }

    public function test_can_submit_failed_test_result_with_app_version(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $client = Client::create(['name' => 'PT Client Version Test 2', 'email' => 'client2@ver.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Bug Version Test',
            'project_code' => 'PRJ-VER-02',
            'status' => 'in_progress',
        ]);

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-VER-01',
            'title' => 'Testing Task',
            'column_id' => 'qc_in_progress',
        ]);

        $tc = ProjectTestCase::create([
            'project_id' => $project->id,
            'project_task_id' => $task->id,
            'code' => 'TC-VER-01',
            'title' => 'Verify Checkout Payment',
            'app_version' => 'v2.0.0',
            'status' => 'pending',
        ]);

        // Submit failed test result with bug report and app_version
        $response = $this->actingAs($qcUser)->postJson("/api/qc/test-cases/{$tc->id}/result", [
            'status' => 'failed',
            'bug_description' => 'Payment gateway timeout error 504',
            'steps_to_reproduce' => '1. Add item to cart\n2. Click pay',
            'severity' => 'Critical',
            'actual_result' => '504 Gateway Timeout',
            'environment' => 'Production / Chrome',
            'app_version' => 'v2.0.0-hotfix1',
            'create_task' => 'false',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verify task_bugs has app_version
        $this->assertDatabaseHas('task_bugs', [
            'project_id' => $project->id,
            'test_case_id' => $tc->id,
            'app_version' => 'v2.0.0-hotfix1',
            'severity' => 'Critical',
        ]);

        // Verify getProjectBugs returns app_version
        $bugListResponse = $this->actingAs($qcUser)->getJson("/api/projects/{$project->id}/qc/bugs");
        $bugListResponse->assertStatus(200);
        $bugListResponse->assertJsonFragment(['app_version' => 'v2.0.0-hotfix1']);
    }
}
