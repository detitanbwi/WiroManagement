<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TestCase;
use App\Models\TaskBug;
use App\Models\User;
use Illuminate\Support\Collection;

class ProjectQcSummaryService
{
    /**
     * Aggregate QA/QC metrics for the given project using high-performance single queries.
     *
     * @param Project $project
     * @return array
     */
    public function aggregateMetrics(Project $project): array
    {
        // 1. Kanban Tasks Aggregation
        $taskStats = ProjectTask::where('project_id', $project->id)
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN column_id = 'todo' THEN 1 END) as todo,
                COUNT(CASE WHEN column_id = 'in_progress' THEN 1 END) as in_progress,
                COUNT(CASE WHEN column_id = 'ready_for_qc' THEN 1 END) as ready_for_qc,
                COUNT(CASE WHEN column_id = 'qc_in_progress' THEN 1 END) as qc_in_progress,
                COUNT(CASE WHEN column_id = 'done' THEN 1 END) as done
            ")
            ->first();

        $kanban = [
            'total' => (int) ($taskStats->total ?? 0),
            'todo' => (int) ($taskStats->todo ?? 0),
            'in_progress' => (int) ($taskStats->in_progress ?? 0),
            'ready_for_qc' => (int) ($taskStats->ready_for_qc ?? 0),
            'qc_in_progress' => (int) ($taskStats->qc_in_progress ?? 0),
            'done' => (int) ($taskStats->done ?? 0),
        ];

        // 2. Test Cases Aggregation
        $testCaseStats = TestCase::where('project_id', $project->id)
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN LOWER(status) = 'passed' THEN 1 END) as passed,
                COUNT(CASE WHEN LOWER(status) IN ('failed', 'fail') THEN 1 END) as failed,
                COUNT(CASE WHEN LOWER(status) NOT IN ('passed', 'failed', 'fail') OR status IS NULL THEN 1 END) as pending
            ")
            ->first();

        $tcTotal = (int) ($testCaseStats->total ?? 0);
        $tcPassed = (int) ($testCaseStats->passed ?? 0);
        $tcFailed = (int) ($testCaseStats->failed ?? 0);
        $tcPending = (int) ($testCaseStats->pending ?? 0);
        $passRate = $tcTotal > 0 ? round(($tcPassed / $tcTotal) * 100, 1) : 0;

        $testCases = [
            'total' => $tcTotal,
            'passed' => $tcPassed,
            'failed' => $tcFailed,
            'pending' => $tcPending,
            'pass_rate' => $passRate,
        ];

        // 3. Bug Tracker Aggregation
        $bugStats = TaskBug::where('project_id', $project->id)
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN LOWER(status) IN ('open', 'in_progress') THEN 1 END) as active,
                COUNT(CASE WHEN LOWER(status) = 'resolved' THEN 1 END) as resolved,
                COUNT(CASE WHEN LOWER(status) = 'closed' THEN 1 END) as closed,
                COUNT(CASE WHEN project_task_id IS NOT NULL THEN 1 END) as assigned,
                COUNT(CASE WHEN project_task_id IS NULL THEN 1 END) as unassigned,
                COUNT(CASE WHEN LOWER(status) IN ('open', 'in_progress') AND project_task_id IS NOT NULL THEN 1 END) as active_assigned,
                COUNT(CASE WHEN LOWER(status) IN ('open', 'in_progress') AND project_task_id IS NULL THEN 1 END) as active_unassigned
            ")
            ->first();

        $bugTotal = (int) ($bugStats->total ?? 0);
        $bugActive = (int) ($bugStats->active ?? 0);
        $bugResolved = (int) ($bugStats->resolved ?? 0);
        $bugClosed = (int) ($bugStats->closed ?? 0);
        $bugAssigned = (int) ($bugStats->assigned ?? 0);
        $bugUnassigned = (int) ($bugStats->unassigned ?? 0);
        $activeAssigned = (int) ($bugStats->active_assigned ?? 0);
        $activeUnassigned = (int) ($bugStats->active_unassigned ?? 0);

        $bugs = [
            'total' => $bugTotal,
            'active' => $bugActive,
            'resolved' => $bugResolved,
            'closed' => $bugClosed,
            'assigned' => $bugAssigned,
            'unassigned' => $bugUnassigned,
            'active_assigned' => $activeAssigned,
            'active_unassigned' => $activeUnassigned,
        ];

        // 4. Project Details & Meta
        $clientName = $project->client ? ($project->client->company_name ?: $project->client->name) : 'Internal / No Client';
        $pmName = $project->pm ? $project->pm->name : 'Unassigned';

        return [
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
                'code' => $project->project_code ?: ('PRJ-' . str_pad($project->id, 4, '0', STR_PAD_LEFT)),
                'status' => ucfirst(str_replace('_', ' ', $project->status ?? 'in_progress')),
                'client_name' => $clientName,
                'pm_name' => $pmName,
                'qc_url' => route('projects.qc', $project->id),
            ],
            'kanban' => $kanban,
            'test_cases' => $testCases,
            'bugs' => $bugs,
            'generated_at' => now()->translatedFormat('d F Y, H:i') . ' WIB',
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Resolve the target destination email for a user.
     * Prioritizes personal_email, falling back to corporate login email.
     *
     * @param User $user
     * @return string|null
     */
    public function getDestinationEmail(User $user): ?string
    {
        $personalEmail = trim($user->personal_email ?? '');
        if (!empty($personalEmail) && filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
            return $personalEmail;
        }

        $loginEmail = trim($user->email ?? '');
        if (!empty($loginEmail) && filter_var($loginEmail, FILTER_VALIDATE_EMAIL)) {
            return $loginEmail;
        }

        return null;
    }

    /**
     * Retrieve all eligible email recipients for this project.
     * Scope: All active members assigned to the project + Project Manager.
     *
     * @param Project $project
     * @return Collection<int, User>
     */
    public function getRecipients(Project $project): Collection
    {
        // 1. Get active users associated via project_user table
        $recipients = $project->users()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNotNull('personal_email')->where('personal_email', '!=', '');
                })->orWhere(function ($sub) {
                    $sub->whereNotNull('email')->where('email', '!=', '');
                });
            })
            ->get();

        // 2. Include project manager (pm_id) if assigned and active
        if ($project->pm_id) {
            $pm = $project->pm;
            if ($pm && $pm->is_active) {
                $hasValidEmail = !empty($this->getDestinationEmail($pm));
                if ($hasValidEmail && !$recipients->contains('id', $pm->id)) {
                    $recipients->push($pm);
                }
            }
        }

        // 3. Fallback: If no project_user records exist yet, ensure PM or project assignees receive it
        if ($recipients->isEmpty()) {
            $taskAssigneeIds = ProjectTask::where('project_id', $project->id)
                ->whereNotNull('assignee_id')
                ->pluck('assignee_id')
                ->unique();

            if ($taskAssigneeIds->isNotEmpty()) {
                $assignees = User::whereIn('id', $taskAssigneeIds)
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->where(function ($sub) {
                            $sub->whereNotNull('personal_email')->where('personal_email', '!=', '');
                        })->orWhere(function ($sub) {
                            $sub->whereNotNull('email')->where('email', '!=', '');
                        });
                    })
                    ->get();
                $recipients = $recipients->merge($assignees);
            }
        }

        return $recipients->unique('id')->values();
    }
}
