<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TestCase;

class QcSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::first();
        if (!$project) return;

        $task1 = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-101',
            'title' => 'Design Landing Page UI',
            'description' => 'Create the initial mockup for the new landing page using Figma.',
            'column_id' => 'done'
        ]);

        $task2 = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-102',
            'title' => 'Implement Authentication API',
            'description' => 'Develop login and register endpoints with JWT.',
            'column_id' => 'in_progress'
        ]);

        $task3 = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-103',
            'title' => 'Dashboard Analytics Chart',
            'description' => 'Integrate Chart.js to show monthly revenue data.',
            'column_id' => 'ready_for_qc'
        ]);

        TestCase::create([
            'project_task_id' => $task3->id,
            'code' => 'TC-001',
            'title' => 'Verify Data Load',
            'preconditions' => 'User has transactions',
            'expected' => 'Chart renders data points correctly',
            'steps' => ['Login as user', 'Navigate to Dashboard', 'Observe revenue chart'],
            'status' => 'pending'
        ]);

        TestCase::create([
            'project_task_id' => $task3->id,
            'code' => 'TC-002',
            'title' => 'Filter by Date',
            'preconditions' => 'Chart is visible',
            'expected' => 'Data updates based on selected date range',
            'steps' => ['Click date filter', 'Select Last 30 Days', 'Verify data changes'],
            'status' => 'pending'
        ]);
        
        $task4 = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-104',
            'title' => 'Checkout Payment Gateway',
            'description' => 'Integrate payment flow for checkout process.',
            'column_id' => 'qc_in_progress'
        ]);

        TestCase::create([
            'project_task_id' => $task4->id,
            'code' => 'TC-003',
            'title' => 'Success Payment Flow',
            'preconditions' => 'Cart has items',
            'expected' => 'User redirected to success page',
            'steps' => ['Proceed to checkout', 'Select Bank Transfer', 'Complete dummy payment in sandbox'],
            'status' => 'passed'
        ]);
    }
}
