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
                            {--sync : Execute immediately without queueing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch QA/QC Summary Emails to project members and stakeholders';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $projectArg = $this->argument('project');
        $runSync = $this->option('sync');

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

        $this->info("Dispatching QA/QC Summary for {$projects->count()} project(s)...");

        foreach ($projects as $project) {
            $this->line("- Scheduling project #{$project->id}: {$project->title}");

            if ($runSync) {
                dispatch_sync(new SendProjectQcSummaryJob($project));
                $this->info("  [SYNC SENT] Completed dispatch for #{$project->id}.");
            } else {
                SendProjectQcSummaryJob::dispatch($project);
                $this->info("  [QUEUED] Job pushed to queue for #{$project->id}.");
            }
        }

        $this->newLine();
        $this->info("All QA/QC summary emails have been queued/dispatched successfully.");

        return self::SUCCESS;
    }
}
