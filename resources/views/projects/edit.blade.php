@extends('layouts.app')

@section('title', 'Edit Project')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Edit Project: {{ $project->title }}</h2>
            <p class="text-gray-500">Update project details and client assignment.</p>
        </div>

        <form action="{{ route('projects.update', $project) }}" method="POST" class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
            @csrf
            @method('PUT')

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Project Title -->
                    <div class="col-span-2">
                        <label class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Project Title</label>
                        <input type="text" name="title" value="{{ old('title', $project->title) }}" required
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-3 border">
                        @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <!-- Client Selection -->
                    <div class="col-span-1">
                        <label class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Client</label>
                        <select name="client_id" required
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-3 border">
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id', $project->client_id) == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }} {{ $client->company_name ? "({$client->company_name})" : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('client_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <!-- Status Selection -->
                    <div class="col-span-1">
                        <label class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Workflow Status</label>
                        <select name="status" required
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-3 border">
                            @foreach(['draft', 'quotation_sent', 'approved', 'in_progress', 'completed', 'cancelled'] as $st)
                                <option value="{{ $st }}" {{ old('status', $project->status) == $st ? 'selected' : '' }}>
                                    {{ str_replace('_', ' ', strtoupper($st)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <!-- Start Date -->
                    <div class="col-span-1">
                        <label class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Start Date</label>
                        <input type="date" name="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-3 border">
                        @error('start_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <!-- End Date -->
                    <div class="col-span-1">
                        <label class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Target Completion</label>
                        <input type="date" name="end_date" value="{{ old('end_date', $project->end_date?->format('Y-m-d')) }}"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-3 border">
                        @error('end_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Informasi Teknis & Kredensial Server -->
                <div class="border-t border-gray-100 pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Informasi Teknis &amp; Kredensial Server</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Tautan deployment dan kredensial hosting terenkripsi (AES-256).</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Enkripsi Otomatis
                        </span>
                    </div>

                    <div class="bg-gray-50/70 p-5 rounded-xl border border-gray-200 space-y-4">
                        <!-- URLs -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="demo_url" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Demo / Staging URL</label>
                                <input type="url" name="demo_url" id="demo_url" value="{{ old('demo_url', $project->demo_url) }}" placeholder="https://staging.contoh.com" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border bg-white">
                                @error('demo_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="repository_url" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Repository URL</label>
                                <input type="url" name="repository_url" id="repository_url" value="{{ old('repository_url', $project->repository_url) }}" placeholder="https://github.com/org/repo" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border bg-white">
                                @error('repository_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="production_url" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Production URL</label>
                                <input type="url" name="production_url" id="production_url" value="{{ old('production_url', $project->production_url) }}" placeholder="https://contoh.com" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border bg-white">
                                @error('production_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Hosting Provider, Host & Port -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2 border-t border-gray-200/80">
                            <div>
                                <label for="hosting_provider" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Hosting Provider</label>
                                <input type="text" name="hosting_provider" id="hosting_provider" value="{{ old('hosting_provider', $project->hosting_provider) }}" placeholder="misal: Niagahoster, AWS, cPanel" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border bg-white">
                                @error('hosting_provider') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="hosting_host" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Host / IP Server</label>
                                <input type="text" name="hosting_credentials[host]" id="hosting_host" value="{{ old('hosting_credentials.host', $project->hosting_credentials['host'] ?? '') }}" placeholder="misal: 192.168.1.1 atau cpanel.domain.com" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border bg-white">
                                @error('hosting_credentials.host') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="hosting_port" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Port</label>
                                <input type="text" name="hosting_credentials[port]" id="hosting_port" value="{{ old('hosting_credentials.port', $project->hosting_credentials['port'] ?? '') }}" placeholder="misal: 22 / 2083" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border bg-white">
                                @error('hosting_credentials.port') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Username & Password -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="hosting_username" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Username / Akun</label>
                                <input type="text" name="hosting_credentials[username]" id="hosting_username" value="{{ old('hosting_credentials.username', $project->hosting_credentials['username'] ?? '') }}" placeholder="Username login" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border bg-white">
                                @error('hosting_credentials.username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div x-data="{ showPass: false }">
                                <label for="hosting_password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Password</label>
                                <div class="relative">
                                    <input :type="showPass ? 'text' : 'password'" name="hosting_credentials[password]" id="hosting_password" value="{{ old('hosting_credentials.password', $project->hosting_credentials['password'] ?? '') }}" placeholder="Password server" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 pr-10 border bg-white">
                                    <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer">
                                        <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg x-show="showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                    </button>
                                </div>
                                @error('hosting_credentials.password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label for="hosting_notes" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Catatan Tambahan Akses / Server</label>
                            <textarea name="hosting_credentials[notes]" id="hosting_notes" rows="2" placeholder="Catatan konfigurasi SSH, directori path, URL login panel, dsb." class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border bg-white">{{ old('hosting_credentials.notes', $project->hosting_credentials['notes'] ?? '') }}</textarea>
                            @error('hosting_credentials.notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Visibility Checkbox Switch -->
                        <div class="pt-3 border-t border-gray-200/80">
                            <label class="relative flex items-start gap-3 cursor-pointer select-none">
                                <input type="checkbox" name="is_credentials_visible_to_client" value="1" {{ old('is_credentials_visible_to_client', $project->is_credentials_visible_to_client) ? 'checked' : '' }} class="mt-0.5 h-4 w-4 text-primary rounded border-gray-300 focus:ring-primary">
                                <div>
                                    <span class="block text-xs font-bold text-gray-800">Izinkan Klien Melihat Kredensial Ini di WiroMitra</span>
                                    <span class="block text-[11px] text-gray-500">Jika dicentang, klien dapat melihat kredensial hosting ini pada dashboard portal WiroMitra mereka. Jika tidak dicentang, kredensial ini hanya dapat diakses secara internal oleh tim.</span>
                                </div>
                            </label>
                            @error('is_credentials_visible_to_client') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs text-blue-700 font-medium">
                                Project value and financial metrics are automatically calculated from linked Quotations and Invoices.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                <a href="{{ route('projects.show', $project) }}"
                    class="px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                    class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
@endsection