@extends('layouts.app')

@section('title', 'Edit User: ' . $user->name)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('users.index') }}" class="text-xs font-bold text-gray-400 hover:text-primary uppercase tracking-widest flex items-center mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Back to Users
        </a>
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-black text-gray-800 tracking-tight">Edit User: {{ $user->name }}</h1>
            @if($user->id === auth()->id())
                <span class="text-xs font-bold text-blue-700 bg-blue-50 px-2.5 py-1 rounded-full border border-blue-200">Your Account</span>
            @endif
        </div>
        <p class="text-gray-500 text-sm mt-0.5">Update profile information and assign multiple roles for this account.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-8">
            <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-8">
                @csrf
                @method('PUT')
                
                <!-- Account Information -->
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Account Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2">Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border text-sm">
                            @error('name') <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2">Email Address <span class="text-rose-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border text-sm">
                            @error('email') <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2">Change Password <span class="text-gray-400 font-normal">(Leave blank if unchanged)</span></label>
                            <input type="password" name="password" placeholder="Minimum 8 new characters"
                                class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border text-sm">
                            @error('password') <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2">Confirm New Password</label>
                            <input type="password" name="password_confirmation" placeholder="Repeat new password"
                                class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border text-sm">
                        </div>
                    </div>
                </div>

                <!-- Multi-Role Selection -->
                <div class="border-t border-gray-100 pt-6">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-xs font-bold text-gray-700 uppercase tracking-widest">Role Assignment (Multi-Role) <span class="text-rose-500">*</span></h3>
                            <p class="text-xs text-gray-500 mt-0.5">Check one or more roles to combine permissions (e.g., PM and QC).</p>
                        </div>
                        <span class="text-[11px] font-bold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full border border-blue-100">Multi-Role Active</span>
                    </div>

                    @error('roles')
                        <div class="mb-3 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        @foreach($roles as $role)
                        @php
                            $isChecked = is_array(old('roles')) 
                                ? in_array($role->slug, old('roles')) 
                                : in_array($role->slug, $userRoleSlugs);
                            $isDisabled = ($user->id === auth()->id() && $role->slug === 'superadmin');
                        @endphp
                        <label class="relative flex items-start p-4 rounded-xl border border-gray-200 hover:border-blue-300 hover:bg-blue-50/30 cursor-pointer transition has-checked:border-primary has-checked:bg-blue-50/50 has-checked:ring-1 has-checked:ring-primary {{ $isDisabled ? 'opacity-90 bg-gray-50' : '' }}">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="roles[]" value="{{ $role->slug }}"
                                    {{ $isChecked ? 'checked' : '' }}
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
                                @if($isDisabled)
                                    <span class="inline-block mt-2 text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                        Primary role for your current account (cannot be self-revoked)
                                    </span>
                                @endif
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- Active Status -->
                <div class="border-t border-gray-100 pt-6">
                    <label class="inline-flex items-center cursor-pointer {{ $user->id === auth()->id() ? 'opacity-60 cursor-not-allowed' : '' }}">
                        <input type="checkbox" name="is_active" value="1" 
                            {{ old('is_active', $user->is_active ? '1' : '0') == '1' ? 'checked' : '' }}
                            {{ $user->id === auth()->id() ? 'disabled' : '' }}
                            class="sr-only peer">
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        <span class="ms-3 text-sm font-bold text-gray-700">Active Account (Can Log In to System)</span>
                    </label>
                    @if($user->id === auth()->id())
                        <p class="text-[11px] text-gray-400 mt-1">You cannot deactivate your own currently active account.</p>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100">
                    <a href="{{ route('users.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-600 hover:bg-gray-50 transition uppercase tracking-wider">
                        Cancel
                    </a>
                    <button type="submit" class="px-7 py-2.5 bg-primary text-white rounded-xl font-bold uppercase tracking-wider text-xs hover:bg-blue-800 transition shadow-lg shadow-blue-100 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
