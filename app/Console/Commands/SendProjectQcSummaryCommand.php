<?php

namespace App\Console\Commands;

use App\Jobs\SendProjectQcSummaryJob;
use App\Models\Project;
use Illuminate\Console\Command;

class SendProjectQcSummaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qc:send-summary 
                            {project? : ID or Project Code to send summary for (optional)}
                            {--sync : Execute immediately (sync mode is default)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send QA/QC Summary Emails synchronously to project members and stakeholders';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $projectArg = $this->argument('project');

        if ($projectArg) {
            $project = Project::where('id', $projectArg)
                ->orWhere('project_code', $projectArg)
                ->first();

            if (!$project) {
                $this->error("Project [{$projectArg}] not found.");
                return self::FAILURE;
            }

            $projects = collect([$project]);
        } else {
            // Find all active projects
            $projects = Project::whereIn('status', ['in_progress', 'approved', 'quotation_sent'])
                ->get();

            if ($projects->isEmpty()) {
                $this->info("No active projects found to send QA/QC summary.");
                return self::SUCCESS;
            }
        }

        $this->info("Sending QA/QC Summary synchronously for {$projects->count()} project(s)...");

        foreach ($projects as $project) {
            $this->line("- Sending summary for project #{$project->id}: {$project->title}");

            try {
                $result = SendProjectQcSummaryJob::dispatchSync($project);
                $sent = $result['sent'] ?? 0;
                $failed = $result['failed'] ?? 0;
                $this->info("  [SENT] Completed dispatch for #{$project->id}: {$sent} sent" . ($failed > 0 ? ", {$failed} failed." : "."));
            } catch (\Throwable $e) {
                $this->error("  [FAILED] Failed sending summary for #{$project->id}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("All QA/QC summary emails have been dispatched successfully.");

        return self::SUCCESS;
    }
}
