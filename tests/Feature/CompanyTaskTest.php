<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CompanyTask;
use App\Models\User;
use Database\Seeders\CompanyTaskSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTaskTest extends TestCase
{
    use RefreshDatabase;

    protected $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->superadmin = User::factory()->create([
            'email' => 'superadmin@test.com',
            'is_active' => true,
        ]);
        $this->superadmin->syncRoles(['superadmin']);

        $this->seed(CompanyTaskSeeder::class);
    }

    public function test_superadmin_can_access_company_tasks_page(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('company-tasks.index'));
        $response->assertStatus(200);
        $response->assertSee('Aktivitas & Task Internal');
        $response->assertSee('Pertemuan Penjajakan Kebutuhan Sistem');
        $response->assertSee('Kalender & Tenggat', false);
        $response->assertSee('Tenggat & Agenda Terdekat', false);
        $response->assertSee('Tambah Petugas');
        $response->assertSee('Pilih Karyawan / Petugas');
    }

    public function test_can_create_new_company_task_with_multiple_assignees(): void
    {
        $client = Client::create([
            'name' => 'PT Mitra Sejahtera',
            'email' => 'mitra@test.com',
        ]);

        $secondUser = User::factory()->create(['is_active' => true]);

        $data = [
            'title' => 'Pertemuan Pembahasan Draf SPK Klien',
            'description' => 'Meeting koordinasi final sebelum penandatanganan dokumen kontrak.',
            'type' => 'meeting',
            'status' => 'todo',
            'priority' => 'high',
            'client_id' => $client->id,
            'assignee_ids' => [$this->superadmin->id, $secondUser->id],
            'due_date' => '2026-11-01',
            'meeting_time' => '2026-11-01 10:00:00',
            'meeting_location' => 'Kantor Wirodev',
        ];

        $response = $this->actingAs($this->superadmin)
            ->postJson(route('company-tasks.store'), $data);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);

        $createdTask = CompanyTask::where('title', 'Pertemuan Pembahasan Draf SPK Klien')->first();
        $this->assertNotNull($createdTask);
        $this->assertCount(2, $createdTask->assignees);
        $this->assertTrue($createdTask->assignees->contains($this->superadmin->id));
        $this->assertTrue($createdTask->assignees->contains($secondUser->id));
    }

    public function test_can_update_company_task_assignees(): void
    {
        $task = CompanyTask::first();
        $secondUser = User::factory()->create(['is_active' => true]);

        $updateData = [
            'title' => 'Judul Tugas Diperbarui',
            'description' => 'Catatan revisi baru.',
            'type' => 'proposal',
            'status' => 'in_progress',
            'priority' => 'urgent',
            'client_id' => $task->client_id,
            'assignee_ids' => [$secondUser->id],
            'due_date' => '2026-11-05',
        ];

        $response = $this->actingAs($this->superadmin)
            ->putJson(route('company-tasks.update', $task), $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $task->refresh();
        $this->assertCount(1, $task->assignees);
        $this->assertEquals($secondUser->id, $task->assignees->first()->id);
        $this->assertEquals('Judul Tugas Diperbarui', $task->title);
        $this->assertEquals('in_progress', $task->status);
    }

    public function test_can_move_task_status_drag_and_drop(): void
    {
        $task = CompanyTask::where('status', 'todo')->first();

        $response = $this->actingAs($this->superadmin)
            ->postJson(route('company-tasks.move', $task), [
                'status' => 'done',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('company_tasks', [
            'id' => $task->id,
            'status' => 'done',
        ]);
    }

    public function test_can_delete_company_task(): void
    {
        $task = CompanyTask::create([
            'code' => 'INT-TEST-DEL',
            'title' => 'Tugas Sementara Untuk Dihapus',
            'type' => 'general',
            'status' => 'todo',
            'priority' => 'low',
        ]);

        $response = $this->actingAs($this->superadmin)
            ->deleteJson(route('company-tasks.destroy', $task));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('company_tasks', [
            'id' => $task->id,
        ]);
    }
}
