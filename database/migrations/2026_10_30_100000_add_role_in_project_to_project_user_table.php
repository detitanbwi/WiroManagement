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
        Schema::table('project_user', function (Blueprint $table) {
            if (!Schema::hasColumn('project_user', 'role_in_project')) {
                $table->string('role_in_project', 100)->nullable()->after('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_user', function (Blueprint $table) {
            if (Schema::hasColumn('project_user', 'role_in_project')) {
                $table->dropColumn('role_in_project');
            }
        });
    }
};
