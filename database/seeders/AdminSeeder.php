<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@wirodev.com'],
            [
                'name' => 'Super Admin Wirodev',
                'password' => Hash::make('wirodev2026'),
                'role' => 'superadmin',
                'is_active' => true,
            ]
        );
        $admin->syncRoles(['superadmin']);

        $staff = User::updateOrCreate(
            ['email' => 'sekretaris@wirodev.com'],
            [
                'name' => 'Sekretaris Wiro Management',
                'password' => Hash::make('wirodev2026'),
                'role' => 'staff',
                'is_active' => true,
            ]
        );
        $staff->syncRoles(['staff']);
    }
}
