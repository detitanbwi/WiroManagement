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

class QcTestCaseDuplicateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_get_project_test_cases_includes_parent_id(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $client = Client::create(['name' => 'PT Client Test', 'email' => 'client@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Tree Test',
            'project_code' => 'PRJ-TREE-01',
            'status' => 'in_progress',
        ]);

        $parentTc = ProjectTestCase::create([
            'project_id' => $project->id,
            'parent_id' => null,
            'code' => 'TC-PARENT01',
            'title' => 'Parent Test Case',
            'status' => 'pending',
        ]);

        $childTc = ProjectTestCase::create([
            'project_id' => $project->id,
            'parent_id' => $parentTc->id,
            'code' => 'TC-CHILD01',
            'title' => 'Child Test Case',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($qcUser)->getJson("/api/projects/{$project->id}/qc/test-cases");
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals($parentTc->id, $data[0]['id']);
        $this->assertNull($data[0]['parent_id']);

        $this->assertCount(1, $data[0]['children']);
        $this->assertEquals($childTc->id, $data[0]['children'][0]['id']);
        $this->assertEquals($parentTc->id, $data[0]['children'][0]['parent_id']);
    }

    public function test_duplicate_child_test_case_maintains_same_parent(): void
    {
        $qcUser = User::factory()->create(['is_active' => true]);
        $qcUser->syncRoles(['qc']);

        $client = Client::create(['name' => 'PT Client Dup', 'email' => 'clientdup@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Project Duplicate Test',
            'project_code' => 'PRJ-DUP-01',
            'status' => 'in_progress',
        ]);

        // 1. Create Parent Test Case
        $parentResponse = $this->actingAs($qcUser)->postJson("/api/projects/{$project->id}/qc/test-cases", [
            'title' => 'Feature Authentication',
            'test_type' => 'Functional',
            'complexity' => 'High',
            'priority' => 'Critical',
        ]);
        $parentResponse->assertStatus(200);
        $parentId = $parentResponse->json('testCase.id');

        // 2. Create Child Test Case under Parent
        $childResponse = $this->actingAs($qcUser)->postJson("/api/projects/{$project->id}/qc/test-cases", [
            'title' => 'Login with valid email & password',
            'parent_id' => $parentId,
            'preconditions' => 'User is registered',
            'expected' => 'Redirect to dashboard',
            'steps' => ['Open login page', 'Type credentials', 'Click submit'],
            'complexity' => 'Medium',
            'priority' => 'High',
            'test_type' => 'Functional',
            'automation_status' => 'Manual',
            'app_version' => 'v1.0.0',
        ]);
        $childResponse->assertStatus(200);
        $childId = $childResponse->json('testCase.id');

        $this->assertDatabaseHas('test_cases', [
            'id' => $childId,
            'parent_id' => $parentId,
            'title' => 'Login with valid email & password',
        ]);

        // 3. Duplicate Child Test Case (client sends parent_id = $childTc->parent_id)
        $duplicateResponse = $this->actingAs($qcUser)->postJson("/api/projects/{$project->id}/qc/test-cases", [
            'title' => 'Login with valid email & password (Copy)',
            'parent_id' => $parentId,
            'preconditions' => 'User is registered',
            'expected' => 'Redirect to dashboard',
            'steps' => ['Open login page', 'Type credentials', 'Click submit'],
            'complexity' => 'Medium',
            'priority' => 'High',
            'test_type' => 'Functional',
            'automation_status' => 'Manual',
            'app_version' => 'v1.0.0',
        ]);
        $duplicateResponse->assertStatus(200);
        $duplicateId = $duplicateResponse->json('testCase.id');

        $this->assertNotEquals($childId, $duplicateId);
        $this->assertDatabaseHas('test_cases', [
            'id' => $duplicateId,
            'parent_id' => $parentId,
            'title' => 'Login with valid email & password (Copy)',
        ]);

        // 4. Verify in the tree structure that both child and duplicate child belong to the same parent
        $treeResponse = $this->actingAs($qcUser)->getJson("/api/projects/{$project->id}/qc/test-cases");
        $treeResponse->assertStatus(200);

        $tree = $treeResponse->json();
        $this->assertCount(1, $tree, 'There should only be 1 root test case');
        $this->assertEquals($parentId, $tree[0]['id']);
        $this->assertCount(2, $tree[0]['children'], 'The parent should have both original child and duplicate child');

        $childrenIds = array_column($tree[0]['children'], 'id');
        $this->assertContains($childId, $childrenIds);
        $this->assertContains($duplicateId, $childrenIds);

        foreach ($tree[0]['children'] as $child) {
            $this->assertEquals($parentId, $child['parent_id'], 'Every child under the parent must have parent_id matching parent');
        }
    }
}
