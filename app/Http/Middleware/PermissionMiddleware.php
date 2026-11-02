<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        // Super Admin bypass
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $projectModel = null;

        // 1. Direct project parameter
        $project = $request->route('project');
        if ($project instanceof \App\Models\Project) {
            $projectModel = $project;
        } elseif (is_numeric($project)) {
            $projectModel = \App\Models\Project::find($project);
        }

        // 2. Extract from task parameter (ProjectTask or ID)
        if (!$projectModel) {
            $task = $request->route('task');
            if ($task instanceof \App\Models\ProjectTask) {
                $projectModel = $task->project;
            } elseif (is_numeric($task)) {
                $taskModel = \App\Models\ProjectTask::find($task);
                $projectModel = $taskModel?->project;
            }
        }

        // 3. Extract from testCase parameter (TestCase or ID)
        if (!$projectModel) {
            $testCase = $request->route('testCase') ?? $request->route('test_case');
            if ($testCase instanceof \App\Models\TestCase) {
                $projectModel = $testCase->project;
            } elseif (is_numeric($testCase)) {
                $testCaseModel = \App\Models\TestCase::find($testCase);
                $projectModel = $testCaseModel?->project;
            }
        }

        // 4. Extract from bug parameter (QcBug / TaskBug or ID)
        if (!$projectModel) {
            $bug = $request->route('bug');
            if ($bug instanceof \App\Models\TaskBug || $bug instanceof \App\Models\QcBug) {
                $projectModel = $bug->project ?? $bug->projectTask?->project;
            } elseif (is_numeric($bug)) {
                $bugModel = \App\Models\TaskBug::find($bug);
                $projectModel = $bugModel?->project ?? $bugModel?->projectTask?->project;
            }
        }

        // 5. Extract from expense parameter (ProjectExpense or ID)
        if (!$projectModel) {
            $expense = $request->route('expense');
            if ($expense instanceof \App\Models\ProjectExpense) {
                $projectModel = $expense->project;
            } elseif (is_numeric($expense)) {
                $expenseModel = \App\Models\ProjectExpense::find($expense);
                $projectModel = $expenseModel?->project;
            }
        }

        // 6. Fallback: project_id from request body / query parameter
        if (!$projectModel && $request->filled('project_id')) {
            $projectId = $request->input('project_id');
            if (is_numeric($projectId)) {
                $projectModel = \App\Models\Project::find($projectId);
            }
        }

        if (!empty($permissions) && !$user->hasAnyPermission($permissions, $projectModel)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Anda tidak memiliki izin untuk tindakan ini.',
                    'required_permissions' => $permissions,
                ], 403);
            }

            abort(403, 'Akses ditolak. Anda tidak memiliki izin yang diperlukan untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
