@extends('layouts.app')

@section('title', 'New Project')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('projects.index') }}" class="text-sm text-gray-500 hover:text-primary flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Back to Projects
        </a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">New Project</h1>
    </div>

    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
        <form action="{{ route('projects.store') }}" method="POST">
            @csrf
            <div class="space-y-6">
                <div>
                    <label for="client_id" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Select Client</label>
                    <select name="client_id" id="client_id" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                        <option value="">-- Select Client --</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->name }} {{ $client->company_name ? "({$client->company_name})" : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('client_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    <div class="mt-2">
                        <a href="{{ route('clients.create') }}" class="text-xs text-indigo-600 hover:text-indigo-900">+ Add new client if not listed</a>
                    </div>
                </div>

                <div>
                    <label for="title" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Project Title</label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required placeholder="e.g. E-Commerce Website Development" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                    @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="status" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Initial Status</label>
                        <select name="status" id="status" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                            <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="quotation_sent" {{ old('status') == 'quotation_sent' ? 'selected' : '' }}>Quotation Sent</option>
                            <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        </select>
                        @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="start_date" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                        @error('start_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Target Completion</label>
                        <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                        @error('end_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="inline-flex items-center px-6 py-3 bg-primary border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-wider hover:bg-blue-700 active:bg-blue-900 transition-all shadow-md">
                    Create Project
                </button>
            </div>
        </form>
    </div>
</div>
@endsection