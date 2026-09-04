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
        Schema::table('task_bugs', function (Blueprint $table) {
            $table->string('severity')->nullable()->after('description'); // Low, Medium, High, Critical
            $table->text('actual_result')->nullable()->after('severity');
            $table->string('environment')->nullable()->after('actual_result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_bugs', function (Blueprint $table) {
            $table->dropColumn(['severity', 'actual_result', 'environment']);
        });
    }
};
