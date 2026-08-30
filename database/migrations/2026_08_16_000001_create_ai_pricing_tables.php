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
        Schema::create('ai_pricing_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->default('Estimasi Proyek Baru');
            $table->string('client_name')->nullable();
            $table->string('client_segment')->default('umkm');
            $table->string('platform')->default('web');
            $table->integer('risk_buffer_percent')->default(0);
            $table->integer('rush_fee_percent')->default(0);
            $table->json('selected_modules')->nullable();
            $table->json('calculation_result')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('ai_pricing_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('ai_pricing_sessions')->cascadeOnDelete();
            $table->string('role')->default('user'); // 'user', 'assistant'
            $table->text('content');
            $table->json('extracted_params')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_pricing_messages');
        Schema::dropIfExists('ai_pricing_sessions');
    }
};
