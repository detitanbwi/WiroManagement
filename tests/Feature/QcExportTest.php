<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TaskBug;
use App\Models\TestCase;
use App\Models\User;
use App\Services\QcExportService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase as BaseTestCase;

class QcExportTest extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_qc_export_service_generates_three_sheets_with_data(): void
    {
        $client = Client::create([
            'name' => 'PT Test Client',
            'email' => 'client@test.com',
        ]);

        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Test Project Export',
            'project_code' => 'PRJ-TEST-01',
            'status' => 'in_progress',
        ]);

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-TEST-1',
            'title' => 'Test Task 1',
            'description' => 'Test task description',
            'column_id' => 'ready_for_qc',
        ]);

        // Root Parent Test Case
        $tcParent = TestCase::create([
            'project_id' => $project->id,
            'project_task_id' => $task->id,
            'code' => 'TC-PARENT-1',
            'title' => 'Modul Dashboard',
            'status' => 'passed',
            'sort_order' => 10,
        ]);

        // Child Test Case under tcParent
        $tcChild = TestCase::create([
            'project_id' => $project->id,
            'parent_id' => $tcParent->id,
            'code' => 'TC-CHILD-1',
            'title' => 'Verify Chart Data',
            'status' => 'passed',
            'steps' => ['Step 1', 'Step 2'],
            'priority' => 'High',
            'complexity' => 'Medium',
            'test_type' => 'Functional',
            'expected' => 'Expected output',
            'sort_order' => 20,
        ]);

        // Second Root Test Case to verify children stay with their parent
        $tcParent2 = TestCase::create([
            'project_id' => $project->id,
            'code' => 'TC-PARENT-2',
            'title' => 'Modul Authentication',
            'status' => 'pending',
            'sort_order' => 30,
        ]);

        $bug = TaskBug::create([
            'project_id' => $project->id,
            'project_task_id' => $task->id,
            'test_case_id' => $tcChild->id,
            'code' => 'BUG-TEST-1',
            'description' => 'Test bug description',
            'severity' => 'Critical',
            'status' => 'open',
            'actual_result' => 'Actual bad result',
            'environment' => 'Production / Chrome',
        ]);

        $service = new QcExportService();
        $spreadsheet = $service->generate($project);

        $this->assertInstanceOf(Spreadsheet::class, $spreadsheet);
        $this->assertEquals(3, $spreadsheet->getSheetCount());

        $sheetNames = $spreadsheet->getSheetNames();
        $this->assertEquals(['Kanban', 'Test Case', 'Bug'], $sheetNames);

        // Verify Sheet 1 (Kanban)
        $kanbanSheet = $spreadsheet->getSheetByName('Kanban');
        $this->assertNotNull($kanbanSheet);
        $this->assertStringContainsString('KANBAN BOARD TASKS', $kanbanSheet->getCell('A1')->getValue());
        $this->assertEquals('Kode Task', $kanbanSheet->getCell('B4')->getValue());
        $this->assertEquals('TSK-TEST-1', $kanbanSheet->getCell('B5')->getValue());
        $this->assertEquals('Ready for QC', $kanbanSheet->getCell('D5')->getValue());

        // Verify Sheet 2 (Test Case)
        $tcSheet = $spreadsheet->getSheetByName('Test Case');
        $this->assertNotNull($tcSheet);
        $this->assertStringContainsString('PROJECT TEST CASES', $tcSheet->getCell('A1')->getValue());
        $this->assertEquals('Kode TC', $tcSheet->getCell('B4')->getValue());
        $this->assertEquals('Modul / Parent', $tcSheet->getCell('C4')->getValue());
        $this->assertEquals('Sub Test Case / Skenario (Anak)', $tcSheet->getCell('D4')->getValue());

        // Row 5: Parent 1
        $this->assertEquals('1', $tcSheet->getCell('A5')->getValue());
        $this->assertEquals('TC-PARENT-1', $tcSheet->getCell('B5')->getValue());
        $this->assertEquals('Modul Dashboard', $tcSheet->getCell('C5')->getValue());
        $this->assertEquals('-', $tcSheet->getCell('D5')->getValue());

        // Row 6: Child 1 (directly below Parent 1, shifted one column right to Column D)
        $this->assertEquals('1.1', $tcSheet->getCell('A6')->getValue());
        $this->assertEquals('TC-CHILD-1', $tcSheet->getCell('B6')->getValue());
        $this->assertEquals('', $tcSheet->getCell('C6')->getValue());
        $this->assertStringContainsString('Verify Chart Data', $tcSheet->getCell('D6')->getValue());
        $this->assertStringContainsString('Step 1', $tcSheet->getCell('K6')->getValue());

        // Row 7: Parent 2 (after Child 1)
        $this->assertEquals('2', $tcSheet->getCell('A7')->getValue());
        $this->assertEquals('TC-PARENT-2', $tcSheet->getCell('B7')->getValue());
        $this->assertEquals('Modul Authentication', $tcSheet->getCell('C7')->getValue());
        $this->assertEquals('-', $tcSheet->getCell('D7')->getValue());

        // Verify Sheet 3 (Bug)
        $bugSheet = $spreadsheet->getSheetByName('Bug');
        $this->assertNotNull($bugSheet);
        $this->assertStringContainsString('DEFECTS & BUG TRACKER', $bugSheet->getCell('A1')->getValue());
        $this->assertEquals('Kode Bug', $bugSheet->getCell('B4')->getValue());
        $this->assertEquals('BUG-TEST-1', $bugSheet->getCell('B5')->getValue());
        $this->assertEquals('Critical', $bugSheet->getCell('C5')->getValue());
    }

    public function test_qc_export_excel_route_downloads_file(): void
    {
        $user = User::factory()->create([
            'email' => 'admin_export@example.com',
            'is_active' => true,
        ]);
        $user->syncRoles(['qc']);

        $client = Client::create([
            'name' => 'PT Test Client 2',
            'email' => 'client2@test.com',
        ]);

        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Test Project Route',
            'project_code' => 'PRJ-TEST-02',
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($user)->get(route('projects.qc.export-excel', $project->id));

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
        $this->assertStringContainsString(
            'QA_QC_',
            $response->headers->get('Content-Disposition')
        );
        $this->assertStringContainsString(
            '.xlsx',
            $response->headers->get('Content-Disposition')
        );
    }

    public function test_qc_export_excel_works_with_empty_project(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->syncRoles(['admin']);

        $client = Client::create(['name' => 'PT Client Empty', 'email' => 'empty@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Empty Project',
            'project_code' => 'PRJ-EMPTY',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->get(route('projects.qc.export-excel', $project->id));
        $response->assertStatus(200);

        $service = new QcExportService();
        $spreadsheet = $service->generate($project);
        $this->assertEquals(3, $spreadsheet->getSheetCount());
        $this->assertEquals(['Kanban', 'Test Case', 'Bug'], $spreadsheet->getSheetNames());
    }

    public function test_unauthorized_internal_role_cannot_export_qc_excel(): void
    {
        $financeUser = User::factory()->create(['is_active' => true]);
        $financeUser->syncRoles(['finance']);

        $client = Client::create(['name' => 'PT Client Test', 'email' => 'client_unauth@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Protected Project',
            'project_code' => 'PRJ-PROT',
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($financeUser)->get(route('projects.qc.export-excel', $project->id));
        $response->assertStatus(403);
    }
}
