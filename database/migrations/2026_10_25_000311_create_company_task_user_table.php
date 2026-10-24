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
        Schema::create('company_task_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_task_id')->constrained('company_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['company_task_id', 'user_id']);
        });

        // Migrate any existing assignee_id into the pivot table
        if (Schema::hasTable('company_tasks')) {
            $existing = DB::table('company_tasks')->whereNotNull('assignee_id')->get();
            foreach ($existing as $item) {
                DB::table('company_task_user')->insertOrIgnore([
                    'company_task_id' => $item->id,
                    'user_id' => $item->assignee_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_task_user');
    }
};
