<?php

namespace Database\Seeders;

use App\Models\Shareholder;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShareholderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $founders = [
            [
                'email' => 'titan@wirodev.com',
                'name' => 'Titan Anggaraksa',
                'phone' => '081234567890',
                'shares_count' => 95000,
                'ownership_percentage' => 95.00,
                'bank_name' => 'BCA',
                'bank_account_number' => '8830123456',
                'bank_account_holder' => 'Titan Anggaraksa',
                'is_active' => true,
            ],
            [
                'email' => 'bimajati@wirodev.com',
                'name' => 'Bima Jati',
                'phone' => '081234567801',
                'shares_count' => 5000,
                'ownership_percentage' => 5.00,
                'bank_name' => 'BCA',
                'bank_account_number' => '8830998877',
                'bank_account_holder' => 'Bima Jati',
                'is_active' => true,
            ],
        ];

        // Clean up old demo shareholders so only the current founders are active
        $currentEmails = array_column($founders, 'email');
        Shareholder::whereNotIn('email', $currentEmails)->delete();

        foreach ($founders as $data) {
            $user = User::where('email', $data['email'])->first();
            $data['user_id'] = $user?->id;

            Shareholder::updateOrCreate(
                ['email' => $data['email']],
                $data
            );
        }
    }
}
