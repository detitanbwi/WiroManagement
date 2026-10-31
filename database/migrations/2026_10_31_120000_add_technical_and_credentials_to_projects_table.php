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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('demo_url')->nullable()->after('end_date');
            $table->string('repository_url')->nullable()->after('demo_url');
            $table->string('production_url')->nullable()->after('repository_url');
            $table->string('hosting_provider', 100)->nullable()->after('production_url');
            $table->text('hosting_credentials')->nullable()->after('hosting_provider');
            $table->boolean('is_credentials_visible_to_client')->default(false)->after('hosting_credentials');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'demo_url',
                'repository_url',
                'production_url',
                'hosting_provider',
                'hosting_credentials',
                'is_credentials_visible_to_client',
            ]);
        });
    }
};
