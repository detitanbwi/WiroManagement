<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TestCase;
use App\Models\TaskBug;
use App\Models\TaskComment;

class QcController extends Controller
{
    public function getTasks(Project $project)
    {
        $tasks = $project->tasks()->with([
            'assignee',
            'testCases' => function($q) {
                $q->with(['bugs.projectTask'])->orderBy('sort_order')->orderBy('id');
            },
            'bugs.testCase.bugs.projectTask',
            'bugs.projectTask',
            'comments.user'
        ])->get();
        
        $formattedTasks = $tasks->map(function ($task) {
            // Collect direct test cases and test cases linked through bugs
            $directTestCases = $task->testCases;
            $bugTestCases = $task->bugs->map(fn($b) => $b->testCase)->filter();
            $allTestCases = $directTestCases->concat($bugTestCases)->unique('id');

            $passedTests = $allTestCases->where('status', 'passed')->count();
            $hasActiveBug = $allTestCases->where('status', 'failed')->count() > 0 || $task->bugs->whereIn('status', ['open', 'in_progress'])->count() > 0;

            $sourceBug = $task->bugs->first(fn($b) => $b->testCase !== null);
            $sourceTestCase = $sourceBug && $sourceBug->testCase ? [
                'id' => $sourceBug->testCase->id,
                'code' => $sourceBug->testCase->code,
                'title' => $sourceBug->testCase->title,
                'bug_code' => $sourceBug->code,
                'preconditions' => $sourceBug->testCase->preconditions,
                'expected' => $sourceBug->testCase->expected,
                'status' => $sourceBug->testCase->status,
                'steps' => $sourceBug->testCase->steps ?? [],
                'payload' => $sourceBug->testCase->payload,
                'complexity' => $sourceBug->testCase->complexity,
                'priority' => $sourceBug->testCase->priority,
                'test_type' => $sourceBug->testCase->test_type,
                'automation_status' => $sourceBug->testCase->automation_status,
            ] : null;

            return [
                'id' => $task->id,
                'code' => $task->code,
                'title' => $task->title,
                'description' => $task->description,
                'assignee' => $task->assignee ? $task->assignee->name : 'Unassigned',
                'column_id' => $task->column_id,
                'attachment_path' => $task->attachment_path,
                'hasActiveBug' => $hasActiveBug,
                'source_test_case' => $sourceTestCase,
                'testCasesCount' => $allTestCases->count(),
                'passedTests' => $passedTests,
                'bugs' => $task->bugs->map(function($bug) {
                    return [
                        'id' => $bug->id,
                        'code' => $bug->code,
                        'description' => $bug->description,
                        'severity' => $bug->severity,
                        'status' => $bug->status,
                        'actual_result' => $bug->actual_result,
                        'environment' => $bug->environment,
                        'created_at' => $bug->created_at ? $bug->created_at->format('d M Y, H:i') : null,
                        'updated_at' => $bug->updated_at ? $bug->updated_at->format('d M Y, H:i') : null,
                        'test_case' => $bug->testCase ? [
                            'id' => $bug->testCase->id,
                            'code' => $bug->testCase->code,
                            'title' => $bug->testCase->title,
                        ] : null,
                    ];
                }),
                'testCases' => $allTestCases->map(function($tc) use ($task) {
                    $linkedBug = $task->bugs->firstWhere('test_case_id', $tc->id)
                        ?? ($tc->relationLoaded('bugs') ? ($tc->bugs->firstWhere('status', 'open') ?? $tc->bugs->firstWhere('status', 'in_progress') ?? $tc->bugs->first()) : null);

                    $tcBugs = $tc->relationLoaded('bugs') ? $tc->bugs : collect();

                    return [
                        'id' => $tc->id,
                        'code' => $tc->code,
                        'title' => $tc->title,
                        'preconditions' => $tc->preconditions,
                        'expected' => $tc->expected,
                        'status' => $tc->status,
                        'steps' => $tc->steps ?? [],
                        'payload' => $tc->payload,
                        'complexity' => $tc->complexity,
                        'priority' => $tc->priority,
                        'test_type' => $tc->test_type,
                        'automation_status' => $tc->automation_status,
                        'is_from_bug' => (bool)$task->bugs->firstWhere('test_case_id', $tc->id),
                        'bug_code' => $linkedBug ? $linkedBug->code : null,
                        'bug' => $linkedBug ? [
                            'id' => $linkedBug->id,
                            'code' => $linkedBug->code,
                            'status' => $linkedBug->status,
                            'severity' => $linkedBug->severity,
                            'description' => $linkedBug->description,
                            'actual_result' => $linkedBug->actual_result,
                            'environment' => $linkedBug->environment,
                            'project_task_id' => $linkedBug->project_task_id,
                        ] : null,
                        'bugs' => $tcBugs->map(function($b) {
                            return [
                                'id' => $b->id,
                                'code' => $b->code,
                                'status' => $b->status,
                                'severity' => $b->severity,
                                'description' => $b->description,
                                'actual_result' => $b->actual_result,
                                'environment' => $b->environment,
                                'attachment_path' => $b->attachment_path,
                                'created_at' => $b->created_at ? $b->created_at->format('d M Y, H:i') : null,
                                'updated_at' => $b->updated_at ? $b->updated_at->format('d M Y, H:i') : null,
                                'project_task' => $b->projectTask ? [
                                    'id' => $b->projectTask->id,
                                    'code' => $b->projectTask->code,
                                    'title' => $b->projectTask->title,
                                    'column_id' => $b->projectTask->column_id,
                                ] : null,
                            ];
                        })->values(),
                    ];
                })->values(),
                'comments_count' => $task->comments->count(),
                'comments' => $task->comments->map(function($comment) {
                    return [
                        'id' => $comment->id,
                        'comment' => $comment->comment,
                        'attachment_path' => $comment->attachment_path,
                        'user' => $comment->user ? [
                            'id' => $comment->user->id,
                            'name' => $comment->user->name,
                            'role' => $comment->user->role,
                        ] : null,
                        'created_at' => $comment->created_at ? $comment->created_at->format('d M Y, H:i') : null,
                        'created_at_human' => $comment->created_at ? $comment->created_at->diffForHumans() : null,
                        'can_delete' => auth()->id() === $comment->user_id || in_array(auth()->user()?->role, ['superadmin', 'admin']),
                    ];
                })->values()
            ];
        });

        return response()->json($formattedTasks);
    }

