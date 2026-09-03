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
        Schema::create('task_bugs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_case_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('description');
            $table->text('steps_to_reproduce')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('open'); // open, resolved, closed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_bugs');
    }
};
