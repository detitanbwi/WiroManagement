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
            ['name' => 'projects.edit', 'label' => 'Edit Proyek', 'group' => 'Manajemen Proyek', 'description' => 'Mengubah informasi proyek, timeline, dan spesifikasi.'],
            ['name' => 'projects.delete', 'label' => 'Hapus Proyek', 'group' => 'Manajemen Proyek', 'description' => 'Menghapus proyek dari sistem.'],
            ['name' => 'projects.status', 'label' => 'Ubah Status Proyek', 'group' => 'Manajemen Proyek', 'description' => 'Memperbarui alur status proyek (in_progress, completed, dll).'],

            // Modul: Quality Control & QA
            ['name' => 'qc.view', 'label' => 'Akses Papan QA / QC', 'group' => 'Quality Control (QA)', 'description' => 'Melihat board task, test case, dan pelacakan bug proyek.'],
            ['name' => 'qc.manage_tasks', 'label' => 'Kelola Task QC', 'group' => 'Quality Control (QA)', 'description' => 'Membuat task, memindahkan kolom board, dan menghapus task QA.'],
            ['name' => 'qc.manage_test_cases', 'label' => 'Kelola Test Case', 'group' => 'Quality Control (QA)', 'description' => 'Merancang skenario uji, mengedit, dan menghapus test case.'],
            ['name' => 'qc.execute_tests', 'label' => 'Eksekusi Pengujian', 'group' => 'Quality Control (QA)', 'description' => 'Menginput hasil pengujian lulus/gagal (pass/fail).'],
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

            // Modul: AI Estimator
            ['name' => 'ai_pricing.use', 'label' => 'Gunakan AI Pricing', 'group' => 'AI Estimator', 'description' => 'Menjalankan fitur kalkulasi estimasi harga proyek cerdas berbasis AI.'],
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
                'projects.view', 'projects.create', 'projects.edit', 'projects.status',
                'qc.view', 'qc.manage_tasks', 'qc.manage_test_cases', 'qc.execute_tests', 'qc.manage_bugs', 'qc.comments',
                'finance.view', 'finance.transactions', 'invoices.manage', 'quotations.manage', 'payments.manage', 'expenses.manage',
                'ai_pricing.use',
            ],
            'pm' => [
                'clients.view', 'clients.portal_credentials',
                'projects.view', 'projects.create', 'projects.edit', 'projects.status',
                'qc.view', 'qc.manage_tasks', 'qc.manage_test_cases', 'qc.execute_tests', 'qc.manage_bugs', 'qc.comments',
                'invoices.manage', 'quotations.manage',
                'ai_pricing.use',
            ],
            'finance' => [
                'clients.view', 'projects.view',
                'finance.view', 'finance.transactions', 'finance.bank_accounts',
                'invoices.manage', 'quotations.manage', 'payments.manage', 'expenses.manage',
            ],
            'qc' => [
                'projects.view',
                'qc.view', 'qc.manage_tasks', 'qc.manage_test_cases', 'qc.execute_tests', 'qc.manage_bugs', 'qc.comments',
            ],
            'staff' => [
                'projects.view',
                'qc.view', 'qc.comments',
                'ai_pricing.use',
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
