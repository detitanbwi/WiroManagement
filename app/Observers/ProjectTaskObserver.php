<?php

namespace App\Observers;

use App\Models\ProjectTask;
use App\Services\TaskProgressService;
use App\Services\WbsService;

class ProjectTaskObserver
{
    public function __construct(
        protected TaskProgressService $progressService,
        protected WbsService $wbsService
    ) {}

    /**
     * Handle the ProjectTask "created" event.
     */
    public function created(ProjectTask $task): void
    {
        $this->wbsService->reindexProjectWbs($task->project_id);

        if ($task->parent_id) {
            $this->progressService->recalculateParent($task);
        } else {
            $this->progressService->recalculateProjectProgress($task->project_id);
        }
    }

    /**
     * Handle the ProjectTask "updated" event.
     */
    public function updated(ProjectTask $task): void
    {
        // If progress, dates, or duration changed
        if ($task->wasChanged(['progress', 'start_date', 'due_date', 'duration_days'])) {
            $this->progressService->recalculateParent($task);
        }

        // If parent_id or sort_order changed
        if ($task->wasChanged(['parent_id', 'sort_order'])) {
            $this->wbsService->reindexProjectWbs($task->project_id);
            $this->progressService->recalculateParent($task);

            if ($oldParentId = $task->getOriginal('parent_id')) {
                $oldParent = ProjectTask::find($oldParentId);
                if ($oldParent) {
                    $this->progressService->recalculateParent($oldParent);
                }
            }
        }
    }

    /**
     * Handle the ProjectTask "deleted" event.
     */
    public function deleted(ProjectTask $task): void
    {
        $this->wbsService->reindexProjectWbs($task->project_id);

        if ($task->parent_id) {
            $this->progressService->recalculateParent($task);
        } else {
            $this->progressService->recalculateProjectProgress($task->project_id);
        }
    }
}
