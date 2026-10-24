<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Table project_user (Project membership)
        Schema::create('project_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });

        // 2. Table project_user_role (Multiple roles per project membership)
        Schema::create('project_user_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_user_id')->constrained('project_user')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_user_id', 'role_id']);
        });

        // 3. Make users.role nullable since new users are regular users
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable()->default(null)->change();
        });

        // 4. Migrate existing projects' pm_id into project_user as 'pm' role
        $pmRole = DB::table('roles')->where('slug', 'pm')->first();
        if ($pmRole) {
            $projectsWithPm = DB::table('projects')->whereNotNull('pm_id')->get();
            foreach ($projectsWithPm as $proj) {
                // Ensure user exists
                $userExists = DB::table('users')->where('id', $proj->pm_id)->exists();
                if ($userExists) {
                    $membershipId = DB::table('project_user')->insertGetId([
                        'project_id' => $proj->id,
                        'user_id' => $proj->pm_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('project_user_role')->insert([
                        'project_user_id' => $membershipId,
                        'role_id' => $pmRole->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_user_role');
        Schema::dropIfExists('project_user');
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('staff')->change();
        });
    }
};
