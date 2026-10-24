<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\CompanyTask;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CompanyTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'superadmin')->first() ?? User::first();
        $staff = User::where('email', 'sekretaris@wirodev.com')->first() ?? User::where('id', '!=', $admin->id)->first();
        $client = Client::first();

        $tasks = [
            [
                'code' => 'INT-001',
                'title' => 'Pertemuan Penjajakan Kebutuhan Sistem & Konsultasi MVP',
                'description' => 'Diskusi awal mengenai spesifikasi modul e-commerce, integrasi payment gateway, dan estimasi timeline pengerjaan.',
                'type' => 'meeting',
                'status' => 'todo',
                'priority' => 'high',
                'client_id' => $client ? $client->id : null,
                'assignee_id' => $admin ? $admin->id : null,
                'created_by' => $admin ? $admin->id : null,
                'due_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
                'meeting_time' => Carbon::now()->addDays(2)->setHour(14)->setMinute(0)->setSecond(0),
                'meeting_location' => 'Google Meet (meet.google.com/abc-defg-hij)',
                'sort_order' => 1,
                'assignee_ids' => array_filter([$admin?->id, $staff?->id]),
            ],
            [
                'code' => 'INT-002',
                'title' => 'Penyusunan Proposal Proyek & Draf Quotation Klien',
                'description' => 'Menyusun dokumen penawaran harga resmi, rincian deliverables, dan termin pembayaran untuk proposal klien baru.',
                'type' => 'proposal',
                'status' => 'in_progress',
                'priority' => 'urgent',
                'client_id' => $client ? $client->id : null,
                'assignee_id' => $admin ? $admin->id : null,
                'created_by' => $admin ? $admin->id : null,
                'due_date' => Carbon::now()->addDays(3)->format('Y-m-d'),
                'meeting_time' => null,
                'meeting_location' => null,
                'sort_order' => 1,
                'assignee_ids' => array_filter([$admin?->id, $staff?->id]),
            ],
            [
                'code' => 'INT-003',
                'title' => 'Menunggu Konfirmasi & Tanda Tangan Kontrak Klien',
                'description' => 'Proposal dan draf SPK sudah dikirimkan via email, menunggu hasil evaluasi dewan direksi klien.',
                'type' => 'proposal',
                'status' => 'waiting_client',
                'priority' => 'medium',
                'client_id' => $client ? $client->id : null,
                'assignee_id' => $admin ? $admin->id : null,
                'created_by' => $admin ? $admin->id : null,
                'due_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
                'meeting_time' => null,
                'meeting_location' => null,
                'sort_order' => 1,
                'assignee_ids' => array_filter([$admin?->id]),
            ],
            [
                'code' => 'INT-004',
                'title' => 'Penyusunan Laporan Keuangan & Rekap Kas Akhir Bulan',
                'description' => 'Rekap pemasukan invoice, pengeluaran operasional perusahaan, mutasi bank, dan persiapan slip gaji tim.',
                'type' => 'report',
                'status' => 'todo',
                'priority' => 'high',
                'client_id' => null,
                'assignee_id' => $admin ? $admin->id : null,
                'created_by' => $admin ? $admin->id : null,
                'due_date' => Carbon::now()->endOfMonth()->format('Y-m-d'),
                'meeting_time' => null,
                'meeting_location' => null,
                'sort_order' => 2,
                'assignee_ids' => array_filter([$admin?->id, $staff?->id]),
            ],
            [
                'code' => 'INT-005',
                'title' => 'Audit Keamanan Server & Backup Rutin Database Perusahaan',
                'description' => 'Pengecekan update security patch Ubuntu, rotasi SSL cert, dan verifikasi snapshot backup database harian.',
                'type' => 'operational',
                'status' => 'done',
                'priority' => 'medium',
                'client_id' => null,
                'assignee_id' => $admin ? $admin->id : null,
                'created_by' => $admin ? $admin->id : null,
                'due_date' => Carbon::now()->subDays(1)->format('Y-m-d'),
                'meeting_time' => null,
                'meeting_location' => null,
                'sort_order' => 1,
                'assignee_ids' => array_filter([$admin?->id]),
            ],
        ];

        foreach ($tasks as $taskData) {
            $assigneeIds = $taskData['assignee_ids'] ?? [];
            unset($taskData['assignee_ids']);
            $task = CompanyTask::updateOrCreate(['code' => $taskData['code']], $taskData);
            if (!empty($assigneeIds)) {
                $task->assignees()->sync($assigneeIds);
            }
        }
    }
}
