<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TestCase;
use App\Models\TaskBug;

class QcController extends Controller
{
    public function getTasks(Project $project)
    {
        $tasks = $project->tasks()->with(['assignee', 'testCases', 'bugs'])->get();
        
        $formattedTasks = $tasks->map(function ($task) {
            $passedTests = $task->testCases->where('status', 'passed')->count();
            $hasActiveBug = $task->testCases->where('status', 'failed')->count() > 0;
            
            return [
                'id' => $task->id,
                'code' => $task->code,
                'title' => $task->title,
                'description' => $task->description,
                'assignee' => $task->assignee ? $task->assignee->name : 'Unassigned',
                'column_id' => $task->column_id,
                'attachment_path' => $task->attachment_path,
                'hasActiveBug' => $hasActiveBug,
                'testCasesCount' => $task->testCases->count(),
                'passedTests' => $passedTests,
                'testCases' => $task->testCases->map(function($tc) {
                    return [
                        'id' => $tc->id,
                        'code' => $tc->code,
                        'title' => $tc->title,
                        'preconditions' => $tc->preconditions,
                        'expected' => $tc->expected,
                        'status' => $tc->status,
                        'steps' => $tc->steps ?? []
                    ];
                })
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

        return response()->json(['success' => true]);
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
            $projectTaskId = $testCase->project_task_id;
            
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('attachments/bugs', 'public');
            }
            
            // Convert to boolean since FormData sends it as a string
            $createTask = filter_var($request->create_task, FILTER_VALIDATE_BOOLEAN);

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

            TaskBug::create([
                'project_id' => $testCase->project_id,
                'project_task_id' => $projectTaskId, // Can be the newly created task or the test case's existing linked task (or null)
                'test_case_id' => $testCase->id,
                'code' => 'BUG-' . strtoupper(substr(uniqid(), -5)),
                'description' => $request->bug_description,
                'steps_to_reproduce' => $request->steps_to_reproduce,
                'severity' => $request->severity,
                'actual_result' => $request->actual_result,
                'environment' => $request->environment,
                'attachment_path' => $attachmentPath,
                'status' => 'open'
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function getProjectTestCases(Project $project)
    {
        // Fetch all test cases for the project
        // Note: For older test cases that might not have project_id, they won't show up unless migrated properly.
        $testCases = TestCase::where('project_id', $project->id)->get();
        
        $tree = $this->buildTestCaseTree($testCases, null);
        
        return response()->json($tree);
    }

    private function buildTestCaseTree($testCases, $parentId = null)
    {
        $branch = [];

        foreach ($testCases as $testCase) {
            if ($testCase->parent_id == $parentId) {
                $children = $this->buildTestCaseTree($testCases, $testCase->id);
                
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
                    'is_expanded' => false // For frontend Alpine state
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
            'parent_id' => 'nullable|exists:test_cases,id'
        ]);

        $newParentId = $request->parent_id;

        // Prevent moving to self
        if ($newParentId == $testCase->id) {
            return response()->json(['success' => false, 'message' => 'Cannot move a test case into itself.'], 400);
        }

        // Prevent cyclic loop (cannot move to a descendant)
        if ($newParentId) {
            $currentParent = TestCase::find($newParentId);
            while ($currentParent) {
                if ($currentParent->parent_id == $testCase->id) {
                    return response()->json(['success' => false, 'message' => 'Cannot move a test case into its own descendant.'], 400);
                }
                $currentParent = $currentParent->parent_id ? TestCase::find($currentParent->parent_id) : null;
            }
        }

        $testCase->update([
            'parent_id' => $newParentId
        ]);

        return response()->json(['success' => true]);
    }
}
