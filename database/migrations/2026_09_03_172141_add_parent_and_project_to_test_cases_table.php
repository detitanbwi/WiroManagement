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
        Schema::table('test_cases', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->after('project_task_id')->constrained('test_cases')->restrictOnDelete();
        });

        // Fill project_id for existing test cases
        \Illuminate\Support\Facades\DB::statement('UPDATE test_cases SET project_id = (SELECT project_id FROM project_tasks WHERE project_tasks.id = test_cases.project_task_id)');

        Schema::table('test_cases', function (Blueprint $table) {
            // Drop foreign key if needed by some DBs before change, but usually change() works on MySQL 8 / newer Laravel.
            $table->unsignedBigInteger('project_task_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_cases', function (Blueprint $table) {
            $table->unsignedBigInteger('project_task_id')->nullable(false)->change();
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
