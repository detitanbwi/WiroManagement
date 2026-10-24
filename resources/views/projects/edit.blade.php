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