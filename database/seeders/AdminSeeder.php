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
        User::updateOrCreate(
            ['email' => 'admin@wirodev.com'],
            [
                'name' => 'Super Admin Wirodev',
                'password' => Hash::make('wirodev2026'),
                'role' => 'superadmin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'sekretaris@wirodev.com'],
            [
                'name' => 'Sekretaris Wiro Management',
                'password' => Hash::make('wirodev2026'),
                'role' => 'staff',
            ]
        );
    }
}
