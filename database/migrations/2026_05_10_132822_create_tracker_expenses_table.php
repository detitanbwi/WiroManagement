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
        Schema::create('tracker_expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->uuid('category_id');
            $table->enum('type', ['personal', 'company']);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->dateTime('expense_date');
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('tracker_accounts')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('tracker_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracker_expenses');
    }
};
