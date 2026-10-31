<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Modul: Pengguna & Peran
            ['name' => 'users.view', 'label' => 'Lihat Pengguna', 'group' => 'Pengguna & Peran', 'description' => 'Melihat daftar seluruh pengguna sistem.'],
            ['name' => 'users.create', 'label' => 'Tambah Pengguna', 'group' => 'Pengguna & Peran', 'description' => 'Mendaftarkan akun staf baru ke sistem.'],
            ['name' => 'users.edit', 'label' => 'Edit Pengguna', 'group' => 'Pengguna & Peran', 'description' => 'Mengubah profil, status akun, dan penugasan peran pengguna.'],
            ['name' => 'users.delete', 'label' => 'Hapus Pengguna', 'group' => 'Pengguna & Peran', 'description' => 'Menghapus akun pengguna dari sistem.'],
            ['name' => 'roles.manage', 'label' => 'Kelola Peran & Izin', 'group' => 'Pengguna & Peran', 'description' => 'Menambah peran baru dan mengatur konfigurasi matriks hak akses.'],

            // Modul: Pengaturan Sistem
            ['name' => 'settings.manage', 'label' => 'Kelola Pengaturan', 'group' => 'Pengaturan Sistem', 'description' => 'Mengubah konfigurasi sistem, terms dokumen, dan rate-card kalkulator.'],

            // Modul: Klien
            ['name' => 'clients.view', 'label' => 'Lihat Klien', 'group' => 'Manajemen Klien', 'description' => 'Melihat data profil dan daftar klien.'],
            ['name' => 'clients.create', 'label' => 'Tambah Klien', 'group' => 'Manajemen Klien', 'description' => 'Mendaftarkan data mitra/klien baru.'],
            ['name' => 'clients.edit', 'label' => 'Edit Klien', 'group' => 'Manajemen Klien', 'description' => 'Mengubah profil showcase, kontak, dan info bisnis klien.'],
            ['name' => 'clients.delete', 'label' => 'Hapus Klien', 'group' => 'Manajemen Klien', 'description' => 'Menghapus data klien dari sistem.'],
            ['name' => 'clients.portal_credentials', 'label' => 'Kredensial Portal Klien', 'group' => 'Manajemen Klien', 'description' => 'Membuat link aktivasi atau kredensial instan untuk portal Wiromitra.'],

            // Modul: Proyek
            ['name' => 'projects.view', 'label' => 'Lihat Proyek', 'group' => 'Manajemen Proyek', 'description' => 'Melihat daftar dan ringkasan proyek.'],
            ['name' => 'projects.create', 'label' => 'Tambah Proyek', 'group' => 'Manajemen Proyek', 'description' => 'Membuat proyek baru untuk klien.'],
            ['name' => 'projects.manage', 'label' => 'Kelola Proyek', 'group' => 'Manajemen Proyek', 'description' => 'Mengakses tombol kelola untuk melihat detail proyek, invoice, dan ringkasan.'],
            ['name' => 'projects.view_financial', 'label' => 'Lihat Finansial Proyek', 'group' => 'Manajemen Proyek', 'description' => 'Melihat tampilan kolom keuangan, nilai proyek, dan sisa tagihan pada daftar proyek.'],
            ['name' => 'projects.qc', 'label' => 'Akses Fitur QC Proyek', 'group' => 'Manajemen Proyek', 'description' => 'Melihat tombol dan mengakses fitur papan pengujian QA/QC proyek.'],
            ['name' => 'projects.gantt', 'label' => 'Akses Fitur Gantt Chart', 'group' => 'Manajemen Proyek', 'description' => 'Melihat tombol dan mengakses tampilan visual diagram Gantt Chart & timeline WBS proyek.'],
            ['name' => 'projects.gantt_manage', 'label' => 'Kelola Gantt Chart & WBS', 'group' => 'Manajemen Proyek', 'description' => 'Membuat tugas WBS baru, memperbarui jadwal & durasi interaktif, menghubungkan dependensi antar tugas, dan menghapus tugas Gantt.'],
            ['name' => 'projects.edit', 'label' => 'Edit Proyek', 'group' => 'Manajemen Proyek', 'description' => 'Mengubah informasi proyek, timeline, dan spesifikasi.'],
            ['name' => 'projects.delete', 'label' => 'Hapus Proyek', 'group' => 'Manajemen Proyek', 'description' => 'Menghapus proyek dari sistem.'],
            ['name' => 'projects.status', 'label' => 'Ubah Status Proyek', 'group' => 'Manajemen Proyek', 'description' => 'Memperbarui alur status proyek (in_progress, completed, dll).'],

            // Modul: Quality Control & QA
            ['name' => 'qc.view', 'label' => 'Akses Papan QA / QC', 'group' => 'Quality Control (QA)', 'description' => 'Melihat board task, test case, dan pelacakan bug proyek.'],
            ['name' => 'qc.manage_tasks', 'label' => 'Kelola Task QC', 'group' => 'Quality Control (QA)', 'description' => 'Membuat task, memindahkan kolom board, dan menghapus task QA.'],
            ['name' => 'qc.manage_test_cases', 'label' => 'Kelola Test Case', 'group' => 'Quality Control (QA)', 'description' => 'Merancang skenario uji, mengedit, dan menghapus test case.'],
            ['name' => 'qc.execute_tests', 'label' => 'Kontrol Run Testing (Pass / Fail)', 'group' => 'Quality Control (QA)', 'description' => 'Menjalankan run testing serta menentukan status kelulusan pengujian (Pass / Fail). Keputusan lulus/gagal pengujian berada pada peran ini (standar: QC).'],
            ['name' => 'qc.manage_bugs', 'label' => 'Kelola Bug & Konversi', 'group' => 'Quality Control (QA)', 'description' => 'Melaporkan bug baru dan mengonversinya menjadi task pengerjaan.'],
            ['name' => 'qc.comments', 'label' => 'Komentar & Lampiran QC', 'group' => 'Quality Control (QA)', 'description' => 'Memberi tanggapan teknis dan melampirkan screenshot pada task QC.'],

            // Modul: Keuangan & Billing
            ['name' => 'finance.view', 'label' => 'Lihat Ringkasan Keuangan', 'group' => 'Keuangan & Billing', 'description' => 'Melihat grafik pendapatan dan overview performa keuangan.'],
            ['name' => 'finance.transactions', 'label' => 'Lihat Transaksi', 'group' => 'Keuangan & Billing', 'description' => 'Melihat riwayat transaksi kas operasional.'],
            ['name' => 'finance.bank_accounts', 'label' => 'Kelola Rekening Bank', 'group' => 'Keuangan & Billing', 'description' => 'Menambah, mengubah, dan menghapus nomor rekening bank perusahaan.'],
            ['name' => 'invoices.manage', 'label' => 'Kelola Invoice', 'group' => 'Keuangan & Billing', 'description' => 'Membuat dan mengelola invoice penagihan klien.'],
            ['name' => 'quotations.manage', 'label' => 'Kelola Quotation', 'group' => 'Keuangan & Billing', 'description' => 'Membuat quotation penawaran harga dan konversi ke invoice.'],
            ['name' => 'payments.manage', 'label' => 'Catat Pembayaran', 'group' => 'Keuangan & Billing', 'description' => 'Mencatat bukti pembayaran klien dan cetak kuitansi.'],
            ['name' => 'expenses.manage', 'label' => 'Kelola Biaya Proyek', 'group' => 'Keuangan & Billing', 'description' => 'Mencatat pos pengeluaran langsung pada proyek.'],
            ['name' => 'finance.work_logs.view', 'label' => 'Lihat Log Kerja & Rate Card', 'group' => 'Keuangan & Billing', 'description' => 'Melihat catatan man-hour log kerja dan rate card proyek.'],
            ['name' => 'finance.work_logs.manage', 'label' => 'Kelola Log Kerja & Rate Card', 'group' => 'Keuangan & Billing', 'description' => 'Mencatat jam kerja, input rate card member, dan mengubah status pembayaran log kerja.'],
            ['name' => 'finance.marketing.manage', 'label' => 'Kelola Kampanye Marketing', 'group' => 'Keuangan & Billing', 'description' => 'Mencatat performa kampanye iklan, ad spend, funnel metriks, dan perhitungan ROI.'],
            ['name' => 'finance.closings.generate', 'label' => 'Generate Closing Bulanan', 'group' => 'Keuangan & Billing', 'description' => 'Menjalankan engine kalkulasi otomatis closing laporan keuangan bulanan.'],
            ['name' => 'finance.closings.audit', 'label' => 'Audit & Setujui Closing Bulanan', 'group' => 'Keuangan & Billing', 'description' => 'Melakukan review rekonsiliasi kas, catatan auditor, dan mengubah status audit closing.'],
            ['name' => 'finance.closings.export', 'label' => 'Ekspor Laporan Finansial', 'group' => 'Keuangan & Billing', 'description' => 'Mencetak dan mengunduh berkas resmi PDF closing bulanan ber-kop surat.'],
            ['name' => 'finance.fixed_expenses.view', 'label' => 'Lihat Pengeluaran Tetap', 'group' => 'Keuangan & Billing', 'description' => 'Melihat daftar pengeluaran tetap bulanan dan status realisasi pembayarannya.'],
            ['name' => 'finance.fixed_expenses.manage', 'label' => 'Kelola Pengeluaran Tetap & Realisasi', 'group' => 'Keuangan & Billing', 'description' => 'Menambah, mengedit, menghapus pengeluaran tetap, generate cycle bulanan, dan mencatat bukti realisasi bayar.'],

            // Modul: AI Estimator
            ['name' => 'ai_pricing.use', 'label' => 'Gunakan AI Pricing', 'group' => 'AI Estimator', 'description' => 'Menjalankan fitur kalkulasi estimasi harga proyek cerdas berbasis AI.'],

            // Modul: Operasional Internal Perusahaan
            ['name' => 'company_tasks.view', 'label' => 'Lihat Task Internal', 'group' => 'Operasional Internal', 'description' => 'Melihat papan kanban aktivitas, pertemuan klien, dan agenda internal perusahaan.'],
            ['name' => 'company_tasks.manage', 'label' => 'Kelola Task Internal', 'group' => 'Operasional Internal', 'description' => 'Membuat, mengedit, memindahkan status, dan menghapus task internal perusahaan.'],
        ];

        foreach ($permissions as $p) {
            Permission::updateOrCreate(['name' => $p['name']], $p);
        }

        $allPermNames = Permission::pluck('name')->toArray();

        $rolePermissionAssignments = [
            'superadmin' => $allPermNames,
            'admin' => [
                'users.view', 'users.create', 'users.edit',
                'clients.view', 'clients.create', 'clients.edit', 'clients.portal_credentials',
                'projects.view', 'projects.create', 'projects.manage', 'projects.view_financial', 'projects.qc', 'projects.gantt', 'projects.gantt_manage', 'projects.edit', 'projects.status',
                'qc.view', 'qc.manage_tasks', 'qc.manage_test_cases', 'qc.manage_bugs', 'qc.comments',
                'finance.view', 'finance.transactions', 'invoices.manage', 'quotations.manage', 'payments.manage', 'expenses.manage',
                'finance.work_logs.view', 'finance.work_logs.manage', 'finance.marketing.manage',
                'finance.closings.generate', 'finance.closings.audit', 'finance.closings.export',
                'finance.fixed_expenses.view', 'finance.fixed_expenses.manage',
                'ai_pricing.use',
                'company_tasks.view', 'company_tasks.manage',
            ],
            'pm' => [
                'clients.view', 'clients.portal_credentials',
                'projects.view', 'projects.create', 'projects.manage', 'projects.view_financial', 'projects.qc', 'projects.gantt', 'projects.gantt_manage', 'projects.edit', 'projects.status',
                'qc.view', 'qc.manage_tasks', 'qc.manage_test_cases', 'qc.manage_bugs', 'qc.comments',
                'invoices.manage', 'quotations.manage',
                'finance.work_logs.view', 'finance.work_logs.manage',
                'ai_pricing.use',
                'company_tasks.view', 'company_tasks.manage',
            ],
            'finance' => [
                'clients.view', 'projects.view', 'projects.manage', 'projects.view_financial', 'projects.gantt',
                'finance.view', 'finance.transactions', 'finance.bank_accounts',
                'invoices.manage', 'quotations.manage', 'payments.manage', 'expenses.manage',
                'finance.work_logs.view', 'finance.work_logs.manage', 'finance.marketing.manage',
                'finance.closings.generate', 'finance.closings.audit', 'finance.closings.export',
                'finance.fixed_expenses.view', 'finance.fixed_expenses.manage',
            ],
            'qc' => [
                'projects.view',
                'projects.qc', 'projects.gantt',
                'qc.view', 'qc.manage_tasks', 'qc.manage_test_cases', 'qc.execute_tests', 'qc.manage_bugs', 'qc.comments',
            ],
            'staff' => [
                'projects.view',
                'projects.qc', 'projects.gantt',
                'qc.view', 'qc.manage_tasks', 'qc.comments',
                'ai_pricing.use',
                'company_tasks.view',
            ],
        ];

        foreach ($rolePermissionAssignments as $roleSlug => $permNames) {
            $role = Role::where('slug', $roleSlug)->first();
            if ($role) {
                $role->syncPermissions($permNames);
            }
        }
    }
}
