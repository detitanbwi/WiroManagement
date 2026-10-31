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
        Schema::create('fixed_expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('category'); // OPEX, UTILITY, SUBSCRIPTION, PAYROLL, INFRASTRUCTURE
            $table->decimal('amount', 15, 2)->default(0);
            $table->unsignedTinyInteger('due_day_of_month'); // 1 - 31
            $table->string('payment_method')->default('Bank Transfer');
            $table->string('vendor')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'is_active']);
            $table->index('is_active');
        });

        Schema::create('fixed_expense_realizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('fixed_expense_id')->constrained('fixed_expenses')->cascadeOnDelete();
            $table->string('month_period', 7); // YYYY-MM
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->dateTime('payment_date')->nullable();
            $table->string('payment_status', 20)->default('PENDING'); // PENDING, PAID, OVERDUE, SKIPPED
            $table->string('receipt_url')->nullable();
            $table->string('proof_of_payment')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['fixed_expense_id', 'month_period']);
            $table->index(['month_period', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_expense_realizations');
        Schema::dropIfExists('fixed_expenses');
    }
};
