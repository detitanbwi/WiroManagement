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
        Schema::table('project_work_logs', function (Blueprint $table) {
            $table->date('end_date')->nullable()->after('work_date');
            $table->decimal('hours_per_day', 8, 2)->nullable()->default(8.00)->after('end_date');
            $table->integer('days_count')->nullable()->default(1)->after('hours_per_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_work_logs', function (Blueprint $table) {
            $table->dropColumn(['end_date', 'hours_per_day', 'days_count']);
        });
    }
};
