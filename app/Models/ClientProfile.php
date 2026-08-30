<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'slug',
        'category',
        'logo',
        'cover_image',
        'description',
        'article_content',
        'website_url',
        'social_links',
        'location_maps',
        'project_title',
        'problem_statement',
        'solution_provided',
        'features_built',
        'tech_stack',
        'gallery_images',
        'testimonial_quote',
        'client_person_name',
        'client_role',
        'is_published',
        'is_featured',
    ];

    protected $casts = [
        'social_links' => 'array',
        'features_built' => 'array',
        'tech_stack' => 'array',
        'gallery_images' => 'array',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
