<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\TaskBug;
use App\Models\TestCase as ProjectTestCase;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QcBugEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_user_with_permission_can_update_bug_details(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $client = Client::create(['name' => 'PT Bug Test', 'email' => 'bug@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Bug Edit',
            'project_code' => 'PRJ-BUG-01',
            'status' => 'in_progress',
        ]);

        $bug = TaskBug::create([
            'project_id' => $project->id,
            'code' => 'BUG-TEST01',
            'description' => 'Original bug description',
            'severity' => 'Low',
            'status' => 'open',
            'actual_result' => 'Old actual result',
            'environment' => 'Old environment',
            'app_version' => 'v1.0.0',
            'steps_to_reproduce' => ['Langkah 1 lama', 'Langkah 2 lama'],
        ]);

        $response = $this->actingAs($qcUser)->putJson("/api/qc/bugs/{$bug->id}", [
            'description' => 'Updated bug description',
            'severity' => 'Critical',
            'status' => 'in_progress',
            'actual_result' => 'New actual result observed',
            'environment' => 'Chrome 120 / Windows 11',
            'app_version' => 'v1.2.0',
            'steps_to_reproduce' => [
                'Buka browser dan akses halaman login',
                'Ketik username dan password yang benar',
                'Klik tombol login',
                'Amati pesan error 500'
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('bug.description', 'Updated bug description');
        $response->assertJsonPath('bug.severity', 'Critical');
        $response->assertJsonPath('bug.status', 'in_progress');
        $response->assertJsonPath('bug.environment', 'Chrome 120 / Windows 11');
        $response->assertJsonPath('bug.app_version', 'v1.2.0');

        $bug->refresh();
        $this->assertEquals('Updated bug description', $bug->description);
        $this->assertEquals('Critical', $bug->severity);
        $this->assertEquals('in_progress', $bug->status);
        $this->assertEquals('Chrome 120 / Windows 11', $bug->environment);
        $this->assertEquals('v1.2.0', $bug->app_version);
        $this->assertCount(4, $bug->steps_to_reproduce);
        $this->assertEquals('Buka browser dan akses halaman login', $bug->steps_to_reproduce[0]);
    }

    public function test_can_update_and_reorder_bug_steps_to_reproduce(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $client = Client::create(['name' => 'PT Bug Steps', 'email' => 'steps@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Bug Steps Reorder',
            'project_code' => 'PRJ-BUG-02',
            'status' => 'in_progress',
        ]);

        $bug = TaskBug::create([
            'project_id' => $project->id,
            'code' => 'BUG-REORDER01',
            'description' => 'Reordering steps bug test',
            'steps_to_reproduce' => ['Langkah A', 'Langkah B', 'Langkah C'],
        ]);

        // Reorder steps: move Langkah B to first position
        $reorderedSteps = ['Langkah B', 'Langkah A', 'Langkah C'];

        $response = $this->actingAs($qcUser)->putJson("/api/qc/bugs/{$bug->id}", [
            'description' => 'Reordering steps bug test',
            'steps_to_reproduce' => $reorderedSteps,
        ]);

        $response->assertStatus(200);

        $bug->refresh();
        $this->assertEquals($reorderedSteps, $bug->steps_to_reproduce);
        $this->assertEquals('Langkah B', $bug->steps_to_reproduce[0]);
        $this->assertEquals('Langkah A', $bug->steps_to_reproduce[1]);
        $this->assertEquals('Langkah C', $bug->steps_to_reproduce[2]);
    }

    public function test_editing_bug_steps_does_not_modify_test_case_steps(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $client = Client::create(['name' => 'PT Isolation Test', 'email' => 'iso@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Table Isolation',
            'project_code' => 'PRJ-ISO-01',
            'status' => 'in_progress',
        ]);

        $testCaseSteps = [
            '1. TC Step: Buka aplikasi',
            '2. TC Step: Masuk ke menu profil',
            '3. TC Step: Klik ubah password',
        ];

        // Create source Test Case with its own test steps
        $testCase = ProjectTestCase::create([
            'project_id' => $project->id,
            'code' => 'TC-ISOLATION01',
            'title' => 'Test Case Profil Password',
            'steps' => $testCaseSteps,
            'status' => 'failed',
        ]);

        // Create Bug linked to the above Test Case
        $bug = TaskBug::create([
            'project_id' => $project->id,
            'test_case_id' => $testCase->id,
            'code' => 'BUG-ISOLATION01',
            'description' => 'Bug pada ubah password',
            'steps_to_reproduce' => ['Bug Step awal: Klik lupa password'],
            'status' => 'open',
        ]);

        // Edit the bug report's steps to reproduce
        $newBugSteps = [
            'Langkah Bug Baru 1: Klik tombol reset password di login',
            'Langkah Bug Baru 2: Masukkan token yang kadaluarsa',
            'Langkah Bug Baru 3: Klik verifikasi',
        ];

        $response = $this->actingAs($qcUser)->putJson("/api/qc/bugs/{$bug->id}", [
            'description' => 'Bug pada ubah password terupdate',
            'steps_to_reproduce' => $newBugSteps,
        ]);

        $response->assertStatus(200);

        // Verify task_bugs table was updated
        $bug->refresh();
        $this->assertEquals($newBugSteps, $bug->steps_to_reproduce);

        // CRUCIAL: Verify test_cases table was NOT modified and still has its original steps!
        $testCase->refresh();
        $this->assertEquals($testCaseSteps, $testCase->steps);
        $this->assertNotEquals($bug->steps_to_reproduce, $testCase->steps);
    }

    public function test_unauthorized_user_cannot_update_bug(): void
    {
        $regularUser = User::factory()->create(['is_active' => true]);
        // No QC manage_bugs permission

        $client = Client::create(['name' => 'PT No Auth', 'email' => 'noauth@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project No Auth',
            'project_code' => 'PRJ-NOAUTH-01',
            'status' => 'in_progress',
        ]);

        $bug = TaskBug::create([
            'project_id' => $project->id,
            'code' => 'BUG-UNAUTH01',
            'description' => 'Unauthorized test bug',
        ]);

        $response = $this->actingAs($regularUser)->putJson("/api/qc/bugs/{$bug->id}", [
            'description' => 'Hacked bug description',
        ]);

        $response->assertStatus(403);
    }

    public function test_can_replace_and_remove_bug_attachment(): void
    {
        Storage::fake('public');

        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $client = Client::create(['name' => 'PT Attach Test', 'email' => 'attach@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Attachment',
            'project_code' => 'PRJ-ATT-01',
            'status' => 'in_progress',
        ]);

        $oldFile = UploadedFile::fake()->create('old_screenshot.png', 500);
        $oldPath = $oldFile->store('attachments/bugs', 'public');

        $bug = TaskBug::create([
            'project_id' => $project->id,
            'code' => 'BUG-ATT01',
            'description' => 'Bug with attachment',
            'attachment_path' => $oldPath,
        ]);

        Storage::disk('public')->assertExists($oldPath);

        // Replace attachment
        $newFile = UploadedFile::fake()->create('new_screenshot.png', 600);
        $replaceResponse = $this->actingAs($qcUser)->postJson("/api/qc/bugs/{$bug->id}", [
            'description' => 'Bug with attachment replaced',
            'attachment' => $newFile,
        ]);

        $replaceResponse->assertStatus(200);

        $bug->refresh();
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($bug->attachment_path);

        // Remove attachment
        $currentPath = $bug->attachment_path;
        $removeResponse = $this->actingAs($qcUser)->postJson("/api/qc/bugs/{$bug->id}", [
            'description' => 'Bug with attachment removed',
            'remove_attachment' => '1',
        ]);

        $removeResponse->assertStatus(200);

        $bug->refresh();
        $this->assertNull($bug->attachment_path);
        Storage::disk('public')->assertMissing($currentPath);
    }
}
