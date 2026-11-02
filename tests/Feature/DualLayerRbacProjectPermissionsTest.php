<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Role;
use App\Models\TaskBug;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DualLayerRbacProjectPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    /**
     * Skenario 1:
     * User dengan role global 'staff' yang ditugaskan sebagai 'pm' di Proyek A dapat melakukan:
     * - POST /projects/{project}/gantt/tasks (Tambah task Gantt) -> 200 OK
     * - POST /projects/gantt/tasks/{task}/interactive (Geser durasi/tanggal interaktif) -> 200 OK
     * - DELETE /projects/gantt/tasks/{task} (Hapus task Gantt) -> 200 OK
     */
    public function test_global_staff_assigned_as_pm_in_project_can_manage_gantt_full(): void
    {
        $client = Client::create(['name' => 'Klien Proyek A', 'email' => 'client-a@test.com']);
        $projectA = Project::create([
            'client_id' => $client->id,
            'title' => 'Proyek Alpha Dual Layer RBAC',
            'contract_value' => 25000000,
            'status' => 'in_progress',
        ]);

        // User dengan role global 'staff'
        $user = User::factory()->create(['is_active' => true]);
        $user->syncRoles(['staff']);

        // Tugaskan sebagai 'pm' pada Proyek A melalui tabel project_user_role
        $projectA->assignMember($user, ['pm'], 'Project Manager');

        // 1. Tambah task Gantt -> 200 OK
        $responseStore = $this->actingAs($user)->postJson(route('projects.gantt.tasks.store', $projectA), [
            'title' => 'WBS Milestones by Project PM',
            'start_date' => '2026-11-01',
            'due_date' => '2026-11-10',
            'progress' => 0,
            'status' => 'todo',
        ]);

        $responseStore->assertStatus(200);
        $this->assertDatabaseHas('project_tasks', [
            'project_id' => $projectA->id,
            'title' => 'WBS Milestones by Project PM',
        ]);

        $task = ProjectTask::where('project_id', $projectA->id)
            ->where('title', 'WBS Milestones by Project PM')
            ->firstOrFail();

        // 2. Geser durasi/tanggal interaktif -> 200 OK (rute child: projects/gantt/tasks/{task}/interactive)
        $responseInteractive = $this->actingAs($user)->postJson(route('projects.gantt.tasks.interactive', $task), [
            'start_date' => '2026-11-02',
            'due_date' => '2026-11-12',
            'progress' => 45,
        ]);

        $responseInteractive->assertStatus(200);
        $task->refresh();
        $this->assertEquals(45, (float)$task->progress);
        $this->assertEquals('2026-11-02', $task->start_date?->format('Y-m-d'));

        // 3. Hapus task Gantt -> 200 OK (rute child: DELETE projects/gantt/tasks/{task})
        $responseDelete = $this->actingAs($user)->deleteJson(route('projects.gantt.tasks.destroy', $task));
        $responseDelete->assertStatus(200);
        $this->assertDatabaseMissing('project_tasks', [
            'id' => $task->id,
        ]);
    }

    /**
     * Skenario 2:
     * User dengan role global 'staff' yang berposisi sebagai 'staff' (Programmer/Desainer) di Proyek A:
     * - Dapat mengakses GET /projects/{project}/gantt -> 200 OK (Mode Lihat)
     * - Ditolak saat POST /projects/gantt/tasks/{task}/interactive -> 403 Forbidden
     * - Berhasil mencatat log jam kerja mandirinya di proyek tersebut -> 200/302 OK
     */
    public function test_global_staff_assigned_as_staff_in_project_gantt_and_work_log_behavior(): void
    {
        $client = Client::create(['name' => 'Klien Proyek A', 'email' => 'client-a2@test.com']);
        $projectA = Project::create([
            'client_id' => $client->id,
            'title' => 'Proyek Alpha Staff Behavior',
            'contract_value' => 15000000,
            'status' => 'in_progress',
        ]);

        $existingTask = ProjectTask::create([
            'project_id' => $projectA->id,
            'type' => 'gantt',
            'code' => 'TSK-001',
            'title' => 'Task Pengerjaan Fitur A',
            'start_date' => '2026-11-01',
            'due_date' => '2026-11-05',
            'duration_days' => 5,
            'progress' => 0,
            'status' => 'todo',
        ]);

        // User dengan role global 'staff'
        $staffUser = User::factory()->create(['is_active' => true]);
        $staffUser->syncRoles(['staff']);

        // Ditugaskan sebagai 'staff' di Proyek A
        $projectA->assignMember($staffUser, ['staff'], 'Programmer');

        // 1. Dapat mengakses GET /projects/{project}/gantt -> 200 OK (Mode Lihat)
        $responseGetGantt = $this->actingAs($staffUser)->get(route('projects.gantt', $projectA));
        $responseGetGantt->assertStatus(200);

        // 2. Ditolak saat POST /projects/gantt/tasks/{task}/interactive -> 403 Forbidden
        $responseForbiddenInteractive = $this->actingAs($staffUser)->postJson(
            route('projects.gantt.tasks.interactive', $existingTask),
            [
                'start_date' => '2026-11-02',
                'due_date' => '2026-11-06',
                'progress' => 30,
            ]
        );
        $responseForbiddenInteractive->assertStatus(403);

        // 3. Berhasil mencatat log jam kerja mandirinya di proyek tersebut -> 200/302 OK
        $responseWorkLog = $this->actingAs($staffUser)->post(route('finance.work-logs.store'), [
            'project_id' => $projectA->id,
            'user_id' => $staffUser->id,
            'work_date' => '2026-11-02',
            'end_date' => '2026-11-02',
            'hours_worked' => 8,
            'source_account_type' => 'office_account',
            'payment_status' => 'unpaid',
            'notes' => 'Pengerjaan API integrasi modul pembayaran',
        ]);

        $this->assertTrue(in_array($responseWorkLog->getStatusCode(), [200, 302]));
        $this->assertDatabaseHas('project_work_logs', [
            'project_id' => $projectA->id,
            'user_id' => $staffUser->id,
            'notes' => 'Pengerjaan API integrasi modul pembayaran',
            'payment_status' => 'unpaid',
        ]);
    }

    /**
     * Verifikasi tambahan: Batasan Timesheet Mandiri
     * - Staf tidak boleh mencatat log kerja untuk user lain (403 Forbidden)
     * - Staf tidak boleh mencatat log kerja pada proyek yang bukan timnya (403 Forbidden)
     * - Staf tidak dapat memanipulasi status pembayaran menjadi 'paid' secara langsung
     */
    public function test_self_work_log_restrictions_for_staff(): void
    {
        $client = Client::create(['name' => 'Klien Proyek B', 'email' => 'client-b@test.com']);
        $projectA = Project::create([
            'client_id' => $client->id,
            'title' => 'Proyek Alpha Member',
            'contract_value' => 20000000,
            'status' => 'in_progress',
        ]);

        $projectB = Project::create([
            'client_id' => $client->id,
            'title' => 'Proyek Beta Unassigned',
            'contract_value' => 20000000,
            'status' => 'in_progress',
        ]);

        $staffUser = User::factory()->create(['is_active' => true]);
        $staffUser->syncRoles(['staff']);
        $projectA->assignMember($staffUser, ['staff'], 'UI/UX Designer');

        $colleague = User::factory()->create(['is_active' => true]);
        $colleague->syncRoles(['staff']);
        $projectA->assignMember($colleague, ['staff'], 'Backend Dev');

        // A. Mencoba mencatat log kerja untuk kolega -> 403 Forbidden
        $responseOther = $this->actingAs($staffUser)->post(route('finance.work-logs.store'), [
            'project_id' => $projectA->id,
            'user_id' => $colleague->id,
            'work_date' => '2026-11-02',
            'hours_worked' => 8,
            'source_account_type' => 'office_account',
            'payment_status' => 'unpaid',
        ]);
        $responseOther->assertStatus(403);

        // B. Mencoba mencatat log kerja di proyek B di mana staf tidak terdaftar -> 403 Forbidden
        $responseNonMember = $this->actingAs($staffUser)->post(route('finance.work-logs.store'), [
            'project_id' => $projectB->id,
            'user_id' => $staffUser->id,
            'work_date' => '2026-11-02',
            'hours_worked' => 8,
            'source_account_type' => 'office_account',
            'payment_status' => 'unpaid',
        ]);
        $responseNonMember->assertStatus(403);

        // C. Jika staf mencoba mengirim payment_status 'paid', sistem memaksanya menjadi 'unpaid'
        $responsePaidAttempt = $this->actingAs($staffUser)->post(route('finance.work-logs.store'), [
            'project_id' => $projectA->id,
            'user_id' => $staffUser->id,
            'work_date' => '2026-11-02',
            'hours_worked' => 8,
            'source_account_type' => 'office_account',
            'payment_status' => 'paid',
            'notes' => 'Attempt marked paid',
        ]);
        $this->assertTrue(in_array($responsePaidAttempt->getStatusCode(), [200, 302]));
        $this->assertDatabaseHas('project_work_logs', [
            'project_id' => $projectA->id,
            'user_id' => $staffUser->id,
            'notes' => 'Attempt marked paid',
            'payment_status' => 'unpaid',
        ]);
    }

    /**
     * Verifikasi tambahan: Pelaporan Bug oleh Staf Teknis
     * - Staf dapat membuat bug baru via api.qc.bugs.store
     * - Keputusan konversi bug ke task dimoderasi (Staf -> 403)
     * - Keputusan penutupan status bug (closed/resolved) dimoderasi (Staf -> 403)
     */
    public function test_staff_bug_reporting_and_moderation_rules(): void
    {
        $client = Client::create(['name' => 'Klien Proyek QC', 'email' => 'client-qc@test.com']);
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Proyek QC Defect Reporting',
            'status' => 'in_progress',
        ]);

        $staffUser = User::factory()->create(['is_active' => true]);
        $staffUser->syncRoles(['staff']);
        $project->assignMember($staffUser, ['staff'], 'DevOps Engineer');

        // 1. Staf melaporkan bug baru -> 201 Created
        $responseBug = $this->actingAs($staffUser)->postJson(route('api.qc.bugs.store', $project), [
            'description' => 'Memory leak pada container Redis',
            'severity' => 'Critical',
            'environment' => 'Production',
        ]);

        $responseBug->assertStatus(201);
        $bugId = $responseBug->json('bug.id');
        $this->assertDatabaseHas('task_bugs', [
            'id' => $bugId,
            'project_id' => $project->id,
            'description' => 'Memory leak pada container Redis',
            'status' => 'open',
        ]);

        $bug = TaskBug::findOrFail($bugId);

        // 2. Staf ditolak saat mencoba konversi bug menjadi task -> 403 Forbidden
        $responseConvert = $this->actingAs($staffUser)->postJson(route('api.qc.bugs.convert', $bug));
        $responseConvert->assertStatus(403);

        // 3. Staf ditolak saat mencoba menutup bug (status: closed/resolved) -> 403 Forbidden
        $responseClose = $this->actingAs($staffUser)->putJson(
            route('api.qc.bugs.update', $bug),
            [
                'description' => 'Memory leak resolved by restarting service',
                'status' => 'closed',
            ]
        );
        $responseClose->assertStatus(403);
    }
}
