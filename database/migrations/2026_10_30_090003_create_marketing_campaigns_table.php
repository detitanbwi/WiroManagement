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
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // meta_ads, google_ads, tiktok_ads, etc.
            $table->string('campaign_name');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('ad_spend', 15, 2)->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('accounts_reached')->default(0);
            $table->unsignedInteger('messages_count')->default(0);
            $table->unsignedInteger('meetings_count')->default(0);
            $table->unsignedInteger('proposals_count')->default(0);
            $table->unsignedInteger('deals_count')->default(0);
            $table->decimal('total_revenue_generated', 15, 2)->default(0);
            $table->decimal('roi_percentage', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('platform');
            $table->index('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_campaigns');
    }
};
