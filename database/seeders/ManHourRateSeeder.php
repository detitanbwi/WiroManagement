<?php

namespace Database\Seeders;

use App\Models\ManHourRate;
use Illuminate\Database\Seeder;

class ManHourRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rates = [
            // Developers
            ['role_slug' => 'developer', 'skill_level' => 'entry', 'base_rate_per_hour' => 50000, 'weekend_multiplier' => 1.10, 'overtime_multiplier' => 1.20, 'description' => 'Junior Developer / Entry level'],
            ['role_slug' => 'developer', 'skill_level' => 'middle', 'base_rate_per_hour' => 85000, 'weekend_multiplier' => 1.10, 'overtime_multiplier' => 1.20, 'description' => 'Middle Developer'],
            ['role_slug' => 'developer', 'skill_level' => 'senior', 'base_rate_per_hour' => 135000, 'weekend_multiplier' => 1.10, 'overtime_multiplier' => 1.20, 'description' => 'Senior / Lead Developer'],

            // Project Managers
            ['role_slug' => 'pm', 'skill_level' => 'entry', 'base_rate_per_hour' => 60000, 'weekend_multiplier' => 1.10, 'overtime_multiplier' => 1.20, 'description' => 'Associate Project Manager'],
            ['role_slug' => 'pm', 'skill_level' => 'middle', 'base_rate_per_hour' => 95000, 'weekend_multiplier' => 1.10, 'overtime_multiplier' => 1.20, 'description' => 'Project Manager'],
            ['role_slug' => 'pm', 'skill_level' => 'senior', 'base_rate_per_hour' => 150000, 'weekend_multiplier' => 1.10, 'overtime_multiplier' => 1.20, 'description' => 'Senior Project Manager / Scrum Master'],

            // Quality Control
            ['role_slug' => 'qc', 'skill_level' => 'entry', 'base_rate_per_hour' => 45000, 'weekend_multiplier' => 1.10, 'overtime_multiplier' => 1.20, 'description' => 'Junior QA / QC Tester'],
            ['role_slug' => 'qc', 'skill_level' => 'middle', 'base_rate_per_hour' => 75000, 'weekend_multiplier' => 1.10, 'overtime_multiplier' => 1.20, 'description' => 'QA / QC Engineer'],
            ['role_slug' => 'qc', 'skill_level' => 'senior', 'base_rate_per_hour' => 115000, 'weekend_multiplier' => 1.10, 'overtime_multiplier' => 1.20, 'description' => 'Lead QA / QC Automation Engineer'],

            // UI/UX Designers
            ['role_slug' => 'designer', 'skill_level' => 'entry', 'base_rate_per_hour' => 50000, 'weekend_multiplier' => 1.10, 'overtime_multiplier' => 1.20, 'description' => 'Junior UI/UX Designer'],
            ['role_slug' => 'designer', 'skill_level' => 'middle', 'base_rate_per_hour' => 80000, 'weekend_multiplier' => 1.10, 'overtime_multiplier' => 1.20, 'description' => 'UI/UX Product Designer'],
            ['role_slug' => 'designer', 'skill_level' => 'senior', 'base_rate_per_hour' => 125000, 'weekend_multiplier' => 1.10, 'overtime_multiplier' => 1.20, 'description' => 'Senior UI/UX & Design Lead'],

            // DevOps / Infrastructure
            ['role_slug' => 'devops', 'skill_level' => 'entry', 'base_rate_per_hour' => 60000, 'weekend_multiplier' => 1.10, 'overtime_multiplier' => 1.20, 'description' => 'Cloud & Infrastructure Junior'],
            ['role_slug' => 'devops', 'skill_level' => 'middle', 'base_rate_per_hour' => 100000, 'weekend_multiplier' => 1.10, 'overtime_multiplier' => 1.20, 'description' => 'DevOps Engineer / Sysadmin'],
            ['role_slug' => 'devops', 'skill_level' => 'senior', 'base_rate_per_hour' => 160000, 'weekend_multiplier' => 1.10, 'overtime_multiplier' => 1.20, 'description' => 'Principal Cloud Architect / DevOps Lead'],
        ];

        foreach ($rates as $rate) {
            ManHourRate::updateOrCreate(
                ['role_slug' => $rate['role_slug'], 'skill_level' => $rate['skill_level']],
                $rate
            );
        }
    }
}
