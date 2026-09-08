<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->string('color', 50)->default('blue');
            $table->timestamps();
        });

        // 2. Create role_user pivot table
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });

        // 3. Seed default roles
        $defaultRoles = [
            [
                'slug' => 'superadmin',
                'name' => 'Super Admin',
                'description' => 'Akses mutlak ke seluruh sistem, manajemen user, dan pengaturan global.',
                'color' => 'purple',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'admin',
                'name' => 'Administrator',
                'description' => 'Akses operasional penuh ke klien, proyek, finance, dan QA/QC.',
                'color' => 'indigo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'pm',
                'name' => 'Project Manager',
                'description' => 'Manajemen proyek, milestone, log progress, pengujian QA, dan koordinasi klien.',
                'color' => 'blue',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'finance',
                'name' => 'Finance & Accounting',
                'description' => 'Pengelolaan invoice, quotation, pembayaran, rekening bank, dan pembukuan.',
                'color' => 'emerald',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'qc',
                'name' => 'Quality Control (QA)',
                'description' => 'Perancangan test case, eksekusi tes aplikasi, dan pelaporan bug proyek.',
                'color' => 'amber',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'staff',
                'name' => 'Staff / Developer',
                'description' => 'Pengerjaan tugas proyek, komentar teknis, eksekusi task, dan estimasi AI.',
                'color' => 'slate',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'client',
                'name' => 'Client Portal',
                'description' => 'Khusus portal klien Wiromitra untuk memantau progres dan invoice proyek sendiri.',
                'color' => 'teal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('roles')->insert($defaultRoles);

        // 4. Migrate existing users' roles into role_user pivot
        $rolesBySlug = DB::table('roles')->pluck('id', 'slug')->toArray();
        $users = DB::table('users')->select('id', 'role')->get();

        foreach ($users as $user) {
            $slug = $user->role ?? 'staff';
            if (isset($rolesBySlug[$slug])) {
                DB::table('role_user')->insertOrIgnore([
                    'user_id' => $user->id,
                    'role_id' => $rolesBySlug[$slug],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
