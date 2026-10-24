<?php

namespace App\Services;

use App\Models\ProjectTask;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TaskProgressService
{
    /**
     * Recalculate progress, dates, and status for the parent task and ancestors recursively.
     */
    public function recalculateParent(ProjectTask $task): void
    {
        if (!$task->parent_id) {
            $this->recalculateProjectProgress($task->project_id);
            return;
        }

        $parent = ProjectTask::find($task->parent_id);
        if (!$parent) {
            return;
        }

        // Aggregate statistics from direct children
        $childStats = ProjectTask::where('parent_id', $parent->id)
            ->selectRaw('
                COUNT(*) as total_children,
                AVG(progress) as avg_progress,
                MIN(start_date) as earliest_start,
                MAX(due_date) as latest_due
            ')
            ->first();

        if ($childStats && $childStats->total_children > 0) {
            $avgProgress = round((float) $childStats->avg_progress, 2);
            $startDate = $childStats->earliest_start ?? $parent->start_date;
            $dueDate = $childStats->latest_due ?? $parent->due_date;

            $durationDays = 1;
            if ($startDate && $dueDate) {
                $durationDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($dueDate)) + 1;
            }

            $status = 'todo';
            if ($avgProgress >= 100.00) {
                $status = 'completed';
            } elseif ($avgProgress > 0.00) {
                $status = 'in_progress';
            }

            $parent->updateQuietly([
                'progress'      => $avgProgress,
                'start_date'    => $startDate,
                'due_date'      => $dueDate,
                'duration_days' => max(1, (int) $durationDays),
                'status'        => $status,
                'column_id'     => $avgProgress >= 100 ? 'done' : ($avgProgress > 0 ? 'in_progress' : 'todo')
            ]);

            // Recursively update higher-level parent if exists
            if ($parent->parent_id) {
                $this->recalculateParent($parent);
            } else {
                $this->recalculateProjectProgress($parent->project_id);
            }
        } else {
            // If parent no longer has children, recalculate upwards and project progress
            if ($parent->parent_id) {
                $this->recalculateParent($parent);
            } else {
                $this->recalculateProjectProgress($parent->project_id);
            }
        }
    }

    /**
     * Recalculate project overall progress based on leaf tasks (work packages).
     */
    public function recalculateProjectProgress(int $projectId): void
    {
        $project = Project::find($projectId);
        if (!$project) {
            return;
        }

        // Project overall progress is based on leaf tasks (tasks without children)
        $leafProgress = ProjectTask::where('project_id', $projectId)
            ->doesntHave('children')
            ->avg('progress');

        $project->updateQuietly([
            'progress_percentage' => $leafProgress !== null ? (int) round($leafProgress) : 0
        ]);
    }
}
