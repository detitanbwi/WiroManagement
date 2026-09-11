@extends('layouts.app')

@section('title', 'Edit Client & Showcase Profile')

@section('content')
<div class="max-w-4xl mx-auto pb-12">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('clients.index') }}" class="text-sm text-gray-500 hover:text-primary flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                Back to Clients
            </a>
            <h1 class="text-2xl font-bold text-gray-800 mt-2">Edit Client &amp; Showcase Profile</h1>
        </div>
        @if($client->profile && $client->profile->slug)
            <a href="http://localhost/wirodev/klien/{{ $client->profile->slug }}" target="_blank" class="inline-flex items-center gap-1 text-sm bg-blue-50 text-primary border border-blue-200 px-3 py-1.5 rounded-md hover:bg-blue-100 font-medium">
                View Public Page ↗
            </a>
        @endif
    </div>

    <!-- Quill Editor CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
        <form action="{{ route('clients.update', $client) }}" method="POST" id="clientForm">
            @csrf
            @method('PUT')

            <!-- Section Tabs -->
            <div class="border-b border-gray-200 bg-gray-50 px-4 sm:px-6 pt-3 sm:pt-4 flex flex-nowrap overflow-x-auto gap-2 sm:gap-4">
                <button type="button" class="tab-btn active font-semibold text-xs sm:text-sm pb-3 border-b-2 border-primary text-primary whitespace-nowrap" data-tab="tab-general">1. Primary Contact Information</button>
                <button type="button" class="tab-btn font-semibold text-xs sm:text-sm pb-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="tab-profile">2. Business Profile &amp; Article (WYSIWYG)</button>
            </div>

            <div class="p-6 space-y-6">
                <!-- TAB 1: GENERAL CONTACT -->
                <div id="tab-general" class="tab-content block space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">PIC Name (Person in Charge) <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $client->name) }}" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border">
                        </div>

                        <div>
                            <label for="company_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Company / Business Name</label>
                            <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $client->company_name) }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border">
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $client->email) }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border">
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Phone / WhatsApp</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $client->phone) }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border">
                        </div>

                        <div class="md:col-span-2">
                            <label for="address" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Office / Operational Address</label>
                            <textarea name="address" id="address" rows="2" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border">{{ old('address', $client->address) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: SHOWCASE PROFILE & WYSIWYG ARTICLE -->
                <div id="tab-profile" class="tab-content hidden space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="profile_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Brand Display Name</label>
                            <input type="text" name="profile_name" id="profile_name" value="{{ old('profile_name', $client->profile->name ?? $client->company_name ?? $client->name) }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border">
                        </div>

                        <div>
                            <label for="slug" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">URL Slug (e.g. kopi-titik-temu)</label>
                            <input type="text" name="slug" id="slug" value="{{ old('slug', $client->profile->slug ?? '') }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border">
                        </div>

                        <div>
                            <label for="category" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Category / Business Industry</label>
                            <input type="text" name="category" id="category" value="{{ old('category', $client->profile->category ?? '') }}" placeholder="e.g. Food & Beverage, Logistics" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border">
                        </div>

                        <div>
                            <label for="website_url" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Client Official Website</label>
                            <input type="url" name="website_url" id="website_url" value="{{ old('website_url', $client->profile->website_url ?? '') }}" placeholder="https://..." class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border">
                        </div>

                        <div class="md:col-span-2">
                            <label for="location_maps" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Google Maps Link</label>
                            <input type="text" name="location_maps" id="location_maps" value="{{ old('location_maps', $client->profile->location_maps ?? '') }}" placeholder="https://maps.google.com/..." class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border">
                        </div>

                        <!-- Social Links -->
                        <div>
                            <label for="instagram" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Instagram URL</label>
                            <input type="text" name="instagram" id="instagram" value="{{ old('instagram', $client->profile->social_links['instagram'] ?? '') }}" placeholder="https://instagram.com/..." class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border">
                        </div>
                        <div>
                            <label for="facebook" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Facebook URL</label>
                            <input type="text" name="facebook" id="facebook" value="{{ old('facebook', $client->profile->social_links['facebook'] ?? '') }}" placeholder="https://facebook.com/..." class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border">
                        </div>
                        <div>
                            <label for="linkedin" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">LinkedIn URL</label>
                            <input type="text" name="linkedin" id="linkedin" value="{{ old('linkedin', $client->profile->social_links['linkedin'] ?? '') }}" placeholder="https://linkedin.com/in/..." class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border">
                        </div>
                        <div>
                            <label for="tiktok" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">TikTok URL</label>
                            <input type="text" name="tiktok" id="tiktok" value="{{ old('tiktok', $client->profile->social_links['tiktok'] ?? '') }}" placeholder="https://tiktok.com/@..." class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border">
                        </div>

                        <div class="md:col-span-2">
                            <label for="description" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Brief Business Description (1-2 Paragraphs)</label>
                            <textarea name="description" id="description" rows="3" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border">{{ old('description', $client->profile->description ?? '') }}</textarea>
                        </div>

                        <!-- WYSIWYG Article Editor -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Full Business Profile / Story (WYSIWYG Editor)
                            </label>
                            <div id="quill-editor" style="min-height: 250px;" class="bg-white border rounded-md">
                                {!! old('article_content', $client->profile->article_content ?? '') !!}
                            </div>
                            <input type="hidden" name="article_content" id="article_content">
                            <p class="text-xs text-gray-500 mt-1">Write an in-depth profile covering company history, brand philosophy, milestones, and competitive advantages.</p>
                        </div>

                        <!-- Testimonial Section -->
                        <div class="md:col-span-2 border-t border-gray-200 pt-5 space-y-4">
                            <h4 class="font-bold text-sm text-gray-800">Client Testimonial</h4>
                            <div>
                                <label for="testimonial_quote" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Testimonial Quote</label>
                                <textarea name="testimonial_quote" id="testimonial_quote" rows="2" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border">{{ old('testimonial_quote', $client->profile->testimonial_quote ?? '') }}</textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="client_person_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Testimonial Author Name</label>
                                    <input type="text" name="client_person_name" id="client_person_name" value="{{ old('client_person_name', $client->profile->client_person_name ?? '') }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border">
                                </div>
                                <div>
                                    <label for="client_role" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Author Role / Title</label>
                                    <input type="text" name="client_role" id="client_role" value="{{ old('client_role', $client->profile->client_role ?? '') }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border">
                                </div>
                            </div>
                        </div>

                        <!-- Visibility flags -->
                        <div class="md:col-span-2 border-t border-gray-200 pt-5 flex items-center gap-6">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_published" value="1" {{ old('is_published', $client->profile->is_published ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring focus:ring-blue-200">
                                <span class="text-sm font-medium text-gray-700">Publish Profile Page on wirodev.com</span>
                            </label>

                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $client->profile->is_featured ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring focus:ring-blue-200">
                                <span class="text-sm font-medium text-gray-700">Display Logo on Homepage Showcase (Featured)</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Save Bar -->
            <div class="bg-gray-50 px-4 sm:px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3">
                <span class="text-xs text-gray-500 text-center sm:text-left">Changes will be synchronized immediately to the public portal.</span>
                <button type="submit" class="inline-flex justify-center items-center px-6 py-2.5 bg-primary border border-transparent rounded-lg font-bold text-sm text-white hover:bg-blue-700 transition shadow-sm">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
        </form>
    </div>
