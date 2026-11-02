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
        // 1. Update Tabel shareholders
        Schema::table('shareholders', function (Blueprint $table) {
            $table->decimal('capital_amount', 15, 2)->default(0)->after('shares_count');
        });

        // 2. Update Tabel dividend_payouts
        Schema::table('dividend_payouts', function (Blueprint $table) {
            $table->decimal('cash_amount', 15, 2)->default(0)->after('payout_amount');
            $table->decimal('reinvest_amount', 15, 2)->default(0)->after('cash_amount');
            $table->decimal('reinvest_percentage', 5, 2)->default(0)->after('reinvest_amount');
        });

        // 3. Update Tabel profit_distributions
        Schema::table('profit_distributions', function (Blueprint $table) {
            $table->string('document_number', 50)->nullable()->unique()->after('id');
            $table->decimal('total_cash_payout', 15, 2)->default(0)->after('distributable_profit');
            $table->decimal('total_reinvested_capital', 15, 2)->default(0)->after('total_cash_payout');
            $table->decimal('next_quarter_total_capital', 15, 2)->default(0)->after('total_reinvested_capital');
        });

        // 4. Buat Tabel shareholder_capital_histories (Buku Besar Mutasi Modal)
        Schema::create('shareholder_capital_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shareholder_id')->constrained('shareholders')->cascadeOnDelete();
            $table->foreignId('quarterly_closing_id')->nullable()->constrained('quarterly_closings')->cascadeOnDelete();
            $table->foreignId('profit_distribution_id')->nullable()->constrained('profit_distributions')->cascadeOnDelete();
            $table->string('transaction_type', 50); // initial_balance, dividend_reinvestment, fresh_capital_injection, capital_withdrawal
            $table->string('effective_quarter_label', 10); // e.g. '2026-Q2'
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('opening_capital', 15, 2)->default(0);
            $table->decimal('closing_capital', 15, 2)->default(0);
            $table->unsignedInteger('opening_shares_count')->default(0);
            $table->unsignedInteger('closing_shares_count')->default(0);
            $table->decimal('opening_ownership_percentage', 5, 2)->default(0);
            $table->decimal('closing_ownership_percentage', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('transaction_type');
            $table->index('effective_quarter_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shareholder_capital_histories');

        Schema::table('profit_distributions', function (Blueprint $table) {
            $table->dropColumn([
                'document_number',
                'total_cash_payout',
                'total_reinvested_capital',
                'next_quarter_total_capital',
            ]);
        });

        Schema::table('dividend_payouts', function (Blueprint $table) {
            $table->dropColumn([
                'cash_amount',
                'reinvest_amount',
                'reinvest_percentage',
            ]);
        });

        Schema::table('shareholders', function (Blueprint $table) {
            $table->dropColumn('capital_amount');
        });
    }
};
