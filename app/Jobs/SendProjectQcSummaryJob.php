<?php

namespace App\Jobs;

use App\Mail\ProjectQcSummaryMail;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectQcSummaryService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendProjectQcSummaryJob
{
    use Dispatchable, SerializesModels;

    /**
     * The project instance.
     *
     * @var Project
     */
    public Project $project;

    /**
     * ID of the user who triggered the summary (if triggered manually).
     *
     * @var int|null
     */
    public ?int $triggeredByUserId;

    /**
     * Create a new job instance.
     *
     * @param Project $project
     * @param int|null $triggeredByUserId
     */
    public function __construct(Project $project, ?int $triggeredByUserId = null)
    {
        $this->project = $project;
        $this->triggeredByUserId = $triggeredByUserId;
    }

    /**
     * Execute the job synchronously.
     *
     * @param ProjectQcSummaryService $service
     * @return array
     */
    public function handle(ProjectQcSummaryService $service): array
    {
        $projectId = $this->project->id;
        $projectTitle = $this->project->title;
        $triggerInfo = $this->triggeredByUserId ? "user ID #{$this->triggeredByUserId}" : "system/scheduler";

        Log::info("[QA/QC Summary] Starting synchronous email dispatch for project #{$projectId} ('{$projectTitle}') triggered by {$triggerInfo}.");

        try {
            // 1. Gather Metrics
            $metrics = $service->aggregateMetrics($this->project);

            // 2. Fetch Eligible Recipients
            $recipients = $service->getRecipients($this->project);

            if ($recipients->isEmpty()) {
                Log::warning("[QA/QC Summary] No eligible active recipients found with valid email addresses for project #{$projectId}. Dispatch aborted.");
                return [
                    'sent' => 0,
                    'failed' => 0,
                    'errors' => [],
                    'total' => 0,
                ];
            }

            Log::info("[QA/QC Summary] Sending QA/QC report for project #{$projectId} to {$recipients->count()} recipient(s).");

            $sentCount = 0;
            $failedCount = 0;
            $errors = [];

            // 3. Dispatch emails synchronously
            $mailable = new ProjectQcSummaryMail($this->project, $metrics);

            foreach ($recipients as $recipient) {
                $targetEmail = $service->getDestinationEmail($recipient);

                if (empty($targetEmail)) {
                    Log::warning("[QA/QC Summary] User #{$recipient->id} ({$recipient->name}) has no valid personal or account email address. Skipping.");
                    continue;
                }

                $emailType = (!empty($recipient->personal_email) && $targetEmail === $recipient->personal_email) 
                    ? 'personal email' 
                    : 'login email (fallback)';

                try {
                    Mail::to($targetEmail)->send($mailable);
                    $sentCount++;

                    Log::info("[QA/QC Summary] Successfully sent report to {$recipient->name} <{$targetEmail}> ({$emailType}) for project #{$projectId}.");
                } catch (Throwable $e) {
                    $failedCount++;
                    $errors[] = [
                        'user_id' => $recipient->id,
                        'email' => $targetEmail,
                        'email_type' => $emailType,
                        'error' => $e->getMessage(),
                    ];

                    Log::error("[QA/QC Summary] Failed sending email to {$targetEmail} ({$emailType}) for project #{$projectId}: {$e->getMessage()}", [
                        'exception' => $e,
                        'project_id' => $projectId,
                        'recipient_id' => $recipient->id,
                    ]);
                }
            }

            Log::info("[QA/QC Summary] Completed dispatch for project #{$projectId}. Success: {$sentCount}, Failed: {$failedCount}.");

            // If all recipients failed and there were recipients, throw exception
            if ($sentCount === 0 && $recipients->isNotEmpty()) {
                throw new \RuntimeException("[QA/QC Summary] All {$failedCount} recipient deliveries failed for project #{$projectId}.");
            }

            return [
                'sent' => $sentCount,
                'failed' => $failedCount,
                'errors' => $errors,
                'total' => $recipients->count(),
            ];

        } catch (Throwable $e) {
            Log::error("[QA/QC Summary] Unexpected failure during summary dispatch for project #{$projectId}: {$e->getMessage()}", [
                'exception' => $e,
                'project_id' => $projectId,
            ]);

            throw $e;
        }
    }
}
