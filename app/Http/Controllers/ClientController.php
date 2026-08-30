<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Client;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::with('profile')->withCount('projects')->latest()->get();
        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string'
        ]);

        Client::create($validated);

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function show(Client $client)
    {
        $client->load('projects');
        return view('clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        $client->load('profile');
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            // Profile fields
            'profile_name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'article_content' => 'nullable|string',
            'website_url' => 'nullable|url|max:255',
            'location_maps' => 'nullable|string',
            'instagram' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'linkedin' => 'nullable|string|max:255',
            'tiktok' => 'nullable|string|max:255',
            'testimonial_quote' => 'nullable|string',
            'client_person_name' => 'nullable|string|max:255',
            'client_role' => 'nullable|string|max:255',
            'is_published' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
        ]);

        $client->update([
            'name' => $validated['name'],
            'company_name' => $validated['company_name'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        // Process profile
        $slug = !empty($validated['slug']) ? \Illuminate\Support\Str::slug($validated['slug']) : \Illuminate\Support\Str::slug($client->company_name ?? $client->name);

        $socialLinks = [
            'instagram' => $validated['instagram'] ?? ($client->profile->social_links['instagram'] ?? null),
            'facebook' => $validated['facebook'] ?? ($client->profile->social_links['facebook'] ?? null),
            'linkedin' => $validated['linkedin'] ?? ($client->profile->social_links['linkedin'] ?? null),
            'tiktok' => $validated['tiktok'] ?? ($client->profile->social_links['tiktok'] ?? null),
        ];

        $client->profile()->updateOrCreate(
            ['client_id' => $client->id],
            [
                'name' => $validated['profile_name'] ?? ($client->company_name ?? $client->name),
                'slug' => $slug,
                'category' => $validated['category'] ?? ($client->profile->category ?? null),
                'description' => $validated['description'] ?? ($client->profile->description ?? null),
                'article_content' => $request->input('article_content', $client->profile->article_content ?? null),
                'website_url' => $validated['website_url'] ?? ($client->profile->website_url ?? null),
                'location_maps' => $validated['location_maps'] ?? ($client->profile->location_maps ?? null),
                'social_links' => $socialLinks,
                'testimonial_quote' => $validated['testimonial_quote'] ?? ($client->profile->testimonial_quote ?? null),
                'client_person_name' => $validated['client_person_name'] ?? ($client->profile->client_person_name ?? null),
                'client_role' => $validated['client_role'] ?? ($client->profile->client_role ?? null),
                'is_published' => $request->has('is_published'),
                'is_featured' => $request->has('is_featured'),
            ]
        );

        return redirect()->route('clients.index')->with('success', 'Client & Profil Showcase updated successfully.');
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }
}
