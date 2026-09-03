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
            'column_id' => 'required|in:todo,in_progress,ready_for_qc,qc_in_progress,done'
        ]);

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'code' => 'TSK-' . strtoupper(substr(uniqid(), -5)),
            'title' => $request->title,
            'description' => $request->description,
            'assignee_id' => $request->assignee_id,
            'column_id' => $request->column_id
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
            'steps_to_reproduce' => 'nullable|string'
        ]);

        $testCase->update([
            'status' => $request->status
        ]);

        if ($request->status === 'failed') {
            TaskBug::create([
                'project_task_id' => $testCase->project_task_id,
                'test_case_id' => $testCase->id,
                'code' => 'BUG-' . strtoupper(uniqid()),
                'description' => $request->bug_description,
                'steps_to_reproduce' => $request->steps_to_reproduce,
                'status' => 'open'
            ]);
        }

        return response()->json(['success' => true]);
    }
}
