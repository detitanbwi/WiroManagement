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
        Schema::table('test_cases', function (Blueprint $table) {
            $table->text('payload')->nullable()->after('preconditions');
            $table->string('complexity')->nullable()->after('expected'); // Low, Medium, High
            $table->string('priority')->nullable()->after('complexity'); // Low, Medium, High, Critical
            $table->string('test_type')->nullable()->after('priority'); // Functional, UI, API, Security, Performance, Edge Case
            $table->string('automation_status')->nullable()->after('test_type'); // Manual, Automated, Not Automatable
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_cases', function (Blueprint $table) {
            $table->dropColumn([
                'payload',
                'complexity',
                'priority',
                'test_type',
                'automation_status'
            ]);
        });
    }
};
