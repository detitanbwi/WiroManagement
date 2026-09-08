<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'slug' => 'superadmin',
                'name' => 'Super Admin',
                'description' => 'Akses mutlak ke seluruh sistem, manajemen user, dan pengaturan global.',
                'color' => 'purple',
            ],
            [
                'slug' => 'admin',
                'name' => 'Administrator',
                'description' => 'Akses operasional penuh ke klien, proyek, finance, dan QA/QC.',
                'color' => 'indigo',
            ],
            [
                'slug' => 'pm',
                'name' => 'Project Manager',
                'description' => 'Manajemen proyek, milestone, log progress, pengujian QA, dan koordinasi klien.',
                'color' => 'blue',
            ],
            [
                'slug' => 'finance',
                'name' => 'Finance & Accounting',
                'description' => 'Pengelolaan invoice, quotation, pembayaran, rekening bank, dan pembukuan.',
                'color' => 'emerald',
            ],
            [
                'slug' => 'qc',
                'name' => 'Quality Control (QA)',
                'description' => 'Perancangan test case, eksekusi tes aplikasi, dan pelaporan bug proyek.',
                'color' => 'amber',
            ],
            [
                'slug' => 'staff',
                'name' => 'Staff / Developer',
                'description' => 'Pengerjaan tugas proyek, komentar teknis, eksekusi task, dan estimasi AI.',
                'color' => 'slate',
            ],
            [
                'slug' => 'client',
                'name' => 'Client Portal',
                'description' => 'Khusus portal klien Wiromitra untuk memantau progres dan invoice proyek sendiri.',
                'color' => 'teal',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }

        // Sync existing users' primary role into pivot
        $allRoles = Role::all()->keyBy('slug');
        $users = User::all();

        foreach ($users as $user) {
            if ($user->role && isset($allRoles[$user->role])) {
                $user->roles()->syncWithoutDetaching([$allRoles[$user->role]->id]);
            }
        }
    }
}
