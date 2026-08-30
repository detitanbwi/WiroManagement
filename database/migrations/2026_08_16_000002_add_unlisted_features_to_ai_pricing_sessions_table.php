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
        Schema::table('ai_pricing_sessions', function (Blueprint $table) {
            $table->json('unlisted_features')->nullable()->after('selected_modules');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_pricing_sessions', function (Blueprint $table) {
            $table->dropColumn('unlisted_features');
        });
    }
};
