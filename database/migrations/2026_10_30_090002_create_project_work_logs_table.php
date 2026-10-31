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
        Schema::create('project_work_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('work_date');
            $table->decimal('hours_worked', 8, 2);
            $table->string('role_in_project')->nullable();
            $table->string('source_account_type')->default('office_account'); // office_account, personal_reimbursement
            $table->decimal('base_rate', 15, 2);
            $table->decimal('calculated_nominal', 15, 2);
            $table->boolean('is_weekend')->default(false);
            $table->boolean('is_overtime')->default(false);
            $table->string('payment_status')->default('unpaid'); // unpaid, pending_approval, paid
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->text('audit_notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'work_date']);
            $table->index(['user_id', 'work_date']);
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_work_logs');
    }
};
