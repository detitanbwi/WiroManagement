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
        Schema::create('client_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();
            $table->text('description')->nullable();
            $table->longText('article_content')->nullable();
            $table->string('website_url')->nullable();
            $table->json('social_links')->nullable();
            $table->text('location_maps')->nullable();
            
            // Tech Spotlight & Portfolio Data
            $table->string('project_title')->nullable();
            $table->text('problem_statement')->nullable();
            $table->text('solution_provided')->nullable();
            $table->json('features_built')->nullable();
            $table->json('tech_stack')->nullable();
            $table->json('gallery_images')->nullable();
            
            // Testimonial
            $table->text('testimonial_quote')->nullable();
            $table->string('client_person_name')->nullable();
            $table->string('client_role')->nullable();
            
            // Visibility
            $table->boolean('is_published')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_profiles');
    }
};
