<?php

namespace App\Services;

use App\Models\ProjectTask;

class WbsService
{
    /**
     * Re-index all WBS codes for a project sequentially based on hierarchy and sort_order.
     */
    public function reindexProjectWbs(int $projectId): void
    {
        $rootTasks = ProjectTask::where('project_id', $projectId)
            ->whereNull('parent_id')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $rootIndex = 1;
        foreach ($rootTasks as $rootTask) {
            $wbs = (string)$rootIndex;
            
            $rootTask->updateQuietly([
                'wbs_code' => $wbs,
                'sort_order' => $rootIndex
            ]);

            $this->reindexChildren($rootTask, $wbs);
            $rootIndex++;
        }
    }

    /**
     * Recursively re-index child tasks.
     */
    private function reindexChildren(ProjectTask $parentTask, string $parentWbs): void
    {
        $children = ProjectTask::where('parent_id', $parentTask->id)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $childIndex = 1;
        foreach ($children as $child) {
            $currentWbs = "{$parentWbs}.{$childIndex}";
            $child->updateQuietly([
                'wbs_code' => $currentWbs,
                'sort_order' => $childIndex
            ]);

            $this->reindexChildren($child, $currentWbs);
            $childIndex++;
        }
    }

    /**
     * Generate next WBS code for a new task.
     */
    public function generateNextWbsCode(int $projectId, ?int $parentId = null): string
    {
        if ($parentId) {
            $parent = ProjectTask::find($parentId);
            if ($parent) {
                $count = ProjectTask::where('parent_id', $parentId)->count();
                $prefix = $parent->wbs_code ?: '1';
                return "{$prefix}." . ($count + 1);
            }
        }

        $rootCount = ProjectTask::where('project_id', $projectId)
            ->whereNull('parent_id')
            ->count();

        return (string)($rootCount + 1);
    }
}
