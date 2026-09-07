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
        // 1. Tambah client_id & flag portal pada tabel users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('id')
                  ->constrained('clients')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('role');
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('must_change_password');
            
            $table->index(['client_id', 'role']);
        });

        // 2. Tambah kolom penunjang progress pada tabel projects
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('pm_id')->nullable()->after('client_id')
                  ->constrained('users')->nullOnDelete();
            $table->string('project_code', 30)->nullable()->unique()->after('id');
            $table->unsignedTinyInteger('progress_percentage')->default(0)->after('status');
        });

        // 3. Tabel Token Undangan / Onboarding Klien
        Schema::create('client_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('email')->index();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });

        // 4. Tabel Milestones untuk tracking progress visual di Wiromitra
        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'review', 'completed'])->default('pending');
            $table->date('due_date')->nullable();
            $table->unsignedSmallInteger('order_index')->default(0);
            $table->timestamps();
        });

        // 5. Tabel Feed / Log Perkembangan Project yang bisa dibaca Klien
        Schema::create('project_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users');
            $table->string('title');
            $table->text('content');
            $table->boolean('is_visible_to_client')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_updates');
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('client_invitations');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['pm_id']);
            $table->dropColumn(['pm_id', 'project_code', 'progress_percentage']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn(['client_id', 'is_active', 'must_change_password', 'last_login_at']);
        });
    }
};
