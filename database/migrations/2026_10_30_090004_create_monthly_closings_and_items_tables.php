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
        Schema::create('monthly_closings', function (Blueprint $table) {
            $table->id();
            $table->string('period_month', 7)->unique(); // YYYY-MM
            $table->decimal('opening_cash_balance', 15, 2)->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('total_cogs', 15, 2)->default(0); // infra + advisor + labor
            $table->decimal('gross_profit', 15, 2)->default(0);
            $table->decimal('total_opex', 15, 2)->default(0); // management salary + marketing + utility
            $table->decimal('net_profit', 15, 2)->default(0);
            $table->decimal('net_profit_margin', 8, 2)->default(0);
            $table->decimal('cash_inflow', 15, 2)->default(0);
            $table->decimal('cash_outflow', 15, 2)->default(0);
            $table->decimal('ending_cash_balance', 15, 2)->default(0);
            $table->string('audit_status')->default('draft'); // draft, audited, approved
            $table->text('auditor_notes')->nullable();
            $table->foreignId('audited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('audit_status');
        });

        Schema::create('monthly_closing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('closing_id')->constrained('monthly_closings')->cascadeOnDelete();
            $table->string('account_code'); // e.g. 1000-04-01
            $table->string('account_name');
            $table->string('category'); // revenue, cogs, opex, cash_mutation
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['closing_id', 'category']);
            $table->index('account_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_closing_items');
        Schema::dropIfExists('monthly_closings');
    }
};
