@extends('layouts.app')

@section('title', 'Create New User')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('users.index') }}" class="text-xs font-bold text-gray-400 hover:text-primary uppercase tracking-widest flex items-center mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Back to Users
        </a>
        <h1 class="text-2xl font-black text-gray-800 tracking-tight">Add New User</h1>
        <p class="text-gray-500 text-sm">Register a new staff account and configure one or more roles (multi-role) simultaneously.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-8">
            <form action="{{ route('users.store') }}" method="POST" class="space-y-8">
                @csrf
                
                <!-- Account Info -->
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Account Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2">Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. John Doe"
                                class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border text-sm">
                            @error('name') <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2">Email Address <span class="text-rose-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" required placeholder="name@wirodev.com"
                                class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border text-sm">
                            @error('email') <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2">Password <span class="text-rose-500">*</span></label>
                            <input type="password" name="password" required placeholder="Minimum 8 characters"
                                class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border text-sm">
                            @error('password') <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2">Confirm Password <span class="text-rose-500">*</span></label>
                            <input type="password" name="password_confirmation" required placeholder="Repeat password above"
                                class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border text-sm">
                        </div>
                    </div>
                </div>

                <!-- Multi-Role Selection -->
                <div class="border-t border-gray-100 pt-6">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-xs font-bold text-gray-700 uppercase tracking-widest">Role Assignment (Multi-Role) <span class="text-rose-500">*</span></h3>
                            <p class="text-xs text-gray-500 mt-0.5">Select one or more roles assigned to this user (e.g., PM and QC).</p>
                        </div>
                        <span class="text-[11px] font-bold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full border border-blue-100">Multi-Select Enabled</span>
                    </div>

                    @error('roles')
                        <div class="mb-3 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        @foreach($roles as $role)
                        <label class="relative flex items-start p-4 rounded-xl border border-gray-200 hover:border-blue-300 hover:bg-blue-50/30 cursor-pointer transition has-checked:border-primary has-checked:bg-blue-50/50 has-checked:ring-1 has-checked:ring-primary">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="roles[]" value="{{ $role->slug }}"
                                    {{ (is_array(old('roles')) && in_array($role->slug, old('roles'))) || (!old('roles') && $role->slug === 'staff') ? 'checked' : '' }}
                                    class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                            </div>
                            <div class="ml-3.5 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-gray-900">{{ $role->name }}</span>
                                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded border {{ $role->badge_classes }}">
                                        {{ $role->slug }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $role->description }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- Active Status -->
                <div class="border-t border-gray-100 pt-6">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} class="sr-only peer">
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        <span class="ms-3 text-sm font-bold text-gray-700">Active Account (Can Log In to System)</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100">
                    <a href="{{ route('users.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-600 hover:bg-gray-50 transition uppercase tracking-wider">
                        Cancel
                    </a>
                    <button type="submit" class="px-7 py-2.5 bg-primary text-white rounded-xl font-bold uppercase tracking-wider text-xs hover:bg-blue-800 transition shadow-lg shadow-blue-100 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Save User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
