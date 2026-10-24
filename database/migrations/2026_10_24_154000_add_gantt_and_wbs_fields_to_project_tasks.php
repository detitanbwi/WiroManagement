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
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('project_id')
                ->constrained('project_tasks')->cascadeOnDelete();
            $table->string('wbs_code', 50)->nullable()->after('parent_id');
            $table->date('start_date')->nullable()->after('description');
            $table->date('due_date')->nullable()->after('start_date');
            $table->unsignedInteger('duration_days')->default(1)->after('due_date');
            $table->decimal('progress', 5, 2)->default(0.00)->after('duration_days');
            $table->string('status', 30)->default('todo')->after('progress');
            $table->unsignedInteger('sort_order')->default(0)->after('status');
        });

        Schema::create('project_task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('predecessor_id')->constrained('project_tasks')->cascadeOnDelete();
            $table->foreignId('successor_id')->constrained('project_tasks')->cascadeOnDelete();
            $table->string('type', 4)->default('FS'); // FS: Finish-to-Start, SS: Start-to-Start
            $table->integer('lag_days')->default(0);
            $table->timestamps();

            $table->unique(['predecessor_id', 'successor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_task_dependencies');
        
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn([
                'parent_id',
                'wbs_code',
                'start_date',
                'due_date',
                'duration_days',
                'progress',
                'status',
                'sort_order',
            ]);
        });
    }
};
