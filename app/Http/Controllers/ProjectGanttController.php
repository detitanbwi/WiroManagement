<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Services\TaskProgressService;
use App\Services\WbsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectGanttController extends Controller
{
    public function __construct(
        protected TaskProgressService $progressService,
        protected WbsService $wbsService
    ) {}

    /**
     * Get Gantt chart and WBS tree data in JSON format.
     */
    public function data(Project $project)
    {
        $allTasks = $project->tasks()
            ->gantt()
            ->with(['assignee:id,name', 'dependencies:id,title,wbs_code', 'children:id,parent_id'])
            ->withCount(['children' => fn($q) => $q->gantt()])
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Build hierarchical tree DFS to ensure subtasks always follow their parent directly
        $tasksByParent = $allTasks->groupBy(fn($t) => $t->parent_id ?: 0);

        $flattenTree = function($parentId, $level = 0) use (&$flattenTree, $tasksByParent) {
            $result = collect();
            $items = $tasksByParent->get($parentId, collect());
            foreach ($items as $item) {
                $item->tree_level = $level;
                $result->push($item);
                $children = $flattenTree($item->id, $level + 1);
                $result = $result->concat($children);
            }
            return $result;
        };

        $orderedTasks = $flattenTree(0, 0);

        // Include any orphan tasks if parent_id was pointing to non-existent ID
        $orderedIds = $orderedTasks->pluck('id')->all();
        $orphans = $allTasks->reject(fn($t) => in_array($t->id, $orderedIds));
        foreach ($orphans as $orphan) {
            $orphan->tree_level = 0;
            $orderedTasks->push($orphan);
        }

        $today = Carbon::today()->format('Y-m-d');
        
        $formattedTasks = $orderedTasks->map(function (ProjectTask $task) use ($today) {
            $isParent = $task->children_count > 0;
            $startDate = $task->start_date ? Carbon::parse($task->start_date)->format('Y-m-d') : $today;
            $dueDate = $task->due_date ? Carbon::parse($task->due_date)->format('Y-m-d') : Carbon::parse($startDate)->addDays(1)->format('Y-m-d');
            
            // Determine custom styling class for Frappe Gantt
            $customClass = 'bar-task';
            if ($isParent) {
                $customClass = 'bar-parent';
            } elseif ($task->progress >= 100) {
                $customClass = 'bar-completed';
            } elseif ($task->progress > 0) {
                $customClass = 'bar-inprogress';
            }

            return [
                'id'            => 'task_' . $task->id,
                'db_id'         => $task->id,
                'parent_id'     => $task->parent_id ? 'task_' . $task->parent_id : null,
                'parent_db_id'  => $task->parent_id,
                'level'         => $task->tree_level ?? 0,
                'sort_order'    => (int) $task->sort_order,
                'wbs_code'      => $task->wbs_code ?: (string)$task->id,
                'title'         => $task->title,
                'description'   => $task->description,
                'name'          => ($task->wbs_code ? $task->wbs_code . ' ' : '') . $task->title,
                'is_parent'     => $isParent,
                'is_leaf'       => !$isParent,
                'children_count'=> $task->children_count,
                'assignee'      => $task->assignee ? [
                    'id'   => $task->assignee->id,
                    'name' => $task->assignee->name
                ] : null,
                'assignee_id'   => $task->assignee_id,
                'start'         => $startDate,
                'end'           => $dueDate,
                'duration_days' => $task->duration_days ?: max(1, Carbon::parse($startDate)->diffInDays(Carbon::parse($dueDate)) + 1),
                'progress'      => (float) $task->progress,
                'status'        => $isParent ? 'parent' : ($task->status ?: ($task->progress >= 100 ? 'completed' : ($task->progress > 0 ? 'in_progress' : 'todo'))),
                'dependencies'         => $task->dependencies->map(fn($dep) => 'task_' . $dep->id)->values()->toArray(),
                'dependency_details'   => $task->dependencies->map(fn($dep) => [
                    'id'       => $dep->id,
                    'wbs_code' => $dep->wbs_code,
                    'title'    => $dep->title,
                ])->values()->toArray(),
                'custom_class'         => $customClass,
            ];
        });

        // Recalculate project progress based on leaf tasks to guarantee fresh percentage
        $this->progressService->recalculateProjectProgress($project->id);
        $project->refresh();

        $users = User::select('id', 'name')->where('is_active', true)->orderBy('name')->get();

        return response()->json([
            'project' => [
                'id'                  => $project->id,
                'title'               => $project->title,
                'start_date'          => $project->start_date ? Carbon::parse($project->start_date)->format('Y-m-d') : null,
                'end_date'            => $project->end_date ? Carbon::parse($project->end_date)->format('Y-m-d') : null,
                'progress_percentage' => (int) ($project->progress_percentage ?: 0),
            ],
            'tasks' => $formattedTasks,
            'users' => $users,
        ]);
    }

    /**
     * Create a new task or sub-task.
     */
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'parent_id'      => 'nullable|exists:project_tasks,id',
            'assignee_id'    => 'nullable|exists:users,id',
            'start_date'     => 'required|date',
            'due_date'       => 'required|date|after_or_equal:start_date',
            'progress'       => 'nullable|numeric|min:0|max:100',
            'dependency_ids' => 'nullable|array',
            'dependency_ids.*' => 'exists:project_tasks,id',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $dueDate = Carbon::parse($validated['due_date']);
        $durationDays = max(1, $startDate->diffInDays($dueDate) + 1);

        $initialProgress = isset($validated['progress']) ? (float)$validated['progress'] : 0.0;
        $parentId = $validated['parent_id'] ?? null;

        // Calculate sort order: place it immediately after the last subtask of the same parent (or last root task)
        $maxSortOrder = ProjectTask::where('project_id', $project->id)
            ->gantt()
            ->where('parent_id', $parentId)
            ->max('sort_order') ?? 0;

        $task = ProjectTask::create([
            'project_id'    => $project->id,
            'type'          => 'gantt',
            'parent_id'     => $parentId,
            'code'          => 'TSK-' . strtoupper(substr(uniqid(), -6)),
            'title'         => $validated['title'],
            'description'   => $validated['description'] ?? null,
            'assignee_id'   => $validated['assignee_id'] ?? null,
            'start_date'    => $startDate->format('Y-m-d'),
            'due_date'      => $dueDate->format('Y-m-d'),
            'duration_days' => $durationDays,
            'progress'      => $initialProgress,
            'sort_order'    => $maxSortOrder + 1,
            'status'        => $initialProgress >= 100 ? 'completed' : ($initialProgress > 0 ? 'in_progress' : 'todo'),
            'column_id'     => $initialProgress >= 100 ? 'done' : ($initialProgress > 0 ? 'in_progress' : 'todo'),
        ]);

        // Attach dependencies if provided
        if (!empty($validated['dependency_ids'])) {
            $syncData = [];
            foreach ($validated['dependency_ids'] as $depId) {
                $syncData[$depId] = [
                    'project_id' => $project->id,
                    'type'       => 'FS',
                    'lag_days'   => 0,
                ];
            }
            $task->dependencies()->sync($syncData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil ditambahkan.',
            'task'    => $task->fresh(['assignee', 'dependencies', 'children'])
        ]);
    }

    /**
     * Update an existing task.
     */
    public function update(Request $request, ProjectTask $task)
    {
        $validated = $request->validate([
            'title'          => 'sometimes|required|string|max:255',
            'description'    => 'nullable|string',
            'parent_id'      => 'nullable|exists:project_tasks,id',
            'assignee_id'    => 'nullable|exists:users,id',
            'start_date'     => 'sometimes|required|date',
            'due_date'       => 'sometimes|required|date|after_or_equal:start_date',
            'progress'       => 'nullable|numeric|min:0|max:100',
            'sort_order'     => 'nullable|integer|min:1',
            'dependency_ids' => 'nullable|array',
            'dependency_ids.*' => 'exists:project_tasks,id',
        ]);

        // Prevent setting parent to itself or a descendant
        if (isset($validated['parent_id']) && $validated['parent_id']) {
            if ($validated['parent_id'] == $task->id) {
                return response()->json([
                    'message' => 'Tugas tidak bisa menjadi sub-tugas dari dirinya sendiri.'
                ], 422);
            }
            if ($this->isDescendant((int)$validated['parent_id'], $task->id)) {
                return response()->json([
                    'message' => 'Tugas tidak dapat dipindahkan ke dalam sub-tugasnya sendiri.'
                ], 422);
            }
        }

        $startDate = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : Carbon::parse($task->start_date);
        $dueDate = isset($validated['due_date']) ? Carbon::parse($validated['due_date']) : Carbon::parse($task->due_date);
        $durationDays = max(1, $startDate->diffInDays($dueDate) + 1);

        $updateData = [
            'duration_days' => $durationDays,
        ];

        if (isset($validated['title'])) {
            $updateData['title'] = $validated['title'];
        }
        if (array_key_exists('description', $validated)) {
            $updateData['description'] = $validated['description'];
        }
        if (array_key_exists('parent_id', $validated)) {
            $newParentId = $validated['parent_id'] ? (int)$validated['parent_id'] : null;
            if ($newParentId !== $task->parent_id) {
                $maxSort = ProjectTask::where('project_id', $task->project_id)
                    ->gantt()
                    ->where('parent_id', $newParentId)
                    ->where('id', '!=', $task->id)
                    ->max('sort_order') ?? 0;
                $updateData['parent_id'] = $newParentId;
                $updateData['sort_order'] = $maxSort + 1;
            }
        }
        if (array_key_exists('assignee_id', $validated)) {
            $updateData['assignee_id'] = $validated['assignee_id'];
        }
        if (isset($validated['start_date'])) {
            $updateData['start_date'] = $startDate->format('Y-m-d');
        }
        if (isset($validated['due_date'])) {
            $updateData['due_date'] = $dueDate->format('Y-m-d');
        }

        // If task has children, do not allow manual progress override (auto-calculated)
        $hasChildren = $task->children()->exists();
        if (isset($validated['progress'])) {
            if (!$hasChildren) {
                $progress = (float)$validated['progress'];
                $updateData['progress'] = $progress;
                $updateData['status'] = $progress >= 100 ? 'completed' : ($progress > 0 ? 'in_progress' : 'todo');
                $updateData['column_id'] = $progress >= 100 ? 'done' : ($progress > 0 ? 'in_progress' : 'todo');
            }
        }

        $task->update($updateData);

        // Update dependencies
        if (array_key_exists('dependency_ids', $validated)) {
            $syncData = [];
            foreach ($validated['dependency_ids'] ?? [] as $depId) {
                $syncData[$depId] = [
                    'project_id' => $task->project_id,
                    'type'       => 'FS',
                    'lag_days'   => 0,
                ];
            }
            $task->dependencies()->sync($syncData);
        }

        // Apply reordering if sort_order specified
        if (isset($validated['sort_order'])) {
            $this->applyReorder($task, (int)$validated['sort_order']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil diperbarui.',
            'task'    => $task->fresh(['assignee', 'dependencies', 'children'])
        ]);
    }

    /**
     * Move task: reorder or change parent (drag-and-drop or dropdown reorder).
     */
    public function move(Request $request, ProjectTask $task)
    {
        $validated = $request->validate([
            'target_id' => 'nullable|exists:project_tasks,id',
            'position'  => 'required|in:before,after,inside',
        ]);

        $targetId = $validated['target_id'] ? (int)$validated['target_id'] : null;
        $position = $validated['position'];
        $projectId = $task->project_id;
        $oldParentId = $task->parent_id;

        // If target is null, move to root level at the end
        if (!$targetId) {
            $maxSort = ProjectTask::where('project_id', $projectId)
                ->gantt()
                ->whereNull('parent_id')
                ->where('id', '!=', $task->id)
                ->max('sort_order') ?? 0;

            $task->update([
                'parent_id'  => null,
                'sort_order' => $maxSort + 1,
            ]);

            $this->normalizeSiblings($projectId, null);
            if ($oldParentId) {
                $this->normalizeSiblings($projectId, $oldParentId);
                $oldParent = ProjectTask::find($oldParentId);
                if ($oldParent) {
                    $this->progressService->recalculateParent($oldParent);
                }
            }

            $this->wbsService->reindexProjectWbs($projectId);
            $this->progressService->recalculateProjectProgress($projectId);

            return response()->json([
                'success' => true,
                'message' => 'Tugas berhasil dipindahkan ke tugas utama.',
            ]);
        }

        // Prevent moving into itself
        if ($targetId === $task->id) {
            return response()->json([
                'success' => false,
                'message' => 'Tugas tidak dapat dipindahkan ke dirinya sendiri.',
            ], 422);
        }

        $target = ProjectTask::findOrFail($targetId);

        // Ensure target is in the same project
        if ($target->project_id !== $projectId) {
            return response()->json([
                'success' => false,
                'message' => 'Tugas target harus berada dalam proyek yang sama.',
            ], 422);
        }

        $newParentId = ($position === 'inside') ? $targetId : $target->parent_id;

        // Prevent cycle (moving into its own descendant)
        if ($newParentId) {
            if ($this->isDescendant($newParentId, $task->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tugas tidak dapat dipindahkan ke dalam sub-tugasnya sendiri.',
                ], 422);
            }
        }

        // Fetch current siblings under newParentId excluding task
        $siblings = ProjectTask::where('project_id', $projectId)
            ->gantt()
            ->where('parent_id', $newParentId)
            ->where('id', '!=', $task->id)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $orderedList = collect();
        if ($position === 'inside') {
            $orderedList = $siblings->push($task);
        } else {
            foreach ($siblings as $sibling) {
                if ($sibling->id === $target->id && $position === 'before') {
                    $orderedList->push($task);
                }
                $orderedList->push($sibling);
                if ($sibling->id === $target->id && $position === 'after') {
                    $orderedList->push($task);
                }
            }
            if (!$orderedList->contains('id', $task->id)) {
                $orderedList->push($task);
            }
        }

        // Apply new parent and strictly ordered sort_order
        $order = 1;
        foreach ($orderedList as $item) {
            $item->updateQuietly([
                'parent_id'  => $newParentId,
                'sort_order' => $order,
            ]);
            $order++;
        }

        // Normalize old parent's siblings if parent changed
        if ($oldParentId !== null && $oldParentId !== $newParentId) {
            $this->normalizeSiblings($projectId, $oldParentId);
        }

        // Re-index all WBS codes sequentially
        $this->wbsService->reindexProjectWbs($projectId);

        // Recalculate progress
        $task->refresh();
        $this->progressService->recalculateParent($task);
        if ($oldParentId && $oldParentId !== $newParentId) {
            $oldParent = ProjectTask::find($oldParentId);
            if ($oldParent) {
                $this->progressService->recalculateParent($oldParent);
            }
        }
        $this->progressService->recalculateProjectProgress($projectId);

        return response()->json([
            'success' => true,
            'message' => 'Posisi tugas berhasil diperbarui.',
        ]);
    }

    /**
     * Reorder task to a specific 1-indexed position among its siblings.
     */
    public function reorder(Request $request, ProjectTask $task)
    {
        $validated = $request->validate([
            'order' => 'required|integer|min:1',
        ]);

        $this->applyReorder($task, (int)$validated['order']);

        return response()->json([
            'success' => true,
            'message' => 'Urutan tugas berhasil diperbarui menjadi posisi #' . $validated['order'],
            'task'    => $task->fresh(['assignee', 'dependencies'])
        ]);
    }

    /**
     * Apply 1-indexed reorder among siblings and re-index WBS.
     */
    private function applyReorder(ProjectTask $task, int $newOrder): void
    {
        $projectId = $task->project_id;
        $parentId = $task->parent_id;

        // Fetch all siblings under the same parent
        $siblings = ProjectTask::where('project_id', $projectId)
            ->gantt()
            ->where('parent_id', $parentId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Remove the task from siblings list
        $siblings = $siblings->reject(fn($t) => $t->id === $task->id)->values();

        // Clamp newOrder between 0 and count of siblings
        $targetIndex = max(0, min($newOrder - 1, $siblings->count()));

        // Insert task at targetIndex
        $siblings->splice($targetIndex, 0, [$task]);

        // Re-assign sort_order sequentially 1..N
        $order = 1;
        foreach ($siblings as $item) {
            $item->updateQuietly([
                'sort_order' => $order,
            ]);
            $order++;
        }

        // Re-index all WBS codes sequentially
        $this->wbsService->reindexProjectWbs($projectId);

        // Recalculate progress if needed
        if ($parentId) {
            $parent = ProjectTask::find($parentId);
            if ($parent) {
                $this->progressService->recalculateParent($parent);
            }
        } else {
            $this->progressService->recalculateProjectProgress($projectId);
        }
    }

    /**
     * Interactive update for drag/resize in Gantt Chart.
     */
    public function updateInteractive(Request $request, ProjectTask $task)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'due_date'   => 'nullable|date|after_or_equal:start_date',
            'progress'   => 'nullable|numeric|min:0|max:100',
        ]);

        $updateData = [];

        if (isset($validated['start_date']) && isset($validated['due_date'])) {
            $startDate = Carbon::parse($validated['start_date']);
            $dueDate = Carbon::parse($validated['due_date']);
            $updateData['start_date'] = $startDate->format('Y-m-d');
            $updateData['due_date'] = $dueDate->format('Y-m-d');
            $updateData['duration_days'] = max(1, $startDate->diffInDays($dueDate) + 1);
        }

        if (isset($validated['progress']) && !$task->children()->exists()) {
            $progress = (float)$validated['progress'];
            $updateData['progress'] = $progress;
            $updateData['status'] = $progress >= 100 ? 'completed' : ($progress > 0 ? 'in_progress' : 'todo');
            $updateData['column_id'] = $progress >= 100 ? 'done' : ($progress > 0 ? 'in_progress' : 'todo');
        }

        if (!empty($updateData)) {
            $task->update($updateData);
        }

        $freshTask = $task->fresh(['assignee', 'dependencies']);
        $isParent = $freshTask->children()->exists();
        $customClass = 'bar-task';
        if ($isParent) {
            $customClass = 'bar-parent';
        } elseif ($freshTask->progress >= 100) {
            $customClass = 'bar-completed';
        } elseif ($freshTask->progress > 0) {
            $customClass = 'bar-inprogress';
        }

        // Collect updated ancestors (parents, grandparents) whose dates/progress rolled up
        $ancestors = [];
        $currParentId = $freshTask->parent_id;
        while ($currParentId) {
            $parent = ProjectTask::find($currParentId);
            if (!$parent) break;
            $ancestors[] = [
                'id'            => 'task_' . $parent->id,
                'db_id'         => $parent->id,
                'start'         => $parent->start_date ? Carbon::parse($parent->start_date)->format('Y-m-d') : null,
                'end'           => $parent->due_date ? Carbon::parse($parent->due_date)->format('Y-m-d') : null,
                'duration_days' => $parent->duration_days ?: 1,
                'progress'      => (float) $parent->progress,
                'status'        => $parent->status,
            ];
            $currParentId = $parent->parent_id;
        }

        $project = Project::find($freshTask->project_id);

        return response()->json([
            'success'   => true,
            'message'   => 'Tugas berhasil diperbarui.',
            'task'      => [
                'id'            => 'task_' . $freshTask->id,
                'db_id'         => $freshTask->id,
                'start'         => $freshTask->start_date ? Carbon::parse($freshTask->start_date)->format('Y-m-d') : null,
                'end'           => $freshTask->due_date ? Carbon::parse($freshTask->due_date)->format('Y-m-d') : null,
                'duration_days' => $freshTask->duration_days ?: 1,
                'progress'      => (float) $freshTask->progress,
                'status'        => $freshTask->status,
                'custom_class'  => $customClass,
            ],
            'ancestors' => $ancestors,
            'project'   => [
                'progress_percentage' => (int) ($project?->progress_percentage ?: 0),
            ]
        ]);
    }

    /**
     * Delete a task.
     */
    public function destroy(ProjectTask $task)
    {
        $projectId = $task->project_id;
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil dihapus.'
        ]);
    }

    /**
     * Helper to verify if candidateId is a descendant of ancestorId to prevent circular references.
     */
    private function isDescendant(int $candidateId, int $ancestorId): bool
    {
        $current = ProjectTask::find($candidateId);
        while ($current) {
            if ($current->id === $ancestorId || $current->parent_id === $ancestorId) {
                return true;
            }
            $current = $current->parent_id ? ProjectTask::find($current->parent_id) : null;
        }
        return false;
    }

    /**
     * Re-normalize sort_order of siblings under a parent to be clean 1..N.
     */
    private function normalizeSiblings(int $projectId, ?int $parentId): void
    {
        $siblings = ProjectTask::where('project_id', $projectId)
            ->gantt()
            ->where('parent_id', $parentId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $order = 1;
        foreach ($siblings as $sibling) {
            $sibling->updateQuietly(['sort_order' => $order]);
            $order++;
        }
    }
}
