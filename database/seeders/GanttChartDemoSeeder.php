<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Carbon\Carbon;

class GanttChartDemoSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::first();
        if (!$project) {
            return;
        }

        $user = User::first();
        $today = Carbon::today();

        // 1. Parent Task (WBS 1.4)
        $parentTask = ProjectTask::create([
            'project_id'    => $project->id,
            'parent_id'     => null,
            'wbs_code'      => '1.4',
            'code'          => 'TSK-DOC01',
            'title'         => 'Document Current Project Page',
            'description'   => 'Dokumentasi komprehensif alur kerja dan modul halaman proyek saat ini.',
            'assignee_id'   => $user?->id,
            'start_date'    => $today->copy()->format('Y-m-d'),
            'due_date'      => $today->copy()->addDays(14)->format('Y-m-d'),
            'duration_days' => 15,
            'progress'      => 0,
            'status'        => 'in_progress',
            'column_id'     => 'in_progress',
            'sort_order'    => 1,
        ]);

        // 2. Sub-tugas 1 (WBS 1.4.1) - 100%
        $subTask1 = ProjectTask::create([
            'project_id'    => $project->id,
            'parent_id'     => $parentTask->id,
            'wbs_code'      => '1.4.1',
            'code'          => 'TSK-MOD01',
            'title'         => 'Modul 1: Dashboard',
            'description'   => 'Dokumentasi komponen dan visualisasi dashboard proyek.',
            'assignee_id'   => $user?->id,
            'start_date'    => $today->copy()->format('Y-m-d'),
            'due_date'      => $today->copy()->addDays(5)->format('Y-m-d'),
            'duration_days' => 6,
            'progress'      => 100,
            'status'        => 'completed',
            'column_id'     => 'done',
            'sort_order'    => 1,
        ]);

        // 3. Sub-tugas 2 (WBS 1.4.2) - 0%
        $subTask2 = ProjectTask::create([
            'project_id'    => $project->id,
            'parent_id'     => $parentTask->id,
            'wbs_code'      => '1.4.2',
            'code'          => 'TSK-MOD02',
            'title'         => 'Modul 2: Logistik',
            'description'   => 'Dokumentasi modul logistik dan supply tracking.',
            'assignee_id'   => $user?->id,
            'start_date'    => $today->copy()->addDays(6)->format('Y-m-d'),
            'due_date'      => $today->copy()->addDays(14)->format('Y-m-d'),
            'duration_days' => 9,
            'progress'      => 0,
            'status'        => 'todo',
            'column_id'     => 'todo',
            'sort_order'    => 2,
        ]);

        // Add Dependency: SubTask2 depends on SubTask1 (Finish-to-Start)
        $subTask2->dependencies()->attach($subTask1->id, [
            'project_id' => $project->id,
            'type'       => 'FS',
            'lag_days'   => 0
        ]);
    }
}