</div>

<!-- Quill Editor JS & Script -->
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            tabBtns.forEach(b => {
                b.classList.remove('active', 'border-primary', 'text-primary');
                b.classList.add('border-transparent', 'text-gray-500');
            });
            tabContents.forEach(c => c.classList.add('hidden'));

            this.classList.add('active', 'border-primary', 'text-primary');
            this.classList.remove('border-transparent', 'text-gray-500');

            const target = this.getAttribute('data-tab');
            document.getElementById(target).classList.remove('hidden');
        });
    });

    // Check URL parameters or hash for active tab
    const urlParams = new URLSearchParams(window.location.search);
    const initialTab = urlParams.get('tab') === 'profile' || window.location.hash === '#tab-profile' ? 'tab-profile' : 'tab-general';

    if (initialTab === 'tab-profile') {
        const profileBtn = document.querySelector('[data-tab="tab-profile"]');
        if (profileBtn) profileBtn.click();
    }

    // Initialize Quill
    const quill = new Quill('#quill-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [2, 3, 4, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link'],
                ['clean']
            ]
        }
    });

    // Populate hidden field on form submit
    const form = document.getElementById('clientForm');
    form.addEventListener('submit', function() {
        const articleInput = document.getElementById('article_content');
        articleInput.value = quill.root.innerHTML;
    });
});
</script>
@endsection
