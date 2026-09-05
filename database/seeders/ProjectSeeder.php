<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Client;
use Carbon\Carbon;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $client = Client::first();
        
        // Ensure there is at least one client to attach the project to
        if (!$client) {
            $client = Client::create([
                'name' => 'Demo Client',
                'email' => 'client@demo.com',
                'phone' => '0800000000',
                'address' => 'Demo Address'
            ]);
        }

        Project::create([
            'client_id' => $client->id,
            'title' => 'Wiro Management System V2',
            'status' => 'in_progress',
            'start_date' => Carbon::now()->subDays(7),
            'end_date' => Carbon::now()->addMonths(2),
        ]);
    }
}