    public function storeTask(Request $request, Project $project)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|exists:users,id',
            'column_id' => 'required|in:todo,in_progress,ready_for_qc,qc_in_progress,done',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx,xls,xlsx|max:10240' // 10MB max
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments/tasks', 'public');
        }

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-' . strtoupper(substr(uniqid(), -5)),
            'title' => $request->title,
            'description' => $request->description,
            'assignee_id' => $request->assignee_id,
            'column_id' => $request->column_id,
            'attachment_path' => $attachmentPath
        ]);

        return response()->json(['success' => true, 'task' => $task]);
    }

    public function updateTaskColumn(Request $request, ProjectTask $task)
    {
        $request->validate([
            'column_id' => 'required|string|in:todo,in_progress,ready_for_qc,qc_in_progress,done'
        ]);

        $task->update([
            'column_id' => $request->column_id
        ]);

        // When a task is marked as done (passed QC), update all associated test cases to passed and resolve bugs
        if ($request->column_id === 'done') {
            $this->markTaskTestCasesAsPassed($task);
        }

        return response()->json(['success' => true]);
    }

    public function passTaskTestCases(ProjectTask $task)
    {
        $this->markTaskTestCasesAsPassed($task);
        return response()->json(['success' => true]);
    }

    private function markTaskTestCasesAsPassed(ProjectTask $task)
    {
        // 1. Direct test cases attached to this task
        $directTestCases = TestCase::where('project_task_id', $task->id)->get();
        foreach ($directTestCases as $tc) {
            $tc->update(['status' => 'passed']);
            TaskBug::where('test_case_id', $tc->id)
                ->whereIn('status', ['open', 'in_progress'])
                ->update(['status' => 'resolved']);
        }

        // 2. Bugs attached to this task, and their source test cases
        $taskBugs = TaskBug::where('project_task_id', $task->id)->get();
        foreach ($taskBugs as $bug) {
            if ($bug->status !== 'resolved') {
                $bug->update(['status' => 'resolved']);
            }
            if ($bug->test_case_id) {
                TestCase::where('id', $bug->test_case_id)->update(['status' => 'passed']);
            }
        }
    }

    public function submitTestResult(Request $request, TestCase $testCase)
    {
        $request->validate([
            'status' => 'required|in:passed,failed',
            'bug_description' => 'required_if:status,failed|string',
            'steps_to_reproduce' => 'nullable|string',
            'severity' => 'nullable|string|in:Low,Medium,High,Critical',
            'actual_result' => 'nullable|string',
            'environment' => 'nullable|string',
            'create_task' => 'nullable|in:true,false,1,0', // FormData sends strings
            'assignee_id' => 'nullable|exists:users,id',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx,xls,xlsx|max:10240'
        ]);

        $testCase->update([
            'status' => $request->status
        ]);

        if ($request->status === 'failed') {
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('attachments/bugs', 'public');
            }
            
            // Convert to boolean since FormData sends it as a string
            $createTask = filter_var($request->create_task, FILTER_VALIDATE_BOOLEAN);

            $projectTaskId = null; // Do NOT inherit previous tasks! New bug will be on a new task or unassigned.

            // If the user wants to auto-create a Kanban task
            if ($createTask) {
                $newTask = \App\Models\ProjectTask::create([
                    'project_id' => $testCase->project_id,
                    'code' => 'TSK-' . strtoupper(substr(uniqid(), -5)),
                    'title' => 'Bug: ' . substr($request->bug_description, 0, 50) . (strlen($request->bug_description) > 50 ? '...' : ''),
                    'description' => "Auto-generated bug report from Test Case: " . $testCase->code . "\n\n" . $request->bug_description,
                    'assignee_id' => $request->assignee_id,
                    'column_id' => 'todo',
                    'attachment_path' => $attachmentPath
                ]);
                $projectTaskId = $newTask->id;
            }

            // Ensure 1-to-1 relationship: 1 failed test case maps to 1 active bug tracker
            $existingBug = TaskBug::where('test_case_id', $testCase->id)
                ->whereIn('status', ['open', 'in_progress'])
                ->first();

            if ($existingBug) {
                $existingBug->update([
                    'project_task_id' => $projectTaskId ?? $existingBug->project_task_id,
                    'description' => $request->bug_description,
                    'steps_to_reproduce' => $request->steps_to_reproduce,
                    'severity' => $request->severity ?? $existingBug->severity,
                    'actual_result' => $request->actual_result,
                    'environment' => $request->environment,
                    'attachment_path' => $attachmentPath ?? $existingBug->attachment_path,
                ]);
                $bug = $existingBug;
            } else {
                $bug = TaskBug::create([
                    'project_id' => $testCase->project_id,
                    'project_task_id' => $projectTaskId, // Can be the newly created task or the test case's existing linked task (or null)
                    'test_case_id' => $testCase->id,
                    'code' => 'BUG-' . strtoupper(substr(uniqid(), -5)),
                    'description' => $request->bug_description,
                    'steps_to_reproduce' => $request->steps_to_reproduce,
                    'severity' => $request->severity ?? 'Medium',
                    'actual_result' => $request->actual_result,
                    'environment' => $request->environment,
                    'attachment_path' => $attachmentPath,
                    'status' => 'open'
                ]);
            }
        } elseif ($request->status === 'passed') {
            // Auto-resolve any open bugs for this test case
            TaskBug::where('test_case_id', $testCase->id)
                ->whereIn('status', ['open', 'in_progress'])
                ->update(['status' => 'resolved']);
        }

        return response()->json([
            'success' => true,
            'bug' => isset($bug) ? [
                'id' => $bug->id,
                'code' => $bug->code,
                'status' => $bug->status,
                'description' => $bug->description,
            ] : null
        ]);
    }

    public function getProjectTestCases(Project $project)
    {
        // Fetch all test cases for the project with their bugs and linked tasks
        $testCases = TestCase::where('project_id', $project->id)
            ->with([
                'bugs' => function($q) {
                    $q->with('projectTask')->orderBy('created_at', 'desc');
                }
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        
        $tree = $this->buildTestCaseTree($testCases, null);
        
        return response()->json($tree);
    }

    private function buildTestCaseTree($testCases, $parentId = null)
    {
        $branch = [];

        foreach ($testCases as $testCase) {
            if ($testCase->parent_id == $parentId) {
                $children = $this->buildTestCaseTree($testCases, $testCase->id);
                
                // Find the active (open/in_progress) or latest bug for this test case
                $activeBug = $testCase->bugs->firstWhere('status', 'open')
                    ?? $testCase->bugs->firstWhere('status', 'in_progress')
                    ?? $testCase->bugs->first();

                $formatted = [
                    'id' => $testCase->id,
                    'code' => $testCase->code,
                    'title' => $testCase->title,
                    'preconditions' => $testCase->preconditions,
                    'expected' => $testCase->expected,
                    'status' => $testCase->status,
                    'steps' => $testCase->steps ?? [],
                    'payload' => $testCase->payload,
                    'complexity' => $testCase->complexity,
                    'priority' => $testCase->priority,
                    'test_type' => $testCase->test_type,
                    'automation_status' => $testCase->automation_status,
                    'is_expanded' => false, // For frontend Alpine state
                    'bug' => $activeBug ? [
                        'id' => $activeBug->id,
                        'code' => $activeBug->code,
                        'status' => $activeBug->status,
                        'severity' => $activeBug->severity,
                        'description' => $activeBug->description,
                        'actual_result' => $activeBug->actual_result,
                        'environment' => $activeBug->environment,
                        'project_task_id' => $activeBug->project_task_id,
                    ] : null,
                    'bugs' => $testCase->bugs->map(function($b) {
                        return [
                            'id' => $b->id,
                            'code' => $b->code,
                            'status' => $b->status,
                            'severity' => $b->severity,
                            'description' => $b->description,
                            'actual_result' => $b->actual_result,
                            'environment' => $b->environment,
                            'attachment_path' => $b->attachment_path,
                            'created_at' => $b->created_at ? $b->created_at->format('d M Y, H:i') : null,
                            'updated_at' => $b->updated_at ? $b->updated_at->format('d M Y, H:i') : null,
                            'project_task' => $b->projectTask ? [
                                'id' => $b->projectTask->id,
                                'code' => $b->projectTask->code,
                                'title' => $b->projectTask->title,
                                'column_id' => $b->projectTask->column_id,
                            ] : null,
                        ];
                    })->values(),
                ];

                $formatted['children'] = $children;
                $branch[] = $formatted;
            }
        }

        return $branch;
    }

    public function storeProjectTestCase(Request $request, Project $project)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'preconditions' => 'nullable|string',
            'expected' => 'nullable|string',
            'parent_id' => 'nullable|exists:test_cases,id',
            'steps' => 'nullable|array',
            'payload' => 'nullable|string',
            'complexity' => 'nullable|string|in:Low,Medium,High',
            'priority' => 'nullable|string|in:Low,Medium,High,Critical',
            'test_type' => 'nullable|string|in:Functional,UI/UX,API,Security,Performance,Edge Case',
            'automation_status' => 'nullable|string|in:Manual,Automated,Not Automatable'
        ]);

        $testCase = TestCase::create([
            'project_id' => $project->id,
            'parent_id' => $request->parent_id,
            'code' => 'TC-' . strtoupper(substr(uniqid(), -5)),
            'title' => $request->title,
            'preconditions' => $request->preconditions,
            'expected' => $request->expected,
            'steps' => $request->steps ?? [],
            'payload' => $request->payload,
            'complexity' => $request->complexity,
            'priority' => $request->priority,
            'test_type' => $request->test_type,
            'automation_status' => $request->automation_status,
            'status' => 'pending'
        ]);

        return response()->json(['success' => true, 'testCase' => $testCase]);
    }

    public function updateProjectTestCase(Request $request, TestCase $testCase)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'preconditions' => 'nullable|string',
            'expected' => 'nullable|string',
            'steps' => 'nullable|array',
            'payload' => 'nullable|string',
            'complexity' => 'nullable|string|in:Low,Medium,High',
            'priority' => 'nullable|string|in:Low,Medium,High,Critical',
            'test_type' => 'nullable|string|in:Functional,UI/UX,API,Security,Performance,Edge Case',
            'automation_status' => 'nullable|string|in:Manual,Automated,Not Automatable'
        ]);

        $testCase->update([
            'title' => $request->title,
            'preconditions' => $request->preconditions,
            'expected' => $request->expected,
            'steps' => $request->steps ?? [],
            'payload' => $request->payload,
            'complexity' => $request->complexity,
            'priority' => $request->priority,
            'test_type' => $request->test_type,
            'automation_status' => $request->automation_status
        ]);

        return response()->json(['success' => true, 'testCase' => $testCase]);
    }

    public function moveProjectTestCase(Request $request, TestCase $testCase)
    {
        $request->validate([
            'target_id' => 'nullable|exists:test_cases,id',
            'position' => 'required|in:before,after,inside'
        ]);

        $targetId = $request->target_id;
        $position = $request->position;
        $projectId = $testCase->project_id;

        // If no target, meaning moving to root at the end
        if (!$targetId) {
            $testCase->update([
                'parent_id' => null,
                'sort_order' => TestCase::where('project_id', $projectId)->whereNull('parent_id')->max('sort_order') + 10
            ]);
            $this->reorderSiblings($projectId, null);
            return response()->json(['success' => true]);
        }

        $target = TestCase::find($targetId);

        // Prevent moving to self
        if ($targetId == $testCase->id) {
            return response()->json(['success' => false, 'message' => 'Cannot move a test case into itself.'], 400);
        }

        $newParentId = $position === 'inside' ? $targetId : $target->parent_id;

        // Prevent cyclic loop (cannot move to a descendant)
        if ($newParentId) {
            $currentParent = TestCase::find($newParentId);
            while ($currentParent) {
                if ($currentParent->parent_id == $testCase->id || $currentParent->id == $testCase->id) {
                    return response()->json(['success' => false, 'message' => 'Cannot move a test case into its own descendant.'], 400);
                }
                $currentParent = $currentParent->parent_id ? TestCase::find($currentParent->parent_id) : null;
            }
        }

        $testCase->parent_id = $newParentId;
        
        if ($position === 'inside') {
            $testCase->sort_order = TestCase::where('parent_id', $newParentId)->max('sort_order') + 10;
            $testCase->save();
        } else {
            // Give it a slightly lower or higher order, then reorder to normalize
            $testCase->sort_order = $position === 'before' ? $target->sort_order - 1 : $target->sort_order + 1;
            $testCase->save();
        }

        $this->reorderSiblings($projectId, $newParentId);

        return response()->json(['success' => true]);
    }

    private function reorderSiblings($projectId, $parentId)
    {
        $siblings = TestCase::where('project_id', $projectId)
            ->where('parent_id', $parentId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        
        $order = 0;
        foreach ($siblings as $sibling) {
            $sibling->sort_order = $order;
            $sibling->save();
            $order += 10;
        }
    }
    public function getProjectBugs(Project $project)
    {
        $bugs = TaskBug::where('project_id', $project->id)
            ->with(['testCase', 'projectTask'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        $formattedBugs = $bugs->map(function($bug) {
            return [
                'id' => $bug->id,
                'code' => $bug->code,
                'description' => $bug->description,
                'steps_to_reproduce' => $bug->steps_to_reproduce,
                'severity' => $bug->severity,
                'status' => $bug->status,
                'actual_result' => $bug->actual_result,
                'environment' => $bug->environment,
                'attachment_path' => $bug->attachment_path,
                'created_at' => $bug->created_at ? $bug->created_at->format('d M Y, H:i') : 'Unknown',
                'created_at_human' => $bug->created_at ? $bug->created_at->diffForHumans() : 'Unknown',
                'updated_at' => $bug->updated_at ? $bug->updated_at->format('d M Y, H:i') : 'Unknown',
                'test_case' => $bug->testCase ? [
                    'id' => $bug->testCase->id,
                    'code' => $bug->testCase->code,
                    'title' => $bug->testCase->title,
                    'status' => $bug->testCase->status,
                ] : null,
                'project_task' => $bug->projectTask ? [
                    'id' => $bug->projectTask->id,
                    'code' => $bug->projectTask->code,
                    'title' => $bug->projectTask->title,
                    'column_id' => $bug->projectTask->column_id,
                ] : null,
            ];
        });

        return response()->json($formattedBugs);
    }

    public function convertBugToTask(Request $request, TaskBug $bug)
    {
        $request->validate([
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        if ($bug->project_task_id) {
            return response()->json(['success' => false, 'message' => 'Bug is already linked to a task.'], 400);
        }

        $testCaseCode = $bug->testCase ? $bug->testCase->code : 'Unknown';
        $projectId = $bug->project_id ?? ($bug->testCase ? $bug->testCase->project_id : null);

        if (!$projectId) {
            return response()->json(['success' => false, 'message' => 'Project ID tidak ditemukan pada bug ini.'], 400);
        }

        $newTask = \App\Models\ProjectTask::create([
            'project_id' => $projectId,
            'code' => 'TSK-' . strtoupper(substr(uniqid(), -5)),
            'title' => 'Bug: ' . substr($bug->description, 0, 50) . (strlen($bug->description) > 50 ? '...' : ''),
            'description' => "Bug report from Test Case: " . $testCaseCode . "\n\n" . $bug->description,
            'assignee_id' => $request->assignee_id,
            'column_id' => 'todo',
            'attachment_path' => $bug->attachment_path
        ]);

        $bug->update([
            'project_id' => $projectId,
            'project_task_id' => $newTask->id
        ]);

        return response()->json(['success' => true, 'task' => $newTask]);
    }

    public function bulkConvertBugsToTask(Request $request, Project $project)
    {
        $request->validate([
            'bug_ids' => 'required|array|min:1',
            'bug_ids.*' => 'required|exists:task_bugs,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|exists:users,id',
            'column_id' => 'nullable|string|in:todo,in_progress,ready_for_qc,qc_in_progress,done'
        ]);

        $bugs = TaskBug::whereIn('id', $request->bug_ids)
            ->where('project_id', $project->id)
            ->with('testCase')
            ->get();

        if ($bugs->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Tidak ada bug valid yang dipilih.'], 400);
        }

        // Build default description if empty
        $description = $request->description;
        if (empty($description)) {
            $descLines = ["Batch defect task untuk perbaikan " . $bugs->count() . " bug:"];
            foreach ($bugs as $bug) {
                $tcCode = $bug->testCase ? $bug->testCase->code : 'No-TC';
                $descLines[] = "• [{$bug->code}] ({$tcCode}): {$bug->description}";
            }
            $description = implode("\n", $descLines);
        }

        // Use the first bug's attachment if available
        $attachmentPath = $bugs->first(fn($b) => !empty($b->attachment_path))?->attachment_path;

        $newTask = \App\Models\ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-' . strtoupper(substr(uniqid(), -5)),
            'title' => $request->title,
            'description' => $description,
            'assignee_id' => $request->assignee_id,
            'column_id' => $request->column_id ?: 'todo',
            'attachment_path' => $attachmentPath
        ]);

        // Link all selected bugs to this single new task
        foreach ($bugs as $bug) {
            $bug->update([
                'project_task_id' => $newTask->id
            ]);
        }

        return response()->json([
            'success' => true,
            'task' => $newTask,
            'count' => $bugs->count()
        ]);
    }

    public function destroyTestCase(TestCase $testCase)
    {
        // Recursive deletion handled by database constraints or model boot method if needed.
        // Assuming cascade on delete is set, or we can just delete it directly.
        $testCase->delete();
        return response()->json(['success' => true]);
    }

    public function destroyTask(ProjectTask $task)
    {
        if ($task->attachment_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($task->attachment_path);
        }
        $task->delete();
        return response()->json(['success' => true]);
    }

    public function destroyBug(TaskBug $bug)
    {
        if ($bug->attachment_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($bug->attachment_path);
        }
        $bug->delete();
        return response()->json(['success' => true]);
    }

    public function getTaskComments(ProjectTask $task)
    {
        $comments = $task->comments()->with('user')->orderBy('created_at', 'asc')->get();

        $formatted = $comments->map(function ($comment) {
            return [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'attachment_path' => $comment->attachment_path,
                'user' => $comment->user ? [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'role' => $comment->user->role,
                ] : null,
                'created_at' => $comment->created_at ? $comment->created_at->format('d M Y, H:i') : null,
                'created_at_human' => $comment->created_at ? $comment->created_at->diffForHumans() : null,
                'can_delete' => auth()->id() === $comment->user_id || in_array(auth()->user()?->role, ['superadmin', 'admin']),
            ];
        });

        return response()->json($formatted);
    }

    public function storeTaskComment(Request $request, ProjectTask $task)
    {
        $request->validate([
            'comment' => 'required_without:attachment|nullable|string',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx,xls,xlsx,zip,txt|max:10240',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments/task_comments', 'public');
        }

        $comment = TaskComment::create([
            'project_task_id' => $task->id,
            'user_id' => auth()->id(),
            'comment' => $request->comment ?? '',
            'attachment_path' => $attachmentPath,
        ]);

        $comment->load('user');

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'attachment_path' => $comment->attachment_path,
                'user' => $comment->user ? [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'role' => $comment->user->role,
                ] : null,
                'created_at' => $comment->created_at ? $comment->created_at->format('d M Y, H:i') : null,
                'created_at_human' => $comment->created_at ? $comment->created_at->diffForHumans() : null,
                'can_delete' => true,
            ]
        ]);
    }

    public function destroyTaskComment(TaskComment $comment)
    {
        if (auth()->id() !== $comment->user_id && !in_array(auth()->user()?->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($comment->attachment_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($comment->attachment_path);
        }

        $comment->delete();

        return response()->json(['success' => true]);
    }
}
