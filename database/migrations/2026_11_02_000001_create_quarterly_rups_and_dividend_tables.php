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
        // 1. Shareholders (Pemegang Saham / Mitra Pendiri)
        Schema::create('shareholders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->unsignedInteger('shares_count')->default(0);
            $table->decimal('ownership_percentage', 5, 2)->default(0); // e.g. 40.00%
            $table->string('bank_name');
            $table->string('bank_account_number');
            $table->string('bank_account_holder');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        // 2. Quarterly Closings (Konsolidasi Penutupan Buku Triwulanan)
        Schema::create('quarterly_closings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter'); // 1, 2, 3, 4
            $table->string('period_label', 10)->unique(); // e.g. '2026-Q1'
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('total_cogs', 15, 2)->default(0);
            $table->decimal('gross_profit', 15, 2)->default(0);
            $table->decimal('total_opex', 15, 2)->default(0);
            $table->decimal('net_profit', 15, 2)->default(0);
            $table->decimal('net_profit_margin', 8, 2)->default(0);
            $table->decimal('opening_cash_balance', 15, 2)->default(0);
            $table->decimal('ending_cash_balance', 15, 2)->default(0);
            $table->decimal('cash_floor_target', 15, 2)->default(15000000); // Rp 15.000.000 Safety Cash Floor
            $table->decimal('cash_surplus_above_floor', 15, 2)->default(0);
            $table->string('audit_status')->default('draft'); // draft, reviewed, approved
            $table->text('auditor_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['year', 'quarter']);
            $table->index('audit_status');
        });

        // 3. Profit Distributions (Keputusan Bagi Hasil / Dividen RUPS)
        Schema::create('profit_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quarterly_closing_id')->constrained('quarterly_closings')->cascadeOnDelete();
            $table->decimal('total_net_profit', 15, 2)->default(0);
            $table->decimal('retained_earnings_amount', 15, 2)->default(0);
            $table->decimal('retained_earnings_percentage', 8, 2)->default(20.00); // Default 20%
            $table->decimal('distributable_profit', 15, 2)->default(0);
            $table->decimal('dividend_per_share', 15, 2)->default(0);
            $table->date('distribution_date')->nullable();
            $table->string('status')->default('draft'); // draft, approved, distributed
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // 4. Dividend Payouts (Rincian Hak & Pencairan Per Pendiri / Shareholder)
        Schema::create('dividend_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profit_distribution_id')->constrained('profit_distributions')->cascadeOnDelete();
            $table->foreignId('shareholder_id')->constrained('shareholders')->cascadeOnDelete();
            $table->unsignedInteger('shares_count')->default(0);
            $table->decimal('ownership_percentage', 8, 2)->default(0);
            $table->decimal('payout_amount', 15, 2)->default(0);
            $table->string('payment_status')->default('pending'); // pending, transferred
            $table->date('payment_date')->nullable();
            $table->string('transfer_receipt_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dividend_payouts');
        Schema::dropIfExists('profit_distributions');
        Schema::dropIfExists('quarterly_closings');
        Schema::dropIfExists('shareholders');
    }
};
