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
        Schema::create('man_hour_rates', function (Blueprint $table) {
            $table->id();
            $table->string('role_slug');
            $table->string('skill_level')->default('middle'); // entry, middle, senior
            $table->decimal('base_rate_per_hour', 15, 2);
            $table->decimal('weekend_multiplier', 4, 2)->default(1.10);
            $table->decimal('overtime_multiplier', 4, 2)->default(1.20);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['role_slug', 'skill_level']);
        });

        Schema::create('project_member_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_slug')->nullable();
            $table->decimal('rate_per_hour', 15, 2);
            $table->decimal('weekend_multiplier', 4, 2)->default(1.10);
            $table->decimal('overtime_multiplier', 4, 2)->default(1.20);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_member_rates');
        Schema::dropIfExists('man_hour_rates');
    }
};
