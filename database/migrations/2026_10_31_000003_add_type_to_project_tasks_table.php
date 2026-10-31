<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->string('type', 20)->default('gantt')->after('project_id');
            $table->index(['project_id', 'type']);
        });

        // Backfill existing data
        // 1. Default all existing tasks to 'gantt'
        DB::table('project_tasks')->update(['type' => 'gantt']);

        // 2. Identify tasks with test cases or bugs
        $qcIdsFromTestCases = DB::table('test_cases')->whereNotNull('project_task_id')->pluck('project_task_id');
        $qcIdsFromBugs = DB::table('task_bugs')->whereNotNull('project_task_id')->pluck('project_task_id');
        $qcIds = $qcIdsFromTestCases->concat($qcIdsFromBugs)->unique();

        if ($qcIds->isNotEmpty()) {
            DB::table('project_tasks')
                ->whereIn('id', $qcIds)
                ->update(['type' => 'qc']);
        }

        // 3. Tasks without wbs_code and start_date are also 'qc'
        DB::table('project_tasks')
            ->where(function ($query) {
                $query->whereNull('wbs_code')->orWhere('wbs_code', '');
            })
            ->whereNull('start_date')
            ->update(['type' => 'qc']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'type']);
            $table->dropColumn('type');
        });
    }
};
