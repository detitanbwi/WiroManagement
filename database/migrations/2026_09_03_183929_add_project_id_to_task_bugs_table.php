<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('task_bugs', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('id')->constrained('projects')->cascadeOnDelete();
        });

        // Fill project_id for existing task bugs
        \Illuminate\Support\Facades\DB::statement('UPDATE task_bugs SET project_id = (SELECT project_id FROM project_tasks WHERE project_tasks.id = task_bugs.project_task_id)');

        Schema::table('task_bugs', function (Blueprint $table) {
            $table->unsignedBigInteger('project_task_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_bugs', function (Blueprint $table) {
            $table->unsignedBigInteger('project_task_id')->nullable(false)->change();
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
