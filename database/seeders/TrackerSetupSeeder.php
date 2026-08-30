<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\TrackerAccount;
use App\Models\TrackerCategory;
use Illuminate\Support\Str;

class TrackerSetupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Default User if not exists (or use existing)
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Tracker Admin',
                'email' => 'tracker@example.com',
                'password' => bcrypt('password'),
                'role' => 'admin'
            ]);
        }

        // Generate Token for the user
        $token = $user->createToken('flutter-tracker-app')->plainTextToken;
        $this->command->info("API Token for Flutter: " . $token);

        // 2. Create Default Accounts
        TrackerAccount::updateOrCreate(['name' => 'Cash (Personal)', 'type' => 'personal'], ['balance' => 0]);
        TrackerAccount::updateOrCreate(['name' => 'BCA (Personal)', 'type' => 'personal'], ['balance' => 0]);
        TrackerAccount::updateOrCreate(['name' => 'Company Cash', 'type' => 'company'], ['balance' => 0]);
        TrackerAccount::updateOrCreate(['name' => 'Operational Bank', 'type' => 'company'], ['balance' => 0]);

        // 3. Create Default Categories
        $personalCategories = ['Food', 'Transport', 'Utilities', 'Entertainment', 'Shopping'];
        foreach ($personalCategories as $cat) {
            TrackerCategory::updateOrCreate(['name' => $cat, 'type' => 'personal']);
        }

        $companyCategories = ['Office Supply', 'Travel', 'Marketing', 'Salary', 'Tax'];
        foreach ($companyCategories as $cat) {
            TrackerCategory::updateOrCreate(['name' => $cat, 'type' => 'company']);
        }
    }
}
